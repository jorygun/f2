<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
require_once "ReadNews.php";


use digitalmx as u;
use digitalmx\flames as f;
use digitalmx\flames\ReadNews;

$read = new ReadNews();
// contains routines needed for the news page





// PUBLISH DATA INSERTED
//     \$mode = 'published';
//     \$condensed_date = '$condensed_date';
//     \$conventional_date = '$conventional_date';
//     # \$base_line = "<base href  = '$new_base'>";
//     \$publish_time = '$now';
//     \$title = '$title';
//



##############################




//PUBLISH DATA

// set up for unpublished newsletter
	$publish_time = '';
$date_code = 000000;
#test to see if newsletter has been publsihed or not
if (file_exists("publish.txt")){
   include "publish.txt"; #publish data block
    //  \$condensed_date = '$pubdate_condensed';
//     \$conventional_date = '$pubdate_ordinary';
//     \$publish_time = '$now';
//     \$title = '$title';
//     \$footer_line = 'Published at $now';
    $min_security = 2;
    $page_title = 'Flame News';

}

else {
#Set up data for preview edition
    $page_title = 'Flame News Preview';
	$min_security = 4;
	$date_code = 999999; #for updating reads count
	
}
$latest_file = u\list_recent_files (1,getcwd())[0];
$updatetime = date ("m/d/y H:i T", filectime($latest_file));
$footer_line = "Updated at $updatetime";

$page_title = "AMD Flames News $pub_date";
$preview='';

if (file_exists("title.txt")){
   $subtitle = file_get_contents('title.txt');

}
elseif (isset($conventional_date)) {
    $title = $conventional_date;
    #from the publish data block
}
else {$subtitle = '';}


$page_options = ['ajax'];


if ($login->checkLogin($min_security)){
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	echo $page->startBody(1,$subtitle);
	echo $preview;
}



echo "<hr style='width: 100%; height: 2px;clear:both;'>\n";
$read->increment_reads($date_code);

#breaking news added after publication
$read->echo_if('breaking.html');
#cartoon
$read->echo_if('news_opener.html');

#site news
$read->echo_if('news_site.html',$read->news_head("Site News"));

$read->echo_if('news_amd.html',$read->news_head("On AMD"));

#normall news articles
$read->echo_if('news_news.html',$read->news_head("Industry News"));
#ieee
$read->echo_if('news_technology.html',$read->news_head("Engineering Dept."));

#nostalgia
$read->echo_if('news_remember.html',$read->news_head("From the Past"));
#funny stuff
$read->echo_if('news_know.html',$read->news_head("Somewhat Off Topic"));
#in the mailbox
$read->echo_if('news_govt.html',$read->news_head("Government","(Optional reading for sensitive persons.  ) "));



$read->echo_if('news_people.html',$read->news_head("Friends"));
$read->echo_if('news_sad.html',$read->news_head("Sad News"));

$read->echo_if('news_mail.html',$read->news_head("In The Mailbox"));



echo $read->news_head("Opportunities");
$read->echo_if('news_opps.html',$read->news_subhead("Business Opportunities"));

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

$read->echo_if('current_articles.html');
echo "<div style='clear:both'></div>\n";

$read->echo_if ('recent_articles.html');
echo "<div style='clear:both'></div>\n";


echo "<div style='clear:both'></div>\n";

echo $read->news_head("Calendar");
echo "
<p> To add an event to the calendar,
just let the <a href='mailto:editor@amdflames.org'>editor know</a>.</p>
";

$read->echo_if('calendar.html');

echo $read->news_head("Membership Updates");
$read->echo_if ("news_updates.html" );



echo "<p><small><?=$footer_line?></small></p>";
echo "</body></html>\n";



