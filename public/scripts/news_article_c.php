#!/usr/local/bin/php
<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

#this script reproduces a single article with the flames comments attached.

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(1)){exit;}
	use digitalmx\MyPDO;
//END START


require_once "news_functions.php";
#require_once HOMEPATH . "/security/f2_disqus.php";

require_once "comments.class.php";

$pdo = MyPDO::instance();


if(isset($_GET['id'])){$item_id = $_GET['id'];}
else {echo "No article requested"; exit;}

$this_userid = $_SESSION['user_id'] + 0; #force numeric.
$ucom = new Comment($this_userid);

if (isset($_SERVER['HTTP_REFERER'])){
    $referer = $_SERVER['HTTP_REFERER'];
    preg_match("{(.*/).*$}",$referer,$match);
    $new_base = $match[1];
}
   # echo "New Base: $new_base<br>";
#find referer to set base for page so relative refs works no matter what
#directory the original article was called from


$on_db = 'article';
$on_id = $item_id;
$single = false;
$commenting_on = true;
$no_email=0;



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//Post data and close window
	#print_r ($_POST);

    $comment = trim($_POST['comment']);
    if (isset($_POST['no_email'] ) ){$no_email=1;}

    $mailto = array('editor@amdflames.org','all');
    #use admin address for testing; does not post comments.
    if (! $_POST['nopost']){

        $r = $ucom->addComment($on_db,$on_id,$comment,$single,$mailto,$no_email);

    }

    // reload page to give a clean page to prevent double submitting
    $referrer =  $_SERVER['PHP_SELF'] . "?id=$item_id";
    if ($_POST['nopost']){echo "Next: $referrer ";}
    else {
        header ("location:$referrer");
    }

}

/*
	Script to generate the an individual page for an
	article with link for discussion.
*/

$topics = array_keys($itypes);
$topics[]='news';

$these_sections = array_keys($sections); #all
#echo "<pre>1. sections \n" , var_dump ($these_sections) , "</pre>";
$show_edit = $show_schedule = 0;
$discussion_topics = array ('nostalgia'); #prevent discussion link from showing up.
$pdo = MyPDO::instance();

$sql = "SELECT * from `$itemdb` WHERE id = $item_id;";
   
     $row = $pdo->query($sql)->fetch();
    $discussion = $row['take_comments'];
     $contributor_id = $row['contributor_id'];
     $contributor_email = get_user_data_by_id ($contributor_id)[1];
     $title = $row['title'];

     
     $show_discussion=false;
     $show_edit = false;
    $stories = build_news_arrays($sql,$show_schedule,$these_sections, $show_edit,$show_discussion);

   #echo "<pre>stories \n" , var_dump($stories) , "</pre>";
    $story_text = build_news_files($stories);
   # echo "<pre>text \n" , print_r ($story_text) , "</pre>";;

    foreach (array_keys($story_text) as $key){
        if ($key != 'teaser'){$section = $key; }
    }

    $story = $story_text[$section];

    #comment parameters

$carray = $ucom->getCommentsByItem($on_db,$on_id);
$clist = $ucom->display_comments($carray,false);
$this_contributor_name = $ucom->getUserName();


$admin_note = $nopost = '';
if ($this_contributor_name == 'Admin'){
    $admin_note = "<p>(Comment from admin will not post)</p>";
    $nopost = 1;
}

$comment_standards= <<<EOT
 <div id='Standards'  style="display:none; border:2px solid green; padding:2px;"  >
<b>About Comments</b>
<p>Comments can be applied to most newsletter articles on this site.<br>
Comments will automatically include your name and will be emailed to the article's author and any previous commenters.</p>
<p>If you include a url, like <code>http://someplace/somewhere</code>, it will be displayed as a link.</p>

<p>You can include a graphic/photo that is already in the site's assets. Just type <code>[asset <i>nn</i>]</code>, where nn is the asset id.  The thumbnail (200 x 200 pixels) of the asset will be inserted. <br>

Use the <a href='/scripts/assets.php' target='assets'>Search Graphics</a> menu item to find assets.  You can also upload a new graphic from there.
 </p>

 <p>Inappropriate comments will be removed.<br>
 <i>Inappropriate</i> means:
libelous, defamatory, or degrading to other AMDers,  obscene, pornographic, sexually explicit, or vulgar,
predatory, hateful, or intended to intimidate or harass, or contains derogatory name-calling.<br>
Please don't.
</p>

<p>Thanks for participating and keeping our site a friendly place for all of us.</p>


</div>
EOT;

$cform = <<<EOT
<!-- comment form -->
	<br><hr>
	<div style='float:left;'>
	<form method='post' >
    <input type=hidden name='nopost' value='$nopost'>
    $admin_note
	<p class='content'><b>Add a comment from $this_contributor_name</b>
	<button type='button' onClick="showDiv('Standards');return false;">Comment Help</button></p>


$comment_standards
<br>
	<textarea name='comment' id='comment' rows='4' cols='60' onkeyup='stoppedTyping()'></textarea>
	<p>If other people comment on this thread, you will receive their comment by email, UNLESS you...<br>
	<input type=checkbox name="no_email" value='1'> check here to block email from other commentors.</p>
	<button type='submit' id='submit_button'  disabled >Submit Comment </button>
	</form>
	</div>
	<br style="clear:both" />
EOT;



############
$nav = new navBar(1);
$navbar = $nav -> build_menu();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="ROBOTS" content="NONE">
<meta http-equiv="PRAGMA" content="NO-CACHE">
<title>FLAMES on '<?=$title?>' </title>
<base href = '<?=$new_base?>'>
<link rel="stylesheet" type="text/css" href="/css/news3.css">
<script type='text/javascript'>
    function stoppedTyping(){
        if(document.getElementById('comment').value.length > 0) {
            document.getElementById('submit_button').disabled = false;
        } else {
            document.getElementById('submit_button').disabled = true;
        }
    }
    /* function verify(){
//         if myText is empty{
//             alert "Put some text in there!"
//             return
//         }
//         else{
//             do button functionality
//         }
    }
*/

</script>
<script type='text/javascript' src='/js/f2js.js'></script>
<style type="text/css">

</style>
</head>
<body>
<?=$navbar?>
<h3>Discuss AMD Flames Story</h3>
<?=$story?>
<?php
if ($discussion){
echo <<<EOF

<hr style="height:3px;color:green;background-color: green;">
<h2>User Comments</h2>
$clist

EOF;
if ($commenting_on){

    echo $cform;
}

}

// $rarray = $ucom->getCommentsByAge('14 day');
// echo "<p>Recent Comments</p>";
// echo display_comments($rarray,true,8);

?>

</body>
</html>
