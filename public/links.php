<?php
#ini_set('display_errors', 1);

/* Link program;
    Used to count the clicks on links in the newsletter.
	caled with links.php?url_encoded_link
	decode link
	return location header
	increment link counter in db

*/

//BEGIN START
	require_once 'init.php';
	if (f2_security_below(0)){exit;}
//END START
	use digitalmx\MyPDO;
//require_once "scripts/news_functions.php";




$root = $_SERVER['DOCUMENT_ROOT'];



    if (isset ($_GET['url'])){
        $url = urldecode($_GET['url']);
        $article_id = $_GET['aid'];
    }
    else {
        $url = urldecode($_SERVER['QUERY_STRING']);
        $article_id = 0;
    }

    if (empty($url)){
        echo "Links.php called without a url.";
        exit;
    }

#echo "Updating $url, $article_id, $my_name<br>";


	update_link_db($url,$article_id);

echo header("Location: $url");


	exit;

	#######################

function update_link_db($url,$article_id){
		#if url exists, update it; otherwise add

       $pdo = MyPDO::instance();
        $add_user_cnt  =   (empty ($_SESSION['login']['username'])) ? 0 : 1 ;

	$sql_user = "INSERT INTO links
		    (url, article_id, count, user_count, last)
		VALUES ('$url',$article_id,1,$add_user_cnt, NOW() )
		ON DUPLICATE KEY UPDATE
		     article_id = $article_id,
		     count=count + 1,
		    user_count= user_count + $add_user_cnt,
		    last= NOW()
		   ;";
		   
      $st = $pdo -> query($sql_user);
      
}
