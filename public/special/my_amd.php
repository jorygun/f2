<?php

namespace Digitalmx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;



if ($login->checkLogin(0)){
   $page_title = 'Memories of AMD';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START



//EOF



$pdo = MyPDO::instance();
$get_id = $_SERVER['QUERY_STRING'];
if (! is_numeric($get_id)){ die ("Invalid Id requested");}

$sql = "
	SELECT username, user_id, user_amd, user_memories FROM `members_f2`
	WHERE user_id = $get_id

		";

if (! $result = $pdo->query($sql) ){
	die ("No such user id $get_id");
}


$row = $result->fetch();


	echo "<h4>${row['username']}, ${row['user_amd']}</h4>";

echo "<div style='width:600px;'>", nl2br(spchar($row['user_memories'])),"</div>" . BRNL;







