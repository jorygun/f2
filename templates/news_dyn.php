<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
$login->checkLevel();

#require_once "ReadNews.php";


use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\ReadNews;
use DigitalMx\Flames\FileDefs;





// contains routines needed for the news page

$pdo = $container['pdo'];
$news = $container['news'];



// get enclosing folder name
$ndir = basename(dirname(__FILE__));
if (preg_match('/(\d+)$/',$ndir,$m) ){
	$issue = $m[1];
} else { $issue = 0; #preview
}
if ( ! u\isInteger($issue )){
	die ("No issue no. in $ndir"):
}


$issue_data = $news->getIssueData($issue);
$page_title = 'Flame News';

$page_options = ['ajax'];




	$subtitle = $issue_data['title'];
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);

	echo $page->startBody(1,$subtitle);
}

$rcount = 0;
if (!empty($issue)){
	$news->increment_reads($issue);
	$rcount = $new->getReads($issue);
}

#breaking news added after publication

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

$read->echo_if('breaking.html');

$sections = $read->get_sections();
#u\echoR($sections);

$artlist = $this->news->getNewsIdsForIssue($issue);
u\echor($artlist,'artlist');
exit;

foreach ($sections as $section => $section_data){
	list ($section_name,$section_subhead) = explode ('|',$section_data);
	$section_file = 'news_' . $section . '.html';
		#echo "Getting $section_file from $section_data; ";

	if ($section_name == 'Opener'){$section_name="";}
	$read->echo_if($section_file,$section_name,$section_subhead);
}
//
// #cartoon
// $read->echo_if('news_opener.html');
//
// #site news
// $read->echo_if('news_site.html',"Site News");
//
// $read->echo_if('news_amd.html',"Our AMD");
//
// #normall news articles
// $read->echo_if('news_news.html',"Industry News");
// #ieee
// $read->echo_if('news_technology.html',"Engineering Dept.");
//
// #nostalgia
// $read->echo_if('news_remember.html',"From the Past");
// #funny stuff
// $read->echo_if('news_know.html',"Very Interesting...");
//
// $read->echo_if('news_fun.html',"Just For Fun");
//
// #in the mailbox
// $read->echo_if('news_govt.html',"Your Government at Work"," (Reader discretion advised)"  );
//
//
//
// $read->echo_if('news_people.html',"Friends");
// $read->echo_if('news_sad.html',"Sad News");
//
// $read->echo_if('news_mail.html',"In The Mailbox");
//
//
//
// echo $read->news_head("Opportunities");
// $read->echo_if('news_opps.html','',"Business Opportunities");

$read->echo_if('profile_updates.html','Profile Updates');

$current_opps = $read->current_ops();

echo $read->news_head("Job Opportunities");
echo " <p>Any FLAMEs member can post job opportunities on the site
by clicking on the 'Opportunities' menu</p>\n";

if ($current_opps>0){
    echo
   "<p>There are $current_opps current employment openings listed. <a href='/show_opp.php' target='_blank'>Click here for details.</a>.</p>
   ";
}

$read->echo_if('opportunities.html' );




echo $read->news_head("Recent Activity");
$read->echo_if('recent_assets.html');

$read->echo_if ('recent_articles.html');
echo "<div style='clear:both'></div>\n";




$read->echo_if('calendar.html');

$read->echo_if ("status_report.html", "Membership Updates");



echo "<hr><p><small>$footer_line</small></p>";
echo "</body></html>\n";



