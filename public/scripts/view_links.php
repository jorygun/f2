<?php


// ini_set('error_reporting', E_ALL);


/*Linkview reports activity in the links database.
  First runs the link updater to get titles if some missing
  Then reports activity in the last 2 weeks

*/

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;


$daysback = 30; #number of days back to look.

	echo <<<EOT
<html>
<head>
<meta charset="utf-8" />
<title>Link Report</title>
<style type='text/css'>
table {border-collapse: collapse;
   ;
    }
table,td,th {
		border:1px solid gray;
		padding:3px;
	}
</style>
</head>
<body>
<h3>Link Clicks in last $daysback days</h3>
<p>This page reports the number of clicks on links in the newsletter in the last $daysback days. That is, if any newsletter link hs been clicked on in the last 30 days, it will show up here, along with the total numbers of clicks since publication. This was added on July 3, 2016, as a way to measure what links members are most interested in.  There is no recording  of who clicked what.</p> <p>This script also checks urls and adds the title if it was not previously known. </p>

EOT;


  update_titles_in_links();

	show_links ($daysback);



	echo "</body></html>";

	exit;

	####################
function show_links($daysback) {
	echo "<p><b>Links in last $daysback days</b></p>\n";
	
	$pdo = MyPDO::instance();

		$sql = "Select l.url,l.count,l.user_count,l.title as linktitle,l.article_id, l.last_user_hit,
			 n.title as itemtitle,n.date_published
			 from `links` l
			 LEFT JOIN `news_items` n
			 ON l.article_id = n.id
			 WHERE l.last_user_hit > NOW() -  interval $daysback day

			ORDER BY date_published DESC,user_count DESC;";

		$result = $pdo->query($sql);

		if ($result){
			echo "<table style='width:800px;table-layout:fixed;'>
				<tr><th>Link</th><th>From Article</th><th>Published</th><th>Clicks user (total)</th></tr>
				";
			while ($row = $result->fetch() ){
				$url = $row['url'];
				$title = substr(htmlentities(stripslashes(trim($row['linktitle']))),0,100);
				if (empty($title)){$title = $url;} #shouldn't ever happen
				$link = "<a href='$url'>$title</a>";
				$source = "<a href='/scripts/news_article_c.php?id=${row['article_id']}'>${row['itemtitle']}</a> ";
				$pub_date =  $row['date_published'];
				echo "
					<tr>
					<td >$link </td>
					<td >$source</td>
					<td>$pub_date</td>
					<td style='text-align:center;'>${row['user_count']} (${row['count']})</td>

					</tr>
					";
			}
			echo "</table>";
		}
		else {echo "<p>No Results</p>";}
}

function get_url_title ($url,$article=0) {
	$title = $webdoc = '';

	#$article = 0;
	$pdo = MyPDO::instance();

// try getting title from article database
	if ($article > 0){
		$linksql = "SELECT `link_title` FROM 	`news_items` where id = $article limit 1;";
	}
	else {
		$linksql = "SELECT `link_title` FROM 	`news_items` where url = '$url' limit 1;";
	}
		#echo "SQL: $linksql";

	$title = $pdo -> query ($linksql) -> fetchColumn();

		#echo "From article: $title";

	if (empty($title)){
		#echo "<pre>Retrieving title from $url\n" ;
	#retrieve the url
		#list($url1, $url2) = explode ("?",$url);
		#$url = $url1 . "?" . urlencode($url2);
		#echo "Getting $url\n";
		#$webdoc = file_get_contents($url) ;
		$webdoca = get_url_data($url);

		// if(! $webdoca){echo "Nothing returned";}
// 		else {recho($webdoca);}
// 		exit;

		if (! $webdoc = $webdoca['content']) {
			#echo "</pre> Cannot Retrieve Document" . BRNL;
			return "Cannot Retrieve Document at $url";
	    }

		$doc = new DOMDocument();

		@($doc -> loadHTML($webdoc) );
		# $doc->loadHTML(mb_convert_encoding($webdoc, 'HTML-ENTITIES', 'UTF-8'));

	// 		//parsing begins here:
	// 		$xpath = new DOMXPath($doc);
	// 		$title =  $xpath->query('//title')->item(0)->nodeValue."\n";


			 $nodes = $doc->getElementsByTagName('title');
			if(isset($nodes->item(0)->nodeValue)) {
				 $title = $nodes->item(0)->nodeValue;
			}
		#echo "Title parsed: $title" . BRNL;
	}

	 if ($title == '') {
		 $title = "(No Title: " . substr($url,0,50) . '...';
	}

    return $title;
}

function update_titles_in_links(){

	$pdo = MyPDO::instance();

	#get blank titles
	$sql = "select * from `links` where `title` is null or `title` = ''
	or `title` in ( 'test title' )
	or `title` like '(No Title %'
	;";
	$stmt = $pdo -> query ($sql);
    $rows = $stmt -> rowCount();
    if ($rows == 0 ){echo "No new titles"; return;}
    echo "Updating $rows entry with no valid title in link table<br>\n";
   echo "<table><tr><th>Link ID</th><th>Link</th><th>Title</th></tr>" ;
	$ustmt = $pdo -> prepare ("Update `links` set title = ? where link_id = ?");
	while ($row = $stmt-> fetch() ){
		$url = stripslashes($row['url']);
		$link_id = $row['link_id'];
		$article = $row['article_id'];
		#for testing...
		#$url = 'https://amdflames.org/title_test.html';
		$title = get_url_title($url,$article);

	#	$title = 'test title';
	       echo "<tr><td> $link_id</td><td> $url</td><td>$title</td></tr>";
        $ustmt -> execute([$title,$link_id]);
    }
    echo "</table> Done.\n";

}


