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


if ($_SERVER['REQUEST_TYPE'] == 'POST'){
	$ids = $as->getIdsFromSearch($_POST);
	u\echor($ids);
	exit;
}

$last_search = $_SESSION['last_asset_search'] ?? [] ;
$search_data -> $as->prepareSearch ($last_search );
echo $templates->render('asearch',$search_data);

exit;


		
