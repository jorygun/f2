<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	


if ($login->checkLogin(0)){
   $page_title = 'Asset Editor';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START
$id = 0;
#$id = (!empty($_GET['id']) ? $_GET['id'] :0;
$id =  $_GET['id'] ?? 0;
// if (empty($id)){
// 	echo "No asset requested"; exit;
// }
$assets = new Assets();
$asseta = new AssetAdmin();


if (!empty($_POST['submit'] )) {
	$this_id = $_POST['id'];
	// if there is a list of assets to edit remove this one from the list
	if (!empty($_SESSION['last_assets_found']) ){
		$_SESSION['last_assets_found']
			= array_diff($_SESSION['last_assets_found'],[$this_id]);
	}
		
	if ($id == 0 ){
		$next_id = $asseta->postAssetFromForm($_POST);
	} elseif ($_POST['submit'] == 'Save'){
		$next_id = $asseta->postAssetFromForm($_POST);
	} elseif ($_POST['submit'] == 'Skip and edit next' ){
		#remove current id from the list, if it's there
		
		$next_id = array_shift($_SESSION['last_assets_found']);
	} else { #save and go next
	
		$last_id = $asseta->postAssetFromForm($_POST);
		$next_id = array_shift($_SESSION['last_assets_found']);
	}
}

######## GET ######################
// set id to geet to last id or get or 0 for new
$id = $next_id ?? 0;
if (!$id) {$id = $_GET['id'] ?? 0;} 


if (! $asset_data = $assets->getAssetDataById ($id) ){
		die ("No such asset number");
}


$current_count =  0;

if (!empty($_SESSION['last_assets_found'])) {
	$current_count = count($_SESSION['last_assets_found']);
	
}
$asset_data['current_count'] = $current_count;


$asset_data['status_style'] = ($asset_data['status'] == 'X')? 'color:red':'';
$asset_data['source_warning']='';

$asset_data['thumb_tics'] = $assets->getThumbTics($id);
// check new thumb if new id or no existing thumb
#$asset_data['thumb_checked'] = ($id == 0)? 'checked':'';
if (!$thumb_tics['thumbs']){$asset_data['thumb_checked']  = 'checked';}
	
// build some input boxes
$asset_data['tag_options'] = u\buildCheckBoxSet ('tags',Defs::$asset_tags,$asset_data['tags'],3);
$asset_data['status_options'] = u\buildOptions(Defs::$asset_status,$asset_data['status']);
$asset_data['Aliastext'] = Defs::getMemberAliasList();

$asset_data['thumb_tics'] = $assets->getThumbTics($id);
$asset_data['status_name'] = Defs::$asset_status[$asset_data['astatus']];

if ($id > 0 && !$assets->checkURL($asset_data['asset_url']) ){
	$asset_data['source_warning'] = "Source cannot be found <br />";
}

	$asset_data['link'] = ($id > 0)? $asseta->getAssetLinked($id,true) : ''; 
	#true prevents cachine of image


	
#u\echor ($asset_data);
echo $templates->render('asset_edit',$asset_data);





