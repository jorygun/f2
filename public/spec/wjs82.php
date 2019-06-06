<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

require_once "init.php";
require_once "../scripts/read_functions.php";

if (security_below(1)){exit;}



require_once "../scripts/comments.class.php";


if (isset ( $_SESSION['user_id']) &&
    is_integer($user_id = $_SESSION['user_id'] + 0)) {}

else{die ("Not logged in. Contact admin@amdflames.org if this is wrong.");}

$ucom = new Comment ($user_id) ;

#comment parameters
$on_db = 'spec';
$on_id = '84';
$single = false;
$mailto = 'admin@amdflames.org';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//Post data and close window
	#print_r ($_POST);

    $comment = trim($_POST['comment']);
    $r = $ucom->addComment($on_db,$on_id,$comment,$single,$mailto);


}

#do a get anyway

if (1){
		 $username = $_SESSION['username'];
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="ROBOTS" content="NONE">
<meta http-equiv="PRAGMA" content="NO-CACHE">
<title>Happy Birthday</title>
<script type='text/javascript' src = '/js/f2js.js'></script>
<link rel="stylesheet" type="text/css" href="/css/news2.css">
<style type="text/css">
.content {text-align:left; margin-left:3em; margin-right:3em;}
</style>
</head>
<body>
<div class='head'>
	<img class="left" alt="AMD Flames" src="/graphics/logo-FLAMEs.gif">
	<p class='title'>Happy Birthday Jerry!<br>
	<span style='font-size:0.5em;'>(He's 82)</span>
	</p>
</div>
<hr style="width: 100%; height: 2px;clear:both;">

 <div class='toon'>
            <img src='/assets/files/4238.jpg' width='600'>
            <p> </p><p style='font-weight:bold;text-align:center;'>Happy Birthday, Jerry.</p><div class='content' style='text-align:left;'>Jerry Sanders is 82 on Monday, September 12. </div>

	</div>
<p class='clear'></p>

EOT;
// echo $cform;
// echo $helpdiv;

echo "<br style='clear:both'><br>";


$carray = $ucom->getCommentsForItem($on_db,$on_id);
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






