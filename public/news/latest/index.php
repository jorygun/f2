<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
$login->checkLevel(0);

#require_once "ReadNews.php";


use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\ReadNews;
use DigitalMx\Flames\FileDefs;





// contains routines needed for the news page


$news = $container['news'];
$article = $container['article'];
$opps = $container['opps'];
$read = new ReadNews();
$calendar = $container['calendar'];
$publish = $container['publish'];

/*
	sequence:
	get issue (from dir)
	show user warning
	show breaking news
	show all news articles
	display opps (live)
	display calendar (live)
	show status report
	display recent (live)

*/


// get issue data using current folder path

$login->checkLevel(1);

$issue = 0; $style = 5;

$strindex = strpos(__DIR__,'/news'); // first news or newsp
// get url of this directory.  that will be used to identify the issue.
$url = substr(__DIR__,$strindex);
$preview = (strpos(__DIR__,'/news/next') !== false) ;

// get issue data inc - title mostly
$sql = "SELECT * from issues where url = '$url'";
if (! $issue_data = $pdo->query($sql)->fetch() ) {
	die ("No issue at url $url");
}
if ($preview) {$style = 6;}
// is preview issue; changes page head
$issue=$issue_data['issue'];

$page_title = 'Flame News ';
$page_options = ['ajax'];
$pubtime = strtotime($issue_data['pubdate']) ?: time();

$published = date('d M, Y', $pubtime);
if (!empty($issue_data['title'])){
	$subtitle = $issue_data['title'] . " &middot; " . $published;
} else {
	$subtitle = $published;
}

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
echo $page->startBody($style,$subtitle);


$rcount = 0;

	$artlist = $news->getIssueArticles($issue) ?: [];

	if ($issue != '1'){
		$news->incrementReads($issue);
	}

// u\echor($artlist, 'artlist'); exit;
echo $read->user_welcome();

 // now display all the articles tied to this issue.
$last_section = '';
$show = 'pops';
foreach ($artlist as $aid) {
	if (!$aid){continue;}

	$art = $article->getArticle($aid);
//u\echor($art);
	$sec_name = $art['section_name'];
	$id = $art['id'];
	if ($sec_name != $last_section) {
		echo "<h2>$sec_name</h2>";
		$last_section = $sec_name;
	}
	$sdata = $container['articlea']->getLiveArticle($aid,$show);
	$sdata['mode'] = 's'; #don't show comments
	$sdata['credential'] = false; #don't show edit buttons
	echo $container['templates']->render('article', $sdata);

}


$read->echo_if('profile_updates.html','Profile Updates');
echo "<div class='clear'></div>";

$current_opps = $opps->getOppCount();
echo $read->news_head("Job Opportunities");
echo " <p>
There are $current_opps current employment openings listed. <br>
Any FLAMEs member can post job opportunities on the site
by clicking on the 'Opportunities' menu.
</p>";
foreach ($opps->linkOppList() as $opp) {
	echo $opp . BRNL;
}

echo $calendar->display_calendar();

echo $read->news_head("Recent Activity");
$read->echo_if ('recent_articles.html');
$read->echo_if('recent_assets.html');
echo "<div style='clear:both'></div>\n";


$read->echo_if ("status_report.html", "Membership Updates");



echo "</body></html>\n";



