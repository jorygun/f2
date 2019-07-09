<?php
//BEGIN START
	require_once "init.php";

	require_once "scripts/news_functions.php";

	use digitalmx\flames\DocPage;
	use digitalmx as u;
	use digitalmx\flames\Definitions as Defs;

	$pdo = MyPDO::instance();

	$page = new DocPage;
	$title = "News Contributor";
	echo $page->startHead($title, 3);
	echo $page->startBody($title ,2);

//END START

$my_id = $_SESSION['login']['user_id'];
$username = $_SESSION['login']['username'];

#$ifile='../news/news_next/newsitems.html';
$now = sql_now();
?>

<p>Use this page to create or edit a news item for the
FLAMEs News, or to comment on any unpublished articles. "Queued"
items are set for inclusion in the next newsletter.  Articles can be
queued or unqueued up until the moment the newsletter is
actually published.</p>


<h2>Personal News Contributions <?=$username?></h2>
<p><i>Note: Some images may show as broken links on this page</i></p>
<hr>
<h3 class="highlight"> Unpublished News Items from YOU: <?=$username?>
<? echo show_edit(0,'Create New Item'); ?>
</h3>
<p>(You can create new articles or edit existing ones here.)</p>
<table>

<?php
$these_sections = array_keys($sections);
$sql= "SELECT * from news_items where status NOt IN ('P','T')
    AND contributor_id = '$my_id'
     ORDER BY status
     ;";

$show_edit=TRUE;
$show_schedule = TRUE;
   $stories = build_news_arrays($sql,$show_schedule,$these_sections,$show_edit);
#echo '<pre>',print_r($stories,true),'</pre>';

if(!empty($stories)){ $story_text = build_news_files($stories);
 foreach ($these_sections as $section){
    if (array_key_exists($section,$story_text)){
        echo $story_text[$section];
    }
  }
}
else {echo "no stories";}

echo "
<h3 class='highlight'>Unpublished News Items from Anyone Else</h3>
<p>(You can view and comment on articles here.)</p>";

$sql= "SELECT * from news_items where status NOt IN ('P','T')
    AND contributor_id != '$my_id'
     ORDER BY contributor_id
     ;";

$show_edit=FALSE;
$show_schedule = TRUE;
$stories = build_news_arrays($sql,$show_schedule,$these_sections,$show_edit);
if (!empty($stories)){
    $story_text = build_news_files($stories);
     foreach ($these_sections as $section){
        if (array_key_exists($section,$story_text)){
            echo $story_text[$section];
        }
    }
}
?>

</div>

<script>
    window.onblur= function() {
        window.onfocus= function () {
            location.reload(true);
        }
    }



</script>

</body></html>

