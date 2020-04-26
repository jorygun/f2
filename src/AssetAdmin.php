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
	use Asset;


class AssetAdmin 
{


	private $pdo;
	private $archive_tag_list_sql;
	private $asset;
	private $member;
	private $mimeinfo;
	
	
	
	
	private static $upload_types = array(
		'uasset','uthumb','uuploads','uftp');
		
	public function __construct($asset) {
		$this->pdo =  MyPDO::instance();
		$this->archive_tag_list_sql =  Defs::getArchivalTagList();
		$this->asset = $asset;
		$this->member = new Member();
		$this->mimeinfo = new \finfo(FILEINFO_MIME_TYPE);
		
		

	}
	// takes asset data array, prepares thumbs needed,
	// and sends to Assets to store (and add computed fields).
	// returns id of asset.
	
	public function postAssetFromForm($post) {
	// prepare data and then send to asset to post
	u\echor($post,'post data in');
	
	
		if (! isset ($post['id'])){
				throw new Exception ("attempt to post asset with no id set");
		}
		if (
			empty($post['title'])
			|| (empty($post['asset_url']) && empty($_FILES['uasset']['tmp_name']) )
			) {
			die ("Asset contains no definition.");
		}
		// must have id before all the data is saved to place files.
		// this creates a skeleton asset record and gets the id.
		if (empty ($id = $post['id'])) {
				$id = $this->asset->getNewID();
				echo "New id $id obtained." . BRNL;
				$adata ['status'] = 'N';
		}
		// for existing items, status is not updated when item is saved
		
		// move the post data needed from thep ost to adata.
		foreach ($this->asset::$editable_fields as $f) {
			$adata[$f] = $post[$f]??'';
		}
		$adata['id'] = $id;
		
		if (!empty($adata['contributor_id']) ){
			$adata['contributor_id'] = (int)$adata['contributor_id'];
		} elseif (!empty ($adata['contributor'] )) {
			if (! list ($adata['contributor'], $adata['contributor_id'] ) 
				= $this->member->getMemberId($adata['contributor']) ){	
				throw new Exception ("Contributor ${adata['contributor_id']} not found");
		} else {
			die ("No contributor info supplied");
		}
		
		$adata['vintage'] = trim((int)$adata['vintage']);
		if (empty($adata['vintage'])){
			$adata['vintage'] = date('Y');}
		}
	
		/* new thumbs is list of thumb types needed - from
			checkboxes on the asset form or from replacing
			existing thumbs because sources have changed.
		*/
		$new_thumbs = []; 
		foreach (Defs::getThumbTypes()  as $ttype) {
	
			if (!empty($post[$ttype])){
				$new_thumbs[] = $ttype;
				echo "New thumb requested: $ttype. ";
			}
		}
		
	// first look for any relocates from file uploads
	// move file into assets files or thubm-sources and
	// change source def to match.
	#u\echor($_FILES,'FILES');
	
		foreach (self::$upload_types as $type){
			if (isset($_FILES[$type]) && !empty ($_FILES[$type]['name'] )){
				echo "starting reload $type" . BRNL;
				$url = $this->relocateUpload($id,$type);
				echo "new url: $url" . BRNL;
				
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
		
		
	
		if (!empty($post['tags']) && is_array ($post['tags'])){
			// convert to string
			$adata['tags'] =  charListToString($post['tags']) ;
		}
		
		 $adata['needs'] = $this->checkThumbNeeds($adata,$new_thumbs);
	u\echor($adata,'Into Checking asset data:');
	#exit;
	
		$this->asset->saveAsset($adata);
		
		echo "<a href='asset_editor.php?id=$id'>View in Editor</a>" . BRNL;
		return $id;

	}
	
	private function checkThumbNeeds($adata,$new_thumbs) {
		// set which thumbs are needed, by checkbox or by changed url
		$id = $adata['id'];
		$needs = array ();
		$result = $this->asset->getAssetDataById($id);
		
		$thumbs = $result['existing_thumbs']; #keys where value is ''
		if (empty($thumbs)){$needs[] = 'thumbs';} #always need this
		// if either url has been changed, all the thumbs need to be regneratied.
		if ($result['asset_url'] != $adata['asset_url']
			|| $result['thumb_url'] != $adata['thumb_url'] 
			|| in_array('all',$new_thumbs) ){
			$needs = $thumbs; #all existing thumbs
			$new_thumbs = array_diff ($new_thumbs,['all']) ;
		}
		// add in any thumbs were checked on the form
		$needs = array_unique(array_merge($needs,$new_thumbs));
		#u\echor($needs,'needs after check needs'); exit;
		
		
		return $needs;
	}
	
	public_function searchAssets() {
	
	
	}
	
	public function getAssetLinked($id) {
	/* returns the asset thumbnail, linked to the asset source */
		$adata = $this->asset->getAssetDataById($id);
		$status = $adata['status'];
		if ($status == 'X') {return "Asset Deleted";}
		elseif ($status == 'T') {return "Asset Temporary";}
		
		$link = $adata['asset_url'];
		if (empty($link)){return '';}
		$thumb = "/assets/thumbs/${id}.jpg";
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
		echo "New url: $new_url" . BRNL;

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
		 
		 switch ($_FILES[$utype]['error']) {
			  case UPLOAD_ERR_OK:
				  break;
			  case UPLOAD_ERR_NO_FILE:
					throw new \RuntimeException('No file uploaded.');
			  case UPLOAD_ERR_INI_SIZE:
			  case UPLOAD_ERR_FORM_SIZE:
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


	private function getYoutubeThumb($url) {
			// returns url to thumbnail for a youtube video.
			// returns false if not a youtube video
			echo "looking for yt match to $url" . BRNL;
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
						if ($yturl = "http://img.youtube.com/vi/$vid/mqdefault.jpg" ){
							return $yturl;
						} else {
							throw new Exception ("Cannot retrieve thumbnail for you tube video.");
						}
					}
					else { // not a youtube video
						return false;
					}
	 }
}
/***********************************************************
	
	private function get_asset_by_id($id,$style='thumb'){
		 if (empty($id)){return array ();}

		 $sql = "SELECT * from `assets` WHERE id = $id";
		 $row = $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);


		 $id = $row['id'];
		 $type = $row['type'];
		 $url = $row['url'];
		 $status = $row['status'];
		 $link = $row['link'];
		 $target = (empty($row['link'])) ? $row['url'] : $row['link'];
		 $caption =  make_links(nl2br($row['caption']));
		 if (empty($caption)){$caption = $row['title'];}

     
			$source_line = $this->buildSourceLine($row);
	
	function buildSourceLine($row){
		 $source_line = $row['source']  ?? '';
		// if ($c = $row['contributor'] ?? '') {
// 			if (substr($c,0,6) !== 'Flames'
// 			&& 
// 				
// 		if
		 if ($row['source'] != $row['contributor']
			  && strncasecmp($row['contributor'], 'Flames',6) != 0
			  ){
			  $source_line .= ' via ';
			  $source_line .= " ${row['contributor']} ";
		 }

		 if (empty($source_line)){ $source_line = "unattributed ";}
		 if (! empty ($row['vintage'] )) {
			 $source_line .=  " (${row['vintage']})";
		 }
		return $source_line;
	}


		 $title_line = spchar($row['title']);
		$caption_line = spchar($row['caption']);
		 $click_line = (!empty($target))? "<p class='small centered'> (Click image for link.)</p>":'';

		  $thumb_url = "/assets/thumbs/${row['thumb_file']}";
		if ( empty($row['thumb_file']) or !file_exists(SITE_PATH . "/$thumb_url") ){ 
			#try to make thumb from source
			
				return "Attempt to link id $id to asset with no thumb: $thumb_url"; 
			
			}
		

		 switch ($style){

		 case '':
		 case 'thumb':
			  $out = "<div class='thumb'>";
			 
			  elseif (substr($target,0,1) == '/' ) {#on site
					if (strpos($target,'/galleries') !== false){
						 $href = $target;
					}
					else { $href=  "/asset_display.php?$id' target='asset' decoration='none'";
					}
			  }
			  else {$href = "$target";}

			  $out .= "
					<a href='$href' target='asset' decoration='none'>
					 <p class='caption'>$title_line</p>
					<img src='$thumb_url'></a>
					<p class='caption'>$caption_line</p>
			  
					$click_line
			  ";

			  $out .= "</div>";
			  break;
		case 'photo':
			$out = "<div>";
			$out .= "<img src='$thumb_url>
				<p class='caption'>$caption_line</p>
				";
		 case 'link':
			  $out = "<a href='$target' target='_blank'>$title_line</a>";
			  break;

		case 'album':
			  $out =  "<div class='album'>";

			  $gfile = choose_graphic_url('/assets/galleries',$id);

			  if (empty($gfile) && file_exists(SITE_PATH . "/$thumb_url")  ) {
					$gfile = $thumb_url;
			  }

			  if (! empty ($gfile)){
					$out .= "
					<a href='/asset_display.php?$id' target='asset' decoration='none'>
					<img src='$gfile' ></a>
					<p class='caption'>$caption</p>
				  <p class='source'>$source_line</p>
					<p class='clear'>[$id]</p>
			  ";
			  }
			  else  {$out .= "(No gallery image for id $id)";}
			  $out .= "</div>";
			  break;

		 case 'toon':
			  $gfile='';
				$gfile = choose_graphic_url('/assets/toons',$id);
			 if (empty ($gfile) ){
					$gfile = $row['url'];
			  }


			 if ( ! empty($gfile)) {$out = "
					<img src='$gfile' width='800'>
					";

			  }
			  else {$out = "(No toon image for id $id)";}
			  $out .= "<p class='center'><b>"
			  .$row['title'] . "</b>";
			  if ($row['title'] != $row['caption']){
					$out .= "<br>" . $row['caption'];
			  }
			  $out .= "</p>\n";
			  $out .= "<p style='text-align:right;font-size:small'>
			  $source_line •
			  <a href='$target' target='_blank'>View source file</a></p>";
			  break;

		 default:
			  $out = "(prepare image failed;  style  $style not understood)";
		 }

		 #update the first used if it's blank and not an admin access
		 $first_date = $row['first_use_date'];
		 if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){

			  $out  .= set_first_use($id);
		 }

		 return $out;
	}

	private function set_asset_skip_time ($id){
		
		 $sql = "Update `assets` set skip_ts = NOW() where id=$id;";
		 $this->pdo->query($sql);
	}




	private function next_asset_id( $id,$id_list = [] ){
		 #get next valid id in sequence from database or next from id_list
		 if (!empty($id_list)){
			$akey = array_search($id,$id_list);
			$nkey = $akey+1;
			#echo "getting next list item $nkey" . BRNL;
			if (($next_id = $id_list[$nkey]) === false){
				echo "No Additional Ids in List";
				return false;
			}
		
		 }
	 
		 else {
		
			$sql = "SELECT id FROM `assets` WHERE id > $id  AND status != 'D' ORDER by id LIMIT 1;";
			if (! $next_id = $this->pdo->query($sql)->fetchColumn()){
				echo "No ids above $id in database";
				return false;
			}
		}
		 return $next_id;
	}



	


	

	private function delete_asset($id){
		 #mark an item as deleted.
		 # if already marked as deleted, then delete assetse,
		

		$sql = "select * from assets where id = '$id';";
		 if (! $row = $this->pdo->query($sql)->fetch()){
			  echo "No asset found at id $id";
			  return;
		 }
		 if ($row['status'] != 'D'){ #fresh delete
			  $sql = "UPDATE `assets` set status = 'D' where id = $id";
			  $this->pdo->query($sql);
			  echo "Asset Marked Deleted (D)" . BRNL;
		 }
	}
	private function delete_files($id){
		
		echo "Deleting files associated with id $id". BRNL;
		$sql = "select * from assets where id = '$id';";
		 if (! $row = $this->pdo->query($sql)->fetch()){
			  echo "No asset found at id $id";
			  exit;
		 }

	
		 $unlink_list = []; #build list of affected files
		 if ($row['type'] == 'Album'){echo "Cannot use this on Albums"; exit;}
		 if (!empty($row['first_use_in'])){
			  echo "Cannot delete asset that has been used.  In {$row['first_use_in']} on {$row['first_use_date ']}";
			  exit;
		 }

		$thumb = $row['thumb_file'] ;

		 if (!empty($thumb)){
			if (! preg_match('/$id\.[jpg|png]/',$thumb))
				 {echo "Cannot delete thumb $thumb" . BRNL;}
			else {
				$file = SITE_PATH . "/assets/thumbs/$thumb";
				if (file_exists($file)){
					$unlink_list['thumb'] = $file;
				}
			}
		 }

		 if (!empty($file = get_gfile("/assets/toons/$id.png"))){
					$unlink_list['toon'] = $file;
		 }

		 if (!empty($file = get_gfile("/assets/galleries/$id.png"))){
					$unlink_list['galleries'] = $file;
		 }

		 $url = $row['url'];
		 if (substr($url,0,1) == '/'){
			  $file = SITE_PATH . "$url";
			  if (file_exists($file)){
					$unlink_list['source'] = $file;
			  }
		 }
		 $link = $row['link'];
		 if (substr($link,0,1) == '/'){
			  $file = SITE_PATH . "$link";
			  if (file_exists($file)){
					$unlink_list['link'] = $file;
			  }
		 }

		 #show results and ask for confirmation
		 echo "The following files will be deleted from the server:" . BRNL;
		 foreach ($unlink_list as $t => $f){
			  echo "$t: $f" . BRNL;
		 }
		 $unlinkjson = json_encode ($unlink_list);
		 echo <<<EOT

		 <form method='post'>
		 To confirm, press Confirm:
		 <input type='hidden' name='unlinkjson' value='$unlinkjson'>
		 <input type='hidden' name='id' value='$id'>
		 <input type='submit' name='delete' value='Confirm Delete'>
		 </form>
EOT;
		 exit;
	}
	
	
	private function delete_confirmed($id,$unlink_list,$doit='true') {
		$doitmsg =  ($doit)?'':'NOT';
	
		 echo "Deleting files for id $id" .BRNL;

		 foreach($unlink_list as $t=>$f){
			  echo "Deleting $t file $f $doitmsg" . BRNL;
			 if ($doit){ unlink ($f);}
		 }

		 echo "Updating asset record to status 'x' $doitmsg".BRNL;
		 $sql = "Update `assets` set status = 'X' WHERE id = $id;";
		 if ($doit){$this->pdo->query($sql);}
		 exit;
	}


	

	



	
	

*/

