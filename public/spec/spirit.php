<?php
//BEGIN START
	require_once 'init.php';
	if (f2_security_below(1)){exit;}
	$nav = new navBar(1);
	$navbar = $nav -> build_menu();
	$pdo = MyPDO::instance();
//END START

// script to enter/update a news item
// call with ?id=nnn for edit.
// call with no parameter for new

require_once 'asset_functions.php';
require_once 'news_functions.php';

$bookthumb = get_asset_by_id(4913);

#get stars

$star_articles = array(
	1761,1738,1745,1748,1749,1750,1755,1757);
$star_set = "'" . implode("','",$star_articles) . "'";

$sql =
	"SELECT n.id,n.title,m.username,n.source, n.asset_id
	FROM `news_items` n
	INNER JOIN `members_f2` m ON m.user_id = n.contributor_id
	WHERE n.id in ($star_set)
	ORDER BY FIELD (n.id,$star_set);";
$result = $pdo->query($sql);
$stars = '<ul>';
foreach ($result as $row){
	$link = "<a href='/scripts/news_article_c.php/?id=${row['id']}' target = 'story'>${row['title']} </a><br>";
	$attr = $row['username'];
	#$oldsters .= get_asset_by_id($row['asset_id']);
	$stars .= " <li><b>$attr</b>  $link" . BRNL;
}
$stars .= "</ul>\n";

#get others
$sql =
	"SELECT n.id,n.title,m.username,n.source, n.asset_id
	FROM `news_items` n
	INNER JOIN `members_f2` m ON m.user_id = n.contributor_id
	WHERE n.type='spirit'   and n.id not in ($star_set)
	ORDER BY n.date_published DESC";

	$result = $pdo->query($sql);
$articles = '<ul>';
foreach ($result as $row){
	$link = "<a href='/scripts/news_article_c.php/?id=${row['id']}' target = 'story'>${row['title']} </a><br>";
	$attr = $row['username'];
	#$oldsters .= get_asset_by_id($row['asset_id']);
	$articles .= " <li><b>$attr</b>  $link" . BRNL;
}
$articles .= "</ul>\n";

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type='text/javascript' src = '/js/f2js.js'></script>
<link rel="stylesheet" type="text/css" href="/css/news3.css">

<title>The Spirit of AMD</title>

<style>
.box {width:80%;max-width:600px;
	margin-left:auto; margin-right:auto;
	border:1px solid #3C3;
	padding:1em;
	}
.brief {
	color:#030;
	margin-bottom:0em;
}
.bsource {font-family:helvetica,arial,san-serif;
	margin-top:0.5em;
	margin-left:3em;
	margin-bottom:1em;
	font-size:0.9em;
}
</style>
</head>
<body >

<?=$navbar?>
<hr>
<p class='title'>The Spirit of AMD</p>

<p class='clear'>This page is devoted to the spirit of AMD &mdash; the company ethos that drove it to success, sustained it through rough times, and left so many people like you feeling that AMD was the best company they ever worked for.</p>

<hr class='clear'>

<?=$bookthumb?>

<div style='float:left;margin-left:2em;'>
<p>The 1998 book by Jeffrey Rodengen tells the story of the beginnings of AMD.</p>
<p class='box' ><i>From the beginning, the company’s chief founder, Chairman and CEO Jerry Sanders, was determined to build an organization that embodied the ideals in which he believed and fought for his entire life: that employees are at the core of success; rewarding merit brings out the best in people; and that loyalty is earned through fair treatment.</i></p>
</div>
<hr class='clear'>


<img src='/assets/toons/4054.jpg' class='centered' />



<hr class='clear'>
<h4>Some AMD stars reflect ... </h4>
<?=$stars?>

<?=$articles?>
<hr class='clear'>
<h4> AMD Flames - the people who were and are AMD -  share their memories.</h4>
<p style='font-size:0.8em'>Listed in order of recent profile updates. If you want to be at the top of this list, update your profile's "memories" section.</p>
<?

$sql = "

	SELECT username, user_id, user_amd, user_memories FROM `members_f2`
	WHERE user_memories != '' AND status in ($G_member_status_set) AND test_status = ''
	ORDER BY profile_updated DESC
		";

$result = $pdo->query($sql);

$textlimit = 240;
foreach ($result as $row){
	$mem = $row['user_memories'];
	if (strlen($mem) > $textlimit){
		$mem = substr($mem,0,$textlimit)
		. ' ... '
		. "<a href='my_amd.php?${row['user_id']}' ><i>Read more</i></a>"
		;
	}


	echo <<<EOT

<p class='brief'>$mem</p>
<p class='bsource'>-- ${row['username']}, ${row['user_amd']} </p>

<p></p>
EOT;
}




