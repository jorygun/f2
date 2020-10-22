<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(3);

$page_title = 'Recent Links';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START



//EOF


$sql = "SELECT l.*,a.title, DATE_FORMAT(l.last,'%M %d %Y') as last from links l
	JOIN articles a on a.id = l.article_id
	WHERE l.last > NOW() - INTERVAL 60 day
	ORDER BY last DESC
	;";

$ll = $pdo->query($sql)->fetchAll();

//u\echor ($ll);
echo "<table>" . BRNL;
echo "<tr><th>Article</th><th>Last Click</th><th>Clicks</th></tr>" . NL;

foreach ($ll as $ld){

	echo "<tr>
	<td>${ld['title']}</td>
	<td>${ld['last']}</td>
	<td class='centered'> ${ld['count']}</td>

	</tr>\n";
}
echo "</table>";


exit;


