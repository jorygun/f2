<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';require_once "../scripts/read_functions.php";

if (security_below(1)){exit;}



require_once "../scripts/comments.class.php";


if (isset ( $_SESSION['user_id']) &&
    is_integer($user_id = $_SESSION['user_id'] + 0)) {}

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
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type='text/javascript' src = '/js/f2js.js'></script>
<link rel="stylesheet" type="text/css" href="/css/news3.css">


<style type="text/css">
.content {text-align:left; margin-left:3em; margin-right:3em;}
</style>
<title>Happy Birthday</title>
<meta http-equiv="PRAGMA" content="NO-CACHE">
</head>
<body>
<div class='head'>
	<img class="left" alt="AMD Flames" src="/graphics/logo-FLAMEs.gif">
	<p class='title'>Happy Birthday Jerry!<br>
	<span style='font-size:0.5em;'>(He's 80)</span>
	</p>
</div>
<hr style="width: 100%; height: 2px;clear:both;">

 <div class='toon'>
            <img src='/assets/toons/1548.jpg' width='600'>
            <p> </p><p style='font-weight:bold;text-align:center;'>Happy 80th, Jerry.</p><div class='content' style='text-align:left;'>Jerry Sanders is 80 on Monday, September 12, 2016. A legend of Silicon Valley &mdash; a man whose impact on technology is vastly larger and infinitely more important than his founding of the company that has inspired the unprecedented loyalty of thousands of &ldquo;Former Loyal AMD Employees.&rdquo;<br> <br>From inception, AMD was unique among the semiconductor start-ups of the late 1960s and early 1970s. We were a company based on values. Based on quality. Based on performance. Based on the recognition that people were any company&rsquo;s most valuable asset. Jerry frequently observed that anyone with sufficient resources could acquire the technology and hardware necessary to enter the microchip industry. The differentiating factor would be attracting and retaining the best and brightest people and then in creating the right environment for success.<br> <br>&ldquo;People first, products and profits will follow&rdquo; was much more than a Jerry slogan&mdash; it was the essence of AMD. Under Jerry&rsquo;s leadership, AMD implemented employee policies that inspired dedication and loyalty: The company&rsquo;s profit-sharing and stock-purchase plans were available to nearly every employee. A lifelong marketing and sales guy, Jerry built a company committed to the notion that the &quot;customer is king.&quot; He often repeated Steve Zelencik&#039;s apt observation that &quot;Intel has captives; AMD has customers.&quot;<br> <br>Competition and meritocracy were the cornerstones of the culture and remained so throughout Jerry&rsquo;s tenure.<br> <br>Jerry was tough. He was demanding and not always easy to work for.  In truth, these are the very qualities that brought out the best in those who were privileged to work closely with him. He demanded the best of himself and never expected or accepted anything less than the best of everyone on the team.<br> <br>Working for and with Jerry Sanders was an enormous privilege &mdash; the defining professional experience of a lifetime.<br> <br>So, lift a glass to The Boss and wish him a very happy 80th birthday and best wishes for many, many more.<br> <br>Elliott Sopkin<br>Vice President, Communications, 1970-1988<br> <br>John Greenagel<br>Director of Public Relations, 1984-2002</div>

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






