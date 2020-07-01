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



	set automatically:
		sizekb
		mime
		astatus
		date_created
		date_modified

	This class has these public methods
	save_asset (array) -
		updates or creates asset with values in array

		returns asset id

	get_existing_thumbs (id)
		returns list of thumb files that exist
	get_asset_data_enhanced
		returns array of asset data, with added info
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
'astatus',
		'notes',

		);

		// these fields are changed automatically and not entered on form
	public static $calculated_fields = array (

		'mime',
		'type',
		'sizekb',
		'local_src',
		'errors',



	);

	// these fields are updated by special methods
	public static $external_fields = array (
		'first_use_date',
		'first_use_in',

		'temptest',
	);

	public static $new_asset = array(
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



	public function saveAsset($adata) {
		/* adata is array with all the asset fields..
		// check data before saving

		*/

			$id = $adata['id'];



		#echo "Saving Asset $id" . BRNL;

		$allowed_fields = array_merge (self::$editable_fields, self::$calculated_fields);


		$prep = u\pdoPrep($adata,$allowed_fields,'id');

  			// asset id already created, so this is ALWAYS an update.

		$sql = "UPDATE `assets2` SET ${prep['updateu']} WHERE id = ${prep['key']} ;";
		//u\echor ($prep, $sql); exit;
		$stmt = $this->pdo->prepare($sql)->execute($prep['udata']);

		return $id;

	}

	public function getAssetDataEnhanced($id) {
		if ($id == 0){
   		// new asset
   		$adata = self::$new_asset;
   		$adata['contributor_id']  = $_SESSION['login']['user_id'];
   		$adata['contributor'] = $_SESSION['login']['username'];
   		$adata['id'] = 0;
   		$adata['date_entered'] = date('M d, Y');
   		$adata['first_use'] = 'Never';
   		$adata['vintage'] = date('Y');
   		$adata['errors'] = '';
   		$adata['next_edit'] = '0';
   		$adata['local_src'] = 'Not Created Yet';

   		return $adata;
   	}

		if (! $adata = $this->getAssetDataById($id) ){
			return [];
		}


	// set first_use text
   	$adata['first_use'] = "Never.";
   	if  (! empty($fud = $adata['first_use_date'])) {
   		$fud = $adata['first_use_date'];
   		$fin = $adata['first_use_in'];
   	}
   	if ($fud){
   		$adata['first_use'] =
   		"On " . date('d M Y',strtotime($fud) )
   		. " In " . "<a href='" . $fin . "'>" . $fin . "</a>";
   	}


		$adata['status_label'] = Defs::$asset_status[$adata['astatus']];

		$adata['image'] = $this->getAssetLinked($adata);

		$adata['link'] = $this->getAssetLinked($adata);

		$adata['warning'] = '';
		if ($adata['status'] == 'D' )	$adata['warning'] = 'Deleted';

		if ($adata['status'] == 'W' )	$adata['warning'] = "<br><span style='background:#CCC;'>${adata['errors']}</span>";

		if ($adata['status'] == 'E' )	$adata['warning'] = "<br><span style='color:red;'>${adata['errors']}</span>";



//u\echor($adata);
		return $adata;
	}

	private function getAssetLinked($adata) {
	/* returns the asset thumbnail, linked to the asset source */

		$status = $adata['astatus'];
		$id = $adata['id'];
		switch ($status) {
			case 'T':
				return "Temporary Asset";
				break;
			case 'D':
				return "Asset Deleted";
				break;
		}


		$link = $adata['asset_url'];
		if (empty($link)){return 'No asset url';}

		$thumb = "/thumbnails/small/${id}.jpg";


		if (!file_exists(SITE_PATH . $thumb)){
			return "No small thumbnail for asset";
		}
		if (1){  // add time to var to prevent caching
			$time = time();
			$thumb .= "?nocache=$time";
		}
		$result = <<<EOF
		<a href='$link' target="assetl">
		<img src='$thumb'>
		</a>
EOF;
#echo "RESULT <br>$result"; exit;

		return $result;

	}

	private function updateThumbUrl($id,$url) {
		$sql = "UPDATE `assets2` SET thumb_url = '$url' WHERE id = '$id' ";
		$this->pdo->query($sql);
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

				copy (FileDefs::asset_dir . "/icons/$use_icon" , FileDefs::thumb_dir . "/$ttype/$thumb");
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
				copy (FileDefs::asset_dir . "/icons/$use_icon" , FileDefs::thumb_dir . "/$ttype/$thumb");
				break;

			case 'video/mp4':
				$use_icon = 'mp4.jpg';
				copy (FileDefs::asset_dir . "/icons/$use_icon" , FileDefs::thumb_dir . "/$ttype/$thumb");
				break;

			case 'audio/mp3':
			case 'audio/m4a':
				$ext = substr($amime,-3,3);
				$use_icon = "${ext}.jpg";
				copy (FileDefs::asset_dir . "/icons/$use_icon", FileDefs::thumb_dir . "/$ttype/$thumb");
				break;
			case 'video/quicktime':
				$use_icon = 'mov.jpg';
				copy (FileDefs::asset_dir . "/icons/$use_icon", FileDefs::thumb_dir . "/$ttype/$thumb");
				break;

			default:
				$use_icon = 'default.jpg';
				copy (FileDefs::asset_dir . "/icons/$use_icon", FileDefs::thumb_dir . "/$ttype/$thumb");
				break;
		}
		echo " /$ttype/$thumb created." . BRNL;
		return true;
	}
}


public function getThumbData($id) {
	$sql = "SELECT asset_url,mime, astatus,
	local_src,title, caption,source
	FROM `assets2`
	WHERE id = $id";

	if ($result = $this->pdo->query($sql)->fetch( ) ){
		return $result;
	} else {
		return [];
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
		 u\echoc($sql,'search sql');
		$found = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);

		return $found;


	}




}



