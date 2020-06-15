<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

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

create_pubs($pdo) ;

if (! $file_index = json_decode (file_get_contents(FileDefs::news_index_json),true) ) {
	die ("Cant find file index");
}

$pubindex = [];

 echo count($file_index) . " records in json index" . BRNL;

		$getreadssql = "SELECT read_cnt from `read_table` where issue = ?  LIMIT 1;";
		$getreadsprep = $pdo->prepare($getreadssql);

   $insertsql = "INSERT INTO pubs (issue, rcount, title, pubdate, url, stories)
		VALUES (:issue, :rcount, :title, :pubdate, :url, :stories);";
	$insertprep = $pdo->prepare($insertsql);

	$sql = 'SELECT id FROM articles WHERE date_published = ?';
	$art_select = $pdo->prepare($sql);

        foreach ($file_index as $dcode => $f){
            //echo "$dcode => $f<br>\n";
				$f = str_replace('/index.php','',$f);

            $url = "/newsp/$f";
            if(! $dt = \DateTime::createFromFormat('Ymd',$dcode) ){
            	echo "Cannot convert date on $dcode:$f" . BRNL;
            	continue;
            }

            $year = $dt->format('Y');
            $cdate = $dt->format('M j, Y');
            $sdate = $dt->format('Y-m-d');



		// retrieve reads from old reads table
				$reads = 0;
				if (substr($dcode,0,4) >= 2016)  {
					$oldissue =  (int)substr($dcode,2);
					try {
						if ($getreadsprep->execute([$oldissue]) ) {
							$reads = $getreadsprep->fetchColumn() ;
						}
					} catch (\PDOException $e) {
						$reads = 0;
						echo $e->getMessage(); exit;
					}
				}
		$pubindex[$sdate] = $dcode; // date to issue

	// retrieve articles with this pub date
		$stories = '';$idsj = ''; $ids=[];

		$sql = "SELECT id FROM `articles`
		WHERE date_published BETWEEN '$sdate'
		AND $sdate' - INTERVAL 2 day)";

		$art_select = $pdo->prepare($sql);

		if ($ids = $art_select->fetchAll(\PDO::FETCH_COLUMN) )  {
			//u\echor($ids,$sdate); exit;
			$stories = join(',',$ids);
			$idsj = json_encode($ids);

		}


// look for title file if the url is a subdir of newsp
				$title = '';
				$dir = dirname($url);
				if (substr($dir,-5) != 'newsp') {
					#echo "path $dir" . BRNL;
						$titlefile = SITE_PATH . $dir . '/title.txt';
						#echo "looking for $titlefile" . BRNL;
						if (file_exists($titlefile)) {
							$title = file_get_contents($titlefile);
						}
				}

				$pdovars = array(
					':issue' => (int)$dcode,
					':url' => $url,
					':pubdate' => $sdate,
					':rcount' => (int)$reads,
					':title' => $title,
					':stories' => $idsj,
					);

            	#u\echor($pdovars,'pdovars');
            $insertprep->execute($pdovars);



        }
//u\echor($pubindex);

         check_articles($pdo,$pubindex);

         exit;

   #############################

function check_articles($pdo,$pubindex) {
	// index is date -> issue
	$sql = "SELECT id, date_published FROM articles";
	$arts = $pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	$no_issue = []; $nocount=0;

	foreach ($arts as $aid => $pdate) {

		if (empty($pubindex[$pdate] )) {
			$no_issue[$pdate][] = $aid;
			++$nocount ;
		}
		//else { echo "$aid found $pdate";}
	}
	echo $nocount . " articles found with no pubdate in index" . BRNL;
	foreach ($no_issue as $pdate=>$alist){
		echo "$pdate : " . join(',',$alist)  . BRNL;
	}
}




function create_pubs($pdo) {

	$pdo->query("DROP TABLE IF EXISTS `pubs`;");

	$sql = "

CREATE TABLE `pubs` (
  `issue` int(11) NOT NULL COMMENT 'yymmdd',
  `rcount` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `pubdate` datetime DEFAULT NULL,
  `url` varchar(64) NOT NULL,
  `stories` varchar(255) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,

  PRIMARY KEY (`issue`),
  UNIQUE KEY `url` (`url`) USING BTREE,
  KEY `pubdate` (`pubdate`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
	";

	$pdo->query($sql);



	// add the preview issue to the db
	$sql2 = "INSERT INTO `pubs` (issue,title,url) VALUES (1,'Preview','/news/next') ";
	$pdo->query($sql2);

	}
