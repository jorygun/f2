<?php

namespace Digitalmx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




if ($login->checkLevel(4)){
   $page_title = 'Article Manager';
	$page_options=['ajax']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
echo <<<EOT
<script>

	function open_article_edit (editid){
		var editme = document.getElementById(editid).value;
		var url = '/article_editor.php?id=' + editme;
		window.open(url,'articleedit');

		}
</script>
EOT;
	echo $page->startBody();
}

//END START
$article = $container['article'];
$templates = $container['templates'];
$asseta = $container['asseta'];
$articlea = $container['articlea'];
$publish = $container['publish'];

// if ($_SERVER['REQUEST_METHOD'] == 'POST'){
// 	u\echor ($_POST,'post'); exit;
// }


if (!empty($_POST['toggle_use'])) {
	$id = $_POST['toggle_use'];
	$article->toggle_use($id);
}

	$style='unpub';
	if (!empty($_POST['style'])) {
		$style = $_POST['style'];
	}
	$d= $articlea->getArticleList($style);
	//u\echor($d); exit;
	echo $templates->render('article_list', $d);
	echo "<hr>\n";



	echo publish::$preview_button;


exit;


// Compile news larticles from items database

/*
Initially show all unpublished items from the db, with
a select box.  Selected items are for next edition.

When news is published, the selected articles are compiled into
an html stream.

This script does 2 things:
a.  display all newsitems with publish box
b. save items and build html stream for checked ones.

When newsletter is actually published, the news items are
then marked as published.

*/




