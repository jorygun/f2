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
		editwin = window.open(url,'aedit');

		}
	function chg_edit_title (em) {
		var title='New';
		var val=parseInt(em.value);
		//alert ("Value is " + val);
		if (! Number.isInteger(val)){
			alert ("Illegal value for article id");
			return false;
		}
		if (val > 0){
			title='Edit';
		}
		document.getElementById('editlabel').innerHTML = title;
		return true;



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

$d = [];
$ilist = $publish->getIssueList();
// date -> issue
//u\echor($ilist);

//$ioptions = u\buildOptions(['one' => 1]);

$stories = [];

if (!empty($_POST['toggle_use'])) {
	$id = $_POST['toggle_use'];
	$article->toggle_use($id);
}

if (!empty($_POST['delete_article'])) {
	$id = $_POST['delete_article'];
	$article->deleteArticle($id);
}

	$cat='unpub'; //defaullt
	// cat can be unpub, recent, or issue
	if (!empty($_POST['cat'])) {
		$cat = $_POST['cat'];
	}

	if ($cat == 'issue'){
			#get articles from POST['issue']
			$issue = $_POST['issue'];
			if (!$stories = $article->getArticleIds('issue',$issue) ){
				die ("Did not get any stories for issue $issue");
			}

		#	$cat .= " " . $ilist[$issue];  // add the issue to the command so
			// it will be in the title
			$cat = 'list'; // change mode to list because already have ids
	}
	$d = $articlea->getArticleListEnhanced($cat,$stories);
//	u\echor($d);

	$d['ioptions'] = u\buildOptions($ilist);
	$d['preview_button'] = $publish::$previewbutton;

//	u\echor($d); exit;
	if ($_SESSION['level'] >7) {
		echo $templates->render('article_list_admin', $d);
	} else {
		echo "Not Allowed";
	}
	echo "<hr>\n";






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




