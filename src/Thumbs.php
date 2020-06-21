<?php
namespace DigitalMx\Flames;

use DigitalMx as u;
use DigitalMx\Flames\Definitions as Defs;
use DigitalMx\Flames\FileDefs;


/* class Thumbs
Needs to take an id number, a source url for the thumb, a mime type for the
asset itself (may be different from thumb url), and a type of thumb (thumb,gallery,toon)

From this it will generate the apprpirate thumb image and put it in the
/assets/type/id.jpg

Don't run this if thumbs already exist.  Just to create new or recreate.

CreateThumb($type) returns a possibly new thumb_url for the asset.

*/


class Thumbs
{
	public function __construct($id,$asset_url,$thumb_url='' ){
		/*
		mime is the mime type of the asset itself, not necessarily
		the asset source, which is in $source.  mime type determines
		what kind of a default thumb might be used.
		Source is the asset thumb_url || asset_url
		*/
		$this->id = $id;
		$this->aurl = $asset_url;
		$this->turl = $thumb_url;
		$this->tsource = $thumb_url ?: $asset_url;

		$this->amime = u\get_mime_from_url($asset_url);

		$this->thumbfile = "${id}.jpg"; // always
		$this->asset_dir = FileDefs::asset_dir;


	}

	public function createThumb ($ttype) {


		$new_thumb_url = '';

		if (! $max_dim = Defs::$thumb_width[$ttype]){
			throw new Exception ("Unknown thumb type requested for thumbnail: $ttype");
		}

		// set source for thumb.  Maybe same; maybe changed.
		$new_thumb_url = $this->setNewThumbUrl () ;

			$thumb_url = $this->makeThumbFromSource($new_thumb_url,$ttype);
			echo "$thumb_url created" . BRNL;
	}


	private function makeThumbFromSource($tsource ,$ttype) {
	echo "Make thumb $ttype from $tsource (mime $this->amime) ." . BRNL;
			$id = $this->id;
			$mime = $this->amime; // of asset, not thumb
			$tsource = $this->tsource;
			$thumbpath = $this->asset_dir . "/${ttype}/${id}.jpg";

			// first see if source is the icon file.  If so,
			// just copy it over
			if (strpos($tsource,'/assets/icons') !== false) {
				copy (SITE_PATH . $tsource,$thumbpath);
				return;
			}

			$tmime = u\get_mime_from_url($tsource);
			$tgroup = Defs::$mime_groups[$tmime];

			if (u\is_local($tsource) ) {
				$tspath = SITE_PATH . $tsource;
			} else {
				$tspath = $tsource;
			}

			// if source is an image, then try to create using gd
			if ( $tgroup == 'Image' ) {
					// try creating thumb from remote or local useing gd
					echo "Creating thumb using gd for id $id from image at $tsource";
					$simage = null;
					if (! u\is_local($tsource) ){

						$sizem = u\get_info_from_curl($tsource)['size'] / 1000000; #MB
						if ($sizem > 32) { //MB
							throw new Exception ("Remote File too large for GD: " . (int) $sizem . 'MB');
						}
					}

				switch ($tmime) {
					case 'image/jpeg':
						$simage = imagecreatefromjpeg($tspath);
						break;
					case 'image/gif':
						$simage = imagecreatefromgif($tspath);
						break;
					case 'image/png':
						$simage = imagecreatefrompng($tspath);
						break;
					default:
						$simage = null;
				}


				if ($timage = imagescale($simage,Defs::$thumb_width[$ttype]) ) {
					imagejpeg($timage, $thumbpath, 90);
					imagedestroy($simage);
					imagedestroy($timage);


				} else {
					throw new Exception ("Failed to make thumb $ttype on id $id");
				}
			} elseif (strpos($mime,'pdf') !== false) {
				$thumb = $this->buildImThumbnail($id,$tspath,$ttype);

			}

}




	private function setNewThumbUrl () {
		/* set thumburl based on source.
			If local, that's the source
			if youtube, get the thumb from youtube, download it, and set thumburl
			If web, use an icon based on asset mime type
		*/
		$new_thumb_url = '';
		$id = $this->id;
		$amime = $this->amime;
		$source = $this->tsource;


		if (u\is_local($source) ){ // local file exists
			$new_thumb_url = $source;

		} elseif ($ytid = u\youtube_id_from_url($source) ) {
			// get url to youtube's thumb file for this video
			$yturl = "http://img.youtube.com/vi/$ytid/mqdefault.jpg";

			$local_source = "/assets/thumb_sources/${id}.jpg";
			//copy from the youtube site to local thumb source dir
			copy ($yturl , SITE_PATH . $local_source);

			$new_thumb_url = $local_source;



		} elseif (0 && $tmime = is_http($source)) {
			// do not allow remote sources for thumbs
			// But if you did, you'd set new thumb and go on.
			$new_thumb_url = $source;

		} elseif ($icon = $this->get_generic_thumb ($id,$amime) ) {
			// set thumb source to generic icon
				$thumburl = "/assets/icons/$icon"; // new thumb source
				if (! file_exists(SITE_PATH . $thumburl)) {
					throw new Exception ("No generic icon for $amime");
				}
				$new_thumb_url = $thumburl;

		} else {
			throw new Exception ("Could not determine thumb source for asset $id");

		}
		return $new_thumb_url;
	}


private function yturl ($tyid) {
	//use this with curl to retrieve a lot of info abut a youtube video
				return "https://www.googleapis.com/youtube/v3/videos?id="
					. $ytid
					. "&part=status&key="
					. Defs::$ytapikey;
			}



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
		  	 $im->setResolution(300,300);
			$im->readImage($path);
			$im->setImage($im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN));
		 $im->setImageFormat('jpeg');

		 $im->thumbnailImage($max_dim, $max_dim,true); #best fit
		 $im->writeImage(SITE_PATH . "/assets/$ttype/$thumb");

		 return $thumb;
	}

private function get_generic_thumb($aid,$amime) {
	// if url is useable to geneatethumb

	$use_mime = array(
		'application/msword' 	=>	'doc.jpg',
		'application/pdf' 	=>	'pdf.jpg',
		'image/gif'	=>	'image.jpg',
		'image/jpeg'	=>	'image.jpg',
		'image/png'	=>	'image.jpg',
		'image/tiff'	=>	'image.jpg',
		'text/html'	=>	'web.jpg',
		'video/mp4'	=>	'mp4.jpg',
		'audio/mp3'	=>	'mp3.jpg',
		'audio/m4a'	=>	'm4a.jpg',
		'video/quicktime'	=>	'mov.jpg',
	);
	$icon = $use_mime[$amime] ?? 'default.jpg';
	return $icon;
}



	private function checkThumbsExist() {
			// now check existance of thumbs
				// not doing for galleries .. too complicated.
				$tpjpg = SITE_PATH . '/assets/thumbs/' . $id . '.jpg';
				$tppng = SITE_PATH . '/assets/thumbs/' . $id . '.png';

				if (file_exists($tpjpg)){
					#ok
				} elseif (file_exists($tppng) ) { // have a png, change to jpg
					$imaget = imagecreatefrompng($tppng);
					imagejpeg($imaget, $tpjpg, 90);
					imagedestroy($imaget);
					logrec ($id,'',"Created a jpeg from existing png");
				} else { // create a new thumb

					#echo "create_thumb($id,$tsrc,'thumbs')" . BRNL;
					if ($assets->saveThumb('thumbs',$id,$tsrc,$amime) ){
						#ok
						logrec($id,' ',"New Thumb from source" ,$tsrc);
 					} else {
 						$estatus = 'E';
						$b['errors'] .=  logrec($id, $estatus,"Cannot create thumb jpg",$tsrc);

					}
				}
				// one last check
				if ($estatus != 'E') {
					if (!file_exists($tpjpg)){
						$estatus = 'E';
						$b['errors'] .= logrec($id,$estatus,"No Thumb File exists");

					}
				}
			}


}
########################
// /*
// public function saveThumb ($ttype,$id,$turl,$amime){
//
// 		/*
// 			creates thubm types in list $needs for asset id $id.
// 			returns true
//
// 			requires the thumb source
// 			Gets mime type from get_mime_from_url (using finfo or curl).
//
// 		turl is url to source document for the thumbnail
// 			(image, video, youtube, whatever).
// 		amime is the mime type of the asset the thumb is for.
//
// 		ttype is thumb type
// 		 If thumbs, creates a 200w thumb in the thumbs directory.
// 		 If galleries, it creates a 330w image in galleries directory
// 		 If toons, it creates an 800w image in the toons directory
// 		 (see thumb_width array in Defs)
//
// 	returns true if everything works.
// 	 */
//
// 	 if (! $max_dim = Defs::$thumb_width[$ttype]){
// 		throw new Exception ("Invalid thumb type requested for thumbnail: $ttype");
// 	 }
//
// 	$tmime = u\get_mime_from_url($turl);
//
// 	 echo "Starting thumb $ttype on $id, asset mime $amime, from $turl. " .BRNL;
// 	 $thumb = "${id}.jpg";
//
// 	if (u\is_local($turl) ){
// 		$tpath = SITE_PATH . $turl;
//
//
// 		switch ($amime) {
// 			case 'application/msword' :
// 				$use_icon="doc.jpg";
//
// 				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
// 				break;
// 			case 'application/pdf' :
// 			case 'image/gif':
// 			case 'image/jpeg':
// 			case 'image/png':
// 			case 'image/tiff':
// 			case 'video/x-youtube':
// 				$thumb = $this->buildImThumbnail($id, $tpath,$ttype);
// 				break;
//
// 			case 'text/html':
// 				$use_icon="web.jpg";
// 				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
// 				break;
//
// 			case 'video/mp4':
// 				$use_icon = 'mp4.jpg';
// 				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
// 				break;
//
// 			case 'audio/mp3':
// 			case 'audio/m4a':
// 				$ext = substr($amime,-3,3);
// 				$use_icon = "${ext}.jpg";
// 				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
// 				break;
// 			case 'video/quicktime':
// 				$use_icon = 'mov.jpg';
// 				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
// 				break;
//
// 			default:
// 				$use_icon = 'default.jpg';
// 				copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb");
// 				break;
// 		}
// 		echo " /$ttype/$thumb created." . BRNL;
// 		return true;
// 	}
// }
