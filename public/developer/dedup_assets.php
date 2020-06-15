<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = '';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START

$sql = "select id, asset_list from articles where length(asset_list) > 6;";
$sth = $pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);

foreach ($sth as $id => $list){

	$alist = explode(" ",$list);
	$alist = array_unique($alist);
	$aval = implode(" ",$alist);
	if ($aval != $list) {
		echo "$id: $list -> $aval" . BRNL;
		$sql = "UPDATE articles set asset_list = '$aval' WHERE id = $id;";
		$pdo->query($sql);
	}

}


//EOF
