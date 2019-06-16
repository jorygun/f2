<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
require_once "read_functions.php";


#ini_set('display_errors', 1);

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


$subtitle = '';
$preview='';
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
    $preview = "<p class='centered'>(Preview Edition)</p>";
    $page_title = 'Flame News Preview';
	$min_security = 4;
	$condensed_date = 999999; #for updating reads count

}
$latest_file = get_recent_files (1, getcwd());
$updatetime = date ("m/d/y H:i T", filectime($latest_file));
$footer_line = "Updated at $updatetime";


if (file_exists("title.txt")){
   $subtitle = file_get_contents('title.txt');

}
else {
    $subtitle = $conventional_date;
    #from the publish data block
}



// can't check security until it's been set above.
	if (security_below($min_security)){exit;}


increment_reads($condensed_date);
$nav = new navBar(false);
$navbar = $nav -> build_menu();
$voting = new Voting();

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type='text/javascript' src = '/js/f2js.js'></script>
<link rel="stylesheet" type="text/css" href="/css/news3.css">

<title><?=$page_title?></title>


<link rel='stylesheet' href='/css/votes.css' />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js">
</script>
<script src='/js/voting3.js'></script>
</head>
<body>

<div class='head'>
	<img class="left" alt="AMD Flames" src="/graphics/logo-FLAMEs.gif">
	<p class='title'>FLAME<i>news</i><br>
	<span style='font-size:0.5em;'><?=$subtitle?></span>
	</p>
</div>
<?=$preview?>
<hr style="width: 100%; height: 2px;clear:both;">

<?

echo $navbar;




#breaking news added after publication
echo_if('breaking.html');
#cartoon
echo_if('news_opener.html');

#site news
echo_if('news_site.html',news_head("Site News"));

echo_if('news_amd.html',news_head("On AMD"));

#normall news articles
echo_if('news_news.html',news_head("Industry News"));
#ieee
echo_if('news_technology.html',news_head("Engineering Dept."));

#nostalgia
echo_if('news_remember.html',news_head("From the Past"));
#funny stuff
echo_if('news_know.html',news_head("Somewhat Off Topic"));
#in the mailbox
echo_if('news_govt.html',news_head("Government","(Optional reading for sensitive persons.  ) "));



echo_if('news_people.html',news_head("Friends"));
echo_if('news_sad.html',news_head("Sad News"));

echo_if('news_mail.html',news_head("In The Mailbox"));



echo news_head("Opportunities");
echo_if('news_opps.html',news_subhead("Business Opportunities"));

$current_opps = current_ops();
echo news_subhead("Job Opportunities");
echo " <p>Any FLAMEs member can post job opportunities on the site
by clicking on the 'Opportunities' menu</p>\n";
if ($current_opps>0){
    echo
   "<p>There are $current_opps current employment openings listed. <a href='/opportunitiesE.php' target='_blank'>Click here for details.</a>.</p>
   ";
}

echo_if('opportunities.html' );




echo news_head("Recent Activity");
echo_if('recent_assets.html');

echo_if('current_articles.html');
echo "<div style='clear:both'></div>\n";

echo_if ('recent_articles.html');
echo "<div style='clear:both'></div>\n";


echo "<div style='clear:both'></div>\n";

echo news_head("Calendar");
echo "
<p> To add an event to the calendar,
just let the <a href='mailto:editor@amdflames.org'>editor know</a>.</p>
";

echo_if('calendar.html');

echo_if ("updates.html", news_head("Membership Updates"));

echo  get_slogan();

echo "<p><small><?=$footer_line?></small></p>";


?>


</body></html>
