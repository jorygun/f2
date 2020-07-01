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

		if (!file_exists(SITE_PATH . $tdata['local_src']) ){
			return "Local thumb source $local_src for id $id does not exist";

		}

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
		/* returns url assets/thumbs/type/id,jpg if exists
			otherwise creates it using gd for the sources available
			(thumb_url, asset_url, or autourl)
			returns url to icon for mime if those aren't available
			return **ERROR** if there is an error
		*/


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
				return '**ERROR** ' . $load_error ;
			}
		}
		if (! file_exists(SITE_PATH . $this->local_src)){
			return '**ERROR** local source does not exist';
		}

		$this -> buildGdImage($this->local_src,$thumb_loc, $ttype);
		return $thumb_loc;

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
				return '**ERROR** ' . $load_error ;
			}
		}


		$asset_url = $this->asset_url;

		$acapt = ($show_caption)?
			"<div class='acaption'>" . $this->caption . "</div>" : '';


		if ($image = $this->getThumb($id,$style) ) {
			if (strpos($image,'**ERROR') !== false){
					$image_data = $image . BR; // is error
			} else {
				$image_data =  "<img src='$image' />";
			}
			$src_data = ($this->source)? "<div class='asource'>--"
				.  $this->source
				. "</div>"
				: '';

			$block = <<<EOT
			<div class='asset'>
				<a href='/asset_viewer.php?$id' target='viewer'>
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





	public static function buildGdImage($srcurl,$desturl, $ttype) {
		// resize local image to thumbnail
		// generall used to build thumb from local image

		$mime = u\is_local($srcurl) ;
		if (empty($mime) || strpos($mime,'image') === false ) {
			throw new Exception ("Must have local image for thumb source");
		}
		echo "Building image from $srcurl type $mime" . BRNL;

		$srcpath = SITE_PATH . $srcurl;
		$destpath = SITE_PATH . $desturl;
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


		if ($timage = imagescale($simage,$max_dim )) {
			imagejpeg($timage, $destpath, 90);
			imagedestroy($simage);
			imagedestroy($timage);
		}

	}

}








