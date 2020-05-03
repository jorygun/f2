<?php
namespace digitalmx\flames;

use digitalmx\MyPDO;
use digitalmx\flames as f;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;

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
    

    private $member;
    
  
						 
	
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
		
	);

	// these fields are updated by special methods
	public static $external_fields = array (
		'first_use_date', 
		'first_use_in', 
		'astatus',
	);
	
	private  $new_asset = array(
		'title' => '',
		'source' => '',
		'contributor_id' => '',
		'status' => 'N',
		'asset_url' => '', 
		'thumb_url' => '', 
		'caption' => '', 
		'keywords' => '', 
		'tags' => '', 
		'vintage' => '', 
		'notes' => '',
		'existing_thumbs' => [],
	);
	
	private $assetfields;
	private $archive_tag_list_sql;
	private static $image_extensions = array('jpg','gif','png','jpeg');
	private static $document_extensions = array('doc','docx','pdf','html');
	private static $mmm_extensions = array('mov','mp4','mp3','m4a');
	
	
    
    public function __construct(){
        $this->pdo = MyPDO::instance();
        $this->mimeinfo  = new \finfo(FILEINFO_MIME_TYPE);
			
			$this->member = new Member();
	
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
	
	private function checkAssetData($adata) {
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
// 	status
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
	
		$adata['id'] = (int)$adata['id'];
		if (! is_integer($adata['id'])){
			throw new Exception ("bad id: ". $adata['id']);
		}
		$adata['asset_url'] = trim ($adata['asset_url']); 
		if (empty($adata['asset_url'])){
			throw new Exception ("No source for asset specified");
		}
		$srcok = false;
		if (substr($adata['asset_url'],0,1) == '/' ) {
			if (!file_exists(SITE_PATH . $adata['asset_url'])){
				throw new Exception ("No file at source " . $adata['asset_url']);
			}
			$srcok = true;
		}
		if (substr($adata['asset_url'],0,4) == 'http') {
			if (! u\url_exists ($adata['asset_url'])) {
				throw new Exception ("Nothinig at url " . $adata['asset_url']);
			}
			$srcok = true;
		}
		if (! $srcok){
			throw new Exception ("asset source not understod " . $adata['asset_url']);
		}
		
		$adata['title'] = trim($adata['title']); #add title case?
		if (empty($adata['title'])){
			throw new Exception ("No title for asset specified");
		}
		
		
		if (! is_integer (0 + $adata['vintage'])){ #to cast as numeric
				throw new Exception ("Vintage is not a valid year");
		}
		
		$adata['astatus'] ?: $adata['status'];
		
		if (! is_integer (0 + $adata['contributor_id']) 
			&& $adata['contributor_id'] > 0 ){
				throw new Exception ("No contributor id");
		}
		
	
		if (! empty($adata['needs'])){
			if (! is_array($adata['needs'])) {
				throw new Exception ("Thumb requested not in a list.");
			}
			foreach ($adata['needs'] as $ttype) {
				if (! in_array($ttype, Defs::getThumbTypes() ) ) {
					throw new Exception ("Unrecognized thumb requested $ttype");
				}
			}
		}
		else {$adata['needs'] = [];}
	
		if (! empty($adata['tags'])){
			foreach (str_split($adata['tags']) as $tag){
				if (! in_array($tag,array_keys(Defs::$asset_tags))){
					throw new Exception ("Unknown asset tag $tag");
				}
			}
		}
		
		
		return $adata;

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
				$adata = $this->checkAssetData($adata);
		
		} catch (Exception $e) {
				echo "Error saving asset data." . BRNL;
				echo $e->getMessage();
				exit;
		}
		
			$source = $adata['asset_url'];

			list ($mime,$size) = $this->getMimesize($source);
			 
			 if (!in_array($mime,Defs::getAcceptedMime() ) ){
			 	throw new Exception ("Source type $mime is not accdptable");
			 }
			$adata['sizekb'] = (int)($size/1000);
			$adata['mime'] = $mime;
			if (!$adata['type'] =  $this->getTypeFromMime ($adata['mime']) ){
				throw new Exception ("Cannot get asset type for mime ${adata['mime']}");
			}
			
			echo "<b>Saving Asset $id mime $mime, sizekb $size</b>" . BRNL;
	
			
			
			// save all tbunbs, including thumb if not there.
		
			if (!empty( $adata['needs'])) {
				echo "Creating thumbs now... " ;
				
				if (empty( $tsource = $adata['thumb_url'] ) ){
						$tsource = $adata['asset_url'];
						$mime = $adata['mime'];
				} else {
					list($mime,$size) = $this->getMimesize($tsource);
				}
				#get temp file if source is a url 
				if (strcasecmp($mime, "text/html") == 0
					|| substr($tsource,0,4) == 'http' ) {
					$temp_source = $this->getTempSource($id,$tsource);
					$tsource = $temp_source;
				}
				echo " from $tsource. " . BRNL;
				
				foreach ($adata['needs'] as $need){
					$this->saveThumb($need,$id,$mime,$tsource);
				}
				if (isset($temp_source) && file_exists($temp_source)){unlink($temp_source);}
				
			}

			
			$allowed_fields = array_merge (self::$editable_fields, self::$calculated_fields);
			if (!empty($adata['status'])) {$allowed_fields[] = 'status';}
			
			$prep = u\pdoPrep($adata,$allowed_fields,'id');
			#u\echor ($prep, 'Prep'); exit;
 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
  			// asset id already created, so this is ALWAYS an update.
		
			 $sql = "UPDATE `assets2` SET ${prep['update']} WHERE id = ${prep['key']} ;";
				$stmt = $this->pdo->prepare($sql)->execute($prep['data']);
		
		return $id;
	
	}
		
	private function searchAssets($as) {
	
	
	
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
					echo "Unable to download $tsource";
					return $tsource;
				}
	}
	
	
	public function getExistingThumbs ($id) {
		// returns list of thumb types that exist
		$thumb = "${id}.jpg";
		$tloc = SITE_PATH . "/assets";
		$ttypes = [];
		foreach (Defs::getThumbTypes() as $ttype){	
			if (file_exists($tloc . '/' . $ttype . '/' . $thumb)){
				$ttypes[] = $ttype; 
			}
		}
		return $ttypes;
	}
	

	public function updateStatus($id,$status){
		if (empty (Defs::$asset_status[$status] )) {
			throw new Exception ("Invalid Asset status code $status");
		}
		$sql = "UPDATE `assets2` SET status = '$status' WHERE id = $id";
		$this->pdo->query($sql);
		return true;
	}
    public function getAssetListByName($name) {
    	// returns list of ids of assets matching name in several fields
        $sql = "SELECT id from `assets2` where 
        concat('', caption,title,keywords) like '%$name%' ";

        $alist = self::$pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
        return $alist;
      }
   
	public function getAssetSummaryById($id) {
		// returns minimal set of asset data
		$sql = "SELECT id,title,caption,asset_url , astatus
			FROM `assets2` WHERE id = $id";
		$d = $this->pdo->query($sql)->fetch();
		return $d;
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
   	if ($id == 0){
   		// new asset
   		$adata = $this->new_asset;
   		$adata['contributor_id']  = $_SESSION['login']['user_id'];
   		$adata['contributor'] = $_SESSION['login']['username'];
   		$adata['id'] = 0;
   		$adata['date_entered'] = date('M d, Y');
   		$adata['first_use'] = 'Never';
   		$adata['vintage'] = date('Y');
   		
   		return $adata;
   	}
   	$sql = "SELECT a.*, m.username as contributor, m.user_email from `assets2` a
   		INNER JOIN `members_f2` m on a.contributor_id = m.user_id where a.id = $id";
   	if (!$adata = $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC) ){ #array
   		return [];
   	}
   	// set tic character for each thumb that currently exixts.
   	$adata['first_use'] = "Never.";
   	
   	$adata['existing_thumbs'] = $this->getExistingThumbs($id);
   	$adata['status'] = $adata['astatus'];
   	
   	
   	if  (empty($fud = $adata['first_use_date'])) {
   		list($fud,$fin) = $this->setFirstUse($id);
   	} else {
   		$fud = $adata['first_use_date'];
   		$fin = $adata['first_use_in'];
   	}
   	if ($fud){
   		$adata['first_use'] = 
   		"On " . date('d M Y',strtotime($fud) )
   		. " In " . "<a href='" . $fin . "'>" . $fin . "</a>";
   	}
   	return $adata;
   }
   
   
   public function checkURL($url) {
   	// checks for existance of file or url
   	$msg = '';
   	if (substr($url,0,1) == '/') { #local file
			$source_path = SITE_PATH . $url;
			if (! file_exists($source_path)){
				$msg = "No file at $url";
			}
		 }	
		 elseif (substr($url,0,4) == 'http') {
			$source_path = $url;
			if (! u\url_exists($source_path)){
				$msg = "Nothing found at source url $url";
			}

		 }
		 else {
		 	$msg = "Unknown url at $url";
		 }
		 if ($msg){ 
		 #	echo "$msg" ; 
		 	return false;
		 }
		 return true;
   }
   
   public function getThumbTics($id) {
   /* returns array of all thumb types and check mark if thumb exists */
   		$thumb_tics = [];
   		$thumb_list = [];
   		if ($id > 0) {
   			$thumb_list = $this->getExistingThumbs($id);
   		}
			foreach(array_keys(Defs::$thumb_width) as $ttype) {
				$thumb_tics[$ttype] = (in_array($ttype,$thumb_list))?'&radic;':'';
			}
			return $thumb_tics;
		}
   private function setFirstUse($id){
		 #sets first use date on an asset
				if ($_SESSION['level'] > 5){return null;} #anythning over member
				
				$ref = $_SERVER['REQUEST_URI'];
				
				$sqld = "UPDATE `assets2` set first_use_date = NOW(), first_use_in = '$ref' where id = '$id';";
				if ($this->pdo->query($sqld)){
					return [date('Y-m-d'),$ref];
				}
				return false;
	}


public function saveThumb ($ttype,$id,$mime,$turl){

		/* 
			creates thubm types in list $needs for asset id $id.
			returns true
			
			requires the thumb source and mime type, which comes from db entry.
			
		source is url to source document for the thumbnail 
		(image, video, youtube, whatever).  
		
	
		ttype is thumb type
		 If thumbs, creates a 200w thumb in the thumbs directory.
		 If galleries, it creates a 330w image in galleries directory
		 If toons, it creates an 800w image in the toons directory
		 (see thumb_width array in Defs)

	returns true if everything works.
	 */
	
	 echo "Starting thumb $ttype ... " ;
	 $thumb = "${id}.jpg";
	if (substr($turl,0,4) == '/tmp') { 
		$tpath = $turl;
	} else {
		$tpath = SITE_PATH . $turl;
	}
	echo " from path $tpath." . BRNL;
		
	if (! $max_dim = Defs::$thumb_width[$ttype]){
		throw new Exception ("Invalid thumb type requested for thumbnail: $ttype");
	 }
 
			switch ($mime) {
				case 'application/msword' :
					$use_icon="doc.jpg";
				
					copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
					break;
				case 'application/pdf' :
				case 'image/gif':
				case 'image/jpeg':
				case 'image/png':
				case 'image/tiff':
					$thumb = $this->buildImThumbnail($id,$mime,$tpath, $ttype);
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
					$ext = substr($mime,6,3);
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
		
	
	

	

	private function buildImThumbnail ($id,$source_mime,$path,$ttype){
		 $thumb = $id . '.jpg';
		 if (!$max_dim = Defs::$thumb_width[$ttype]){
		 	throw new Exception ("unknown thumb type requested: $ttype.");
		 }
		 if ($source_mime == 'application/pdf'){
			$source = trim($source) . '[0]'; #page 1
		 }
		 
		  	 $im = new \Imagick ( $path);
		
		 $im->setImageFormat('jpg');
	 
		#autoRotateImage($im); 

		
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
		if (!$this->checkURL($url)){
			die ("url cannot be located");
		}
		
		 if (substr($url,0,1) == '/') { #local file
			$source_path = SITE_PATH . $url;
			if (! file_exists($source_path)){
				return [ "File $url does not exist", 0];
			}
			$mime = $this->mimeinfo->file($source_path);
			$size = filesize($source_path) ?? 0;
		 	
		 }	
		 else {
			$source_path = $url;
			$mime = u\get_mime_from_url($url);
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
	
	

	private function post_asset($post_array){
		/*
			 $post_array should contain all editable fields.
			 automatic fields computed prior to post.
			
		
		 */
		 // check for required fields
		 if (
		 	! is_numeric($post_array['id'])
		 	|| empty ($post_array['title'])
		 	|| empty ($post_array['url'] )
		 	){
		 		throw new Exception ("Asset missing required id, title, or url.");
		 	}
		 	
		 $changed_asset= false;
		$datetag=date('m/d/y');
	
		 $id = $post_array['id'];
		 
		 
		  if ($id == 0){

			  $title = "temp holding place";
			  $sql = "INSERT into `assets` (astatus,title,date_entered,type,thumb_file) values ('T','$title',NOW() ,'','');";
			  echo $sql . BRNL;
			  $this->pdo->query($sql);
			  $last_id = $this->pdo->lastInsertId();
			  $post_array['id'] = $id = $last_id;
			  $post_array['status'] = 'T';
			  $post_array['type'] = '';
			  echo "New ID created (temp): $id<br>\n";
		 }

		 echo "<hr>Starting post_asset on id $id. " . BRNL;
		# recho ($post_array,'Post_array');
		# recho ($_FILES,'FILES array');
	 
		 $form_link = $post_array['link'] ?? '';
		
	
	 
	/**
		 relocate uploads
	 
		 Files are either uploaded from form or uploaded some other way
		 into specific directories ftp or uploads.
		 These files need to be moved into correct location in assets, and
		 then the asset created with the appropriate link.
	 
		 From asset form:
		
			'link_source' used for both source file and link to.
			'thumb_source' used for additional file just to use for thumbnail
			in either one, you can have
			* a url
			* a local directory/file
			* ftp/filename
			* uploads/filename 
		
			or you can use an uploaded file
			'link_upload is the uploaded main fail
			'thumb_upload' is the uploaded thumb source file
		
			uploaded file always takes priority for main link
			else use the link directory name
		
			For link possibility
				check to make sure file exists
				move file to appropriate directory, renamed in most cases
				set link in asset to new loacation/name
			
				file uploaded with form has priority
			Then set thumb from uplink if supplied
		
			*/
		
			// get the main source
		
			if (!empty($_FILES['linkfile']['name'])){
				$link = relocate ($id,'link_upload');
			
			} elseif (strncmp ($form_link, '/uploads',8) == 0) {
				 $link = relocate($id, 'uploads',$form_link);
			} elseif (strncmp ($form_link, '/ftp',4) == 0) {
				$link = relocate($id, 'ftp',$form_link);
			} else {
				$link = $form_link;
			}
		 if (substr($link,0,1) == '/') { #local file
			 $finfo = new \finfo(FILEINFO_MIME);
			 $post_array['mime'] = $finfo->file(SITE_PATH . "/$link");
			 $post_array['sizekb'] =  round(filesize(SITE_PATH . "/$link")/1000,0);
			 $post_array['link'] = $link;
			}
			$linkdata = add_link_data($link);
			$post_array = array_merge ($post_array,$linkdata);
		
		 echo "post_array[link] set to $link" . BRNL;
  
			#now check for separate thumb file source
			#remove old duplicate of link
			// if ($post_array['url'] == $post_array['link'] ){
	// 	 		$post_array['url'] = '';
	// 	 	}
		
			if (!empty($_FILES['upfile']['name'])) {
				$thumb_source = relocate ($id,'thumb_upload' );
				$post_array['url'] = $thumb_source;
			}
			if (!empty ($post_array['url'])){
				$thumb_source = $post_array['url'];
			} else {
				$thumb_source = $link;
			}
		
	
  

	 #test to see if url has changed; if so update thumb
		  $row = $this->pdo->query("SELECT link,url from `assets` where id = $id;")->fetch(\PDO::FETCH_ASSOC);
			$orig_link = $row['link'];
			$orig_url = $row['url'];
		
			if( $orig_link != $post_array['link'] ){
			  if (! empty($orig_link)) {
					echo "Source has changed (was $orig_link); will regenerate thumb" . BRNL;
						 $changed_asset = true;
			  }
			  $post_array['need_thumb'] = true;
		 }
		 if( $orig_url != $post_array['url'] ){
			  if (! empty($orig_url)) {
					echo "Thumb source has changed (was $orig_url); will regenerate thumb" . BRNL;
						 $changed_asset = true;
			  }
			  $post_array['need_thumb'] = true;
		 }

	#now create thumbs
		  

	

			  if (isset($post_array['need_thumb'])){
					echo "Need new thumbnail from $thumb_source... " . BRNL;
					if($thumb = create_thumb ($id,$thumb_source,'thumbs')){
						 //$post_array['has_thumb'] = true;
						 $post_array['thumb_file'] = $thumb;
						 echo "Thumb $thumb created. ";
					}
					echo "<br>";
			  }
			  if (isset($post_array['need_gallery'])){
					echo "Need new gallery ... ";
					if($thumb = create_thumb ($id,$thumb_source,'galleries')){
						 echo "Gallery $thumb created. ";
						 //$post_array['has_gallery'] = true;
					}
					echo "<br>";
			  }
			  if (isset ($post_array['need_toon']) ){
					echo "Need new toon ... ";
					if($thumb = create_thumb ($id,$thumb_source,'toons')){
						 echo "Toon $thumb created";
						 //$post_array['has_toon'] = true;
					}
					echo "<br>";
			  }

		 // $post_array['has_thumb'] = png_or_jpg_exists('thumbs',$id);
	// 	$post_array['has_gallery'] = png_or_jpg_exists('galleries',$id);
	//     $post_array['has_toon'] =  png_or_jpg_exists('toons',$id);;

	#recho ($post_array,"Ready to Update"); exit;
		 // Decomptress the tag options
		 if (!empty($post_array['tags'])){
			$post_array['tags'] = charListToString($post_array['tags']) ;
		 }

		 #remove entities from title, caption, notes
		 foreach (['caption','title','notes'] as $v){
			  $post_array[$v] = spchard($post_array[$v]) ?? '';
		 }
	
		if ($post_array['status'] == 'T'){$post_array['status'] = 'N';}
		# else { $post_array['status'] = $itemdata['status'];}

	#recho ($post_array,'Post array ');
			update_asset($post_array);

		 return $id;

	}
	
private function create_thumb($id,$fsource,$ttype='thumbs'){

		 #if (!$id || !$type){die "Create thumb called with $id,$type empty";}
		/* returns url (/assets/thumbs/$id.png) to thumbnail file at $source
		
	
		fsource is url to source.  Maybe remote or local
	
		 tType is array of types:
		 If thumbs, creates a 200w thumb in the thumb file.
		 If galleries, it creates a 300w copy
		 If toons, it creates an 800w copy.

		 if asset is local, set thumb to either 200w copy of the image
		 or to generic document image

		 if image is on a url, set to generic url image (or
		 curl the url and build a png thumb)


	 */
		$fsource = trim($fsource);
	
		#check to see if ttype requested is recognized width
		 if (! $max_dim = self::$thumb_width[$ttype]){die ("Invalid thumb type requested for thumbnail: $ttype");}
	
		 if (empty($fsource)){die ("No file specified to create thumb  from.<br>\n");}
		 else {echo "Creating thumb from $fsource" . BRNL;}
	
		 $thumb = '';
	 
		if ($videoid = $this->youtube_id_from_url($fsource)){
			#echo "got videoid $videoid" . BRNL;
			$yturl = "http://img.youtube.com/vi/$videoid/mqdefault.jpg" ;
			#echo "yturl $yturl". BRNL;
			$thumb = "${id}.jpg";
			copy ($yturl , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
		
		}
	
	
		 #set source path to either absolute file path or url
	 
		 if (substr($fsource,0,1) == '/') { #local file
			$source_path = SITE_PATH . $fsource;
		 }	
		 else {
			$source_path = $fsource;
		
		 }
		 if (! file_exists($source_path)){
			throw new Exception ("No file found at $source_path");
		 }
		 $finfo = new \finfo(FILEINFO_MIME_TYPE);
	 
		 if (substr($source_path,0,4) == 'http'){
			$source_mime = get_url_mime_type($source_path);
		 } elseif ( $source_mime = $finfo->file($source_path)) {
			
		} else {
			echo "Unable to get mime type from source $source_path" . BRNL;
		}
	
		echo "Mime: $source_mime" . BRNL;
		
		
		switch ($source_mime) {
			case 'application/msword' :
				$use_icon="doc.jpg";
				$thumb = "${id}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
				return $thumb;
				break;
			case 'application/pdf' :
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/tiff':
				$thumb = build_im_thumbnail($id,$source_mime,$source_path,$ttype,$max_dim);
				return $thumb;
				break;
			case 'text/html':
				$use_icon="web.jpg";
				$thumb = "${id}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
				return $thumb;
				break;
			case 'video/mp4':
				$use_icon = 'mp4.jpg';
				$thumb = "${id}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
				return $thumb;
				break;
			case 'audio/mp3':
			case 'audio/m4a':
				$ext = substr($source_mime,6,3);
				$use_icon = "${ext}.jpg";
				$thumb = "${id}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
				return $thumb;
				break;
			case 'video/quicktime':
				$use_icon = 'mov.jpg';
				$thumb = "${id}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
				return $thumb;
				break;
			
			default:
				$use_icon = 'default.jpg';
				$thumb = "${id}.jpg";
				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
				return $thumb;
				break;
			
		}
		 #if still haven't created a thumb...
			die("Cannot determine how to build thumb on $fsource (mime: $source_mime)");



	}

		private function youtube_id_from_url($url) {
			
					 $pattern = 
					'%#match any youtube url
						 (?:https?://)?  # Optional scheme. Either http or https
						 (?:www\.)?      # Optional www subdomain
						 (?:             # Group host alternatives
							youtu\.be/    # Either youtu.be,
						 | youtube\.com/
						 )				# or youtube.com
						 (?:          # Group path alternatives
							  embed/     # Either /embed/
							| v/         # or /v/
							| watch\?v=  # or /watch\?v=			
						 ) ?            # or nothing# End path alternatives.
											 # End host alternatives.
						 ([\w-]+)  # Allow 10-12 for 11 char youtube id.
						 %x'
						 ;	          
					$result = preg_match($pattern, $url, $matches);
					if (array_key_exists(1,$matches)){
						$vid = $matches[1] ;
						echo "Matched youtube $matches[0] to video id $vid " . BRNL;
						return $vid; 
					}
					else {
						#echo "No youtube id in $url" . BRNL;
						return false;
					}
	 }

	 
	 
	
	private function get_mime_type_from_url($url)
	{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_exec($ch);
	return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	}

	private function build_im_thumbnail ($id,$source_mime,$source,$ttype,$max_dim){
		 $thumb = $id . '.jpg';
		 if ($source_mime == 'application/pdf'){
			$source = trim($source) . '[0]'; #page 1
		 }
		  $im = new Imagick ( $source);
		 $im->setImageFormat('jpg');
	 
		autoRotateImage($im); 


		 $im->thumbnailImage($max_dim, $max_dim,true); #best fit
		 $im->writeImage(SITE_PATH . "/assets/$ttype/$thumb");
		 return $thumb;
	}
	
	public function getIdsFromWhere($where) {
		// used to retrieve list of ids selected by the
		// WHERE clause in sdata
		$sql = "SELECT id from `assets2` WHERE $where LIMIT 500";
		$found = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
		
		return $found;
		
	
	}




}

    

