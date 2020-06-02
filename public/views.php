<?php

namespace Digitalmx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;




if ($login->checkLevel(4)){
   $page_title = 'Views By Issue';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();

	$latest = $container['news']->getLatestIssue();

	$container['news'] ->buildChart(FileDefs::view_chart_url);

	echo <<<EOT
<h4>Count of views by issue date</h4>
<p>(started Jan 18, 2016)</p>
<p>Latest: ${latest['hdate']}</p>
EOT;
 echo "<img src = '" . FileDefs::view_chart_url . "'>";
}

//END START

// $proj = dirname(dirname(dirname($me))); #flames




