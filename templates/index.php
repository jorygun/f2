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
$calendar = new Calendar();
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


// get enclosing folder name
$issue = 0; $preview = false;
$ndir = basename(dirname(__FILE__));
if (preg_match('/(\d+)$/',$ndir,$m) ){
	$issue = (string)$m[1];
} elseif ($ndir == 'next') {
	$issue = '1'; #preview
	$preview = true;
} else {
	die ("No issue no. in $ndir");
}

if (! $issue_data = $news->getIssueData($issue) ) {
	die ("No issue $issue defined");
}
//echo "Issue: $issue" . BRNL;

//u\echor($issue_data);

$page_title = 'Flame News ';
$page_options = ['ajax'];
$subtitle = $issue_data['title'];
$page = new DocPage($page_title);
echo $page -> startHead($page_options);
echo $page->startBody(1,$subtitle,$preview);


$rcount = 0;
if ($issue != '1'){
	$news->incrementReads($issue);
	$rcount = $news->getReads($issue);
}

#breaking news added after publication
//$read->echo_if('breaking.html');


echo $read->user_welcome();

// get array of all articles for issue, sorted in display
// order and with topics and section info
$stories = $issue_data['stories']; #list of ids

$artlist = $article->getArticleList($stories);

 // now display all the articles tied to this issue.
$last_section = '';
foreach ($artlist as $art) {
	//echo "$artid, "; continue;
	//$art = $article->getArticle($id);
	$sec_name = $art['section_name'];
	$id = $art['id'];
	if ($sec_name != $last_section) {
		echo "<h2>$sec_name</h2>";
		$last_section = $sec_name;
	}
	$sdata = $container['articlea']->buildStory($id);
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

echo $calendar->build_calendar();

echo $read->news_head("Recent Activity");
$read->echo_if('recent_assets.html');

$read->echo_if ('recent_articles.html');
echo "<div style='clear:both'></div>\n";


$read->echo_if ("status_report.html", "Membership Updates");



echo "</body></html>\n";



