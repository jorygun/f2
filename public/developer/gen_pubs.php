<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);
/*
	Uses articles and read_table in this db and json index in live site  to build new pubs table in this db.
*/

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = 'Rebuild Pubs Table';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START



//EOF

$logurl =  '/developer/logs/log.gen_pubs.log';
$logfile = SITE_PATH . $logurl;

create_pubs($pdo) ;

// gets the old news_indexx here because for some reason I can't copy
// over using a shell scrpt.  ??

if (strpos(__FILE__,'/Users/john') !== false) {
		$index_json = PROJ_PATH . '/f2/public/news/current/news_index.json';
} else {
	$index_json = PROJ_PATH . '/live/public/news/current/news_index.json';
}

if (! $file_index = json_decode (file_get_contents($index_json),true) ) {
	die ("Cant find file index");
}

$pubindex = [];
echo "Rebuild Issues and Links version " . date('m/d H:i',filemtime(__FILE__)) . " at " . date('M d Y H:i') . BRNL;

 echo count($file_index) . " records in json index" . BRNL;

		$getreadssql = "SELECT read_cnt from `read_table` where issue = ?  LIMIT 1;";
		$getreadsprep = $pdo->prepare($getreadssql);

   $insertsql = "INSERT INTO issues (issue, rcount, title, pubdate, url)
		VALUES (:issue, :rcount, :title, :pubdate, :url );";
	$insertprep = $pdo->prepare($insertsql);

	$artsql = "SELECT id FROM articles
		WHERE date_published <= :sdate
		AND date_published >= DATE_SUB(:sdate1 , INTERVAL 4 day);
		";

		$art_stmt = $pdo->prepare($artsql);


// file index is bult from newp directory entries: datecode -> url
	  foreach ($file_index as $dcode => $path){
			//echo "$dcode => $f . ";
			$path = str_replace('/index.php','',$path);

			$url = "/newsp/$path";
			if(! $dt = \DateTime::createFromFormat('Ymd',$dcode) ){
				echo "Cannot convert date on $dcode:$f" . BRNL;
				continue;
			}

			$year = $dt->format('Y');
			$cdate = $dt->format('M j, Y');
			$sdate = $dt->format('Y-m-d');



	// retrieve reads from reads table, starts in 2016
			$reads = 0;
			if (substr($dcode,0,4) >= 2016)  {
				$oldissue =  substr($dcode,2);
				try {
					if ($getreadsprep->execute([$oldissue]) ) {
						$reads = $getreadsprep->fetchColumn() ;
					}
				} catch (\PDOException $e) {
					$reads = 0;
					echo $e->getMessage(); exit;
				}
			}

			$pubindex[] = $dcode; // date to issue. used for article index



// look for title file if the url is a subdir of newsp
			$title = '';
			$dir = dirname($url);
			if (substr($dir,-5) != 'newsp') { // not directly in newsp.
				#echo "path $dir" . BRNL;
					$titlefile = SITE_PATH . $dir . '/title.txt';
					#echo "looking for $titlefile" . BRNL;
					if (file_exists($titlefile)) {
						$title = file_get_contents($titlefile);
					}
			}

			$pdovars = array(
				':issue' => $dcode,
				':url' => $url,
				':pubdate' => $sdate,
				':rcount' => (int)$reads,
				':title' => $title,

				);

				#u\echor($pdovars,'pdovars');
			$insertprep->execute($pdovars);



	  } // end foreach
//u\echor($pubindex);

			echo "Issues built;"  . BRNL;
         check_articles($pdo,$pubindex);

         exit;

   #############################






function check_articles($pdo,$pubindex) {
	// index is date -> issue
	$sql = "SELECT id, DATE_FORMAT(date_published,'%Y%m%d') as pubdate FROM articles";
	$arts = $pdo->query($sql);
	$no_issue = []; $nocount=0;

	$sql = "INSERT into publinks SET issue = ?, article = ?;";
	$storyinsert = $pdo->prepare($sql);


	while ($row = $arts->fetch() ) {
		$aid = $row['id'];
		$pdate = $row['pubdate'];


		if (!in_array($pdate, $pubindex) ) {
			$no_issue[$pdate][] = $aid;
			++$nocount ;
		} else {
			$storyinsert->execute([$pdate,$aid]);
		}
	}
	echo $nocount . " articles found with no matching pubdate" . BRNL;
	echo "PUBDATE.......ARTICLES" . BRNL;
	foreach ($no_issue as $pdate=>$alist){
		echo "$pdate : " . join(',',$alist)  . BRNL;
	}

}




function create_pubs($pdo) {

	$pdo->query("DROP TABLE IF EXISTS `publinks`;");
	$pdo->query("DROP TABLE IF EXISTS `issues`;");

	$sql = "

CREATE TABLE `issues` (
  `issue` int(11) NOT NULL COMMENT 'yymmdd',
  `rcount` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `pubdate` datetime DEFAULT NULL,
  `url` varchar(64) DEFAULT NULL,
  `updated` datetime DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
  `last_scan` datetime DEFAULT NULL,

  PRIMARY KEY (`issue`),
  UNIQUE KEY `url` (`url`) USING BTREE,
  KEY `pubdate` (`pubdate`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
	";

	$pdo->query($sql);

	$sql = "
	CREATE TABLE `publinks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `issue` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `article` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `issue` (`issue`) USING BTREE,
  KEY `article` (`article`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
		$pdo->query($sql);

	// add the preview issue to the db
	$sql2 = "INSERT INTO `issues` (issue,title,url) VALUES (1,'','/news/next') ";
	$pdo->query($sql2);

	}
