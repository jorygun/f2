<?php
namespace DigitalMx\Flames;

ini_set('default_socket_timeout', 10);
ini_set('display_errors',1);

/* script to read news_items and produce articles table both in same db.

*/


//BEGIN START
		require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\Assets;
	#use DigitalMx\flames\DocPage;


	$page_title = 'Gen Articles';
	$page_options = [];
	//
//
    $login->checkLogin(0);
 	$page = new DocPage($page_title);
 	echo $page -> startHead($page_options);
//

// script to modify news_items and get articles



$logurl =  '/developer/logs/log.gen_articles.log';
$logfile = SITE_PATH . $logurl;





$start_id =  0;
$end_id =  0;
$check_yt = $_GET['check_yt'] ?? false;


$start_time = time();
$verbose = false; #gloabl tracking flag

$dt = new  \DateTime('now',new \DateTimeZone('America/Los_Angeles'));
$start_human = $dt->format ('M d, Y H:i') ;
file_put_contents($logfile,
"Gen Articles starting $start_human" . NL . NL);

echo "Starting  at $start_human" . BRNL;





$bsame = array(
		'id'	,
		'use_me',
		'title',
		'source',
		'contributor_id',
		'link_title',
		'status',
		'content',
		'ed_comment',
		'take_comments',
		'take_votes',
		'date_entered',
		'date_edited',
		'date_published',
	);

	$bnew = array (
		'id'	=> 0 ,
		'use_me'	=> 0 ,
		'title'	=> '' ,
		'source'	=> '' ,
		'contributor_id'	=> 0 ,
		'link_title'	=> '' ,
		'status'	=> 'N' ,
		'content'	=> '' ,
		'ed_comment'	=> '' ,
		'take_comments'	=> 0 ,
		'take_votes'	=> 0 ,
		'date_entered'	=> date('Y-m-d') ,
		'date_edited'	=> date('Y-m-d') ,
		'date_published'	=> date('Y-m-d') ,
		'topic'=> '' ,
			'link'		=> '' ,
			'asset_list' => [] ,
			'pub_issue'	=>	 0 ,
			'asset_main' => '',
			'source_date' => date('Y-m-d') ,

	);

$rept_interval = 25; // print progress every this many records;
$null = null;
$estatus = '';
$rc = 0;

create_articles($pdo) ;

// insert statuement

	$prep = u\pdoPrep($bnew,[],''); #no key field.  Must retain id

	$sqli = "INSERT into `articles` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
	echo "Setting insert sql:" . BRNL;
	$stmti = $pdo->prepare($sqli);
	u\echor($prep,$sqli);





$sql = "SELECT * from `news_items` WHERE  status not in ('T','X') order by id ";
echo $sql . BRNL;
$stmtb = $pdo->query($sql);
echo $stmtb->rowCount() . " Records in news_items" . BRNL;


while ($row = $stmtb->fetch() ) {
	++$rc;
	$id = $row['id'];

	if (is_integer($rc/$rept_interval)) echo "<small>getting record $rc id $id</small><br>";


	$last_id = $id;
	$ostatus = $row['status']; #old status
	// combine the two asset fields into new asset list
	$new_list = trim($row['asset_id'] . ' ' . $row['asset_list']);

	$in_issue = ''; // issue this was published in
	$pub_date = $row['date_published'];
	// translate to issue and make sure it exits

		// remove source date if theres no wource
	$src_date = (empty($row['source']) ) ? '' : $row['source_date'] ;

		####### DO thE WORK ############
			$mod_record = array (
			'topic'	=>	$row['type'],
			'link'	=>	$row['url'],
			'asset_list'	=>	$new_list,
			'pub_issue'	=>	$in_issue,
			'asset_main' => '',
			'source_date' => $src_date,

			);

	$new_record = [];
	foreach ($bsame as $var ){
		$new_record[$var] = $row[$var];
	}
	$new_record = array_merge ($new_record,$mod_record);

	if (!record_result($new_record, $stmti) ) {echo "oops"; exit;}


}


$end_time = time();
$elapsed = $end_time - $start_time + 1; // so you don't get 0
$elapsedh = u\humanSecs ($elapsed);
echo "done.  $elapsedh. Last ID $last_id. <br>";
//add time to url to prevent caching
echo "<a href='/log.fix_news.log?$end_time' target = 'log'>Log</a>" . BRNL;
exit;

#######################


function record_result($b,$stmti) {


	$mytrack = true; #this routine only
	$track = $mytrack || $verbose;
	$prep = u\pdoPrep($b,[],''); #no key field.  Must retain id
			//if ($track) u\echor ($prep['data'],$sqli);
	if ($stmti->execute($prep['data']) ) {
		//u\echor($prep['data']);
		return true;
	}
	return false;
	}

function logrec($aid,$e,$msg,$src='') {
	// logs errors in logfile reclog and also returns the message to go
	// into the new record array[errors]
	global $logfile;
	$local=localtime();
	$t = $local[1] . ':' . $local[0];

	echo "<p class='red'>$aid: $msg</p>";
	file_put_contents($logfile,
		sprintf("%6s %4s %1s %s\n",$t,$aid,$e,$msg),FILE_APPEND);
	if (!empty($src)) {
		file_put_contents($logfile,
		sprintf("%12s %s\n",'',$src),FILE_APPEND);
	}
	return "($e) $msg ". NL;
}

function create_articles( $pdo ) {
	$pdo->query("DROP TABLE IF EXISTS `articles`");

	$sql = <<<EOT

CREATE TABLE `articles` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `use_me` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255)  ,
  `topic` varchar(32) ,
  `source` varchar(255) ,
  `contributor_id` smallint(6) DEFAULT '0',
  `source_date` varchar(25)  ,
  `link_title` mediumtext  ,
  `link` varchar(255) ,
  `asset_list` tinytext ,
  `asset_main` tinytext,
  `status` char(2)  NOT NULL DEFAULT 'N',
  `content` mediumtext ,
  `ed_comment` mediumtext,
  `take_comments` tinyint(1) DEFAULT '0',
  `take_votes` tinyint(1) NOT NULL DEFAULT '1',
  `pub_issue` tinytext ,
   `date_entered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_edited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_published` date DEFAULT NULL,


  PRIMARY KEY (`id`),
  KEY `topic` (`topic`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1774 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

EOT;
	$pdo->query($sql);

}


