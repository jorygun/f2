<?php

namespace DigitalMx\Flames;
use DigitalMx as u;
    use DigitalMx\Flames as f;
#ini_set('display_errors', 1);


   /**
    *  displays an article and associated comments
    *  Call with
    *  getnews.php?id[m]
    *  --  where id is article id and m is d (for discussion also)
    *
    *  or
    *
    *  get-article.php?id=n&m=d
    *  -- (id and mode)
    *  @id int article id number
    *  @m  string  optional single character 'd'
    *
    */



//BEGIN START
    require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';



//  use DigitalMx\Flames\Definitions as Defs;
//  use DigitalMx\Flames\DocPage;
//  use DigitalMx\Flames\FileDefs;
$asseta = $container['asseta'];

$login->checkLevel(4);

 $page_title = 'News Article';
 $page_options=['votes','tiny','ajax','no-cache']; #ajax, votes, tiny

 $page = new DocPage($page_title);
 echo $page -> startHead($page_options);
 # other heading code here
 // aq1 puts retrieved asset id in asset_list field, replacing whatevers there.
 // aq ADDs the retrieved asset id to existing content.
 // use aq1 for comments (only 1 asset) and aq for article editor (multiples.)

echo  "<script src='/js/aq1.js'></script>";
//echo  "<script src='/js/aqx.js'></script>";

// prevent caching so new comments get displayed on refresh

 echo $page->startBody();


$articlea = $container['articlea'];
$templates = $container['templates'];


// have to get on_id, either from posted message or from GET
$mode = 's'; // no discussion

$show ='pops';
$user = array(
    'user_id' => $_SESSION['login']['user_id'],
    'username' => $_SESSION['login']['username'],
    );

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['on_id'] ?? 0;  // 0 shoul be error
} elseif (isset($_GET['id'])) {
    // from ?id=n&m=mode
        $id = $_GET['id'] ?? 0;
        $mode = $_GET['m']?? 's';
} else {
    // from query string ?n[m]
        $id = trim($_SERVER['QUERY_STRING']);
    if (strpos($id, 'd') !== false) {
        $mode = 'd'; #story + discusson
        $id = substr($id, 0, -1);
    } else {
        $mode = 's'; #show story

    }
}

if ($mode == 'd') {
	$show =	'comments';
}

if (u\isInteger($id) && $id > 0) {
} else {
    die("Invalid item id requested: $id");
}



// if post, add the comment and set the mode to d to display omments on return
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$params = array(
		 'user_id' => $_SESSION['login']['user_id'],
		 'username' => $_SESSION['login']['username'],
		 'on_db' => 'article',
		 'mailto' => ['commenters','contributor','editor'],
		 'single' => false,
		 'on_id' => $id,
		 'admin_note' => '',
    );
        $container['comment'] -> addComment($_POST,$params);
        $show = 'comments' ;
}

// in any event, get the article and display
// pass on the comment_params to get id, user, etc.

	/* returns array of all ldata needed to render story:
		story = html content of the story
		( from pops)
		take commments,
		take_votes,
		contributor_id
		edit_credential,
		vblock (if requested)
		cblock (comments) if requested

	*/

    $sdata = $articlea->getLiveArticle($id, $show);


     //u\echor($sdata, 'story array');
   // echo $container['templates']->render('article', $sdata);

	echo $templates->render('article',$sdata);


//EOF
