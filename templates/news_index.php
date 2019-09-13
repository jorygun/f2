<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
require_once "ReadNews.php";


use digitalmx as u;
use digitalmx\flames as f;
use digitalmx\flames\ReadNews;
use digitalmx\flames\FileDefs;

$read = new ReadNews();
// contains routines needed for the news page





//PUBLISH DATA

// set up for unpublished newsletter
	$publish_time = $pubdate = $date_code = '';
	$page_title = 'Flame News';
#test to see if newsletter has been publsihed or not
if (file_exists(FileDefs::pubfile)){
    $min_security = 2;
    $pubdata = trim(file_get_contents(FileDefs::pubfile));
    list($pubdate,$date_code)  = explode('|',$pubdata);
   # echo "got $pubdate,$date_code" . BRNL;
}

else {
#Set up data for preview edition
	$min_security = 4;
	
}
$latest_file = u\list_recent_files (1,getcwd())[0];
$updatetime = date ("m/d/y H:i T", filectime($latest_file));
$footer_line = "Last update at $updatetime";

$subtitle = $read ->getTitle();


$page_options = ['ajax'];


if ($login->checkLogin($min_security)){
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	echo $page->startBody(1,$subtitle);
}


if ($date_code){$read->increment_reads($date_code);}

#breaking news added after publication
$read->echo_if('breaking.html');
#cartoon
$read->echo_if('news_opener.html');

#site news
$read->echo_if('news_site.html',"Site News");

$read->echo_if('news_amd.html',"On AMD");

#normall news articles
$read->echo_if('news_news.html',"Industry News");
#ieee
$read->echo_if('news_technology.html',"Engineering Dept.");

#nostalgia
$read->echo_if('news_remember.html',"From the Past");
#funny stuff
$read->echo_if('news_know.html',"Somewhat Off Topic");
#in the mailbox
$read->echo_if('news_govt.html',"Government"," (Optional reading for sensitive persons."  );



$read->echo_if('news_people.html',"Friends");
$read->echo_if('news_sad.html',"Sad News");

$read->echo_if('news_mail.html',"In The Mailbox");



echo $read->news_head("Opportunities");
$read->echo_if('news_opps.html','',"Business Opportunities");

$current_opps = $read->current_ops();

echo $read->news_subhead("Job Opportunities");
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



