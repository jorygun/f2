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


$asseta = $container['asseta'];

$next_id = 0;
$list_note = '';

if (!empty($_POST['submit'] )) {
	$this_id = $_POST['id'];

	// set next id to display next asset based on
	// which submmit button the user pressed.
	// Determine next id .  After posting, the display routine
	// will display contents of next_id

	if ($this_id == 0 ){
	// if script called with id == 0, create new asset and then display it
	/// does not affect saved asset list
		$next_id = $asseta->postAssetFromForm($_POST);

	} elseif ($_POST['submit'] == 'Save'){
	// if 'Save' button pressed, save this asset and display it next
		// if its in the saved list, remove it.
		// (next_id and this_id are the same)
		$next_id = $asseta->postAssetFromForm($_POST);
		$asseta->removeIdFromSavedList($this_id);

	} elseif ($_POST['submit'] == 'Skip and edit next' ){
		// if 'Skip' button, do not save or remove from edit list, but go to next
		// and put this id back onto the end of the stack (if its  not 0)
		$next_id = array_shift($_SESSION['last_assets_found']);
		$list_note = '(Retrieved next id from current search list)';
		if ($this_id > 0) array_push ($_SESSION['last_assets_found'],$this_id);

	} elseif ($_POST['submit'] == 'Save and edit next' ) {
	// else save this asset, remove from list,  display next in the list
		$last_id = $asseta->postAssetFromForm($_POST);
		$next_id = array_shift($_SESSION['last_assets_found']);
		$list_note = '(Retrieved next id from current search list)';
		$asseta->removeIdFromSavedList($this_id);

	}
	else {
		die ("unknown submit value.  Cannot determine what to do.");
	}

}

######## GET ######################
// set id to geet to last id or get or 0 for new
//END START

// if next id set in post command, that's whewre to go next
if (!empty($next_id)) $id = $next_id;
// else looks for get id (may be 0, for new asset)
elseif (isset ($_GET['id'])) $id = $_GET['id']  ;
// else, just get next id off the stack
elseif (!empty($_SESSION['last_assets_found'])){
	$id = array_shift ($_SESSION['last_assets_found']);
	$list_note = '(Retrieved next id from current search list)';
}
else $id = 0;


$current_count = (isset($_SESSION['last_assets_found'])) ?
		count($_SESSION['last_assets_found']) : 0;

if (! $asset_data = $asseta->getAssetData ($id) ){
		die ("No such asset number");
}

$asset_data['list_note'] = $list_note;
$asset_data['current_count'] = $current_count;

$asset_data['status_style'] = ($asset_data['astatus'] == 'X')? 'color:red':'';
$asset_data['source_warning']='';

$asset_data['thumb_tics'] = $asseta->getThumbTics($id);
// check new thumb if new id or no existing thumb
#$asset_data['thumb_checked'] = ($id == 0)? 'checked':'';
$asset_data['thumb_checked'] = ($asset_data['thumb_tics'] ) ? '' : 'checked';

// build some input boxes
$asset_data['tag_options'] = u\buildCheckBoxSet ('tags',Defs::$asset_tags,$asset_data['tags'],3);
$asset_data['status_options'] = u\buildOptions(Defs::$asset_status,$asset_data['astatus']);
$asset_data['Aliastext'] = Defs::getMemberAliasList();

$asset_data['thumb_tics'] = $asseta->getThumbTics($id);
$asset_data['status_name'] = Defs::$asset_status[$asset_data['astatus']];

if ($id > 0 && ! u\url_exists($asset_data['asset_url']) ){
	$asset_data['source_warning'] = "Source cannot be found <br />";
}

	$asset_data['link'] = ($id > 0)? $asseta->getAssetLinked($id,true) : '';
	#true prevents cachine of image



#u\echor ($asset_data);
echo $container['templates']->render('asset_edit',$asset_data);



#################################


