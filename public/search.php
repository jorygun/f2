<?php

namespace DigitalMx\Flames;
ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLevel(4)){
   $page_title = 'Flames Search';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
echo <<<EOT
	<style>
	.searchforms .sf {
		float:left;
		width:30%;
		min-width:275px;
		padding:3px;
	}
	</style>
EOT;

	echo $page->startBody();
}

//END START

	$admin = $container['membera'];
	$assets = $container['assets'];
	$asseta = $container['asseta'];
	$pdo = $container['pdo'];
	$thumbs = $container['assetv'];


if (isset($_POST['search'])){
   if ($_POST['search'] == 'Search DB'){

	$mdata = $admin->listMembers ($_POST);
	$data = [
		'mdata' => $mdata,
		'info' => 'Found ' . count ($mdata)
	];
#u\echor($data,'data found');
	echo $container['templates']->render('user_list',$data);

	}
	elseif ($_POST['search']== 'Search Assets'){

		$name = $_POST['name'];
		$alist = $assets->getAssetListByName($name);
		echo "<h4>Assets with '$name'  </h4>";;
		if (empty($alist)){
			echo "No assets found" . BRNL;
		} else {
			echo "<div class='asset-row'>";
			#u\echor($alist,'assets');
			foreach ($alist as $id){
				echo $thumbs->getAssetBlock($id,'small',false);
			}
			echo "</div><div class='clear'></div>";
		}

	}
	elseif ($_POST['search'] == 'Search News'){
	   $term = $_POST['news_name']??'';
	   $back = $_POST['back']??'';
      echo search_news($term,$back,$pdo);
	}
	else {echo "Invalid Search";}

   echo "<hr><a href='/search.php'>New Search </a>\n";
   exit;
}



elseif (isset($_GET['uid']) && $uid = $_GET['uid']){
echo "Getting profile";
    $profile_data = $ma->getProfileData($uid);
	echo  $container['templates']->render('profile', $profile_data);
	exit;
}

###########################################

function search_news($term,$back,$pdo) {

// always come here from a post

	$found = 0;
//	if (empty($term)){return "No search term";}
	echo "<h3>Search for '$term' in newsletters published in last $back years</h3>";
//echo "<p>(Note: this search requires mysql 5.x.  Code must be changed for version 8.x.)</p>";

	$term = trim($term);
	//$sterm = preg_quote($term,'/'); #escape regex specials
	$issue_count = 0;
	$last_issue = '';
	$hits = [];

/* determine which routines to use:
	for issues before 5 july 2015, use the text search
	for issues after that date, use the sql search

*/

	// find new material in db
	$term = addslashes($term);
	if ($back == 0){
		$dcompare = "'" . date('Y') . '-01-01' . "'";
	} else {
		$dcompare = "NOW() - interval $back year";
	}

	if (1 ) { // do the sql search
		$sql = "SELECT a.content,a.id, i.issue,a.title, DATE_FORMAT(i.pubdate,'%M %d, %Y') as pdate
			FROM publinks l
			INNER JOIN  articles a on a.id = l.article
			INNER JOIN issues i on l.issue = i.issue
			WHERE i.pubdate > $dcompare
				AND a.content REGEXP '[[:<:]]{$term}[[:>:]]'
	/*			AND a.content REGEXP '\\\b{$term}\\\b' */
			ORDER BY pubdate DESC
			";
			/* mysql < 8.0.4 uses the old style char classes;
				later versions use the more standard escaped chars.
				The newer version also supports unicode and mb chars and the old
				one doesn't
			*/
	//	echo $sql . BR;
	#echo "<p>(Note: this search requires mysql 5.x.  Code must be changed for version 8.x.)</p>";
		$selected = $pdo -> query($sql)->fetchAll();
		//u\echor($selected);


		foreach ($selected as $data) {
		//u\echor($data);

			// set vars
			$aid = $data['id'];
			echo "<p> In <a href='/get-article.php?id=$aid' target='_blank'>" . $data['title'] . "</a> (" . $data['pdate'] . ')</p>';

			echo show_matches($term,$data['content']);
		}
	}
// for text files
	if ($back > 5) {
		echo "<p><b><i>Newsletters below this point are not divided into articles.</i></b></p><hr>" . NL;

		$sql = "SELECT issue,url,DATE_FORMAT(pubdate,'%M %d, %Y') as pdate from issues
			WHERE pubdate < '2015-07-05'
				;";
		$issuest = $pdo->query($sql)->fetchAll();

		foreach ($issuest as $issuedata) {
			// set vars
			$issue = $issuedata['issue'];


			if (!empty($hits) && $last_issue && $last_issue != $issue){
				// get context
				echo "<a href='$url' target='news'> Issue $last_issue ($pdate) </a>: " . BR;
				foreach ($hits as $hit){
					echo show_matches($term,file_get_contents($hit));

				}
				echo BRNL;
				++$issue_count;
				$hits = [];
			}
	// set these after the new issue test
			$url = $issuedata['url'];
			$pdate = $issuedata['pdate'];
			$search_path = SITE_PATH. $url; // file or  folder

			$filesrch = "grep -iRl --include '*.html'  '$term' $search_path ";
			//$exec = "grep -iRl --include '*.html'  'springer' .* ";
			//grep -iRl --include "*.html" 'springer' .*
			//echo $exec . BRNL;
			$hit = exec($filesrch); // file with matching term
			if (!empty($hit)) {$hits[]=$hit;}
			$last_issue = $issue;

		}


	}
	echo show_news_search();

}

function show_matches ($term, $content ) {
	/* matches term in content, and return a ul
		showing 50 characters of matched line with the term
		in the middle
	*/

		$clean_content = strip_tags($content);
		$t = "<ul>";

		preg_match_all(
		'/\b' . $term . '\b/i',
		$clean_content,$m,PREG_OFFSET_CAPTURE
		);

//		u\echor($m);

		foreach ($m[0] as $match){
			$offset = $match[1];
			$st_from_end = min (strlen($clean_content) ,strlen($clean_content) - $offset +20);

			$start = strrpos($clean_content,' ',-$st_from_end); // first space before
			$phrase = substr($clean_content,$start,60);
			$phrase = preg_replace("/\b{$term}\b/i","<b>$0</b>",$phrase);

			$t .= "<li>" . $phrase;
		}
		$t .= "</ul>";
		return $t;
	}

#show search screen
echo "<div class='searchforms'>";
echo "<h3>Search For Members or Topics </h3>" . NL;
echo show_member_search();
echo show_news_search();
echo show_asset_search();
echo "</div>" . NL;
exit;
##########################

function show_news_search() {
return <<<EOT
<div class='sf'>
<h4>Locate a phrase (e.g., member name) in past newsletters</h4>

<form  method = 'POST'>
Enter the text you're looking for (NOT case-sensitive)<br> and the range
of years to search in.

Search for:
 <input type="text" name="news_name" >
 <br>

Search In
<select name='back'>
<option value=0>This year</option>
<option value="1">1 year back</option>
<option value="2">2 years back</option>
<option value="3">3 years back</option>
<option value="4">4 years back</option>
<option value="5">5 years back</option>
<option value="99">For All Time</option>
</select>

<br>
<input type=submit name='search' value='Search News'>
</form>
</div>
EOT;
}
function show_member_search() {
return <<<EOT
<div class='sf'>
<h4>Locate a Member in the Member Database</h4>
To find a member enter name or email address. Partials work. Not case sensitive. Limited to 100 found.
<form  method = 'POST'>

By name: <input type='text' name = 'name' ><br>
OR<br>
By email: <input type='text' name='email'><br>

<input type=submit name='search' value='Search DB'>
</form>
</div>
EOT;
}
function show_asset_search() {
return <<<EOT
<div class='sf'>
<h4>Locate a Member in the Photo/Asset Library</h4>
<form  method = 'POST'>

Find by name: <input type='text' name = 'name' ><br>
<input type=submit name='search' value='Search Assets'>
</form>
</div>
EOT;
}

