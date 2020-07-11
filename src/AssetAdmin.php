<?php
/* contains a bunch of definitions and scripts used by multiple asset
    related scripts.

    Enter with an array of asset data, with id=0 for new.
    asset source can be ext. url or local url or uploaded file.
    If uploaded file, file is saved at /assets/files

    Every asset has a thumb file at /thumbnails/$type/id.jpg
    If no thumbsource specified, thumb is vreated from asset.
    If local thumbsource thumb created from that
    If ext thubmsource, source is downloaded to temp file and thumb
     created for that.
   If file upload thumbsource, that file is saved to /assets/thumb_urls
     and thumb createdd from that. (and thumb source changed.)
*/
namespace DigitalMx\Flames;

	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\MyPDO;
	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\FileDefs;
	use DigitalMx\Flames\Assets;


class AssetAdmin
{


	private $pdo;
	private $archive_tag_list_sql;
	private $Assets;
	private $Member;
	private $mimeinfo;




	private static $upload_types = array(
		'uasset','uthumb','uuploads','uftp','image_old','image_now','image_fun');

	public function __construct($container) {
		$this->pdo =  $container['pdo'];
		$this->archive_tag_list_sql =  Defs::getArchivalTagList();
		$this->Assets = $container['assets'];
		$this->Member = $container['member'];
		$this->mimeinfo = new \finfo(FILEINFO_MIME_TYPE);
		$this->Assetv = $container['assetv'];


	}



	// takes asset data array, prepares thumbs needed,
	// and sends to Assets to store (and add computed fields).
	// returns id of asset.

	public function postAssetFromForm($post) {
	// prepare data and then send to asset to post

	#u\echor($post,'post data in');

		$changed = false;

		if (! isset ($post['id'])){
				throw new Exception ("attempt to post asset with no id set");
		}

			// block empty post before it gets far.
		if (
			empty($post['title'])
			// || (empty($post['asset_url']) && empty($_FILES['uasset']['tmp_name']) )
			) {
			die ("Asset needs title.");
		}

		// move the post data needed from thep ost to adata.
		foreach ($this->Assets::$editable_fields as $f) { // includes astatus
			$adata[$f] = $post[$f]??'';
		}


	//echo 'stat is ' . $post ['astatus'] . ' was ' . $post['old_status'] . BRNL;
		if ($adata ['astatus'] != 'E' &&
			$post['old_status'] == 'E') {
				$adata['errors'] = '';
		}

		// must have id before all the data is saved to place files.
		// this creates a skeleton asset record and gets the id.
		if (empty ($id = $post['id'])) {
				$id = $this->Assets->getNewID();
				#echo "New id $id obtained." . BRNL;
				$adata ['astatus'] = 'U';
				$changed = true;
		} elseif ($adata['asset_url'] != $post['old_aurl']
			|| $adata['thumb_url'] != $post['old_turl'] ) {
				$changed = true;
				$adata['astatus'] = 'U';
		}

		$adata['id'] = $id;  // to pick up new id from new entery

	// if status changed fro E status, then clear the errors field



		// set contributor id if one not set yet and
            // valid member name is in the contributo name field
            // no contributor (=0) is not an error
        $cd = $this->Member->setContributor($post['contributor_id'], $post['contributor']);
       // u\echor($cd); exit;

        //put the new contrib info into the adata array
 			$adata = array_merge($adata,$cd);



		$adata['vintage'] = trim($adata['vintage']);
		if (empty($adata['vintage'])){
			$adata['vintage'] = date('Y');
		}

// looks for uploaded files from the form
		foreach (self::$upload_types as $type){
			if (isset($_FILES[$type]) && !empty ($_FILES[$type]['name'] )){
				#echo "Relocating $type... " ;
				try {
				$url = $this->relocateUpload($id,$type);
				} catch (\Exception $e) {
					echo "Upload failed for $id, $type. <br> " . $e -> getMessage();
					continue;
				}
				#echo "to: $url" . BRNL;

				if ($type == 'uthumb'){
					$adata['thumb_url'] = $url;
				} else {
					$adata['asset_url'] = $url;
					$adata['notes'] .= "Uploaded from " . $_FILES[$type]['name'] . "\n";
				}
				$changed = true;
				$adata['astatus'] = 'U';

			}
		}

		if (empty($adata['asset_url'])) {
			die ("Asset $id has no asset source");
		}



		$adata['sizekb'] = 0;
		$adata['mime'] = '';
echo "{$adata['astatus']}" . BRNL;

		// test assset_url
		if (1 || $adata['astatus'] != 'K' ){ #error override

			$adata['mime']  = u\get_mime_from_url ($adata['asset_url'] );
	//echo $adata['mime'] . " mime from url " . $adata['asset_url']; exit;
echo "mime " $adata['mime'] . BRNL;

				$adata['type'] = Defs::getAssetType($adata['mime']);
				if ($adata['mime']){
					if (u\is_local($adata['asset_url']) ) {
						$path = SITE_PATH . $adata['asset_url'];
						$size = filesize($path);
						$adata['sizekb'] = (int)($size/1000);
					} else {
						$adata['astatus'] = 'E'; // not local
					}
				} else {
					$adata['astatus'] = 'E';  /// cannot access the source url
				}

		}

			if (!empty($post['tags']) && is_array ($post['tags'])){
				// convert to string
				$adata['tags'] =  u\charListToString($post['tags']) ;
			}



		if ($changed) { //new or changed urls.  Make sure thumb sources are in place
			echo "Asset sources have changed." . BRNL;
			// remove eisting thumbs
			foreach (
				[FileDefs::asset_dir . '/thumb_generated' . "/${id}.jpg",
				FileDefs::thumb_dir . '/small'. "/${id}.jpg",
				FileDefs::thumb_dir .'/ medium'. "/${id}.jpg",
				FileDefs::thumb_dir . '/large'. "/${id}.jpg",
				] as $thumb) {
				//echo "checking $thumb .. ";
				if (file_exists($thumb)){
					echo "removing old file $thumb <br>";
					unlink ($thumb) ;
				} else {
				echo "<br>";
				}
			}
			try {
				$adata['local_src'] = $this->checkThumbSources
				($id,$adata['asset_url'],$adata['thumb_url'],$adata['mime'] ) ;

				if (!$adata['local_src'] ){ // could not verify thumb sources
					throw new Exception ("Could not determine local source for asset $id thumb.");
				}
				$desturl = "/thumbnails/small/${id}.jpg";
				$this->Assetv::buildGdImage($id,$adata['local_src'], 'small');
				$this->checkAssetData($adata);
				$this->Assets->saveAsset($adata);
			}  catch (\Exception $e) {
				echo "Error in asset data. Asset not saved." . BRNL;
				echo $e->getMessage();
				$id='';
			}

		}


		return $id;

	}

	public function removeFromLIst($id) {
		// removes id from the saved list

		if (!empty($_SESSION['last_assets_found']) ){
			$_SESSION['last_assets_found']
			= array_diff($_SESSION['last_assets_found'],[$id]);
		}
	}

	public function getNext($id) {
		//returns
		// next id from the list (if there is a list):
		//  or next incrementally (if empty list)
		// or 0 if no next
		if (!empty($_SESSION['last_assets_found']) ){
				$next_id = array_shift($_SESSION['last_assets_found']);
		} else {
			// get next sequential id
			$sql = "SELECT id FROM `assets2` WHERE id >= $id ORDER by id LIMIT 2";
			if ($result = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN) ) {
				if (count($result) == 2) {
					$next_id = $result[1];
				} else {
					$next_id = 0;
				}
			} else {
				$next_id = 0;
			}
		}

		return $next_id;
	}

public function checkAssetData($adata) {

	/* checks that required dat a is present.
		Does NOT check that urls exist, because that is
		done by checkThumbSources in assetadmin

		If a url is not accessible, the asset status should be
		set to ....  to indicate it has a known problem but is ok.

	Fields:

	id
	asset_url  (nee link) (asset source url)
	thumb_url (nee url) (thumb source url)
	title
	caption
	keywords
	tags
	source
	vintage
	contributor id
	astatus

	first_use_date
	first_use_in

	mime
	type
	sizekb

	date entered
	date modified
*/

	#u\echor($adata,'Into Checking asset data:');
	$id = $adata['id'];
//u\echor($adata);exit;

	foreach ($adata as $var => $val){
		switch ($var) {
			case 'id':
				if (! is_integer( (int)$val) ) die ("bad id: ". $val);
				break;

			case 'asset_url':
				if (empty($val)) {
					throw new Exception ("Id $id: No source for asset specified");
				}
				if ($adata['astatus'] != 'K' ) { #over-ride inaccessible source
					$amime = u\url_exists ($val);
					//echo "$val is mime '$amime'";
					if ($amime === false) {
						throw new Exception ("Id $id: Asset source cannot be accessed." . $val) ;
					}
				}
				break;
			case 'thumb_url':
				if(empty($val)) break;
				// thumb must be either local or a youtube
				if ( strpos($val,'/') === 0 ||  u\is_youtube($val) ){
					#ok
				} else {
					throw new Exception ("Id $id: Thumb source is remote " . $val) ;
				}
				break;

			case 'title':
				if (empty($val)){
					throw new Exception ("Id $id: No title provided");
				}
				break;
			case 'vintage':
				 if (! u\isInteger ($val) ){
				 	throw new Exception ("Id $id: Vintage is not a number");
				 }
				 if ( $val > 2050 ){ #to cast as numeric
						throw new Exception ("Id $id: Vintage is not a valid year $val");
					}
				break;
			case 'astatus':
				if (! in_array($val,array_keys(Defs::$asset_status))){
					throw new Exception ("Id $id: Unknown asset status $val");
				}
				break;

			case 'contributor_id' :
				if (! $this->Member->getMemberBasic($val) ){
					#0 is allowed; returns "not a member"
					throw new Exception ("Id $id: No user found for contributor id " . $val);
				}
				break;


			case 'tags':
				if (empty($val)) break;
				foreach (str_split($val) as $tag){
					if (! in_array($tag,array_keys(Defs::$asset_tags)))
					throw new Exception (" Id $id: Unknown asset tag $tag");
				}

				break;
			case 'mime':

				if (!in_array($val,Defs::getAcceptedMime() ) )
			 		throw new Exception ("Id $id: Source mime '$val' is not acceptable"  );
			 	break;

			default: #do nothing

		} #end switch
	}
	return true;

	}


	public function getExistingThumbs ($id) {
		// returns list of thumb types that exist
		$thumb = "${id}.jpg";
		$tloc = SITE_PATH . "/thumbnails";
		$ttypes = [];
		if ($id > 0 && !file_exists("${tloc}/small/$thumb") ){
			// make small thumb.  Everyone needs one
			$th = $this->Assetv->getThumb($id,'small');
			if (strpos($th,'**ERROR') != false){
				echo "<p class='red'>$th</p>" . NL;
			}
		}
		foreach (Defs::getThumbTypes() as $ttype){
			if (file_exists($tloc . '/' . $ttype . '/' . $thumb)){
				$ttypes[] = $ttype;
			}
		}
		return $ttypes;
	}


	private function yt_info ($tyid) {
	//use this with curl to retrieve a lot of info abut a youtube video
				return "https://www.googleapis.com/youtube/v3/videos?id="
					. $ytid
					. "&part=status&key="
					. Defs::$ytapikey;
	}


// replace this with something to force receck of thumb sources
// 	private function checkThumbNeeds($adata,$new_thumbs) {
// 		// set which thumbs are needed, by checkbox or by changed url
// 		$id = $adata['id'];
// 		$needs = array ();
// 		$result = $this->getAssetDataEnhanced($id);
//
// 		$thumbs = $result['existing_thumbs']; #keys where value is ''
//
// 		// if either url has been changed, all the thumbs need to be regneratied.
// 		if ($result['asset_url'] != $adata['asset_url']
// 			|| $result['thumb_url'] != $adata['thumb_url']
// 			|| in_array('all',$new_thumbs) ){
// 			$needs = $thumbs; #all existing thumbs
// 			$new_thumbs = array_diff ($new_thumbs,['all']) ;
// 		}
// 		if (! in_array('thumbs',$thumbs)){$new_thumbs[] = 'thumbs';} #always need this
// 		// add in any thumbs were checked on the form
// 		$needs = array_unique(array_merge($needs,$new_thumbs));
// 		#u\echor($needs,'needs after check needs'); exit;
//
//
// 		return $needs;
// 	}




private function buildImagicImage ($src_url,$dest_url){
		// imagick image will go into thumb sources
		$max_dim = 800; // pixels for saved thumb source image
		$tpath = $src_url;
		if (strpos($src_url,'/') === 0 ){$tpath = SITE_PATH . $src_url;}

		$destpath = SITE_PATH . $dest_url;

		 echo "calling imagick on $tpath" . BRNL;
		  	 $im = new \Imagick ();
		  	 $im->setResolution(300,300);
			$im->readImage($tpath);
			$im->setImage($im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN));
		 $im->setImageFormat('jpeg');

		 $im->thumbnailImage($max_dim, $max_dim,true); #best fit
		 $im->writeImage($destpath);

		 return true;
	}



	public function checkThumbSources ($id,$aurl,$turl,$amime) {
		/* set thumb ssource
			make sure thumb sources are avaialble.
			$tsrc will be turl or aurl.

			if it's local and graphic, you're set
			if it's remote and graphic, download to thumb_sources
			if it's local or remote and a pdf, make image in thumb_sauto
			if it's a youtube video, download the yt thumb and put in thumb_auto

			returns the local thumb source

			else use the icon for the asset mime time ?? or do this at get thumb time??
			also will create small thumb, as every asset needs one.
		*/

echo "Building local source" . BRNL;

		$genurl = "/assets/thumb_generated/$id.jpg" ;
		$gurl = (file_exists(SITE_PATH . $genurl) ) ? $genurl : '';

		$tsrc = '';
		if (!empty($turl)){
			if (!file_exists(SITE_PATH . $turl)){
				throw new Exception ("Deisngated thumb url on id $id does not exist");
			} elseif (! u\is_local($turl)) {
				throw new Exception ("Thumb url is not local file");
			} else {
			$tsrc = $turl;
			}
		}
		if (empty($tsrc) && !empty($gurl)){
			$tsrc = $gurl;
		}

		if (empty ($tsrc) && !empty($aurl)) {
			$tsrc = $aurl;
		}

echo "tsrc: $tsrc" . BRNL;

		$tmime = '';
		$local_src = '';

		$tmime = u\is_local($tsrc) ;
		if ($tmime !== false ) {
			//echo "$tsrc is local $tmime" . BR;
			// is local file
			if (strpos($tmime,'image') !== false) {
				// is an image file.  Ok to use as is
				$local_src = $tsrc;

			}
			elseif (strpos($tmime,'pdf') !== false) {
				// is pdf.  Use imagic to make image and put it in thumb sources
				$this->buildImagicImage($tsrc.'[0]', $genurl);
				$local_src = $genurl;
			}
		}
		if (empty($local_src)){
			$ytid = u\youtube_id_from_url($aurl) ;
			if (!empty($ytid)){
				//echo "$aurl is youtube" . BRNL;
				$tmime = 'video/x-youtube';
			// get url to youtube's thumb file for this video
				$yturl = "http://img.youtube.com/vi/$ytid/mqdefault.jpg";

				if (! @copy ($yturl ,  SITE_PATH . $genurl)  ) {
					throw new Exception ("Youtube thumb cannot be retrieved on id $id");
				}
				$local_src = $genurl;
			}
		}

		if (!$local_src ) {
			$tmime = u\is_http($aurl);
			if ($tmime) {
				//echo "http tmime $tmime" . BRNL;
				$size = u\get_info_from_curl($aurl)['size'] ?? 0;
				$sizem = $size / 1000000; #MB
				if ( $sizem <= 2 && strpos($tmime,'image') !== false) { //MB
					if (! @copy ($aurl ,  $genurl) ) {
						throw new Exception ("Failed to copy $aurl on id $id");
					}
					$local_src = $genurl;
				} elseif ($sizem <= 16 && strpos($tmime,'pdf') !== false) {
					try {
						$this->buildImagicImage($aurl.'[0]', $genurl) ;
					} catch (\ImagickException $e) {
						echo "Imagick failed on remote document" . BR;
						echo $e->getMessage();
						throw new Exception ("Failed to build imiage of pdf id $id");
					}
					$local_src = $genurl;
				}
			}
		}
		if (! $local_src) { // use icon from mime
			echo " Using icon for $amime on id $id $tsrc." . BRNL;
		// return generic icon for amime type
		  $icon = Defs::getIconForMime($amime) ;
			$local_src =  "/assets/graphics/icons/$icon";
		}
//echo "Local source $local_src" . BRNL;
		return $local_src;

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

		$this->checkUploads($type);
		$orig_path = $_FILES[$type]['tmp_name'];
		$orig_mime = $this->mimeinfo->file($orig_path) ;
		$orig_name = $_FILES[$type]['name'];
		$orig_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

		$new_loc = ($type == 'uthumb')? '/assets/thumb_sources/' : '/assets/files/' ;
		$new_url = $new_loc . $id . '.' . $orig_ext;
		$new_path = SITE_PATH . $new_url;

		echo "Now moving $orig_path to $new_url" . BRNL;
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
		 	throw new \RuntimeException("Error: Files array for $utype");

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

		 return true;
	}




}
