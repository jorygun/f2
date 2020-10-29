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
   $page_title = 'User Article Manager';
	$page_options=['ajax']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
echo <<<EOT
<script>

	function open_article_edit (editid){
		var editme = document.getElementById(editid).value;
		var url = '/article_editor.php?id=' + editme;
		window.open(url,'aedit');

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

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	if (!empty($_POST['toggle_use'])) {
	$id = $_POST['toggle_use'];
	$article->toggle_use($id);
	}
}



$stories = [];



	$cat='unpub'; //defaullt

	$d = $articlea->getArticleListEnhanced($cat);

	$d['preview_button'] = $publish::$previewbutton;

//	u\echor($d); exit;
	#echo $templates->render('user_article_list', $d);
	echo $templates->render('article_list_user', $d);
	echo "<hr>\n";






exit;


