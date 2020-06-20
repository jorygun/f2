<?php
namespace digitalmx\flames;

/* this script needs to run in the old site to copy assets to assets2,
 which the new site will use.
	Rewritten 6/19 to remove trying to fix thumbs.  Need a separate fix_thumbs script.

*/

ini_set('default_socket_timeout', 10);
ini_set('display_errors',1);

//BEGIN START
		require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\Assets;
	#use digitalmx\flames\DocPage;


	$page_title = 'Asset Fixer';
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
$bsame = array(
	'id',	'keywords','type','vintage','source','contributor_id','date_entered',			'sizekb','notes',	'first_use_date',	'first_use_in','tags', 'title',	'caption',	'mime','type',
	);



$removed = array (
'contributor','height',	'width','has_thumb',	'has_gallery',	'has_toon',
'reviews',	'up_votes',	'down_votes',	'votes',	'comment_count',	'gallery_items',	'user_info',	'temptest','url', 'link','status','mod_date',
'review_ts',	'skip_ts','thumb_file'
);
// initialize new or altered fields
$bnew = array (
'astatus' => 'N',
'thumb_url' =>'',
'asset_url' => '',
'errors' => '',

'mime'=>'',
'type'=>'Other',
);

$asset_db = 'live_assets'; #local copy of product assets table

// leave out date modified ; they are automatic
$bauto = array();

$bvars = array_merge($bsame,array_keys($bnew));
$sqli = '';

$logfile = SITE_PATH . '/log.asset_fixer.log';


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
"Fix Assets starting $start_human" . NL . NL);

echo "Starting from $start_id at $start_human" . BRNL;

if ($start_id == 0 ) {
	#rebuild assets2 from scratch
	create_assets2($pdo) ;
} else {

		$whereend = $end_id != 0  ? " AND id <= $end_id " : '';
	// remove assets between start and end
	$sql = "DELETE from assets2 WHERE id >= $start_id $whereend ";
	if($pdo->query($sql) ) {
		echo "deleted from $start_id to $whereend" . BRNL;
	} else {echo "delete failed";
	}
}

$last_id = runit($pdo,$start_id,$end_id,$bsame,$bnew,$check_yt);


$end_time = time();
$elapsed = $end_time - $start_time + 1; // so you don't get 0
$elapsedh = u\humanSecs ($elapsed);
echo "done.  $elapsedh. Last ID $last_id. <br>";
//add time to url to prevent caching
echo "<a href='/asset_fixer.log?$end_time' target = 'log'>Log</a>" . BRNL;
exit;

#######################

function runit($pdo,$next_id,$end,$bsame,$bnew,$check_yt) {
	global $asset_db;
	$rept_interval = 25; // print progress every this many records;
	$end_condition = ($end > 0) ? " AND id <= $end " : '';
	$sql = "SELECT * from `$asset_db` WHERE id >= ? $end_condition and status not in ('T','X') order by id  LIMIT 200";
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
			$b = $bnew; // new data set
			$estatus = ''; // capture e and w codes
			$ostatus = $row['status']; #old status

			$id = $row['id'];
			$tsrc = $src = '';
			if (is_integer($rc/$rept_interval)) echo "<small>At record $rc id $id</small><br>";

			if ($end != 0 && $id > $end){
				$done = true;
				$next_id = $last_id + 1;
				return $last_id;
			}
			$last_id = $id;
			// path to thumb as jpg
			$tpjpg = SITE_PATH . '/assets/thumbs/' . $id . '.jpg';


			if (in_array($row['status'],['X','T','D'])){continue;}

			// make new array 'b'

			foreach ($bsame as $v){
				$b[$v] = $row[$v];
			}



			$b['title'] = stripslashes($row['title']) ?: 'Untitled';
			$b['caption'] = stripslashes($row['caption']);
			if ( $b['title'] == $b['caption']) {$b['caption'] = '';}
			$b['astatus'] = '';

			//develop estatus during scan for errors and warnings.
			// at the end set astataus = estatus || original status
			// this preserves the old status settings.
			// status at the end.
			$b['vintage'] = $row['vintage'] ;
			$b['sizekb'] = $row['sizekb'] ?: 0;

			$b['date_entered'] =  $row['date_entered'] ?: date('Y-m-d');
			$b['contributor_id'] = $row['contributor_id'] ?: 13146; // flames admin

			$fud = $row['first_use_date'];
			if (empty($fud) || $fud == '0000-00-00') {
				$fud = $null;
			}
			$b['first_use_date'] = $fud;

			if ($ostatus == 'E'){ // existing status field: Error
				// just copy stuff over with the E status
				$b['mime'] = $row['mime'];
				$b['asset_url'] = $row['link'];
				$b['type'] =  Defs::getMimeGroup($mime) ?: 'Other';
				$thumburl = $row['url'];
				if ( empty($thumburl) || $thumburl == $row['link'] ) {
					$thumburl = '';  // blank for now.  will gt written back to the b array.
				}
				$b['thumb_url'] = $thumburl;
				$b['astatus'] = 'E';

			} else { #do everything ellse

			//check link
			if (empty($src = $row['link'])) {
				$estatus = 'E';
				$b['errors'] .= logrec($id, $estatus, "No source (link) specified ");

			}

			if ($estatus != 'E') {
				if ($thumburl = $row['url'] ) {
					if ( $thumburl == $row['link'] ) {
						$thumburl = '';  // blank for now.  will gt written back to the b array.
					}
				}
			}

			if ($estatus != 'E') {
			// fix relocated sources
				$src = preg_replace('|^/reunions|','/assets/reunions',$src);
				$src = preg_replace('|^/newsp/SalesConf|','/assets/sales_conferences',$src);
				$src = preg_replace('|^/sales_conferences|','/assets/sales_conferences',$src);
			}


			if ($estatus != 'E') {
				if ( !$mime = source_exists($src,$check_yt))  {
					$estatus = 'E';
					$b['errors'] .= logrec($id, $estatus, "Source does not exist or can't get mime",$src);

				}
				$b['thumb_url'] = $thumburl;
				$b['mime'] = $mime;
				$b['asset_url'] = $src;
				$type =  Defs::getMimeGroup($mime) ?: 'Other';
				$b['type'] = $type;
			}


		// check valid thumb source,





			// copy old status if nothing changed.
			$b['astatus'] = $estatus ?: $ostatus;
			} #end if old status not error
			record_result($b);

		} #end while adb loop
	$next_id = $last_id + 1;
	} #end while !done loop

} #end function

###########
function source_exists($src, $check_yt=false) {
	/* checks if the source file or url exists, and
		if so, returns the mime type.  Done together
		because it saves processing
		check_yt checks to see if a youtube link is stil a valid video.
	*/
	static $mimeinfo;
	global $verbose;
	$mytrack = false; #this routine only
	$track = $mytrack || $verbose;

	if (empty($mimeinfo)){
		$mimeinfo = new \finfo(FILEINFO_MIME_TYPE); // finfo->mime
	}

	if ($track)  echo "checking source $src.. " ;
	if (substr($src,0,1) == '/'){
		if ($track)  echo "is local...";
			$path = SITE_PATH . $src;
			if (! file_exists($path)){
				if ($track)  echo "no file..". BRNL;;
				return false;
			}
			$mime = $mimeinfo->file($path);
			if ($track)  echo "file exists $mime." . BRNL;
			return $mime;

	} elseif ($ytid = u\get_youtube_id ($src)) {

		if ($track)  echo "is youtube $ytid... " ;
		if ($check_yt) {
			$ytapi = "https://www.googleapis.com/youtube/v3/videos?id=$ytid&part=status&key=AIzaSyAU30eOK0Xbqe4Yj0OMG9fUj3A9C_K_edU";

			 try {
				$result = u\get_url_data($ytapi);
				$content = (array) json_decode($result['content']); // class ojbect
				//u\echor($content);
				// no entry for items seems to mean the video has been removed.
				if (!empty($items =  @$content['items'][0] ) ) {
					$ps = $items->status->privacyStatus;
				} else {
						$ps = 'no items returned';
				}

			} catch (Exception $e) {
				$ps = 'yt exception';
			}
			if ($ps != 'public') {
				if ($track)  echo "Failed youtube $src. ps= $ps" . BRNL;
				//u\echor($content);
				return false;
			}
			elseif ($track) {
				echo " OK" . BRNL;
			}
		}
		return 'video/x-youtube';


	} elseif (u\is_valid_url($src) ) {

		if ($track)  echo "is url... ";

		if (!$mime = u\get_mime_from_curl($src) ) {
			return false;
		}
		foreach (array_keys(Defs::$mime_groups) as $m) {
			if (strpos($mime,$m) !== false) {
				$mime = $m;
			} #eliminate other data
		}
		if ($track)  echo "yup $mime". BRNL;
		return $mime;
		} else { return false;}
}



function record_result($b) {
	global $pdo,$null;
	global $verbose;
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

function create_assets2 ($pdo) {

try {
	$pdo->query("DROP TABLE IF EXISTS `assets2`;");


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
	  `date_modified` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	  `sizekb` int(11) DEFAULT NULL,
	  `notes` mediumtext COLLATE utf8mb4_unicode_ci,
	  `first_use_date` timestamp NULL DEFAULT NULL,
	  `first_use_in` mediumtext COLLATE utf8mb4_unicode_ci,
	  `tags` tinytext COLLATE utf8mb4_unicode_ci,
	  `errors` mediumtext COLLATE utf8mb4_unicode_ci,

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
		echo "Failed to drop assets2 tABLE";
		echo $e-getMessage() . BRNL;
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
