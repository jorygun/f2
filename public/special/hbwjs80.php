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



if ($login->checkLogin(4)){
   $page_title = 'Happy Birthday, Jerry!';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START
use DigitalMx\Flames\Comment;

#require_once "../scripts/comments.class.php";



#comment parameters
$comment_params = array (
   'on_db' => 'spec',
   'on_id' => '80',
   'single' => true,
   'mailto' => [],
   'user_id' => $_SESSION['login']['user_id'],
   'enabled' => false,
);


$ucom = new Comment($container);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//Post data and close window
	#print_r ($_POST);

    $comment = trim($_POST['comment']);
    $r = $ucom->addComment($_POST, $comment_params);


}

#do a get anyway


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


//  echo <<<EOT
//  div class='head'>
// 	<img class="left" alt="AMD Flames" src="/assets/graphics/logo-FLAMEs.gif">
// 	<p class='title'>Happy Birthday Jerry!<br>
// 	<span style='font-size:0.5em;'>(He's 80)</span>
// 	</p>
// </div>
// EOT;


 echo <<<EOT
 <hr style="width: 100%; height: 2px;clear:both;">
 <div class='toon'>
            <img src='/assets/graphics/misc/sanders.jpg' width='600'>
            <p> </p><p style='font-weight:bold;text-align:center;'>Happy 80th, Jerry.</p><div class='content' style='text-align:left;'>Jerry Sanders is 80 on Monday, September 12, 2016. A legend of Silicon Valley &mdash; a man whose impact on technology is vastly larger and infinitely more important than his founding of the company that has inspired the unprecedented loyalty of thousands of &ldquo;Former Loyal AMD Employees.&rdquo;<br> <br>From inception, AMD was unique among the semiconductor start-ups of the late 1960s and early 1970s. We were a company based on values. Based on quality. Based on performance. Based on the recognition that people were any company&rsquo;s most valuable asset. Jerry frequently observed that anyone with sufficient resources could acquire the technology and hardware necessary to enter the microchip industry. The differentiating factor would be attracting and retaining the best and brightest people and then in creating the right environment for success.<br> <br>&ldquo;People first, products and profits will follow&rdquo; was much more than a Jerry slogan&mdash; it was the essence of AMD. Under Jerry&rsquo;s leadership, AMD implemented employee policies that inspired dedication and loyalty: The company&rsquo;s profit-sharing and stock-purchase plans were available to nearly every employee. A lifelong marketing and sales guy, Jerry built a company committed to the notion that the &quot;customer is king.&quot; He often repeated Steve Zelencik&#039;s apt observation that &quot;Intel has captives; AMD has customers.&quot;<br> <br>Competition and meritocracy were the cornerstones of the culture and remained so throughout Jerry&rsquo;s tenure.<br> <br>Jerry was tough. He was demanding and not always easy to work for.  In truth, these are the very qualities that brought out the best in those who were privileged to work closely with him. He demanded the best of himself and never expected or accepted anything less than the best of everyone on the team.<br> <br>Working for and with Jerry Sanders was an enormous privilege &mdash; the defining professional experience of a lifetime.<br> <br>So, lift a glass to The Boss and wish him a very happy 80th birthday and best wishes for many, many more.<br> <br>Elliott Sopkin<br>Vice President, Communications, 1970-1988<br> <br>John Greenagel<br>Director of Public Relations, 1984-2002</div>

	</div>
<p class='clear'></p>

EOT;
// echo $cform;
// echo $helpdiv;

echo "<br style='clear:both'><br>";

$on_db = 'spec_items';
$carray = $ucom->getComments(80,'spec');

//echo "Getting " . count($carray) . " comments for $on_db, $on_id" . BRNL;

//u\echor($carray,'from getCommentsForItem');


if (!empty($carray)){
    echo display_comments_wjs($carray);

}

if ($comment_params['enabled']) {echo $cform;}

echo "</body></html>\n\n";

exit;
########################
function display_comments_wjs($carray){
    if (empty($carray)){return '';}

    $clist =  "<div style='width:100%;background-color:#eee; border:1px solid #393; '>\n";

    foreach ($carray as $cdata){
        $ucomment = htmlentities($cdata['comment']);
			$pdate = $cdata['pdate'];
			$cuser_id = $cdata['user_id'];
			$user_contact = $cdata['username'];

         $clist .= "
             <div class='comment_box' style='width:28%;
      display:inline-block;'>
         <p class='presource'> $user_contact  - $pdate</p>
             <p class='comment'>$ucomment</p>
             </div>
             ";

    }

    $clist .= "</div>\n";
    return $clist;
}






