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



if ($gid = $_SERVER['QUERY_STRING']){
	$galleries->display_gallery($gid);
}
else{
	$galleries->show_galleries();
}


echo "</body></html>\n";
exit;
