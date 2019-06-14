<?php
//BEGIN START
	require_once "init.php";
	if(login_security_below(7)){exit;};
//END START
$nav = new navBar(1);
$navbar = $nav -> build_menu();


	$latest_bulk = SITEPATH . "/logs/bulk_mail_logs/log_last.txt";

    // get latest published update date
	$ptime_file = SITEPATH . "/news/last_update_published.txt";
	if (file_exists($ptime_file)){
		$p_time_s = trim(file_get_contents($ptime_file));
		if(preg_match('/.*?([\d\:\-]+ [\d\:\-]+)/',$p_time_s,$m)){
			 $last_ptime = ($m[1]);
		}
	}
	else {
		echo "No valid p_time file; setting time to one week ago." . BRNL;
		$dt = new DateTime(" - 7 days");
		
		$last_ptime= $dt->format('Y-m-d');
		
		
		
	}

    //get current title, if any
    $titlefile = SITEPATH . '/news/news_next/title.txt';
    if (file_exists($titlefile)){$current_title = file_get_contents($titlefile);}
    else {$current_title = 'Title Not Set';}
    $current_title_decoded = htmlspecialchars_decode($current_title);


?>
<html>
<head>
<title>News Publisher Page</title>
<link rel='stylesheet' href='/css/flames2.css'>
<style type="text/css">
	li	{margin-bottom:1em;}
</style>
</head>

<body>
<h1>News Publisher Page</h1>


<?=$navbar?>



<p>To publish newsletter, follow the steps below.  The newsletter is constructed in
/news/news_next by assembling component files into that directory.  News articles are in the article database, and pressing "Build Files" will insert the article files and teasers into the news_newxt directory.  The Update Report puts a list of all member changes since last publication into news_next. When published, the files are moved to news_latest and then into the main news directory at /newsp/news_yymmdd/. Finally copy news_model into news_next to set it up for the next newsletter.</p>
<ol>


<li>Prepare and build the news article collection.<br>
<form><a href="/scripts/news_items.php" target='news_items'>Review/Edit News Items</a></form>


<li>Run the update report.  This creates a report of all the member changes since the  date/time of the last update report that was published. Also pulls any new opportunities from the database. If necessary, set the Last Published Time.  This simply sets the time that the update report will work against.  It is set automatically during publish, but if you want to set it to something else, you can.<br>
    <form method='get' action="/scripts/report_updates.php" target="_blank" >
   From: <input type=text name='ptime' value="<?=$last_ptime?>"><input type=submit value="Run Update Report"></form><br>

<li>Run the Calendar report. Add events and create an html and text version of the calendar for use by the
newsletter and the email. <br>
<a href="/scripts/calendar.php"  target='calendar'>Run Calendar</a><br>
<br>
<li>Set the Newsletter title<br>
    <form method='post' action='/scripts/set_title.php'>
    Title: <input type='text' name='title' value='<?=$current_title_decoded?>'><input type='submit'>
    </form>

<li> Check the newsletter carefully before you publish!!  It's hard to fix after it's published.
<form><a href="/news/news_next/" target="preview">View Next newsletter</a> </form></li>

<li>Publish the newsletter.  This script assembles the pieces in news_next, places it into a directory at /news/news_latest, and copies that directory to newsp/news_yymmdd (the normal repository for news), and sets the path to that directory in news/index.php as a relocate command. It also rebuilds the news index.
	<br><form><a href="/scripts/publish.php" target="_blank">Publish news</a></form><br>

<li>Check the latest news</a>, to make sure it published.
<form><a href="/news/" target="_blank">Latest News</a></form></li>

<li>If you are sure the newsletter published successfully, copy the model news to news_next, setting up the directory for the next issue.<br>
<!-- <form><a href='#' onclick="window.open('/scripts/copy_model_to_next.php','copy','height=200,width=400');return false;">Copy Model News to Next News</a></form> -->
<form><a href='#' onclick="window.open('/scripts/copy_model_to_next.php','copy','height=200,width=400');return false;">Copy Model News to Next News</a></form>
<br>

<li>Run the bulk email to send out the Flame News Is Ready Email. Note: the email will pull "teasers"
from the news_latest directory, so they reflect last news published.
<br><form><a href="/scripts/bulk_mail_setup.php" target = "_blank">Set up Bulk Email</a>
</form>
<a href = "/logs/bulk_mail_logs/log-last.txt" target="_blank">View Last Bulk Mail Log</a> &nbsp;&nbsp;&nbsp;

In case of emergency: <a href="#"  onclick="window.open('/scripts/abort_bulk_mail.php','abort','height=200,width=600');return false;">Abort Bulk Mail</a>

</li>


</ol>
<p><b>Add a breaking news to current newsletter</b></p>
<form method='post' action='/scripts/breaking.php'>
<textarea name='bnews' rows=6 cols=40>
</textarea>
<input type=submit>
</form>

</div>
</div>
</body></html>

