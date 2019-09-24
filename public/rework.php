<?php


#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;



if ($login->checkLogin(4)){
   $page_title = 'Asset Editor';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START

$sql1 = "SELECT id,url,link from `assets` where url like '/reunion/%' or link like '/reunion%' ";
$sql2 = "UPDATE `assets` SET url = ?, link = ? WHERE id = ? ;";
$ins = $pdo->prepare($sql2);

$src = $pdo->query($sql1);
foreach ($src as $row){
    $id = $row['id'];
    $url = $row['url'];
    $link = $row['link'];

    $rurl = preg_replace ('|^/reunions|','/assets/reunions/',$url);
    echo "$id<br>";
    echo "$url -> $rurl" . BRNL;
    $rlink = preg_replace ('|^/reunions|','/assets/reunions/',$link);
     echo "$link -> $rlink" . BRNL;
    $ins->execute([$rurl,$rlink,$id]);
   $count++;

   }
   echo "$count records updated." . BRNL;


