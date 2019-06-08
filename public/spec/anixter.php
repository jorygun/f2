<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);
#require_once "/usr/home/digitalm/public_html/amdflames.org/ap-functions.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
require_once "../scripts/read_functions.php";

if (security_below(0)){exit;}

require_once "../scripts/comments.class.php";

if (isset ( $_SESSION['user_id']) &&
    is_integer($user_id = $_SESSION['user_id'] + 0)) {
        $username = $_SESSION['username'];

}
else {$user_id = 0;$username = '';}
$ucom = new Comment ($user_id) ;


#comment parameters
$on_db = 'spec';
$on_id = '82';
$single = true;
$mailto = ['admin@amdflames.org'];
$enable_comments = true;

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

		#$htitle = htmlspecialchars($ucom->getTitle($on,$on_id));
		$htitle = "Ben Anixter";

	$help = <<<EOF
	<p style='font-size:0.9em;'>Your name and AMD position (from your profile) will automatically be added to your post. You can only make one post on this page, so if you
	post again, your new post will replace your previous post. If you have any problems, please just <a href="mailto:admin@amdflames.org">contact the admin</a>.</p>

EOF;

   $cform = <<<EOF
	<br><hr>
	<div style='float:left;'>
	<form method='post' >

	<p class='content'>Add or replace your rememberance of Ben. You are posting as $username. (max 600 chars)</p>
	<textarea name='comment' rows='6' cols='100' maxlength='900'></textarea>
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

<title>Ben Anixter</title>

<style type="text/css">
.content {text-align:left; margin-left:3em; margin-right:3em;}
.local_comment {
    font-size:1em;

    width:750px; background-color:#FFF;
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
	<p class='title'>Ben Anixter<br>

	</p>
</div>
<hr style="width: 100%; height: 2px;clear:both;">

 <div class='toon' style='width:800px'>
        <img src='/assets/files/4003.jpg' >
        <p style="font-size:0.8em;font-style:italic;">(photo courtesty Elliott Sopkin.) </p>



        <h3 class='centered'>Nobody Did It Better.</h3>
        <div style='text-align:left' >
<p>
Our great friend and colleague, Ben Anixter, passed away July 30, 2017 at his home.
</p><p>
Flames members can post their rememberances here.  At the appropriate time, we will compile all of the messages and forward them to Ben's family.
</p>


<p>Like most AMDer's, Ben loved the showmanship of the annual sales conference.
One of the presentations he was most proud of producing is this one, <a href='/asset_display.php?3809' target='_blank'>"Coming to America"</a>,
from the 1981 sales conference. It was a 16-projector slide show. It was magnificent. Enjoy!  (Hat tip to Barry Fitzgerald for supplying the VHS tape.)</p>

<p>There's lots of pictures of Ben on the site.  Go to the <a href='/scripts/assets.php' target='_blank'>Asset manager</a>, and enter "Anixter" in the search field.</p>

<div style='border:2px solid black;margin:1em;padding:1em; font-family:bookman,times,serif; font-size:0.9em;'>
<h4 class='center'>Benjamin Martin Anixter</h4>
<p>
Hillsborough, Calif. Aug 1, 2017 ---- Ben Anixter, a Silicon Valley pioneer, who turned marketing semiconductors into the high competition he embraced in all aspects of his life, especially in athletics, lost his race with cancer Sunday and died in his home surrounded by his family. He was 79.
</p><p>
Benjamin Martin Anixter, the son of Simon and Leslie Anixter, was born in San Francisco. The family moved to Kentfield in Marin County when he was 12 years old. He went to Drake High School where he ran track specializing in 100- and 200-yard sprints. At 15, he began playing golf at the Lake Merced Country Club with his lifelong friends.
</p><p>
He graduated from high school, matriculated to Stanford University where he earned both a bachelor’s and master’s degrees in electrical engineering. Joining the track team at Stanford he continued to run the 100- and 200-yard track events, but this time under the guidance of legendary U.S. Olympic team coach Payton Jordan . In the early 1960s, Anixter joined Fairchild Semiconductor, a division of Fairchild Camera and Instrument Corp. He first was a marketing manager for diodes. After several years working out of Fairchild’s Hollywood office, Anixter moved to the firm’s Mountain View headquarters, where he was to head all digital product marketing. He left Fairchild and along with fellow Fairchild marketers John Bosch and Gordon Russell and formed Anixter, Bosch and Russell. The firm consulted with smaller electronics companies.
</p><p>
In 1971 Anixter picked up Advanced Micro Devices’ badge #260 and became the fledgling firm’s director of marketing for digital products. In the1980s his functions were expanded to include all product-support activities such as public relations and advertising. He was then assigned government-related activities, including working with the Semiconductor Industry Association (SIA) in its lobbying efforts in Washington. He was also put in charge of the firm’s charitable-giving program. He was named Vice President for External Affairs. Anixter spent more than 30 years at Advanced Micro Devices (AMD).
</p><p>
W.J. (Jerry) Sanders III, founder and chief executive officer of AMD, noted that he met “Ben more than 50 years ago and he instantly won my respect and friendship. Until our mutual retirement we worked together as colleagues and friends. No one could be a better or truer friend and teammate,” said Sanders. “His unassailable character and discipline coupled with his devotion to family and friends will be a cherished memory. He was a man that stood strong with his convictions."
</p><p>
Steve Zelencik, AMD’s former senior vice president of sales, worked with Anixter from the early days at Fairchild. “Benny was always true to himself and to his principles -- whether he was right or wrong, and, of course, Benny was never wrong,” Zelencik said. “Anixter was extremely loyal to his friends, his company, and above all else to his family.”
</p><p>
AMD’s former public relations chief, John Greenagel, said “Ben had a reputation of being kind of like great French bread -- crusty on the outside but soft and warm on the inside.”
</p><p>
Anne Craib, who worked with Anixter in the SIA, remembers some of Anixter’s common sayings: “Do the right thing, even if it's hard -- and even when no one is watching;” and: “You can't win if you don't play.” And when someone tried to pull a fast one: “They've been trying to get away with that since I had hair.”
</p><p>
Anixter was generous. Among his favorites, was the effort in research in cancer and blood diseases at the Lucile Packard Children’s Hospital at Stanford, the university’s track and field department and the Jewish Home of San Francisco.
</p><p>
The Anixters are members of the Peninsula Temple Sholom in Burlingame. Ben was a long-time committee member for the annual golf tournament for the Jewish Home of San Francisco, where he also served on its board of directors.
</p><p>
“What’s the real take-away?” Ben Anixter the consummate marketing man would ask: He was a proud American. He was moral, ethical and highly principled. A teacher to many, he was always learning—from history classes, books, newspapers and people. For years after college, Anixter was a competitive sprinter, and, when his knees got older than his spirit, he switched to roller blading. He then graduated to swimming and he continued playing golf. Ben was an exceptional father who respected his children as individuals. He loved the San Francisco Giants; he was a highly talented athlete, and he was a good friend of Israel. And, he hated garlic.
</p><p>
Anixter is survived by his wife, Patricia Fischer Anixter, and his sister, Katherine Anixter Browning and her children, Jason and Adam; his children, Shelley Jane Anixter, Jeffrey Tod Anixter, and his wife Gina, Martin Beldin Anixter, Simon Benjamin Anixter and Harrison David Anixter; his grandchildren Aly and Natalie Anixter and Abigail and Julia Kravec. Anixter is also survived by his cousins Louis (Bill) Honig, Sue Honig, Joseph Nadel, Leslie Flemming and Johnny Davis.
</p><p>
Funeral services will begin at noon, Thursday, at Peninsula Temple Sholom, 1655 Sebastian Drive, Burlingame, California 94010 In lieu of flowers, the family suggests a memorial contribution to Lucile Packard Children’s Hospital (LPFCH.org ) directed to the Weinberg Stem Cell Laboratory or The Jewish Home of San Francisco (JHSF.org).
</p>
</div>
</div>
</div>

<p class='clear'></p>

EOT;


if (!$enable_comments) {echo "Commenting disabled.";}
elseif (! isset ($_SESSION['level'] ) || $_SESSION['level'] <1){echo "Must be logged in to comment.";}
else {
    echo $cform;
    echo $helpdiv;
    }




echo "<br style='clear:both'><br>";


$carray = $ucom->getCommentsByItem($on_db,$on_id);
if (!empty($carray)){
    $clist = display_comments_benji($carray);
    echo $clist;
}


}
exit;
########################
function display_comments_benji($carray){
    if (empty($carray)){return '';}
    $clist =  "<div style='width:800px;background-color:#eee;padding:1em;border:1px solid #393;'>";
    foreach ($carray as $cdata){
        $ucomment = nl2br($cdata['comment']);
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






