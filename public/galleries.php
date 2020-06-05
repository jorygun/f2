<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;


if ($login->checkLogin(1)){
   $page_title = 'Galleries';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START

$galleries = $container['galleries'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$gid = $galleries->post_gallery($_POST);
	$galleries->display_gallery($gid);
	exit;
}

if ($gid = $_SERVER['QUERY_STRING']){

	if (u\isInteger($gid)) {
		$galleries->display_gallery($gid);
		exit;
	}
	else  {
		$gid = $_GET['id'] ?? '0';
		$mode = $_GET['mode'] ?? '';

		if ($mode == 'edit') {
			$galleries->edit_gallery($gid);
			exit;
		}
	}
}
// if allelse fails
$galleries->show_galleries();



echo "</body></html>\n";
exit;
