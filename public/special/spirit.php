<?php
//BEGIN START

namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLogin(4)){
   $page_title = 'The Spirit of AMD';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	echo <<<EOT
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
EOT;
	echo $page->startBody();
}

//END START



// don't think newsfunctions needed

$bookthumb = "<img src='/assets/thumbs/4913.jpg'>";

#get stars

$star_articles = array(
	1761,1738,1745,1748,1749,1750,1755,1757);
$star_set = "'" . implode("','",$star_articles) . "'";

$sql =
	"SELECT n.id,n.title,m.username,n.source
	FROM `articles` n
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
	"SELECT n.id,n.title,m.username,n.source
	FROM `articles` n
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


</head>
<body >


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
<?php
$member_status_set = Defs::getMemberInSet();
$sql = "

	SELECT username, user_id, user_amd, user_memories FROM `members_f2`
	WHERE user_memories != '' AND status in ($member_status_set) AND test_status = ''
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




