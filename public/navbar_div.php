<?php
	use digitalmx\MyPDO;

if (isset($_SESSION['pwid'])){
  $lvl = $_SESSION['level'];
  $linkedin= $_SESSION['linkedin'];
  $username = $_SESSION['username'];
  $usertype = $_SESSION['type'];
}
else {
    $lvl = 0;
    $linkedin = '';
    $username = '(Not logged in)';
    $usertype = '';
}
function count_opps(){
    $pdo = MyPDO::instance();
    $sql = "SELECT count(*) FROM opportunities WHERE
            expired = '0000-00-00' OR
            expired > NOW();";

    $opp_rows = $pdo -> query($sql) -> fetchColumn();
    return $opp_rows;

}

#get # of opportunities
//  $expired = $row[expired];
//         	if (substr($expired,0,4)=='0000'){$expired='';}
//             $status = (empty($expired))?'Active':'Expired';

// $sql = "SELECT id FROM opportunities WHERE
//             expired = '0000-00-00' OR
//             expired > NOW();";
// $result = query($sql);
// $opp_rows = mysqli_num_rows($result);
$opp_rows = count_opps();

$opp_tag= ($opp_rows>0)? "(Currently: $opp_rows)" : '';

$framed = true; #for to force framed page
?>

<div class="navbar" id="navframe">
<h2>FLAME<em>site</em> 2</h2>
<hr>
<h4><?=$username?> <br><small><?=$usertype?></small></h4>

<ul>
<?php
 echo "<a href='/'><li style='margin-top:1em;'>Home Page</li></a>" . BRNL;
	if($lvl	>=	8){

	    echo "<h3>User Admin</h3>";
	    echo "<li><a href='/level8.php' target='_blank'>User Admin</a></li>";
	    echo "<li><a href='/info.php' target='_blank'>Site Info</a></li>";

	    }

	if($lvl	>=	7){
	    echo "<h3>News Publisher</h3>";
	    echo "<li><a href='/level7.php'>News Admin</a></li>";
	    echo "<li><a href='/scripts/news_items.php' target='newsitems'>Review Articles</a></li>";
	    echo "<li><a href='/news/news_next/' target='preview'>Preview</a></li>";
	    echo "<li><a href='/scripts/assets.php' target='assets'>Asset Manager</a></li>";
	    echo "<li><a href='/WWW/amdflames.org.html' target='_blank'>Web Stats</a></li>";


	    }

	if($lvl	>=	6){
	    echo "<h3>Authors</h3>";
	    echo "<li><a href='/level6.php'>Add/View Pending Articles</a></li>";
        echo "<li><a href='/scripts/assets.php' target='assets'>Add/Find Graphics</a></li>";
	    echo "<li><a href='/views.php' target='data'>Count of Views by Issue</a></li>";
	    echo "<li><a href='/scripts/view_links.php'  target='data'>Link Activity</a></li>";

	}




if ($lvl>0){
    echo "<h3>Member Menu</h3>\n";
    if($lvl>2){echo "<li ><a href='/scripts/profile_view.php' target='profile'>View/Edit My Profile</a></li>";}
	if($lvl>0){echo "<li style='margin-top:1em;'><a href='/news/' target='newsletter'>Latest Newsletter</a></li>";}

	if($lvl>2){echo "<li><a href='/newsp/' target='_blank'>Newsletter Index</a></li>";}
	if($lvl>2){echo "<li><a href='/scripts/search_news.php' target='_blank'>Search Newsletters</a></li>";}
	 if ($lvl>2){echo "<li><a href='/scripts/assets.php' target='assets'>Search Graphics/Video</a></li>";}
	 if($lvl>2){echo "<li><a href='/scripts/search_member.php' target='_blank'>Search For a Member</a></li>";}
    if ($lvl>2){echo "<li><a href='/galleries' target='gallery'>Photo Galleries</a></li>";}
    if ($lvl>2){echo "<li><a href='/special.php' target='special'>Special Pages</a></li>";}


	if ($linkedin){echo "<li><a href='$linkedin' target='_blank'>My LinkedIn Page</a></li>";}
    echo '<li ><a href="/?s=0">Log Out</a></li>';
 }
echo "<h3>Site</h3>\n";

	if (true){echo "<li style='margin-top:1em;'><a href='/opportunitiesE.php' target='_blank'>Opportunities $opp_tag</a></li>";}

    echo "<li ><a href='http://www.linkedin.com/groups?gid=117629&trk=myg_ugrp_ovr' target='_blank'>AMD Alumni on LinkedIn</a></li>";



?>



<li><a href="/help.html">Help</a></li>
<li><a href="/about.php" target='about'>Support/About This Site</a></li>
<li><a href='mailto:admin@amdflames.org'>When all else fails, email the admin</a></li>
</ul>
<p>.....<?=$lvl?>.....</p>

</div>


