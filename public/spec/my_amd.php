<?php
//BEGIN START
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
if (f2_security_below(1)){exit;}
	$nav = new NavBar(1);
	$navbar = $nav -> build_menu();
//END START

// script to enter/update a news item
// call with ?id=nnn for edit.
// call with no parameter for new


echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type='text/javascript' src = '/js/f2js.js'></script>
<link rel="stylesheet" type="text/css" href="/css/news3.css">

<title>Members Remember</title>


</head>
<body >
$navbar
<h3>AMD Flames Memories of AMD</h3>
EOT;




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



	


  
