<?php
namespace digitalmx\flames;

/* this script translates assets to asset2, both in same db.

*/

ini_set('default_socket_timeout', 10);
//ini_set('display_errors',1);

//BEGIN START
		require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\Assets;
	#use digitalmx\flames\DocPage;

$Assets = $container['assets'];
$Asseta = $container['asseta'];

	$page_title = 'Gen Assets2';
	$page_options = [];
	//
//
    $login->checkLogin(0);
 	$page = new DocPage($page_title);
 	echo $page -> startHead($page_options);
//

// script to copy assets to assets, and to check values


//require_once 'scripts/asset_functions.php';


$adb = array(
	'id',	'status',	'title',	'caption',	'keywords',	'mime',	'type',	'url',	'thumb_file',	'link',	'vintage',	'source',	'contributor',	'contributor_id',	'date_entered',	'mod_date',	'height',	'width',	'sizekb',	'notes',	'has_thumb',	'has_gallery',	'has_toon',	'review_ts',	'skip_ts',	'first_use_date',	'first_use_in',	'tags',	'reviews',	'up_votes',	'down_votes',	'votes',	'comment_count',	'gallery_items',	'user_info',	'temptest',
	);
$bnew = array (
	'astatus' => 'U',
	'thumb_url' =>'',
	'asset_url' => '',
	'errors' => '',

	'mime'=>'',
	'type'=>'Other',
	'local_src' => '',
	);

$bsame = array( // 8
	'id',	'keywords','vintage','source','notes','first_use_in', 'mime',

	);
$bchanged = array ( //10
	'title','caption','astatus','sizekb','date_entered','contributor_id',
	'first_use_date','asset_url','type','thumb_url','tags'
);
$bauto = array ( //2
	'date_modified', 'errors'
	);


$asset_db = 'assets'; #local copy of product assets table
$null = null;
$sqli = '';

$logurl =  '/developer/logs/log.gen_assets2.log';
$logfile = SITE_PATH . $logurl;
date_default_timezone_set("America/Los_Angeles");



// if (empty($_GET)) {
// 	echo show_form();
// 	exit;
// // } else {
// // 	u\echor($_GET);
// // 	exit;
// }
$check_yt = false;
$start_id = $_GET['start'] ?? 0;
$end_id = $_GET['end'] ?? 0;
$check_yt = $_GET['check_yt'] ?? false;
$rept_interval = 25; // ping every this many records

$start_time = time();
$verbose = false; #gloabl tracking flag

$dt = new  \DateTime('now',new \DateTimeZone('America/Los_Angeles'));
$start_human = $dt->format ('M d, Y H:i') ;
file_put_contents($logfile,
"Gen_assets2 starting $start_human" . NL . NL);

echo "Starting from $start_id at $start_human" . BRNL;

if ($start_id == 0 ) {
	#rebuild assets2 from scratch
	create_assets2($pdo) ;
}

$sql = "SELECT * from `$asset_db` WHERE  status not in ('T','X','D') order by id";
$stmta = $pdo->query($sql);
$cnt = $stmta->rowCount();
echo "$cnt records in $asset_db" . BRNL;


$estatus = '';
$rc = 0;
$scount = array (
	'E' => 0,
	'N' => 0,
	'O' => 0,
	'K' => 0,
	);
	// count of status codes
while ($row = $stmta -> fetch() ) {
	++$rc;
	$b = $bnew; // new data set
	$estatus = ''; // capture e and w codes


	$id = $row['id'];
	$tsrc = $src = '';
	if (is_integer($rc/$rept_interval)) {
		echo "<small>At record $rc id $id</small><br>";
		myFlush();
	}



	$b = translate_fields($row);
	// set mime type based on real url


	if ($estatus != 'E') {
		if ( !$mime = u\url_exists($b['asset_url'] ) ) {
			$estatus = 'E';
			$b['errors'] .= logrec($id, $estatus, "Source does not exist or can't get mime",$src);

		} else {
			$b['mime'] = $mime;
			$b['type'] = Defs::getAssetType($mime);
		}
	}

	if ($estatus != 'E') {
		try{
			$c = $Asseta->checkAssetData($b);

		} catch (Exception $e) {
			$msg = $e->getMessage();
			//echo "Error in asset data: " .$msg . BRNL;
			$estatus = 'E';
			$b['errors'] .= logrec($id, $estatus, $msg);

		}
	}
	if ($estatus != 'E') {
	  try{
			$b['local_src'] = $Asseta->checkThumbSources($id,$b['asset_url'] , $b['thumb_url'], $b['mime']);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			//echo "Error in thumb sources: " .$msg . BRNL;
			$estatus = 'E';
			$b['errors'] .= logrec($id, $estatus, $msg);
		}
	}

	$b['astatus'] = $estatus ?: $b['astatus'];
	$last_id = $id;
	record_result($b);


}

#### FINISH ####

$end_time = time();
$elapsed = $end_time - $start_time + 1; // so you don't get 0
$elapsedh = u\humanSecs ($elapsed);
echo "done.  $elapsedh. Last ID $last_id. <br>";
u\echor($scount);


echo "<a href='$logurl?$end_time' target = 'log'>Log</a>" . BRNL;


exit;

#######################
// OLD STATUAT TO NEW STATUS IF DIFFERENT
$asset_tran = array (
	'R' => 'O',
	'S' => 'O',
	'D' => 'X',
);



function translate_fields($a) {
	// $a is existing assets data, returns new asset2 data
	global $bsame,$bnew,$null;

	// make new array 'b'
	$b = $bnew;

	foreach ($bsame as $v){
		$b[$v] = $a[$v];
	}
	$ostatus = $a['status']; #old status

	$b['title'] = stripslashes($a['title']) ?: 'Untitled';
	$b['caption'] = stripslashes($a['caption']);
	if ( $b['title'] == $b['caption']) {$b['caption'] = '';}
	$b['astatus'] = $asset_tran[$a['status']] ?? $ostatus;

	//develop estatus during scan for errors and warnings.
	// at the end set astataus = estatus || original status
	// this preserves the old status settings.
	// status at the end.

	$b['sizekb'] = $a['sizekb'] ?: 0;

	$b['date_entered'] =  $a['date_entered'] ?: date('Y-m-d');
	$b['contributor_id'] = $a['contributor_id'] ?: Defs::$editor_id;

	$fud = $a['first_use_date'];
	if (empty($fud) || $fud == '0000-00-00') {
		$fud = $null;
	}
	$b['first_use_date'] = $fud;

	$b['tags'] = '';

	foreach (str_split($a['tags']) as $tag){
		if (in_array($tag,array_keys(Defs::$asset_tags))) {
			$b['tags'] .= $tag;
		}
	}



	$thumburl = $a['url'];
	if ( empty($thumburl) || $thumburl == $a['link'] ) {
		$thumburl = '';  // blank for now.  will gt written back to the b array.
	}

	$b['thumb_url'] = $thumburl;


	$src = $a['link'];
		$src = preg_replace('|^/reunions|','/assets/reunions',$src);
		$src = preg_replace('|^/newsp/SalesConf|','/assets/sales_conferences',$src);
		$src = preg_replace('|^/sales_conferences|','/assets/sales_conferences',$src);
	$b['asset_url'] = $src;

	return $b;
}


function record_result($b) {
	global $pdo,$null;
	global $verbose;
	global $scount;

	++$scount[$b['astatus']];

	$mytrack = false; #this routine only
	$track = $mytrack || $verbose;

	static $stmti,$sqli;
		if (empty($stmti)){
			$prep = u\pdoPrep($b,[],''); #no key field.  Must retain id

			$sqli = "INSERT into `assets2` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
			echo "Setting insert sql:<br>
			$sqli" . BRNL;
			$stmti = $pdo->prepare($sqli);

			// only need to do once
		}

		try {
			$prep = u\pdoPrep($b,[],''); #no key field.  Must retain id
			if (empty($prep['data']['first_use_date']) ) {
				$prep['data']['first_use_date'] = $null;
			}
			if ($track) u\echor ($prep['data'],$sqli);
			$stmti->execute($prep['data']) ;
		} catch (\PDOException $e) {
			echo "Error writing data". BRNL;
			echo $e->getMessage() . BRNL;
			u\echor ($prep['data'],$sqli);
			return false;
		}
		return true;

	}

function logrec($aid,$e,$msg,$src='') {
	// logs errors in logfile reclog and also returns the message to go
	// into the new record array[errors]
	global $logfile;


	$ts = date('H:i');

	echo "<p class='red'>$aid: $msg</p>";
	file_put_contents($logfile,
		sprintf("%6s %4s %1s %s\n",$ts,$aid,$e,$msg),FILE_APPEND);
	if (!empty($src)) {
		file_put_contents($logfile,
		sprintf("%12s %s\n",'',$src),FILE_APPEND);
	}
	return "($e) $msg ". NL;
}

function create_assets2 ($pdo) {

	$pdo->query("DROP TABLE IF EXISTS `assets2`;");

	try {

	$sql = <<<EOT
	CREATE TABLE `assets2` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `title` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
	  `astatus` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'T',
	  `caption` mediumtext COLLATE utf8mb4_unicode_ci,
	  `keywords` mediumtext COLLATE utf8mb4_unicode_ci,
	  `mime` text COLLATE utf8mb4_unicode_ci,
	  `type` text COLLATE utf8mb4_unicode_ci,
	  `thumb_url` text COLLATE utf8mb4_unicode_ci,
	  `asset_url` text COLLATE utf8mb4_unicode_ci,
	  `vintage` int(4) DEFAULT NULL,
	  `source` mediumtext COLLATE utf8mb4_unicode_ci,
	  `contributor_id` smallint(4) DEFAULT NULL,
	  `date_entered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	  `date_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	  `sizekb` int(11) DEFAULT NULL,
	  `notes` mediumtext COLLATE utf8mb4_unicode_ci,
	  `first_use_date` timestamp NULL DEFAULT NULL,
	  `first_use_in` mediumtext COLLATE utf8mb4_unicode_ci,
	  `tags` tinytext COLLATE utf8mb4_unicode_ci,
	  `errors` mediumtext COLLATE utf8mb4_unicode_ci,
	  `local_src` mediumtext COLLATE utf8mb4_unicode_ci,

	  UNIQUE KEY `id` (`id`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=5405 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOT;

	if ($pdo->query($sql)) {
			echo "Rebuilding assets2" . BRNL;
			return true;
		}
		echo "Rebuilding table failed";
		return false;
	} catch (\PDOException $e) {
		echo "PDO error ";
		echo $e->getMessage() . BRNL;
		exit;
	}
}

function show_form() {

	$t =  <<<EOT
	<p>This program copies assets from the assets table to the asset2 table,
	checking for errors as it goes and creating new thumbs when it can.</p>
	<br>
	<p><a href='/asset_fixer.log'>Click here</a> to look at log from last run.</p>
	<form method = 'GET'>
	<p>Enter starting id # (use 0 to completely rebuild) <input type='number' name = 'start' value='1000'>;<br>
	Enter ending id # (leave blank for all) <input type='number' name='end' value=0><br>
	Check to validate all youtube videos <input type = 'checkbox' name ='check_yt'>
	</p>
	<input type='submit'>
	</form>
EOT;
 return $t;

}

function myFlush() {
    echo(str_repeat(' ', 1024));
    if (@ob_get_contents()) {
        @ob_end_flush();
    }
    flush();
}
