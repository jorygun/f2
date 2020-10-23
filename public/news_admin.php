<?php

namespace DigitalMx\Flames;

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\Publish;


	$publish = $container['publish'];
	$news = $container['news'];

$login->checkLevel(4);
$page_title = 'News Admin';
$page_options=['ajax']; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START



// set date last newsletter published;

    $ptime = $news->getLatestIssue()['last_scan'];


//get current title, if any

   $current_title = $news->getTitle(1);


// action buttons for ajax stuff
    $indexaction = f\actionButton('news_index > next','copyIndex',0,'','Done');
    $rebuildaction = f\actionButton('Rebuild','indexNews',0,'','resp');
    $updateaction = f\actionButton('Update','copyLatest',0,'','resp');
    $publishaction = f\actionButton('Publish','publish',0,'','resp');






?>


<p>To publish newsletter, follow the steps below.  The newsletter is constructed in
/news/news_next by assembling component files into that directory.  News articles are in the article database, and pressing "Build Files" will insert the article files and teasers into the news_newxt directory.  The Update Report puts a list of all member changes since last publication into news_next. When published, the files are moved to news_latest and then into the main news directory at /newsp/news_yymmdd/. Finally copy news_model into news_next to set it up for the next newsletter.</p>
<ol>
<li>Prepare and build the news article collection.<br>
<button type='button' onClick="window.open('/article_manager.php','article_manager')">News Items</button></li>
<br>

<li>Run the change reports.  This creates a report of all the member changes since the  last scan before the last newsletter was was published.  If necessary, set some other date/time.    <br>
    Since: <input type=text id='ptime' value="<?=$ptime?>">
   <button type='button' onClick = "runStatus('ptime');">Run Report</button>


<br>
<li>Set the Newsletter title<br>

    Title: <input type='text' name='title' id='title_text' placeholder = 'Title Not Set' value='<?=$current_title?>'>
    <button type='button' onClick="setTitle('title_text');">Set Title</button>


<li> Check the newsletter carefully before you publish.<br>
	<?=Publish::$previewaction?></li>

<li>Publish the newsletter.  This script assembles the pieces in news_next, places it into a directory at /news/news_latest, and copies that directory to newsp/news_yymmdd (the normal repository for news), and sets the path to that directory in news/current/index.php as a relocate command. It also rebuilds the news index.
	<br><?=$publishaction?><br>

<li>Check the latest news</a>, to make sure it published.
<button type='button' onClick="window.open('/news/current','news')">Latest News</button></li>


<li>Run the bulk email to send out the Flame News Is Ready Email. Note: the email will pull "teasers"
from the news_latest directory, so they reflect last news published.
<br><form><a href="/bulk_admin.php" target = "bulk_admin">Set up Bulk Email</a>
</form>



</ol>
<hr>
<h3>Tests</h3>

<?php
echo f\actionButton('test','test',0,'','resp');
echo f\actionButton('publish->next-to-latest ','move-next',0,'','resp');
?>

<hr>
<h3>Utilities</h3>
<p>Update News/next Index from template <?=$indexaction?></p>

<p>Copy news/latest (updated) to copy in archive <?=$updateaction?></p>


<p><b>Add a breaking news to current newsletter</b></p>
<form>
<textarea name='bnews' rows=6 cols=40 id='bnews'>
</textarea>
<button type='button' onClick=''></button>
<!-- // $publish->write_breaking(bnews); -->
</form>




</body></html>

