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
update_galleries($container['pdo']);


function update_galleries ($pdo) {

	// first, explode all the galleries and mark first use
	$sql = "SELECT id, gallery_items from galleries";
	$gh = $pdo->query($sql);
	$asql = "UPDATE assets SET first_use_in = ?
			WHERE id = ? AND (first_use_in is null OR first_use_in = '');";

	$asqlh = $pdo->prepare($asql);
	while ($row = $gh->fetch()) {
		$gid = $row['id'];
		$gurl = "/galleries.php?$gid";
		$atxt = $row['gallery_items'];
		$alist = u\number_range($atxt);
		foreach ($alist as $aid) {
			$asqlh->execute([$gurl,$aid]);
			if ($asqlh->rowCount() > 0){
				echo "Updated asset $aid for gallery $gid" . BRNL;
			}
		}
	}


}


//EOF
