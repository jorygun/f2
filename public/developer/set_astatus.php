<?php

namespace DigitalMx\Flames;

ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = 'Set Admin Status';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START
$sql = "UPDATE members_f2 set admin_status = 'G' WHERE username = ? ";
$sqlupd = $pdo->prepare($sql);

$uname = 'Barry Fitzgerald';
$sqlget = $pdo->prepare("SELECT user_id from members_f2 where username = ? ");

$unames = file('uname_list.txt');

echo "Loaded " . count($unames) . " records. " . BRNL;

foreach ($unames as $uname){
	//echo "Getting $uname... ";
	$uname = trim($uname);
	if (! $sqlget -> execute([$uname]) ) {die ("oops");}
 	if (!$uid = $sqlget->fetchColumn() ) {
 		echo "User $uname not found" . BRNL;
 		continue;
 	}
 	//echo "found" . BRNL;

	if (! $sqlupd->execute([$uname]) ){
		echo "Failed on $uname" . BRNL;
	} else {
		//echo "Updated $uname" . BRNL;
	}
}
echo 'Done' . BRNL;



//paste user names below



//EOF
