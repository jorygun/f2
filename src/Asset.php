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
	
**/

class Asset {
    
    private static $pdo;
    
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
	
    public function getAssetsByName($name) {
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

	 
	 
	
	private function get_url_mime_type($url)
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

    

