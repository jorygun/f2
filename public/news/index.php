<?php
namespace Digitalmx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	 require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

    use DigitalMx as u;
    use DigitalMx\Flames as f;
    use DigitalMx\Flames\Definitions as Defs;
    use DigitalMx\Flames\DocPage;
    use DigitalMx\Flames\FileDefs;


if ($login->checkLevel(4)){
   $page_title = 'News Index';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	echo "<script type='text/javascript' src='/js/collapsibleLists.js'></script>";
	echo $page->startBody(4);
}

//END START
echo $container['news']->getNewsIndex();



//EOF
