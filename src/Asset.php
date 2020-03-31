<?php
namespace digitalmx\flames;

use digitalmx\MyPDO;
use digitalmx\flames as f;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;

/** 
	Main interface to assets
	Create Asset (including thumbnail)
	Edit Asset
	Bulk Create Assets
	Get asset id list for some search term
	Get asset data by id
	
	
	

	
**/

class Asset {
    
    private static $pdo;
    
    /* database fields entereed by form */
    	private static $editable_fields = array(

		 'title',
		 'source',
		 'url',
		 'thumb_file',
		  'link',
		 'contributor',
		 'contributor_id',
		 'caption',
		 'notes',
		 
		 'vintage',
		 'status',
		
		 'tags'

		);

	private static $auto_fields = array(
		 'date_entered',
		 'height',
		 'width',
		 'sizekb',
		 'first_use_date',
		 'first_use_in',

		 'mime',
		 'type'

	);
    
    private static $thumb_width = array(
						 'thumbs' => 200,
						 'galleries' => 330,
						 'toons' => 800
						 );
						 
	private static $accepted_mime = array(
					'jpg' => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'png' => 'image/png',
					'gif' => 'image/gif',
					'pdf' => 'application/pdf',
					'mp4' => 'video/mp4',
					'mov' => 'video/quicktime',
					'mp3' => 'audio/mpeg',
					'm4a' => 'audio/mp4',
					'tif' => 'image/tiff',
					'doc' => 'application/msword',
					'docx' => 'application/msword',
					'html' => 'text/html'

			  );


	private static $asset_status = array(
		 'R' => 'Reviewed-R',
		 'S' => 'S',
		 'U' => 'Updated: re-review',
		 'T' => 'temp holding',
		 'E' => 'Has Error',
		 'D' => 'Deleted',
		 'X' => 'Deleted and Unlinked',
		 'N' => 'New',
		 'O' => 'OK'

	);
	private $assetfields;
	private $archive_tag_set;
	private static $image_extensions = array('jpg','gif','png','jpeg');
	private static $document_extensions = array('doc','docx','pdf','html');
	private static $mmm_extensions = array('mov','mp4','mp3','m4a');

    
    public function __construct(){
        self::$pdo = MyPDO::instance();
        $this->archive_tag_set =  $this->get_archival_tag_list();
		$this->assetfields  = array_merge(self::$editable_fields,self::$auto_fields);
	
    }
    
    private function getMimeGroup ($mime) {
		if (strncmp($mime,'image/',6) == 0 ){return 'image';}
		elseif (strncmp($mime,'video/',6) == 0) {return 'av';}
		elseif (strncmp($mime,'audio/',6) == 0) {return 'av';}
		elseif (strncmp($mime,'application/',12) == 0) {return 'doc';}
		else {return '';}
	}
	
		private function get_archival_tag_list ()  {
		
		$archival_tags = [];
		foreach ($this->asset_tags as $tag=>$label){
			if (strpos($label,'*') !== false){
				$archive_tags[] = "'$tag'";
			}
		}
		return join(',',$archive_tags);
	}
	
    public function getAssetIDsByName($name) {
        $sql = "SELECT id from `assets` where 
        concat('', caption,title) like '%$name%' ";
        $alist = self::$pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
        return $alist;
      }
   
   public function getAssetDataByID($id){
   	$sql = "SELECT * from `assets` where id = $id";
   	$adata = self::$pdo->query($sql)->fetch(\PDO::FETCH_ASSOC); #array
   	return $adata;
   }
   
   private function set_first_use($id){
		 #sets first use date on an asset
					
					$ref = $_SERVER['REQUEST_URI'];
					// dont count if it's coming from the asset search script
					if (strpos ($ref, '/scripts/assets.php' ) === false){return null;}
					if ($_SESSION['level'] > 5){return null;} #anythning over member

					
					$sqld = "UPDATE `assets` set first_use_date = NOW(), first_use_in = '$ref' where id = '$id';";
					if ($this->pdo->query($sqld)){return true;}
					return false;
	}



   public function getGalleryAsset($id) {
      
		if (empty($id)){return '';}
		 
		 $sql = "SELECT * from `assets` WHERE id = $id";
		 $row = self::$pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
		 if (empty($row)){return '';}
		 
		 $url = $row['url'];
		 $link = $row['link'];
		 $target = (empty($row['link'])) ? $row['url'] : $row['link'];
		 $caption =  make_links(nl2br($row['caption']));
		 if (empty($caption)){$caption = $row['title'];}

		 $source_line = "<p class='source'>";
		 $source_line .=  (! empty($row['source']))? "${row['source']}" : 'Unattributed';
		 if (! empty ($row['vintage'] )) {
			 $source_line .=  " (${row['vintage']})";
		 }
		 $source_line .= "</p>\n";

		 $title_line = u\special($row['title']);

		 $click_line = (!empty($target))? "<p class='small centered'> (Click image for link.)</p>":'';

		  $thumb_url = "/assets/thumbs/${row['thumb_file']}";

		  $editable = (strcasecmp ($_SESSION['login']['username'] ,$row['contributor']) == 0) ? true : false;
			  if ($_SESSION['level'] > 7) {$editable=true;}


		$edit_field = ($editable) ? "<a href='/scripts/asset_edit.php?id=$id&type=specadmin'>Edit</a> " : '';


		if ( empty($row['thumb_file']) or !file_exists(SITE_PATH . "/$thumb_url") ){ return "Attempt to link to asset with no thumb: id $id" . BRNL; }


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
			 $source_line
			  <p class='clear'>$edit_field [$id]</p>
		 ";
		 }
		 else  {$out .= "(No gallery image for id $id)";}
		 $out .= "</div>";



		 #update the first used if it's blank and not an admin access
		 $first_date = $row['first_use_date'];
		 if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){

			  $out  .= set_first_use($id,$status);
		 }

		 return $out;
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
			  $sql = "INSERT into `assets` (status,title,date_entered,type,thumb_file) values ('T','$title',NOW() ,'','');";
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

}

    

