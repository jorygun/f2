<?php

namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;



if ($login->checkLogin(4)){
   $page_title = 'News Admin';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START

      $dt = new \DateTime();
      $dt ->setTimeZone(new \DateTimeZone('America/Los_Angeles'));

    // get latest published update date
    if ($last_ptime = f\getLastPub() ){
      $dt->setTimestamp($last_ptime);
    } else {
		echo "Could not get last pub date. Setting to one week ago." . BRNL;
		$dt -> modify (" - 7 days");
	}
    $ptime = $dt->format('M j H:i T');

    //get current title, if any
    $titlefile = SITE_PATH . '/news/news_next/title.txt';
    if (file_exists($titlefile)){$current_title = file_get_contents($titlefile);}
    else {$current_title = 'Title Not Set';}
    $current_title_decoded = htmlspecialchars_decode($current_title);

?>


<p>To publish newsletter, follow the steps below.  The newsletter is constructed in
/news/news_next by assembling component files into that directory.  News articles are in the article database, and pressing "Build Files" will insert the article files and teasers into the news_newxt directory.  The Update Report puts a list of all member changes since last publication into news_next. When published, the files are moved to news_latest and then into the main news directory at /newsp/news_yymmdd/. Finally copy news_model into news_next to set it up for the next newsletter.</p>
<ol>


<li>Prepare and build the news article collection.<br>
<form><a href="/scripts/news_items.php" target='news_items'>Review/Edit News Items</a></form>


<li>Run the change report.  This creates a report of all the member changes since the  date/time of the last update report that was published. Also pulls any new opportunities from the database. If necessary, set the Last Published Time.  This simply sets the time that the update report will work against.  It is set automatically during publish, but if you want to set it to something else, you can.<br>
    <form method='get' action="/scripts/report_updates.php" target="_blank" >
   From: <input type=text name='ptime' value="<?=$ptime?>"><input type=submit value="Run Change Report"></form><br>

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

