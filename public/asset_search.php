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



if ($login->checkLogin(1)){
   $page_title = 'Search Assets';
	$page_options=['ajax']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	echo <<<EOT
<script>
function asset_status_search(setter) {
	var setterid = setter.id;
	//alert('setterid '+setterid);

	var all = document.getElementById('all_active');
	var unr = document.getElementById('unreviewed');
	var sel = document.getElementById('status_options');


	switch (setterid) {
		case 'all_active' :
			//alert('all active changed');
			if(setter.checked) {
				unr.checked = false;
				sel.value= '';
			}
			break;

		case 'unreviewed' :
		//alert('unreviewed changed');
			if(setter.checked) {
				all.checked = false;
				sel.value = '';
			}
			break;

		case 'status_options' :
		//alert('options changed val ' + setter.value);
			if(setter.value == '') {
				all.checked = true;
				unr.checked = false;
			}
			else {
				unr.checked = false;
				all.checked = false;
			}
			break;
		default:

	}

}
</script>

EOT;

	echo $page->startBody();
}

//END START


$as = new AssetSearch($container);
	$templates = $container['templates'];


if ($_SERVER['REQUEST_METHOD'] == 'POST'){
 //u\echor ($_POST);

	// save search so easy to repeat/modify
	$_SESSION['last_asset_search'] = $_POST;

	// save list of ids, for sequential editing.
	$ids = $as->getIdsFromSearch($_POST);

	$_SESSION['last_assets_found'] = $ids;
	$count = count($ids);
	echo "$count assets found. (max: 100)";
	if ($count == 0) return;

	if ($count < 11 ) echo join(', ',$ids) . BRNL;

	foreach ($ids as $id){
		$asset = $as->getAssetSummary($id);
		 #u\echor($asset, "selected asset $id");
		echo $templates->render('asset_mini',$asset);
	}
	# u\echor ($ids, 'ids');


}
echo "<hr";
$last_search = $_SESSION['last_asset_search'] ?? [] ;
$search_data = $as->prepareSearch ($last_search );
echo $templates->render('asearch',$search_data);

exit;



