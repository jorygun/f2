<?php
namespace DigitalMx\Flames;

ini_set('default_socket_timeout', 10);
ini_set('display_errors',1);

//BEGIN START
		require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\Assets;
	#use DigitalMx\flames\DocPage;


	$page_title = 'News Fixer';
	$page_options = [];
	//
//
    $login->checkLogin(0);
 	$page = new DocPage($page_title);
 	echo $page -> startHead($page_options);
//

// script to modify news_items and get articles




$logfile = SITE_PATH . '/log.fix_news.log';




if (empty($_GET)) {
	echo show_form();
	exit;
// } else {
// 	u\echor($_GET);
// 	exit;
}
$check_yt = false;
$start_id = $_GET['start'] ?? 0;
$end_id = $_GET['end'] ?? 0;
$check_yt = $_GET['check_yt'] ?? false;


$start_time = time();
$verbose = false; #gloabl tracking flag

$dt = new  \DateTime('now',new \DateTimeZone('America/Los_Angeles'));
$start_human = $dt->format ('M d, Y H:i') ;
file_put_contents($logfile,
"Fix News Items starting $start_human" . NL . NL);

echo "Starting from $start_id at $start_human" . BRNL;



if ($start_id == 0 ) {
	#rebuild assets2 from scratch
	create_articles($pdo) ;
} else {

		$whereend = $end_id != 0  ? " AND id <= $end_id " : '';
	// remove assets between start and end
	$sql = "DELETE from articles WHERE id >= $start_id $whereend ";
	if($pdo->query($sql) ) {
		echo "deleted from $start_id to $whereend" . BRNL;
	} else {echo "delete failed";
	}
}

$bsame = array(
		'id'	,
		'use_me',
		'title',
		'source',
		'contributor_id',
		'source_date',
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
	);

$last_id = runit($pdo,$start_id,$end_id,$bsame,$bnew,$check_yt);


$end_time = time();
$elapsed = $end_time - $start_time + 1; // so you don't get 0
$elapsedh = u\humanSecs ($elapsed);
echo "done.  $elapsedh. Last ID $last_id. <br>";
//add time to url to prevent caching
echo "<a href='/log.fix_news.log?$end_time' target = 'log'>Log</a>" . BRNL;
exit;

#######################

function runit($pdo,$next_id,$end,$bsame,$bnew,$check_yt) {
	$rept_interval = 25; // print progress every this many records;
	$end_condition = ($end > 0) ? " AND id <= $end " : '';

	$sql = "SELECT * from `news_items` WHERE id >= ? $end_condition and status not in ('T','X') order by id  LIMIT 200";
	echo $sql . BRNL;

	$stmtb = $pdo->prepare($sql);
	$done = false;
	$null = null;

	$estatus = '';
	$rc = 0;

	while (!$done ){

		echo "<p><i>Getting records >=  $next_id" . $end_condition . "</i></p>" . BRNL;
		//echo $sql . BRNL;

		$stmtb->execute([$next_id]);
		if (! $stmtb->rowCount() ){#no more records to get
			$done = true;
			echo "No more rows". BRNL;
			return $last_id;
		}

		while ($row = $stmtb->fetch() ){
			++$rc;
			$id = $row['id'];

			if (is_integer($rc/$rept_interval)) echo "<small>getting record $rc id $id</small><br>";

			if ($end != 0 && $id > $end){
				$done = true;
				$next_id = $last_id + 1;
				return $last_id;
			}
			$last_id = $id;
			$ostatus = $row['status']; #old status
			$new_list = trim($row['asset_id'] . ' ' . $row['asset_list']);

		$in_issue = ''; // issue this was published in
		$pub_date = $row['date_published'];
		// translate to issue and make sure it exits



			####### DO thE WORK ############
			$mod_record = array (
			'topic'	=>	$row['type'],
			'link'	=>	$row['link'],
			'asset_list'	=>	$new_list,
			'pub_issue'	=>	$in_issue,
			'asset_main' = '',

			);


			####################
		$new_record = [];
	foreach ($bsame as $var ){
		$new_record[$var] = $row[$var];
	}
	$new_record = array_merge ($new_record,$mod_record);

			if (!record_result($new_record) ) {echo "oops"; exit;}

		} #end while adb loop
	$next_id = $last_id + 1;
	} #end while !done loop

} #end function

###########

function record_result($b) {
	global $pdo,$null;
	global $verbose;
	$mytrack = true; #this routine only
	$track = $mytrack || $verbose;

	static $stmti,$sqli;
	try {
		if (empty($stmti)){
			$prep = u\pdoPrep($b,[],''); #no key field.  Must retain id

			$sqli = "INSERT into `articles` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
			echo "Setting insert sql:<br>
			$sqli" . BRNL;
			$stmti = $pdo->prepare($sqli);

			// only need to do once
		}


			$prep = u\pdoPrep($b,[],''); #no key field.  Must retain id
			//if ($track) u\echor ($prep['data'],$sqli);



			$stmti->execute($prep['data']) ;
		} catch (\PDOException $e) {
			echo "Error writing data". BRNL;
			echo $e->getMessage() . BRNL;
			if ($track) u\echor ($prep['data'],$sqli);
			return false;
		}
		return true;

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

function show_form() {

	$t =  <<<EOT
	<p>This program adjusts the news_items table,
	</p>
	<br>
	<p><a href='/log.fix_news.log'>Click here</a> to look at log from last run.</p>
	<form method = 'GET'>
	<p>Enter starting id # (use 0 to completely rebuild) <input type='number' name = 'start' value='1000'>;<br>
	Enter ending id # (leave blank for all) <input type='number' name='end' value=0><br>

	</p>
	<input type='submit'>
	</form>
EOT;
 return $t;

}
