<?php

namespace DigitalMx\Flames;

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

    use DigitalMx as u;
    use DigitalMx\Flames as f;

//  use DigitalMx\Flames\Definitions as Defs;
//  use DigitalMx\Flames\DocPage;
//  use DigitalMx\Flames\FileDefs;

if ($login->checkLogin(4)) {
    $page_title = 'News Article';
    $page_options=['votes','tiny']; #ajax, votes, tiny

    $page = new DocPage($page_title);
    echo $page -> startHead($page_options);
    # other heading code here

    echo $page->startBody();
}

$comment_params = array(
    'user_id' => $_SESSION['login']['user_id'],
    'username' => $_SESSION['login']['username'],
    'on_db' => 'article',
    'mailto' => ['commenters','contributor','editor',13105],
    'single' => false,
    'on_id' => 0,
    );

// have to get on_id, either from posted message or from GET
$mode = 's'; // no discussion

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $on_id = $_POST['on_id'] ?? 0;
} elseif (isset($_GET['id'])) {
    // from ?id=n&m=mode
        $on_id = $_GET['id'] ?? 0;
        $mode = $_GET['m']?? 's';
} else {
    // from query string ?n[m]
        $id = trim($_SERVER['QUERY_STRING']);
    if (strpos($id, 'd') !== false) {
        $mode = 'd'; #story + discusson
        $on_id = substr($id, 0, -1);
    } else {
        $mode = 's'; #show story
        $on_id = $id;
    }
}
$comment_params['on_id'] = $on_id;

if (u\isInteger($on_id) && $on_id > 0) {
} else {
    die("Invalid item id requested: $on_id");
}

$ucom = new Comment($container);


// if post, add the comment and set the mode to d to display omments on return
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $ucom -> addComment($_POST,$comment_params);
        $mode='d';
}

// in any event, get the article and display

    $container['article']->getArticle($on_id);
    $na = $container['article']->buildStory();
    $na['credential'] =
    	$_SESSION['level'] > 4
    	|| $na['contributor_id'] == $_SESSION['login']['user_id'];

    $na['mode'] = $mode; //if 's' ,displays voting block after article

    # u\echor($na, 'story array');
    echo $container['templates']->render('article', $na);

// if mode == 'd' ,display comment section (but not voting block)
if ($mode == 'd') {
    $carray = $ucom->getComments($comment_params);
     echo "<div class='comment_background'>
         <h2>Reader Comments</h2>
         ";
    if (empty($carray)) {
        echo "No Comments Yet";
    } else {
        foreach ($carray as $row) {
            echo $container['templates']->render('comment', $row);
        }
    }
        echo "</div>" . NL;

    if ($na['take_comments']) {
        echo "<hr>";
        $nc['username'] = $comment_params['username'];
			$nc['admin_note'] = '';
			$nc['on_id'] = $on_id;
        echo $container['templates']->render('new_comment', $nc);
    }
}

//EOF
