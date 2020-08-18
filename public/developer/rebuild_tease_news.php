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




$login->checkLevel(0);

$page_title = '';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();

echo "Starting __SCRIPT__ ";
$pdo = $container['pdo'];
buildTeaser();


//END START



//EOF
function buildTeaser() {
		$sql = "SELECT a.title,u.username as contributor
		FROM articles a
		JOIN members_f2 u on a.contributor_id = u.user_id
		where issue = '20200817' ";
		$artlist = $pdo->query($sql);
		u\echor ($artlist,$sql);


		$t = "News Stories: \n------------------\n";
		$nbsp3 = "&nbsp;&nbsp;&nbsp;";

		//u\echor ($artlist); //exit;
		foreach ($artlist as $article) {
			$t .= $nbsp3 . $article['title'] . " (" . $article['contributor'] . ")" . NL;
		}
		$t .= "\n";
		echo $t  . BRNL;

		file_put_contents(FileDefs::next_dir . '/' . FileDefs::tease_news,$t);

	}
