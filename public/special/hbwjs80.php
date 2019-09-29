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
   $page_title = 'Happy Birthday, Jerry!';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START
use digitalmx\flames\Comment;

#require_once "../scripts/comments.class.php";


if (isset ( $_SESSION['login']['user_id']) &&
    is_integer($user_id = $_SESSION['login']['user_id'] + 0)) {}

else{die ("Not logged in. Contact admin@amdflames.org if this is wrong.");}

$ucom = new Comment ($user_id) ;

#comment parameters
$on_db = 'spec';
$on_id = '80';
$single = true;
$mailto = 'admin@amdflames.org';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//Post data and close window
	#print_r ($_POST);

    $comment = trim($_POST['comment']);
    $r = $ucom->addComment($on_db,$on_id,$comment,$single,$mailto);


}

#do a get anyway

if (1){
		 $username = $_SESSION['login']['username'];
		#$htitle = htmlspecialchars($ucom->getTitle($on,$on_id));
		$htitle = "Happy Birthday, Jerry";

	$help = <<<EOF
	<p style='font-size:0.9em;'>Your name and email will automatically be added to your post. You can only make one post on this page, so if you
	post again, your new post will replace your previous post. If you have any problems, please just <a href="mailto:admin@amdflames.org">contact the admin</a>.</p>

EOF;

   $cform = <<<EOF
	<br><hr>
	<div style='float:left;'>
	<form method='post' >

	<p class='content'>Send a birthday greeting from $username to Jerry here:</p>
	<textarea name='comment' rows='4' cols='60'>

	</textarea>
	<input type='submit'>
	</form>
	</div>

EOF;

	$helpdiv = <<<EOF
	<div style='float:left;width:300px;margin-left:1em;'>
	<input type='button' name='ShowHelp' onclick="showDiv('help')" value="Help" />
	<div id='help' style='display:none'>$help</div>

	</div>
EOF;


 echo <<<EOT
 div class='head'>
	<img class="left" alt="AMD Flames" src="/graphics/logo-FLAMEs.gif">
	<p class='title'>Happy Birthday Jerry!<br>
	<span style='font-size:0.5em;'>(He's 80)</span>
	</p>
</div>
<hr style="width: 100%; height: 2px;clear:both;">

 <div class='toon'>
            <img src='/assets/toons/1548.jpg' width='600'>
            <p> </p><p style='font-weight:bold;text-align:center;'>Happy 80th, Jerry.</p><div class='content' style='text-align:left;'>Jerry Sanders is 80 on Monday, September 12, 2016. A legend of Silicon Valley &mdash; a man whose impact on technology is vastly larger and infinitely more important than his founding of the company that has inspired the unprecedented loyalty of thousands of &ldquo;Former Loyal AMD Employees.&rdquo;

	</div>
<p class='clear'></p>

EOT;
// echo $cform;
// echo $helpdiv;

echo "<br style='clear:both'><br>";


$carray = $ucom->getCommentsForItem($on_db,$on_id);
echo "Getting comments for $on_db, $on_id" . BRNL;

#u\echor($carray,'from getCommentsForItem');


if (!empty($carray)){
    $clist = display_comments_wjs($carray);
    echo $clist;
}

#    echo $cform;


}
exit;
########################
function display_comments_wjs($carray){
    if (empty($carray)){return '';}
    $clist =  "<div style='width:100%;background-color:#eee;padding:1em;border:1px solid #393;'>";
    foreach ($carray as $cdata){
        $ucomment = htmlentities($cdata['comment']);
            $pdate = $cdata['pdate'];
            $cuser_id = $cdata['user_id'];

            $user_contact = $cdata['user_contact'];
             $clist .= "<div class='comment_box' style='width:300px;float:left;background-color:#FFF;
             	border:1px solid #999;'>
             <p class='presource'> $user_contact  - $pdate</p>
             <p class='comment'>$ucomment</p>
             </div>\n";
    }
    $clist .= "<p class='clear'></p></div>\n";
    return $clist;
}





