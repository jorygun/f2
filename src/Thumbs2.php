<?php
namespace DigitalMx\Flames;

use DigitalMx as u;
use DigitalMx\Flames\Definitions as Defs;
use DigitalMx\Flames\FileDefs;


/* class Thumbs
Builds the source files for a thumbnail image,
Creates thumbs of a given type for an id.

Thumb of type ttype for id id will be in /assets/thumbs/ttype/id.jpg

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


class Thumbs2
{

private $asset_url='';
private $thumb_url='';
private $id = 0;
private $Assets;



	public function __construct($container){
		/*
		To build a thumbnail, first load the item info: id, aurl, turl
		either at creation or with reLoad.
		aurl is needed as possible thumb source, and also because asset mime type
		determines generic thumb for docs, m4a, etc.

		turl is an alternative local grapic source for the thumbnail

		autourl is an automatically created local sources for youtube or default icon
		are stored in /assets/thumb_sources/id.jpg.

		thumbs are created from turl || autourl || aurl
		and saved in /assets/thumbs/type/id.jpg

		Once instantiated, thumbnail is created by
			create_thumb (type)
		which creates correct graphic and puts it at assets/thumbs/type/id.jpg

		Sometimes (like first time) the turl needs to be created from the sources,
		so there is a local thumb source graphic.  For example for youtube videos,
		the youtube id is obtained and the youtube thumbnail downloaded into
		/assets/thumbsources/id.jpg and that is returned as the new turl.
		*/

		$this->Assets = $container['assets'];

	}



	public function loadId($id) {
	//echo "Loading data: $id, $asset_url, $thumb_url" . BRNL;

		if (empty($id) ){
			die ("Must have id to load data");
		}

		if (empty($tdata = $this->Assets->getThumbData($id) ) ) {
			die ("Asset $id does not exist.");
		}


		$this->id = $id;
		$this->aurl = $tdata['asset_url'];
		$this->turl = $tdata['thumb_url'];;
		$this->amime = $tdata['mime'];

		if (empty($tdata['asset_url'] )) {
			die ("Asset $id does not have a source defined.");
		}
		if (empty($tdata['mime'] )) {
			die ("Asset $id does not have a defined mime type.");
		}

		if (!u\url_exists($this->aurl) ){
			die ("Asset $this->aurl for asset id $id does not exist");
		}

		$autourl = "/assets/thumb_sources/$id.jpg" ;
			$this->autourl = $autourl;
		$info =  <<<EOT
		LOADED id: $id,
		aurl: $this->aurl,
		turl: $this->turl,
		amime: $this->amime,
		auto: $this->autourl,

EOT;
	echo nl2br($info);

}


	public function getThumb($id,$ttype) {
		/* returns url assets/thumbs/type/id,jpg if exists
			otherwise creates it using gd for the sources available
			(turl, aurl, or autourl)
			returns url to icon for mime if those aren't available
		*/

		$this->loadId($id);

		$thumb_loc = "/assets/thumbs/$ttype/${id}.jpg";
 	 echo "Looking for $thumb_loc" . BRNL ;
		echo "aurl shows: " .  u\is_local($this->aurl) . BRNL;
		if (file_exists(SITE_PATH . $thumb_loc)) {
			echo " Found." . BRNL;
			return $thumb_loc;
		}


		// else build one using gd
		echo ".. building new. " . BRNL;

		$tsrc = '';
		// use thumb url if specified
		if (!empty($this->turl)) {
			$tsrc = $this->turl;
			echo "From turl $tsrc. ";
		}
		// else check thumb_sources
		elseif (file_exists(SITE_PATH . $this->autourl)) {
				$tsrc =  $this->autourl;
			echo "From auto $tsrc";

		// see if asset os local graphic

		} elseif ($amime = u\is_local($this->aurl) ) {
				if (strpos($amime,'image') !== false) {
					$tsrc = $this->aurl;
					echo "From asset graphic" ;
				}
		}

		if (!empty($tsrc)){
			echo " Found" . BRNL;
			$this -> buildGdImage($tsrc,$thumb_loc, $ttype);
			return $thumb_loc;

		} else {
			echo " No source found" . BRNL;
		// return generic icon for amime type
		  $icon = self::$mime_icons[$amime] ?? 'default.jpg';
			return  "/assets/icons/$icon";
		}



}

public function getAssetBlock($id,$style,$show_caption=false) {
		/* returns a div with the asset and title in it.
		uses asset thumb or gallery size
		shows thumb linked to asset
		below thumb is title in bold and optional in italic

		styles defined in assets.css
		<div class='asset-row'>
		foreach... echo assetblock
		</div>

		*/
		if (! $adata = $this->assets->getAssetDataEnhanced($id) ) {
			return "
				<div class='asset'>
					Asset $id does not exist
				</div>
			";
		}

		$aurl = $adata['asset_url'];
		$atitle = $adata['title'];
		$acapt = ($show_caption)?
			"<div class='acaption'>${adata['caption']}</div>" : '';


		if ($image = $this->getThumb($id,$style) ) {
			$image_data =  "<img src='$image' />";
		} else {
			return "<div class='asset'>Could not get Thumb for asset $id</div>";
		}


			$block = <<<EOT
			<div class='asset'>
				<a href='/asset_viewer.php?$aid' target='viewer'>
				$image_data </a>
				${adata['attr_block']}
				<div class='atitle'>$atitle</div>
				$acapt
			</div>
EOT;




		return $block;



	}

	public function getAssetLinked($id,$nocache=false) {
	/* returns the asset thumbnail, linked to the asset source */
		$adata = $this->Assets->getAssetDataById($id);

		$status = $adata['astatus'];
		$id = $adata['id'];
		switch ($status) {
			case 'T':
				return "Temporary Asset";
				break;
			case 'D':
				return "Asset Deleted";
				break;
		}


		$link = $adata['asset_url'];
		if (empty($link)){return 'No asset url';}

		$thumb = "/assets/thumbs/${id}.jpg";
		if (!file_exists(SITE_PATH . $thumb)){
			return "No thumbnail for asset";
		}
		if ($nocache){
			$time = time();
			$thumb .= "?nocache=$time";
		}
		$result = <<<EOF
		<a href='$link' target="assetl">
		<img src='$thumb'>
		</a>
EOF;
#echo "RESULT <br>$result"; exit;

		return $result;

	}


	private function buildGdImage($srcurl,$desturl, $ttype) {
		// resize local image to thumbnail
		// generall used to build thumb from local image

		if (! $mime = u\is_local($srcurl) || strpos($mime,'image') === false ) {
			throw new Exception ("Must have local image for thumb source");
		}


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








