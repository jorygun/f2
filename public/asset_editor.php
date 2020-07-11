<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require $_SERVER['DOCUMENT_ROOT'] . '/init.php';
	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLevel(1)){
   $page_title = 'Asset Editor';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

$assets = $container['assets'];
$asseta = $container['asseta'];
$assetv = $container['assetv'];

$next_id = 0;
$list_note = '';

if (!empty($_POST['submit'] )) {
	$this_id = $_POST['id'];
	unset ($_GET['id']);

	/*
		save (or not) current id 'this_id'
		remove this-id from saved list,
		and set next_id to display in editor

	// next_id is obtained from removeIdFromSavedList

	// Determine next id .  After posting, the display routine
	// will display contents of next_id

*/

	if ($_POST['submit'] == 'save'){
	/*  save this asset (gettoing new id if was new)
		 if its in the saved list, remove it.
		 show this id again

	*/
		$last_id = $asseta->postAssetFromForm($_POST);
		// does nothing if id not in list
		$next_id = $asseta->removeFromList($last_id);
		$next_id = $last_id;  // show current again

	} elseif ($_POST['submit'] == 'skip' ){
		// if 'Skip' button, do not save, remove from edit list, but go to next
		$asseta->removeFromList($this_id);
		$next_id =  $_POST['next_edit'];

		//if ($this_id > 0) array_push ($_SESSION['last_assets_found'],$this_id);

	} elseif ($_POST['submit'] == 'next' ) {
	// else save this asset, remove from list,  display next in the list
		$last_id = $asseta->postAssetFromForm($_POST);
		$asseta->removeFromList($last_id);
		$next_id = $_POST['next_edit'];

		$list_note = '(Retrieved next id from current search list)';


	}
	elseif ($_POST['submit'] == 'new' ) {
	// remove displayed asset from list and open new
		$asseta->removeFromList($last_id);
		$next_id = 0;
		$list_note = '';


	}
	else {
		die ("unknown submit value.  Cannot determine what to do.");
	}

}

######## GET ######################
// set id to get to last id or get or 0 for new
//END START

if (isset ($_GET['id'])) {
	$id = $_GET['id'] ;
	//$asseta->removeFromList($id);  // only remove on post, not get
	$list_note = '(Retrieved next id _GET)';
// if next id set in post command, that's where to go next
// may be 0
} elseif (isset($next_id)) {
	$id = $next_id;
	$list_note = '(Retrieved next id from go next input)';
// else looks for get id (may be 0, for new asset)

// else, just get next id off the stack
} elseif (!empty($_SESSION['last_assets_found'])){
	$id = array_shift ($_SESSION['last_assets_found']);
	$list_note = '(Retrieved next id from current search list)';
} else {
	$id = 0;
	$list_note = '(No next id, creating new.)';
}




if (! $asset_data = $assets->getAssetDataEnhanced ($id) ){
		die ("No such asset number");
}

$current_count = (isset($_SESSION['last_assets_found'])) ?
		count($_SESSION['last_assets_found']) : 0;



$asset_data['list_note'] = $list_note;
$asset_data['current_count'] = $current_count;

$asset_data['status_style'] = ($asset_data['astatus'] == 'X')? 'color:red':'';
$asset_data['source_warning']='';


$asset_data['thumb_tics'] = getThumbTics($asseta->getExistingThumbs($id));

// just geet the next sequentail id
if ($id != 0) {
	$asset_data['next_edit'] = $asseta->getNext($id);
} else {
	$asset_data['next_edit'] = 0;
}
// build some input boxes
$asset_data['tag_options'] = u\buildCheckBoxSet ('tags',Defs::$asset_tags,$asset_data['tags'],3);
$asset_data['status_options'] = u\buildOptions(Defs::$asset_status,$asset_data['astatus']);
$asset_data['Aliastext'] = Defs::getMemberAliasList();


$asset_data['status_name'] = Defs::$asset_status[$asset_data['astatus']];

if ($id > 0 && ! u\url_exists($asset_data['asset_url']) ){
	$asset_data['source_warning'] = "Unable to access source. <br />";
}



//u\echor ($asset_data);
echo $container['templates']->render('asset_edit',$asset_data);



#################################
function getThumbTics($thumb_list) {
   /* returns array of all thumb types and check mark if thumb exists */
		 $thumb_tics = [];
		$typelist = array_keys(Defs::$thumb_width);
		$typelist[] = 'source';
		foreach($typelist as $ttype) {
				  $thumb_tics[$ttype] = (in_array($ttype,$thumb_list))?'&radic;':'';
		}

		return $thumb_tics;
}



