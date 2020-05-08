<?php
/* contains a bunch of definitions and scripts used by multiple asset
    related scripts.
    
    Enter with an array of asset data, with id=0 for new.
    asset source can be ext. url or local url or uploaded file.
    If uploaded file, file is saved at /assets/files
    
    Every asset has a thumb file at /assets/thumbs/id.jpg
    If no thumbsource specified, thumb is vreated from asset.
    If local thumbsource thumb created from that
    If ext thubmsource, source is downloaded to temp file and thumb
     created for that.
   If file upload thumbsource, that file is saved to /assets/thumb_urls
     and thumb createdd from that. (and thumb source changed.)
*/
namespace digitalmx\flames;

	use digitalmx\flames\Definitions as Defs;
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\FileDefs;
	use digitalmx\flames\Assets;


class AssetAdmin 
{


	private $pdo;
	private $archive_tag_list_sql;
	private $assets;
	private $member;
	private $mimeinfo;
	
	
	
	
	private static $upload_types = array(
		'uasset','uthumb','uuploads','uftp');
		
	public function __construct() {
		$this->pdo =  MyPDO::instance();
		$this->archive_tag_list_sql =  Defs::getArchivalTagList();
		$this->assets = new Assets();
		$this->member = new Member();
		$this->mimeinfo = new \finfo(FILEINFO_MIME_TYPE);
		
		
		


	}
	// takes asset data array, prepares thumbs needed,
	// and sends to Assets to store (and add computed fields).
	// returns id of asset.
	
	public function postAssetFromForm($post) {
	// prepare data and then send to asset to post
	
	#u\echor($post,'post data in');

	
	
		if (! isset ($post['id'])){
				throw new Exception ("attempt to post asset with no id set");
		}
		if (
			empty($post['title'])
			// || (empty($post['asset_url']) && empty($_FILES['uasset']['tmp_name']) )
			) {
			die ("Asset needs title.");
		}
		// must have id before all the data is saved to place files.
		// this creates a skeleton asset record and gets the id.
		if (empty ($id = $post['id'])) {
				$id = $this->assets->getNewID();
				echo "New id $id obtained." . BRNL;
				$adata ['astatus'] = 'N';
		}
		// for existing items, status is not updated when item is saved
		
		// move the post data needed from thep ost to adata.
		foreach ($this->assets::$editable_fields as $f) {
			$adata[$f] = $post[$f]??'';
		}
		$adata['id'] = $id;
		
		#echo "pre c ookup: " . $post['contributor'] . ' ' . $post['contributor_id'] . BRNL; 
		
		if (!empty($post['contributor_id']) && $id > 0 ){
			$adata['contributor_id'] = $post['contributor_id'];
		} elseif (!empty ($post['contributor'] )) {
			list ($adata['contributor'], $adata['contributor_id'] ) 
				= $this->member->getMemberId($post['contributor']) ;
				if (empty($adata['contributor'])){	
				throw new Exception ("Contributor ${post['contributor']} not found"); }
		} else {
			die ("No contributor info supplied");
		}
		
	#echo "after c ookup: " . $adata['contributor'] . ' ' . $adata['contributor_id'] . BRNL; exit;
	
		$adata['vintage'] = trim($adata['vintage']);
		if (empty($adata['vintage'])){
			$adata['vintage'] = date('Y');
		}
	
		/* new thumbs is list of thumb types needed - from
			checkboxes on the asset form or from replacing
			existing thumbs because sources have changed.
		*/
		$new_thumbs = []; 
		foreach (Defs::getThumbTypes()  as $ttype) {
	
			if (!empty($post[$ttype])){
				$new_thumbs[] = $ttype;
				#echo "New thumb requested: $ttype. ";
			}
		}
		
	// first look for any relocates from file uploads
	// move file into assets files or thubm-sources and
	// change source def to match.
	#u\echor($_FILES,'FILES');
	
		foreach (self::$upload_types as $type){
			if (isset($_FILES[$type]) && !empty ($_FILES[$type]['name'] )){
				echo "Relocating $type... " ;
				$url = $this->relocateUpload($id,$type);
				echo "to: $url" . BRNL;
				
				if ($type == 'uthumb'){
					$adata['thumb_url'] = $url;
				} else {
					$adata['asset_url'] = $url;
					$adata['notes'] .= "Uploaded from " . $_FILES[$type]['name'] . "\n";
				}
				$new_thumbs[] = 'all'; #flag to recreate thunbs
				$adata['status'] = 'N';
			}
		}
		
		if (empty($adata['asset_url'])) {
			die ("Asset requires a source");
		}
		
		
		
		if (!empty($post['tags']) && is_array ($post['tags'])){
			// convert to string
			$adata['tags'] =  charListToString($post['tags']) ;
		}
		
		 $adata['needs'] = $this->checkThumbNeeds($adata,$new_thumbs);
	
	#exit;
	
		$this->assets->saveAsset($adata);
		
		echo "<a href='asset_editor.php?id=$id' target='asset_editor'>View in Editor</a>" . BRNL;
		return $id;

	}
	
	private function checkThumbNeeds($adata,$new_thumbs) {
		// set which thumbs are needed, by checkbox or by changed url
		$id = $adata['id'];
		$needs = array ();
		$result = $this->assets->getAssetDataById($id);
		
		$thumbs = $result['existing_thumbs']; #keys where value is ''
		
		// if either url has been changed, all the thumbs need to be regneratied.
		if ($result['asset_url'] != $adata['asset_url']
			|| $result['thumb_url'] != $adata['thumb_url'] 
			|| in_array('all',$new_thumbs) ){
			$needs = $thumbs; #all existing thumbs
			$new_thumbs = array_diff ($new_thumbs,['all']) ;
		}
		if (! in_array('thumbs',$thumbs)){$new_thumbs[] = 'thumbs';} #always need this
		// add in any thumbs were checked on the form
		$needs = array_unique(array_merge($needs,$new_thumbs));
		#u\echor($needs,'needs after check needs'); exit;
		
		
		return $needs;
	}
	
	public function getAssetBlock($aid,$style,$show_caption=false) {
		/* returns a div with the asset and title in it.
		uses asset thumb or gallery size
		shows thumb linked to asset
		below thumb is title in bold and optional in italic
		
		style left = float left, fixed width blocks to line up
			in a column.
		style top = float left, fixed height blocks to line up in rows
		style center = use gallery size thumb, no float.
		
		*/
		if (! $adata = $this->assets->getAssetDataById($aid) ) {
			return "Asset $id not found";
		}
		$aurl = $adata['asset_url'];
		$atitle = $adata['title'];
		$acapt = ($show_caption)? 
			"<div class='acaption'>${adata['caption']}</div>" : '';

		$attr = $adata['source'];
		$attr_block = (!empty($attr))? "<div class='asource'>-- $attr</div>" : '';
			
		
		
		switch($style) {
			case 'thumb':
				$block = <<<EOT
				<div class= 'asset'>
					<a href='/asset_viewer.php?$aid' target='viewer'>
					<img src='/assets/thumbs/$aid.jpg' /> </a>
					$attr_block
					<div class='atitle'>$atitle</div>
					$acapt
				</div>
EOT;
				break;
			case 'gallery':
				$block = <<<EOT
				<div class='asset'>
					<a href='/asset_viewer.php?$aid' target='viewer'>
					<img src='/assets/galleries/$aid.jpg' /> </a>
					$attr_block
					<div class='atitle'>$atitle </div>
					$acapt
				</div>
EOT;
				break;
			case 'toon' :
				$block = <<<EOT
				<div class='asset'>
					<a href='/asset_viewer.php?$aid' target='viewer'>
					<img src='/assets/toons/$aid.jpg' /> </a>
					$attr_block
					<div class='atitle'>$atitle</div>
					$acapt
				</div>
EOT;
				break;
			
			default: 
				$block = 'Unknown asset display style';
		}
					
		return $block;
		
	
	
	}
	
	
	public function getAssetLinked($id,$nocache=false) {
	/* returns the asset thumbnail, linked to the asset source */
		$adata = $this->assets->getAssetSummaryById($id);
		return $this->returnAssetLinked($adata,$nocache);
	}
	
	public function returnAssetLinked ($adata,$nocache=false) {
		$status = $adata['astatus'];
		$id = $adata['id'];
		if ($adata['astatus'] == 'X') {return "Asset Deleted";}
		elseif ($adata['astatus'] == 'T') {return "Asset Temporary";}
		
		$link = $adata['asset_url'];
		if (empty($link)){return 'No asset url';}
		if (! u\url_exists($link)){
			return "Linked asset doesn't exist";
		}
		
		$thumb = "/assets/thumbs/${id}.jpg";
		if (!file_exists(SITE_PATH . $thumb)){
			return "No thumbnail for asset";
		}
		if ($nocache){
			$time = time();
			$thumb .= "?nocache=$time";
		}
		$result = <<<EOF
		<a href='$link' target="_blank">
		<img src='$thumb'>
		</a>
EOF;	
#echo "RESULT <br>$result"; exit;

		return $result;
	
	}
	

	private function relocateUpload ($id,$type){
		/**
			Moves file described in _FILES array into appropriate
			place and rturns the url.  Used for uploads and also
			for other places where asset exists in one place and needs
			to bre moved.
			
			@utype is uasset,uthumb,uftp, or uupload
			type refers to an entry in _FILES array which has file location,
			original name.  type also gets location info from
			the Files object.
			
			@id is id this asset will have; may be used as file name.
			@returns url to relocated file
		
	 		  Need: $_FILES[type]['error'] UPLOAD_ERR_OK.
		   Need: $_FILES[type]['tmp_name'] location of file
		     Need: $_FILES[type]['name'] orig file name
		     
		     
	**/
		if (! is_integer($id) && ! $id > 0) {
			throw new Exception ("Calling relocate Upload without an id");
		}
	
		if (! $this->checkUploads($type)) {exit;}
		$orig_path = $_FILES[$type]['tmp_name'];
		$orig_mime = $this->mimeinfo->file($orig_path) ;
		$orig_name = $_FILES[$type]['name'];
		$orig_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
		echo "Moving uploaded $type file $orig_name ... " ;
		$new_loc = ($type == 'uthumb')? '/assets/thumb_sources/' : '/assets/files/' ;
		$new_url = $new_loc . $id . '.' . $orig_ext;
		$new_path = SITE_PATH . $new_url;
		
		#echo "Now moving $orig_path to $new_path" . BRNL;
		rename ($orig_path,$new_path);
		chmod ($new_path,0644);
		if (! file_exists($new_path)){ die ("file did not move to $new_url");}
		#echo "New url: $new_url" . BRNL;

		return $new_url;
	}

	
	private function checkUploads ($utype){
		// checks for upload errors, file exits,
		// returns the original name of the file.
	
		 // Need: $_FILES[$utype]['error'] UPLOAD_ERR_OK.
		  // Need: $_FILES[$utype]['tmp_name'] location of file
		   // Need: $_FILES[$utype]['name'] orig file name
		   
		if (! in_array($utype,self::$upload_types)){
			throw new \RuntimeException ('invalid upload type');
		}
		   
		 if (empty($_FILES[$utype] )){
		 	throw new \RuntimeException('No file of type $utype found in _FILES.');
		 }
		 if ($_FILES[$utype]['error'] != UPLOAD_ERR_OK ){
		 	u\echor ($_FILES[$utype], "Error: Files array for $utype");
			exit;
		 	}
		 switch ($_FILES[$utype]['error']) {
			  case UPLOAD_ERR_OK:
				  break;
			  case UPLOAD_ERR_NO_FILE:
					throw new \RuntimeException('No file uploaded.');
			  case UPLOAD_ERR_INI_SIZE:
			  case UPLOAD_ERR_FORM_SIZE:
			  	$size = $_FILES[$utype]['size'] / 1000000;
					throw new \RuntimeException('Exceeded filesize limit.');
			  default:
					throw new \RuntimeException('Unknown errors.');
		 }
		 if (!file_exists($_FILES[$utype]['tmp_name'] )){
			throw new \RuntimeException ("uploaded file does not exist.");
		 }
		 $fmime = $_FILES[$utype]['type'];
		 $original = $_FILES[$utype]['name'];
		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

		// check if mim on accepted list
		$ok_mimes = Defs::getAcceptedMime() ;
		 if (! in_array ($fmime, $ok_mimes)) {
			  throw new \RuntimeException ("uploaded file $original type $fmime is not an accepted type.");
		 }
		 if (empty($tmime = Defs::getMimeFromExt($ext) ) ){
		 	echo "Warning: file extension $ext not in accepted mime extensions" . BRNL;
		 } elseif ($fmime != $tmime){
		 	echo "Warning: file $original extension does not match mime type $fmime" . BRNL;
		 }
		 
		 return true;
	}


	// private function getYoutubeThumb($url) {
// 			// returns url to thumbnail for a youtube video.
// 			// returns false if not a youtube video
// 			echo "looking for yt match to $url" . BRNL;
// 					 $pattern = 
// 					'%#match any youtube url
// 						 (?:https?://)?  # Optional scheme. Either http or https
// 						 (?:www\.)?      # Optional www subdomain
// 						 (?:             # Group host alternatives
// 							youtu\.be/    # Either youtu.be,
// 						 | youtube\.com/
// 						 )				# or youtube.com
// 						 (?:          # Group path alternatives
// 							  embed/     # Either /embed/
// 							| v/         # or /v/
// 							| watch\?v=  # or /watch\?v=			
// 						 ) ?            # or nothing# End path alternatives.
// 											 # End host alternatives.
// 						 ([\w-]+)  # Allow 10-12 for 11 char youtube id.
// 						 %x'
// 						 ;	          
// 					$result = preg_match($pattern, $url, $matches);
// 					if (array_key_exists(1,$matches)){
// 						$vid = $matches[1] ;
// 						echo "Matched youtube $matches[0] to video id $vid " . BRNL;
// 						if ($yturl = "http://img.youtube.com/vi/$vid/mqdefault.jpg" ){
// 							return $yturl;
// 						} else {
// 							throw new Exception ("Cannot retrieve thumbnail for you tube video.");
// 						}
// 					}
// 					else { // not a youtube video
// 						return false;
// 					}
// 	 }


}
