<?php
namespace DigitalMx\Flames;

use DigitalMx\MyPDO;
use DigitalMx\Flames as f;
use DigitalMx as u;
use DigitalMx\Flames\Definitions as Defs;

/**
	Main interface to assets

	Can insert and update assets
	Can retrieve asset data

	to save an asset, deliver an array containing these fields:

	id (0 for new)
	asset_url  (nee link) (asset source url)
	thumb_url (nee url) (thumb source url)
	title
	caption
	keywords
	tags
	source
	vintage
	contributor id
	astatus

	first_use_date
	first_use_in

	needs [thumbs,galleries,toons]  // list of needed thumbs

	set automatically:
		sizekb
		mime
		astatus
		date_created
		date_modified

	This class has these public methods
	save_asset (array) -
		updates or creates asset with values in array
		creates thumbs reequested in needs
		returns asset id
	save_thumbs (id,needs)
		creates thumbs from thumb source for id.
	check_thumb_files (id)
		returns list of thumb files that exist
	get_asset_data_by_id
		returns array of asset data
	get_asset_list_by_name
		returns list of ids matching name in title,caption, or keys


**/




class Assets {

    private  $pdo;
    public $mimeinfo;


    private $Member;




	// these fields can be changed on edit form
	public static $editable_fields = array (
		'asset_url',
		'thumb_url',
		'title',
		'caption',
		'keywords',
		'tags',
		'source',
		'vintage',
		'contributor_id',

		'notes',

		);

		// these fields are changed automatically and not entered on form
	public static $calculated_fields = array (

		'mime',
		'type',
		'sizekb',
		'astatus',
		'height',
		'width',

	);

	// these fields are updated by special methods
	public static $external_fields = array (
		'first_use_date',
		'first_use_in',

		'temptest',
	);

	public  $new_asset = array(
		'title' => '',
		'source' => '',
		'contributor_id' => '',
		'astatus' => 'N',
		'asset_url' => '',
		'thumb_url' => '',
		'caption' => '',
		'keywords' => '',
		'tags' => '',
		'vintage' => '',
		'notes' => '',
		'existing_thumbs' => [],
	);



    public function __construct($container){
        $this->pdo = $container['pdo'];
        $this->mimeinfo  = new \finfo(FILEINFO_MIME_TYPE);
			$this->Member = $container['member'];

    }


	public function getNewID () {
		// remove any extraneous temp records - avoid race for recent entry
		$sql = "DELETE FROM `assets2` WHERE astatus = 'T' and date_entered < now() - interval 1 hour";
		$this->pdo->query($sql);
		//enter new temp record to get an id
		$sql = "INSERT into `assets2` (title) VALUES ('temp')";
		// status = T by default value
		$this->pdo->query($sql);
		$id = $this->pdo->lastInsertId();
		return $id;
	}

	public function deleteAsset($id) {
		$sql = "UPDATE `assets2` SET astatus = 'X' WHERE id = $id";
		$this->pdo->query($sql);
		return true;
	}

	public function checkAssetData($adata) {
	// id
// 	asset_url  (nee link) (asset source url)
// 	thumb_url (nee url) (thumb source url)
// 	title
// 	caption
// 	keywords
// 	tags
// 	source
// 	vintage
// 	contributor id
// 	astatus
//
// 	first_use_date
// 	first_use_in
//
// 	mime
// 	type
// 	sizekb
//
// 	date entered
// 	date modified

	#u\echor($adata,'Into Checking asset data:');
	foreach ($adata as $var => $val){
		switch ($var) {
			case 'id':
				if (! is_integer( (int)$val) ) throw new Exception ("bad id: ". $val);
				break;

			case 'asset_url':
				if (empty($adata['asset_url'])){
					throw new Exception ("No source for asset specified");
				}
				if (! u\url_exists ($val) ) {
					throw new Exception ("Asset source does not exist." . $val) ;
				}
				break;
			case 'thumb_url':
				if(empty($val)) break;
				// thumb must be either local or a youtube
				if (! u\is_local($val) && ! is_youtube($val) ){
					throw new Exception ("Thumb source not useable " . $val) ;
				}
				break;

			case 'title':
				if (empty($val)){
					throw new Exception ("No title provided");
				}
				break;
			case 'vintage':
				 if (! is_integer (0 + $val)
				 	|| $val < 1800
				 	|| $val > 2050 ){ #to cast as numeric
						throw new Exception ("Vintage is not a valid year");
					}
				break;
			case 'astatus':
				if (! in_array($val,array_keys(Defs::$asset_status))){
					throw new Exception ("Unknown asset status $val");
				}
				break;

			case 'contributor_id' :
				if (! $this->Member->getMemberBasic($val) ){ #0 is allowed
					throw new Exception ("No user found for contributor id " . $val);
				}
				break;

			case 'needs':
				if (empty($val)) break;
				if (! is_array($val) ){
					throw new Exception ("Thumb requested not in a string. " . typeof($val) );
				}
				foreach ($val as $ttype) {
					if (! in_array($ttype, Defs::getThumbTypes() ) ) {
						throw new Exception ("Unrecognized thumb requested $ttype");
					}
				}
				break;

			case 'tags':
				if (empty($val)) break;
				foreach (str_split($val) as $tag){
					if (! in_array($tag,array_keys(Defs::$asset_tags)))
					throw new Exception (__LINE__ . " Unknown asset tag $tag");
				}

				break;
			case 'mime':
				if (!in_array($val,Defs::getAcceptedMime() ) )
			 		throw new Exception ("Source type $mime is not acceptable: " . $val);
			 	break;

			default: #do nothing

		} #end switch
	}
	return true;

	}


	public function saveAsset($adata) {
		/* adata is array with all the asset fields..
		// check data before saving
			adata['needs'] is array of required thumb types that
			will be created.  thumbs are never deleted (unless record
			is deleted).  So once created, you don't need to ask again
			ujless source is updated.
		*/
			// data already checked in AssetAdmin

			//db field 'astatus' may be in post astatus or just status

			$id = $adata['id'];

		try {
				 $this->checkAssetData($adata);

		} catch (Exception $e) {
				echo "Error in asset data. Asset not saved." . BRNL;
				echo $e->getMessage();
				exit;
		}

		$adata['sizekb'] = 0;
		try {
		$adata['mime']  = u\get_mime_from_url ($adata['asset_url'] );
		$adata['type'] = $this->getTypeFromMime($adata['mime']);
		} catch (Exception $e) {
			echo "Failed to get mime info for asset $id";
			echo $e->getMessage();
			exit;
		}
		if (u\is_local($adata['asset_url']) ) {
			$path = SITE_PATH . $adata['asset_url'];
			$size = filesize($path);
			$adata['sizekb'] = (int)($size/1000);
			// if ($adata['type'] == 'Image'){
// 				list($adata['width'], $adata['height'], $junk) = getimagesize(SITE_PATH . $adata['asset_url']);
// 			}
		}
		#echo "Saving Asset $id" . BRNL;

		// save all tbunbs, including thumb if not there.
		$needs = $adata['needs'];


			/*
			external fields are not included here.  This is an
			update, so existing values do not get changed.
			*/
		$allowed_fields = array_merge (self::$editable_fields, self::$calculated_fields);


		$prep = u\pdoPrep($adata,$allowed_fields,'id');

 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
  			// asset id already created, so this is ALWAYS an update.

		$sql = "UPDATE `assets2` SET ${prep['updateu']} WHERE id = ${prep['key']} ;";
		//u\echor ($prep, $sql); exit;
		$stmt = $this->pdo->prepare($sql)->execute($prep['udata']);
			// save the data because createThumbs always wants to get start from db.
		$this->createThumbs($id,$needs);

		return $id;

	}
	private function updateThumbUrl($id,$url) {
		$sql = "UPDATE `assets2` SET thumb_url = '$url' WHERE id = '$id' ";
		$this->pdo->query($sql);
	}

	public function createThumbs($id,$needs){
		/* routine to create thumbs of any sizes.

			id is asset id
			needs is an array of needed thumb types ['thumb','gallery']
// try with id,src,mime,needs
src is thumb_url || asset_url

		If the asset has thumb_url, then that is the source
		of the thumb file.  It is generated from the source
		(which must be local) using imagick.

		if there is no thumb_url, then the source_url is used.
		If the source is a local file, thumb is generated.
		If the source is youtube, a thumb is retrieved from youtube,
			saved in /assets/thumb_sources/, and that is placed into thumb_url
		If the source is any other url except youtube, a generic icon for the
			mime type is used.

		*/
		if (empty($needs)) return true;

		if (! $adata = $this->getAssetDataById($id) ) {
			throw new ResourceException ("Trying to create thumb but no asset at id $id");
		}
		$turl = $adata['thumb_url'];
		$aurl = $adata['asset_url'];
		$amime = $adata['mime'];

		// set thumb source from db
		$tsource = $turl;

			// need to get thumb from the asset.
			// is the asset local
		if (! $tsource && u\is_local($aurl)) {
				$tsource = $aurl;
		}
		// is the asset a hyoutube video?
		if (! $tsource && $ytid = u\youtube_id_from_url($aurl) ){
			$yturl = "http://img.youtube.com/vi/$ytid/mqdefault.jpg";
			//echo "yturl $yturl". BRNL;exit;
			// is http://youtube.com/...../xx.jpg
			$thumb_url = "/assets/thumb_sources/${id}.jpg";
			//copy from the youtube site to local thumb source dir
			copy ($yturl , SITE_PATH . $thumb_url);
			$this->updateThumbUrl($id,$thumb_url);
			$tsource = $thumb_url;
		}
		if ($tsource && ! file_exists(SITE_PATH . $tsource) ) {
			throw new ResourceException ("Thumb source file $tsource does not exist.");
		}
		if ( (!$tsource) && u\is_http($aurl)  ){
				$tsource = $aurl;
				// but will use icons for the thumb
		}
			// if ($temp_source = $this->getTempSource($id,$tsource) ){
// 				$tsource = $temp_source;
// 				try {
// 					$mime = u\get_mime_from_url($temp_source);
// 				} catch (Exception $e) {
// 					echo "Failed to get mime info for asset $id";
// 					echo $e->getMessage();
// 					exit;
// 				}
// 			} else {
// 				throw new Exception ("Cannot download thumb source $tsource");
// 			}

		if (! $tsource)  {throw new ResourceException ("No valid thumb source for asset $id");}

		foreach ($needs as $need){
			$this->saveThumb($need,$id,$tsource,$amime);
		}

		return true;

	}


	private function getTempSource($id,$tsource) {
			/* downloads a web url to make a thumb and rturns path.
				If no download, returns requested source, and a
				genereic web icon will be used for thumb.
				*/

				$temp_source = "/tmp/" . "temp_thumb_$id";
				if (copy ($tsource, $temp_source) ){
					echo " (Retrieved url to $temp_source) " ;
					return $temp_source;
				} else {
					return false;
				}
	}




	public function updateStatus($id,$status){
		if (empty (Defs::$asset_status[$status] )) {
			throw new Exception ("Invalid Asset status code $status");
		}
		$sql = "UPDATE `assets2` SET astatus = '$status' WHERE id = $id";
		if($this->pdo->query($sql) ) return true;
		return false;
	}
    public function getAssetListByName($name) {
    	// returns list of ids of assets matching name in several fields
        $sql = "SELECT id from `assets2` where
        concat('', caption,title,keywords) like '%$name%'
        AND astatus not in ('X','E','T') ";

        $alist = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
        return $alist;
      }


	public function getAssetSummaryFromList($id_list){
		// returns minimal set of asset data
		$in_ids = join(',' , $id_list);
		$sql = "SELECT id,title,caption,asset_url,contributor_id,type, , astatus
			FROM `assets2` WHERE id in ($in_ids)";
		#	echo $sql . BRNL;
		$d = $this->pdo->query($sql)->fetchAll();
		return $d;
	}

   public function getAssetDataById($id){

   	$sql = "SELECT a.*,m.username as contributor from `assets2` a
   			LEFT JOIN members_f2 m on a.contributor_id = m.user_id
   			WHERE a.id = $id";
   	if (!$adata = $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC) ){ #arra
   		return [];
   	}

   	$adata['status'] = $adata['astatus'];


   	return $adata;
   }


   public function setFirstUse($ids,$ref){
   	/* pass a list of ids and a ref to a newsletter in newsp or
   		a gallery.  Run this when publishing an article or when
   		publishinig a gallery
   	*/
   	if (! $idlist = u\make_inlist_from_list ($ids) ){
   		throw new Exception ("First use not a list of ids");
   	}
   	if (! in_array(substr($ref,0,6) ,['/newsp','/galle'])) {
   		throw new Exception ("First use not in newsletter or gallery");
   	}


		$sql = "UPDATE `assets2` set first_use_date = NOW(), first_use_in = '$ref'
		WHERE id in ($idlist) AND first_use_date IS NULL ;";
		if ($this->pdo->query($sql)) return true;

		return false;
	}


public function getThumbUrl($id,$type){
// delivers the url to a thumb image, making it if necessary.
	// tesst for valid thumb type
	if (empty( Defs::$thumb_width[$type] )) {
		throw new RuntimeException ("Illegal thumb type requested $type");
	}

	$url = '/assets/' . $type . "/$id.jpg";
	if (file_exists (SITE_PATH . $url)) {
		return $url;
	} elseif (0) {//put another file test here
	} else {
		return false;
	// go make it
		//echo "Making new thumb $type for asset $id" . BRNL;
		//$this->createThumbs($id,[$type] );
	}
	return $url;
}

public function saveThumb ($ttype,$id,$turl,$amime){

		/*
			creates thubm types in list $needs for asset id $id.
			returns true

			requires the thumb source
			Gets mime type from get_mime_from_url (using finfo or curl).

		turl is url to source document for the thumbnail
			(image, video, youtube, whatever).
		amime is the mime type of the asset the thumb is for.

		ttype is thumb type
		 If thumbs, creates a 200w thumb in the thumbs directory.
		 If galleries, it creates a 330w image in galleries directory
		 If toons, it creates an 800w image in the toons directory
		 (see thumb_width array in Defs)

	returns true if everything works.
	 */

	 if (! $max_dim = Defs::$thumb_width[$ttype]){
		throw new Exception ("Invalid thumb type requested for thumbnail: $ttype");
	 }

	$tmime = u\get_mime_from_url($turl);

	 echo "Starting thumb $ttype on $id, asset mime $amime, from $turl. " .BRNL;
	 $thumb = "${id}.jpg";

	if (u\is_local($turl) ){
		$tpath = SITE_PATH . $turl;


		switch ($amime) {
			case 'application/msword' :
				$use_icon="doc.jpg";

				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
				break;
			case 'application/pdf' :
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/tiff':
			case 'video/x-youtube':
				$thumb = $this->buildImThumbnail($id, $tpath,$ttype);
				break;

			case 'text/html':
				$use_icon="web.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
				break;

			case 'video/mp4':
				$use_icon = 'mp4.jpg';
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
				break;

			case 'audio/mp3':
			case 'audio/m4a':
				$ext = substr($amime,-3,3);
				$use_icon = "${ext}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
				break;
			case 'video/quicktime':
				$use_icon = 'mov.jpg';
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
				break;

			default:
				$use_icon = 'default.jpg';
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
				break;
		}
		echo " /$ttype/$thumb created." . BRNL;
		return true;
	}
}




// not using GD instead of Imagick because gd can't do pdfs.
	private function buildImThumbnail ($id,$path,$ttype){
		 $thumb = $id . '.jpg';
		 if (!$max_dim = Defs::$thumb_width[$ttype]){
		 	throw new Exception ("unknown thumb type requested: $ttype.");
		 }
		 if (strcasecmp (pathinfo($path,PATHINFO_EXTENSION),'pdf') == 0){
			$path = trim($path) . '[0]'; #page 1
		 }
		 echo "calling imagick on $path" . BRNL;
		  	 $im = new \Imagick ();
			$im->readImage($path);
		 $im->setImageFormat('jpg');

		 $im->thumbnailImage($max_dim, $max_dim,true); #best fit
		 $im->writeImage(SITE_PATH . "/assets/$ttype/$thumb");

		 return $thumb;
	}


	private function getTypeFromMime ($mime) {
		 if (strpos($mime,'image') !== false){$type = 'Image';}
        elseif (strpos($mime,'audio') !== false){$type = 'Multimedia';}
        elseif (strpos($mime,'video') !== false){$type = 'Multimedia';}
        elseif (strpos($mime,'application') !== false){$type = 'Document';}
        elseif (strpos($mime,'html') !== false){$type = 'Web Page';}
      return $type;
   }



	private function getMimeSize ($url) {
	 	echo "Getting Mimesize for $url ... ";
		if (! u\url_exists ($url)) die ("url cannot be located");

		if ($mime = u\is_local($url)){
			$path = SITE_PATH . $url;
			$size = filesize($path) ?? 0;

		 }
		 else {

			$mime = u\get_info_from_curl($url)['mime'];
			$size = 0;

		 }

		if (empty ($mime)){
			$mime = 'n/a';
		}
		// remove extraneous stuff from mime
		preg_match('/(\w*\/\w+).*/',$mime,$m);
		$mime = $m[1];
		echo "$mime, $size" . BRNL;

		return [$mime,$size];
	}




	public function getIdsFromWhere($where) {
		// used to retrieve list of ids selected by the
		// WHERE clause in sdata
		$sql = "SELECT id from `assets2` WHERE $where LIMIT 100";
		// u\echoc($sql,'search sql');
		$found = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);

		return $found;


	}




}



