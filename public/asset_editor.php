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

$id = $_GET['id']?? 0;
// if (empty($id)){
// 	echo "No asset requested"; exit;
// }
$asset = new Asset();
$asseta = new AssetAdmin($asset);


if ($_SERVER['REQUEST_METHOD'] == 'GET'){
	if (! $asset_data = $asset->getAssetDataById ($id) ){
		die ("No such asset number");
	}
	$asset_data['status_style'] = ($asset_data['status'] == 'X')? 'color:red':'';
	$asset_data['source_warning']='';
	
	$asset_data['thumb_checked'] = ($id == 0)? 'checked':'';
	// build some input boxes
	$asset_data['tag_options'] = u\buildCheckBoxSet ('tags',Defs::$asset_tags,$asset_data['tags'],3);
	$asset_data['status_options'] = u\buildOptions(Defs::$asset_status,$asset_data['status']);
	$asset_data['Aliastext'] = Defs::getMemberAliasList();
	$asset_data['link'] = $asseta->getAssetLinked($id);
	$asset_data['thumb_tics'] = $asset->getThumbTics($id);
	$asset_data['status_name'] = Defs::$asset_status[$asset_data['status']];
	
	if ($id > 0 && !$asset->checkURL($asset_data['asset_url']) ){
		$asset_data['source_warning'] = "Source cannot be found <br />";
	}
		
	#u\echor ($asset_data);
	echo $templates->render('asset_edit',$asset_data);
}

elseif ($_POST['submit'] == 'Submit') {
	$id = $asseta->postAssetFromForm($_POST);
}






