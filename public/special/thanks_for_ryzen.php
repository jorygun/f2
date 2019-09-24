<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);
#require_once "/usr/home/digitalm/public_html/amdflames.org/ap-functions.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once "../scripts/read_functions.php";

if (security_below(-1)){exit;}

require_once "../scripts/comments.class.php";

if (isset ( $_SESSION['user_id']) &&
    is_integer($user_id = $_SESSION['user_id'] + 0)) {}
else {$user_id = 0;}
$ucom = new Comment ($user_id) ;


#comment parameters
$on_db = 'spec';
$on_id = '81';
$single = true;
$mailto = ['admin@amdflames.org'];
$enable_comments = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//Post data and close window
	#print_r ($_POST);
    if (isset ( $_SESSION['user_id'])
    && is_integer($user_id = $_SESSION['user_id'] + 0)) {

        $comment = trim($_POST['comment']);
        $r = $ucom->addComment($on_db,$on_id,$comment,$single,$mailto);
    }
    else {
        echo "Error: Attempt to post without being logged in.";
            exit;
        }


}

#do a get anyway

if (1){
		 $username = $_SESSION['username'];
		#$htitle = htmlspecialchars($ucom->getTitle($on,$on_id));
		$htitle = "Congratulations, AMD!";

	$help = <<<EOF
	<p style='font-size:0.9em;'>Your name and AMD position (from your profile) will automatically be added to your post. You can only make one post on this page, so if you
	post again, your new post will replace your previous post. If you have any problems, please just <a href="mailto:admin@amdflames.org">contact the admin</a>.</p>

EOF;

   $cform = <<<EOF
	<br><hr>
	<div style='float:left;'>
	<form method='post' >

	<p class='content'>Send your congratulations to AMD: (max 500 chars)</p>
	<textarea name='comment' rows='5' cols='100' maxlength='500'>

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
<title>Congratulations, AMDers</title>
<script type='text/javascript' src = '/js/f2js.js'></script>
<link rel="stylesheet" type="text/css" href="/css/news3.css">
<style type="text/css">
.content {text-align:left; margin-left:3em; margin-right:3em;}
.local_comment {
    font-size:1em;

    width:800px; background-color:#FFF;
    border:1px solid #999;
    padding-left:1em; padding-right:1em;
    }
.local_commenter {
    text-align:right;
    font-size:0.8em;
    font-style:italic;
}
.flame_note {
    left-margin:auto;right-margin:auto;
    width:800px;
    padding:1em;
    border: 1px solid #0f0;
    font-size: 0.8em;
}

</style>
</head>
<body>
<div class='head'>
	<img class="left" alt="AMD Flames" src="/graphics/logo-FLAMEs.gif">
	<p class='title'>Congratulations, AMDers!<br>


	</p>
	<p>[Should we do this page?  Tell me what you think.  Send comments to
<a href='mailto:editor@amdflames.org'>editor@amdflames.org</a>.]</p>
</div>
<hr style="width: 100%; height: 2px;clear:both;">

 <div class='toon' style='width:800px'>
        <img src='/assets/toons/3911.jpg' width='600'>
        <p style="font-size:0.8em;font-style:italic;">"AMD's Ryzen - One of the most important desktop processor launches in 10 years" - Tech Deals</p>



        <h3 class='centered'>Well Done.</h3>
        <div style='text-align:left' >
<p>We're the 2000 members of AMD Flames* - an AMD Alumni Group that was started in 1997. Ryzen has really got us excited, and we wanted to send some congratulatory messages from old ex-AMDers to the new crop of AMDers.</p>


<p>When we worked at AMD it was a magical time.  We worked hard, designed great
products, made them with advanced technology, and we outsold everyone. (Almost.)
We also had legendary parties, stirring sales conferences, and we gave away a house to an employee in 1970-something.  We were the best.
</p>
<p>
You folks have once-again kindled the spirit of AMD that we felt years ago.  We can see the pride and excitement in the interviews, product literature, and launch events.
</p>
<p>We've asked our members to leave you some comments, and they are presented below. We hope you enjoy hearing them.  We are so proud of you.
</p>

<p class='flame_note'>* FLAMEs stands for "Former Loyal AMD Employees".  We stay in touch with a newsletter and occasional get-togethers and enjoy sharing stories about technology, fast cars, and the best place any of us ever worked - AMD. <br><br>
The FLAME group was started in 1997 by John McKean, the Field Application Engineer in Toronto.
 </p>
</div>
</div>

<p class='clear'></p>

EOT;


if (!$enable_comments) {echo "Commenting disabled.";}
elseif ($_SESSION['level'] <1){echo "Must be logged in to comment.";}
else {
    echo $cform;
    echo $helpdiv;
    }




echo "<br style='clear:both'><br>";


$carray = $ucom->getCommentsByItem($on_db,$on_id);
if (!empty($carray)){
    $clist = display_comments_ryzen($carray);
    echo $clist;
}

#    echo $cform;

#build user list
//     $clist = '';
//     $carray = $ucom->getCommentsByUser($user_id,'asset');
//     if (false && !empty($carray)){
//       $clist = display_comments($carray);
//         echo $clist;
//      }
}

exit;
########################
function display_comments_ryzen($carray){
    if (empty($carray)){return '';}
    $clist =  "<div style='width:800px;background-color:#eee;padding:1em;border:1px solid #393;'>";
    foreach ($carray as $cdata){
        $ucomment = htmlentities($cdata['comment']);
            $pdate = $cdata['pdate'];
            $cuser_id = $cdata['user_id'];
            $cuser_from = $cdata['user_from'];
            $cuser_amd = $cdata['user_amd'];
            $cusername = $cdata['username'];
            $comment_length = strlen($ucomment);

            $user_contact = $cdata['user_contact'];
             $clist .= <<<EOT
             <div class='local_comment'>

             <p >$ucomment</p>
             <p class='local_commenter'>$cusername - $cuser_from<br>$cuser_amd</p>

             </div>
EOT;
    }
    $clist .= "<p class='clear'></p></div>\n";
    return $clist;
}






