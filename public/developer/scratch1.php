function validate_asset_data($a) {
	// previouosly ran check_data so  data is apparently good.

// check mime value for asset
	$val = $a['asset_url'];
	if (! $mime = u\get_mime_from_url ($val ){
		throw new Exception ("Cannot get mime value for url " . $val);
	}

// check thumb exists
	$tpjpg = SITE_PATH . '/assets/thumbs/' . $id . '.jpg';
//   local
	if (!file_exists($tpjpg)){
		// no thumb file.  Make one.

	if ($path = u\is_local($val)
//   youtube
//   http

//


}
