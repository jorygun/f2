<?php
namespace DigitalMx\Flames;
ini_set('display_errors', 1);



//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;

$mode = $_GET['mode'] ?? '';  // j when opened by javascript

$login->checkLevel(0);

   $page_title = 'Search Assets';
	$page_options=['ajax','help']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	echo <<<EOT
<script>
  function send_id(aid) {
  	var target = window.opener;
   target.postMessage(aid);
    window.close();
   return true;
  	}
</script>
EOT;





echo $page->startBody();


//END START


$as = $container['assetsearch'];
$templates = $container['templates'];



if ($_SERVER['REQUEST_METHOD'] == 'POST'){
 //u\echor ($_POST);

	// save search so easy to repeat/modify
	$_SESSION['last_asset_search'] = $_POST;

	// save list of ids, for sequential editing.

	$data = $as->getIdsFromSearch($_POST);
	u\echoc($data['sql'],'search WHERE');

	if (!empty($data['error'])) {
		echo $data['error'];
	} else {

		$ids = $data['list'];
		# u\echor ($ids, 'ids');

		$_SESSION['last_assets_found'] = $ids;
		$count = count($ids);
		echo "$count assets found. (max: 100)";

		if ($count < 11 ) echo join(', ',$ids) . BRNL;

		foreach ($ids as $id){
			$asset = $as->getAssetSummary($id);

			$asset['mode'] = $mode; // was seach opened from javascript?
			 #u\echor($asset, "selected asset $id");
			echo $templates->render('asset_mini',$asset);

		}


	}

}

echo "<hr>";
$last_search = $_SESSION['last_asset_search'] ?? [] ;
$search_data = $as->prepareSearch ($last_search );
echo $templates->render('asearch',$search_data);
// echo "</body></html>" . NL;
//
