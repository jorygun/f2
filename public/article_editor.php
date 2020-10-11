<?php
namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
    require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

    use DigitalMx as u;
    use DigitalMx\Flames as f;
    use DigitalMx\Flames\Definitions as Defs;
    use DigitalMx\Flames\DocPage;
    use DigitalMx\Flames\FileDefs;

if ($login->checkLevel(4)) {
    $page_title = 'Article Editor';
    $page_options=['ajax','tiny']; #ajax, votes, tiny

    $page = new DocPage($page_title);
    echo $page -> startHead($page_options);
    # other heading code here
	echo  "<script src='/js/aqx.js'></script>";
    echo $page->startBody();
}

$news = $container['news'];
$article = $container['article'];
$articlea = $container['articlea'];
$member = $container['member'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post = $_POST;
    $post['take_votes'] =  (isset($post['take_votes']))? 1 : 0;
    $post['take_comments'] =  (isset($post['take_comments']))? 1 : 0;

   try {
   	$id = $article->saveArticle($post);
   } catch (Exception $e) {
   	echo "Error saving article. " . $e->getMessage();
   	echo "<button type='button'  onclick = 'history.back();'>back</button>" . BRNL;
   	exit;
   }
    $_GET['id'] = $id;
    // comment out echo line below to reload eidt window with new content
    // window.close only works if page opened with js, which it normally is.

    echo "<script>window.close()</script>\n"; exit;
}

$id = $_GET['id'] ?? 0;
if (!u\isInteger($id)){
	die ("Article ID must be an integer");
}
try {
	$adata = $article->getArticle($id);
 } catch (Exception $e) {
            echo "Article data error." . BRNL . $e->getMessage();
            echo "<button type='button'  onclick = 'history.back();'>back</button>" . BRNL;
            exit;
}
$credential = $_SESSION['level'] > 7
	|| $_SESSION['login']['user_id'] == $adata['contributor_id'];

if (!$credential) {
	echo "Permission Denied";
	echo "<button type='button'  onclick = 'history.back();'>back</button>" . BRNL;
            exit;
}

//u\echor ($adata);

$adata['Aliastext'] = Defs::getMemberAliasList();
// admin users get more choices on topics
$user_level = ($_SESSION['level'] > 6)? 'A' : 'U' ;
$mytopics = $news->getTopics($user_level);
$adata['topic_options'] = $news->buildTopicOptions($adata['topic'],$user_level);
$adata['status_options'] = u\buildOptions(Defs::$news_status, $adata['status']);
$adata['status_name'] = Defs::$news_status[$adata['status']];
// convert use_me to a string (its retreived integer in db).
$queue_select = $news->getQueueOption($adata['use_me']);
echo "qs: $queue_select" . BR;
$adata['queue_select'] = $queue_select;
$adata['queue_options' ] =
  u\buildOptions($news->getQueueOptions(), $queue_select, false);
$adata['votes_checked'] = $adata['take_votes'] ? 'checked' : '';
$adata['comments_checked'] = $adata['take_comments'] ? 'checked' : '';
$adata['contributor'] = ($adata['contributor_id'] == 0) ? $_SESSION['login']['username'] : $member->getMemberName($adata['contributor_id']);
$adata['status_name'] = Defs::$news_status[$adata['status']];
echo $container['templates']->render('article_edit', $adata);



//END START
