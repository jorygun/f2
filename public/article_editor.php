<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	


if ($login->checkLogin(4)){
   $page_title = 'Article Editor';
	$page_options=['ajax','tiny']; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	$news = new News();
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST' ){	
		$post = $_POST;
		$post['take_votes'] =  (isset($post['take_votes']))? 1 : 0;
		$post['take_comments'] =  (isset($post['take_comments']))? 1 : 0;
		 $id = $news->saveArticle($post);
		$_GET['id'] = $id;
	}
	
		$id = $_GET['id'] ?? 0;
		$adata = $news->getArticle($id);
		$adata['Aliastext'] = Defs::getMemberAliasList();
		// admin users get more choices on topics
		$user_level = ($_SESSION['level'] > 4)? 'A' : 'U' ;
		$mytopics = $news->getTopics($user_level);
		$adata['topic_options'] = u\buildOptions($mytopics,$adata['topic']);
		$adata['status_options'] = u\buildOptions(Defs::$news_status,$adata['status']);
		$adata['status_name'] = Defs::$news_status[$adata['status']];
		// convert use_me to a string (its retreived integer in db).
		$queue_select = $news->getQueueOptions($adata['use_me']);
		$adata['queue_options' ] = 
			u\buildOptions($news->getQueueOptions(),$queue_select,false);
		$adata['votes_checked'] = $adata['take_votes'] ? 'checked' : '';
		$adata['comments_checked'] = $adata['take_comments'] ? 'checked' : '';
		
		echo $templates->render('article_edit',$adata);
		
	
		
//END START
