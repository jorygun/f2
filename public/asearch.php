<?php
namespace digitalmx\flames;
ini_set('display_errors', 1);



//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	


if ($login->checkLogin(1)){
   $page_title = 'Search Assets';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START
$as = new AssetSearch();
$asset = new Asset();

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
	if (empty($adata = $_SESSION['last_asset_search_post'])){
		$adata = $as->getEmpty();
	}
	$asprep = $as->prepare_asset_search($adata);
	echo $templates->render('asearch',$asprep);
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' ){
	// save the post data so it can be reused  for the next search
	$_SESSION['last_asset_search_post'] = $_POST;
	// translate the search criteria into sql
	$sql = $as->processAssetSearch($_POST);
	echo $sql;
	echo "<hr>";
	$found = $asset->retrieveIds($sql);
	$acount = count($found);
	echo "$acount Assets Match". BRNL;
	#u\echor($found);


}

$asprep = $as->prepare_asset_search(
	$_SESSION['last_asset_search_post']
			);
	
	echo $templates->render('asearch',$asprep);

		
