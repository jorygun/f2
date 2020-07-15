<?php
namespace DigitalMx\Flames;

use DigitalMx as u;
use DigitalMx\Flames\Definitions as Defs;
use DigitalMx\Flames\FileDefs;


/* class Thumbs
Builds the source files for a thumbnail image,
Creates thumbs of a given type for an id.

Thumb of type ttype for id id will be in /thumbnails/ttype/id.jpg

Strategy:

Step 1 is insuring that there is a local graphic than can be used
as the thumb source.

The user provides a link to the asset (asset_url) and optionally an alternative local
url (thumb_url) to use as the thumb source.
The thumb_url must be both local and a graphic image.

If the asset_url is not a graphic, then a new graphic will be generated in
/assets/thumb_sources to use as the source graphic , using imagick or using
generic icon files for the asset mime type.


Thumbs will always be built from a local Image source.
Choose from thumb_url OR thumb_sources OR asset_url in that order.
Thumbs are built using the gd library, with size and location determined
by the thumb type.

createThumb($id,[$types]);  // may be multiple

*/


class AssetView
{

private $asset_url='';
private $thumb_url='';
private $id = 0;
private $Assets;



	public function __construct($container){
		/*
		To build a thumbnail, first load the item info: id, asset_url, thumb_url
		either at creation or with reLoad.
		asset_url is needed as possible thumb source, and also because asset mime type
		determines generic thumb for docs, m4a, etc.

		thumb_url is an alternative local grapic source for the thumbnail

		autourl is an automatically created local sources for youtube or default icon
		are stored in /assets/thumb_sources/id.jpg.

		thumbs are created from thumb_url || autourl || asset_url
		and saved in /thumbnails/type/id.jpg

		Once instantiated, thumbnail is created by
			create_thumb (type)
		which creates correct graphic and puts it at assets/thumbs/type/id.jpg

		Sometimes (like first time) the thumb_url needs to be created from the sources,
		so there is a local thumb source graphic.  For example for youtube videos,
		the youtube id is obtained and the youtube thumbnail downloaded into
		/assets/thumbsources/id.jpg and that is returned as the new thumb_url.
		*/

		$this->Assets = $container['assets'];
		$this->Member = $container['member'];

	}



	public function loadId($id) {
	//echo "Loading data: $id, $asset_url, $thumb_url" . BRNL;
	// loads id, return true if ok.
	// if an error, then it returns the error message
	// so test if(! empty ($errors = loadId($id))){ xxx

		if (empty($id) ){
			return "Must have id to load data";

		}

		if (empty($tdata = $this->Assets->getThumbData($id) ) ) {
			return "Asset $id does not exist.";

		}

//u\echor($tdata); exit;

		foreach ($tdata as $var=>$val) {
			$this->$var = $val;
		}


		if (empty($tdata['asset_url'] )) {
			return "Asset $id does not have a source defined.";

		}
		if (empty($tdata['mime'] )) {
			return "Asset $id does not have a defined mime type.";

		}

		if (empty($tdata['local_src']) ){
			return "No local source for id $id.";

		}
		// if ( !file_exists(SITE_PATH . $tdata['local_src']) ){
// 			return "Local thumb source ${tdata['local_src']} for id $id does not exist";
//
// 		}

		$info =  <<<EOT
		LOADED id: $id,
		asset_url: $this->asset_url,
		thumb_url: $this->thumb_url,
		amime: $this->mime,
		local: $this->local_src,

EOT;
	//echo nl2br($info);
		return '';

}


	public function getThumb($id,$ttype) {
		/* returns url or error text
			url is /thumbnails/type/id,jpg if exists
			otherwise creates it using gd for the sources available
			(thumb_url, asset_url, or autourl)
			returns url to icon for mime if those aren't available
			return **ERROR** if there is an error
		*/
		$styletext = (!empty($style)) ? "style='$style'" : '' ;


		$thumb_loc = "/thumbnails/$ttype/${id}.jpg";
 	 //echo "Looking for $thumb_loc" . BRNL ;

		if (file_exists(SITE_PATH . $thumb_loc)) {
			return $thumb_loc;

		}


		// else build one using gd
		//echo ".. building new. " . BRNL;

		if ($id != $this->id) {
			$load_error = $this->loadId($id) ;
			if (!empty($load_error)){ // returned errors
				return "<p class='red'> **ERROR** " . $load_error . '</p>';
			}
		}
		if (! file_exists(SITE_PATH . $this->local_src)){
			return "<p class='red'> **ERROR** local source does not exist for $id</p>";
		}

		$this -> buildGdImage($id,$this->local_src, $ttype);
		return $thumb_loc;

}

public function getUserPhoto($aid,$type){
	// type is view, edit, or new
	//
			$p = [];  // array to build data in
			if (empty($aid)){
				die ("no id for getUserPhoto");
			}

			$pdata = $this->Assets->getThumbData($aid) ;

			if (empty($pdata))  {

			$title = "(missing graphic)";
			$caption = '';
			$image_data = "*** Graphic $aid Not Found ** "; 	// <img ...> or **ERROR**
			} else {

			$th = $this->getThumb($aid,'small');
			if (strpos($th,'**') !== false) {$image_data = $th;}
			else {$image_data = "<img src='$th' />";}

			$title = $pdata['title'];
			$caption = $pdata['caption'];
			//can edit graphic if yours or have admin status
			$credential = $_SESSION['level'] > 6
				|| $pdata['contributor_id'] == $_SESSION['login']['user_id'] ;
			}

			$p['id'] = $aid;
			$p['title'] = $title;
			$p['caption'] = $caption;
			$p['image_data'] = $image_data; 	// <img ...> or **ERROR**


				if ($type == 'view') {

					$p['block'] = <<<EOT
					<div class='asset'>
					<div class='atitle'>$title</div>
					<a href='/asset_viewer.php?$aid' target='assetv'>
					$image_data </a>
					<p><i>$caption'</i></p>
					<p><small>(id: $aid)</small></p>
					</div>
EOT;
					return $p;
			} elseif ($type=='edit') {
					$p['block'] = <<<EOT
					<div class='asset' style='width:300px;'>
					<div class='atitle'>$title</div>
					<a href='/asset_viewer.php?$aid' target='assetv'>
					$image_data </a>
					<p><i>$caption'</i></p>
EOT;
			} else {
				die ("Unknown type for getUserPhoto: '$type'") ;
			}



	if ($credential) {$p['block'] .= <<<EOT
	<button type='button'
				onClick = "window.open('/asset_editor.php?id=$aid','assete')" >
				Edit asset $aid</button>

EOT;
	}
			$p['block'] .= "</div>";


			return $p;


	}

public function getAssetBlock($id,$style,$show_caption=false) {
		/* returns a div with the asset and title in it.
		uses asset small or medium size
		shows thumb linked to asset
		below thumb is title in bold and optional in italic

		styles defined in assets.css
		<div class='asset-row'>
		foreach... echo assetblock
		</div>

		*/

		if ($id != $this->id) {
			if (!empty($load_error = $this->loadId($id) )) { // returned errors
				echo "Asset $id could not be loaded";
				return "<p class='red'>**ERROR** " . $load_error . '</p>';
			}
		}


		$asset_url = $this->asset_url;

		$acapt = ($show_caption)?
			"<div class='acaption'>" . $this->caption . "</div>" : '';


		if ($th = $this->getThumb($id,$style) ) {
			if (strpos($th,'**') !== false) {$image_data = $th;}
			else {$image_data = "<img src='$th' />";}

			$src_data = ($this->source)? "<div class='asource'>--"
				.  $this->source
				. "</div>"
				: '';

			$block = <<<EOT
			<div class='asset'>
				<a href='/asset_viewer.php?$id' target='assetv'>
				$image_data </a>
				$src_data
				<div class='atitle'>$this->title</div>
				$acapt
			</div>
EOT;
			return $block;
		} else {
			return "<div class='asset'>Could not get Thumb for asset $id (local souce missing?)</div>";
		}





	}





	public static function buildGdImage($id,$srcurl,$ttype) {
		// resize local image to thumbnail
		// generall used to build thumb from local image

		$mime = u\is_local($srcurl) ;

		if (empty($mime) || strpos($mime,'image') === false ) {
			throw new Exception ("Must have local image for thumb source '$srcurl'. Mime '$mime' ");
		}


		$srcpath = SITE_PATH . $srcurl;
		$desturl = "/thumbnails" . "/$ttype" . "/${id}.jpg";
		$destpath = SITE_PATH . $desturl;
		echo "Building image from $srcurl type $mime to $desturl ". BRNL;
		$max_dim = Defs::$thumb_width[$ttype];

		$simage = null;

		switch ($mime) {
			case 'image/jpeg':
				$simage = imagecreatefromjpeg($srcpath);
				break;
			case 'image/gif':
				$simage = imagecreatefromgif($srcpath);
				break;
			case 'image/png':
				$simage = imagecreatefrompng($srcpath);
				break;
			default:
				$simage = null;
		}

		$exif = @exif_read_data($srcpath);

		if ($exif && !empty($exif['Orientation']))
		{
			 switch($exif['Orientation']) {
				  case 8:
						$simage = imagerotate($simage, 90, 0);
				  break;
				  case 3:
						$simage = imagerotate($simage, 180, 0);
				  break;
				  case 6:
						$simage = imagerotate($simage, -90, 0);
				  break;
			 }
		}

		$timage = imagescale($simage,$max_dim );
			imagejpeg($timage, $destpath, 90);
			imagedestroy($simage);
			imagedestroy($timage);


	}

}








