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


CreateThumb($type) returns a possibly new thumb_url for the asset.

*/


class Thumbs
{

private $asset_url='';
private $thumb_url='';
private $id = 0;

private static $mime_icons = array(
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


	public function __construct($id='',$asset_url='',$thumb_url='' ){
		/*
		To build a thumbnail, first load the item info: id, aurl, turl
		either at creation or with reLoad.
		aurl is needed as possible thumb source, and also because asset mime type
		determines generic thumb for docs, m4a, etc.

		turl is an alternative grapic source for the thumbnail

		Thumbnail is created from turl || aurl.

		Once instantiated, thumbnail is created by
			create_thumb (type)
		which creates correct graphic and puts it at assets/thumbs/type/id.jpg

		Sometimes (like first time) the turl needs to be created from the sources,
		so there is a local thumb source graphic.  For example for youtube videos,
		the youtube id is obtained and the youtube thumbnail downloaded into
		/assets/thumbsources/id.jpg and that is returned as the new turl.

		This is done by
			$turl = rebuildSource ();


		*/

		$this->asset_dir = FileDefs::asset_dir;
		$this->thumb_dir = FileDefs::thumb_dir;

		if ($id && $asset_url) {
			$this->reLoad($id,$asset_url,$thumb_url);
		}



	}
	public function reLoad($id,$asset_url,$thumb_url='') {
	//echo "Loading data: $id, $asset_url, $thumb_url" . BRNL;

		if (empty($id) || empty($asset_url) ){
			die ("Must have id and asset_url to load data");
		}
		$this->id = $id;
		$this->aurl = $asset_url;
		$this->turl = $thumb_url;
		$this->tsource = $thumb_url ?: $asset_url;
		$this->thumbfile = "${id}.jpg"; // alway
		$this->amime = u\get_mime_from_url($asset_url); // of asset, not thumb

	}
	function rebuildThumbUrl () {
		// uses sources to rebuild the thumb url
		if (! ($this->id && $this->aurl) ) {
			die ('No data loaded.  Do $thumbs->loadData($id,$asset_url [,$thumb_url]');
		}




	|
	public function createThumb ($ttype) {

		if (! ($this->id && $this->aurl) ) {
			die ('No data loaded.  Do $thumbs->loadData($id,$asset_url [,$thumb_url]');
		}
		$new_thumb_url = '';

		if (! $max_dim = Defs::$thumb_width[$ttype]){
			throw new Exception ("Unknown thumb type requested for thumbnail: $ttype");
		}

		// set source for thumb.  Maybe same; maybe changed.
			$tsrc = $this->getThumbSource () ;
			if ($tsrc != $this->tsource) {
				// put new tsrc into asset db
				echo "New thumb source needs to be saved $tsrc";
			}
			$thumb_url = $this->makeThumb($tsrc,$ttype);
			echo "$thumb_url created" . BRNL;
			return $thumb_url;
	}


	private function makeThumb($tsrc, $ttype) {
		$id = $this->id;
		if (! $tmime = $this->tmime) {
			throw new Exception ("thumb source mime not set");
		}
		$thumbpath = $this->thumb_dir . "/${ttype}/${id}.jpg";

		echo "Making thumb $ttype from $tsrc (mime $tmime) ." . BRNL;


			if (! u\is_local($tsrc) ) {
				$tspath = SITE_PATH . $tsrc;
			} else {
				throw new Exception ("non-local source for thumb on id $id");
			}

			// if source is an image, then try to create using gd
			$tgroup = Defs::$asset_types[$tmime];

			if ( $tgroup == 'Image' ) {
					// try creating thumb from remote or local useing gd
					echo "Creating thumb using gd for id $id from image at $tsource";
					$simage = null;

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
				$thumb = $this->buildImThumbnail($id,$tspath,$ttype,$thumbpath);
				// id, path to source, thumb type, path to destination

			}

}




private function getThumbSource () {
		/* set thumb ssource
			set real thumb source based on tsource (turl || aurl)
			if youtube, get the thumb from youtube, download it, and set thumburl
			If web, use an icon based on asset mime type
			else Exception

			returns _local_ url to thumb source
		*/

		$id = $this->id;

		if (!$tmime = u\url_exists($this->tsource) ) {
				throw new Exception ("Thumb source $this->tsource does not exist.");
			}


		if ( u\is_local($this->tsource) ) {
			$tsrc = $this->tsource;

		} elseif ($ytid = u\youtube_id_from_url($this->turl) ) {
			// get url to youtube's thumb file for this video
				$yturl = "http://img.youtube.com/vi/$ytid/mqdefault.jpg";
				$local_url = "/assets/thumb_sources/${id}.jpg";
				$local_path = SITE_PATH . $local_url;
				//copy from the youtube site to local thumb source dir
				copy ($yturl ,  $local_path);

				$tsrc = $local_url;


		} elseif (0 && $tmime = is_http($this->tsource)) {
				$sizem = u\get_info_from_curl($tsource)['size'] / 1000000; #MB
				if ($sizem > 32) { //MB
					//"Remote File too large for GD: " . (int) $sizem . 'MB');
					$tsrc = getIconThumb($tmime);
				} elseif (
					strpos($tmime('image') !== false)
					|| strpos($tmime('pdf') !== false)
				) {
						$local_url = "/assets/thumb_sources/${id}.jpg";
						$local_path = SITE_PATH . $local_url;
				//copy from the site to local thumb source dir
						copy ($this->tsource ,  $local_path);
						$tsrc = $local_url;
				}



		} elseif ( $tsrc = $this->getIconThumb ($this->amime) ) {
			// set thumb source to generic icon
			$tsrc = getIconThumb($tmime);


		} else {
			throw new Exception ("Could not create thumb for asset $id");
		}
		if (! u\is_local($tsrc)) {throw new Exception (
			"Genereated non-local url for thumb on id $id: $tsrc"
			);
		}
		$this->tmime = $tmime;
		$this->tsrc = $tsrc;
		return $tsrc;
}


private function yturl ($tyid) {
	//use this with curl to retrieve a lot of info abut a youtube video
				return "https://www.googleapis.com/youtube/v3/videos?id="
					. $ytid
					. "&part=status&key="
					. Defs::$ytapikey;
			}



private function buildImThumbnail ($id,$path,$ttype,$dest){

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
	$im->writeImage($dest);

	return true;
}

private function getIconThumb($mime) {
	// returns url for asset mime type
	$icon = self::mime_icons[$mime] ?? 'default.jpg';
	$tsrc = "/assets/icons/$icon"; // new thumb source
	if (! file_exists(SITE_PATH . $tsrc)) {
			throw new Exception ("No generic icon for $this->amime");
	}
	return $tsrc;
}



	private function checkThumbsExist($id,$ttype) {
			// now check existance of thumbs
				// not doing for galleries .. too complicated.
				$tfile = "${id}.jpg";
				$tpjpg = $this->thumb_dir . "/$ttype" . "/$tfile";
				$tppng = $this->thumb_dir . "/$ttype" . "${id}.png";

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
