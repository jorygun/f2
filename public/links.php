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

//require_once "scripts/news_functions.php";


$my_name = (isset ($_SESSION['username']))?$_SESSION['username']:'';

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

echo "Updating $url, $article_id, $my_name<br>";


	update_link_db($url,$article_id,$my_name);


header( "Location: $url");
	exit;

	#######################

function update_link_db($url,$article_id,$my_name){
		#if url exists, update it; otherwise add

       $pdo = MyPDO::instance();

        $inc_user   =   (empty ($my_name))?0:1;

   #     $now = date('Y-m-d');

	$sql_user = "INSERT INTO digitalm_db1.links
		    (url, article_id, count, user_count, last, last_user_hit, last_user)
		VALUES ('$url',$article_id,1,1, NOW(),NOW(), '$my_name')
		ON DUPLICATE KEY UPDATE
		    count=count + 1,
		    user_count= user_count + 1,
		    last= NOW(),
		    last_user_hit = NOW(),
		    article_id = $article_id,
		    last_user = '$my_name'
	;";

    $sql_nonuser = "INSERT INTO digitalm_db1.links
		    (url, article_id, count,last)
		VALUES ('$url',$article_id,1, NOW())
		ON DUPLICATE KEY UPDATE
		    count=count + 1,
		    last = NOW()
	;";

    if ($inc_user == 1){
        $st = $pdo -> query($sql_user);
      #  echo "Using user code<br>";

    }
    else {
        $st = $pdo -> query($sql_nonuser);
       # echo "Non-user code<br>";
    }


   


	}

?>
