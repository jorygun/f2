<?php
	namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;
	

	
if ($login->checkLogin(2)){
   $page_title = 'Index to Newsletters';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody(4); #collapsible list style
}
	
//END START


 if (! file_exists(FileDefs::news_index_inc)) {die ("Cannot find news index.; contact admin.");}


echo file_get_contents(FileDefs::news_index_inc);
echo "</body></html>\n\n";


