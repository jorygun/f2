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

$issue = 0; $preview = false;
$dir = dirname(__FILE__);
$strindex = strpos($dir,'/news'); // news or newsp

$url = substr($dir,$strindex);

$sql = "SELECT * from pubs where url = '$url'";
if (! $issue_data = $pdo->query($sql)->fetch() ) {
	die ("No issue at url $url");
}

$issue=$issue_data['issue'];

$page_title = 'Flame News ';
$page_options = ['ajax'];
$subtitle = $issue_data['title'];
$page = new DocPage($page_title);
echo $page -> startHead($page_options);
echo $page->startBody(1,$subtitle,$preview);


$rcount = 0;
if ($issue == '1'){
	$artlist = $article->getArticleIds('next');
} else {
	$rcount = $news->incrementReads($issue);
	// get array of all articles for issue, sorted in display
	// order and with topics and section info
	$artlist = json_decode($issue_data['stories'] )?: []; #list of ids

}

//u\echor($artlist, 'artlist');
echo $read->user_welcome();

 // now display all the articles tied to this issue.
$last_section = '';
$show = 'pops';
foreach ($artlist as $aid) {
	//$art = $article->getArticleList($aid);
	//echo "$artid, "; continue;
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



