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
	status
	
	first_use_date
	first_use_in
	
	needs [thumbs,galleries,toons]  // list of needed thumbs

	set automatically:
		sizekb
		mime
		status
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



class Asset {
    
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
		'status',
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
		$sql = "DELETE FROM `assets2` WHERE status = 'T' and date_entered < now() - interval 1 hour";
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
		
		
		if (! is_integer ($adata['vintage'])){
				throw new Exception ("Vintage is not a valid year");
		}
		
		
		if (
			! list ($contributor, $contributor_id ) 
			= $this->member->getMemberId($adata['contributor']) 
			|| ! $adata['contributor'] == $contributor 
			){	
				throw new Exception ("Mismatch contributor name and id");
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
   
   public function getAssetDataById($id){
   	if ($id == 0){
   		// new asset
   		$adata = $this->new_asset;
   		$adata['contributor_id']  = $_SESSION['login']['user_id'];
   		$adata['contributor'] = $_SESSION['login']['username'];
   		$adata['id'] = 0;
   		$adata['date_created'] = date('M d, Y');
   		$adata['first_use'] = 'Never';
   		return $adata;
   	}
   	$sql = "SELECT a.*, m.username as contributor, m.user_email from `assets2` a
   		INNER JOIN `members_f2` m on a.contributor_id = m.user_id where a.id = $id";
   	if (!$adata = $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC) ){ #array
   		die ("No asset at $id");
   	}
   	// set tic character for each thumb that currently exixts.
   	$adata['first_use'] = "Never.";
   	
   	$adata['existing_thumbs'] = $this->getExistingThumbs($id);
   	
   	if  (!empty($fud = $adata['first_use_date'])) {
   		$adata['first_use'] = 
   		"On " . date('d M Y',strtotime($fud) )
   		. " In " . "<a href='" . $adata['first_use_in'] . "'>" 
   		.	$adata['first_use_in'] . "</a>";
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
		 if ($msg){ echo "$msg" ; return false;}
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
					
				$ref = $_SERVER['REQUEST_URI'];
				// dont count if it's coming from the asset search script
				if (strpos ($ref, '/scripts/assets.php' ) === false){return null;}
				if ($_SESSION['level'] > 5){return null;} #anythning over member

				
				$sqld = "UPDATE `assets2` set first_use_date = NOW(), first_use_in = '$ref' where id = '$id';";
				if ($this->pdo->query($sqld)){return true;}
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
			$mime = $this->mimeinfo->file($source_path);
			
			$size = filesize($source_path) ?? 0;
		 	
		 }	
		 else {
			$source_path = $url;
			list($mime,$size) = $this->getMimesizeFromCurl($source_path);
		 }
		
		if (empty ($mime)){
			$mime = 'n/a/';
		} 
		echo "$mime, $size" . BRNL;
		
		return [$mime,$size];
	}
	
	private function getMimesizeFromCurl($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_exec($ch);
		$mime =  curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		return [$mime,$size];
	}
	
	private function getMimeGroup ($mime) {
		if (strncmp($mime,'image/',6) == 0 ){return 'image';}
		elseif (strncmp($mime,'video/',6) == 0) {return 'av';}
		elseif (strncmp($mime,'audio/',6) == 0) {return 'av';}
		elseif (strncmp($mime,'application/',12) == 0) {return 'doc';}
		else {return '';}
	}

	private function autoRotateImage($image) { 
		 $orientation = $image->getImageOrientation(); 

		 switch($orientation) { 
			  case imagick::ORIENTATION_BOTTOMRIGHT: 
					$image->rotateimage("#000", 180); // rotate 180 degrees 
			  break; 

			  case imagick::ORIENTATION_RIGHTTOP: 
					$image->rotateimage("#000", 90); // rotate 90 degrees CW 
			  break; 

			  case imagick::ORIENTATION_LEFTBOTTOM: 
					$image->rotateimage("#000", -90); // rotate 90 degrees CCW 
			  break; 
		 } 

		 // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image! 
		 $image->setImageOrientation(imagick::ORIENTATION_TOPLEFT); 
	} 

	private function get_gfile($filepath) {
		 #looks for designated file and returns its full path
		 # if not found looks for either jpg or png or gif with same name

		 $path = SITE_PATH . $filepath;
		# ,);
		 if (file_exists($path)){return $path;}
		 else {
			  $ext = pathinfo($path,PATHINFO_EXTENSION);
			  $dir = pathinfo($path,PATHINFO_DIRNAME);
			  #drop extension
			  preg_match('/(^.*?)\.\w+$/',$path,$m);
			  $path2 = $m[1];
			  foreach (['jpg','png','gif'] as $ex){
					if ($ex == $ext){continue;}
					$tfile = $path2 . ".$ex";
					if (file_exists($tfile)){return $tfile;}
			  }
		 }
	}

}

    

