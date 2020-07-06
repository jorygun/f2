<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);
ini_set('default_socket_timeout', 10);
ini_set('display_errors',1);


//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;



$login->checkLevel(0);

$page_title = 'Check Thumbs';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START

$asset_db = 'assets2'; #local copy of product assets table
$logurl = '/developer/log.thumb_check.log';
$logpath = SITE_PATH . $logurl;
$assets = $container['assets'];  //Assets Class


if (empty($_GET)) {
	echo show_form();
	exit;
// } else {
// 	u\echor($_GET);
// 	exit;
}

$check_yt = false;
$start_id = 0;
$end_id =  0;
$check_yt = $_GET['check_yt'] ?? false;
$do_all = $_GET['do_all'] ?? false;


$start_time = time();
$verbose = false; #gloabl tracking flag

$dt = new  \DateTime('now',new \DateTimeZone('America/Los_Angeles'));
$start_human = $dt->format ('M d, Y H:i') ;
file_put_contents($logpath,
"THumb check starting $start_human" . NL . NL);

echo "Starting from $start_id at $start_human" . BRNL;

$whereend = $end_id != 0  ? " AND id <= $end_id " : '';

$last_id = runit($pdo,$check_yt,$do_all);


$end_time = time();
$elapsed = $end_time - $start_time + 1; // so you don't get 0
$elapsedh = u\humanSecs ($elapsed);
echo "done.  $elapsedh. Last ID $last_id. <br>";
//add time to url to prevent caching
echo "<a href='$logurl'?$end_time' target = 'log'>Log</a>" . BRNL;
exit;


function runit($pdo,$check_yt,$do_all) {
	$next_id = 1; $end = 0; $bsame=[]; $bnew = [];
	global $asset_db; global $assets; // db name and asset class
if (!is_object($assets)) die ("No asset class");
	$rept_interval = 25; // print progress every this many records;
	$end_condition = ($end > 0) ? " AND id <= $end " : '';
	$sql = "SELECT * from `$asset_db` WHERE id >= ? $end_condition and astatus not in ('T','X') order by id ";
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
			$tsrc = $src = '';
			if (is_integer($rc/$rept_interval)) echo "<small>At record $rc id $id</small><br>";

			if ($end != 0 && $id > $end){
				$done = true;
				$next_id = $last_id + 1;
				return $last_id;
			}

			$last_id = $id;



			$estatus = ''; // capture e and w codes
			$ostatus = $row['astatus']; #old status
			if (in_array($ostatus,['X','T','D','E'])){continue;}
			$b = array(
				'errors'=>'',
				'id' => $id,
			); // for collectioin updates, especailly errors
			$tpjpg = SITE_PATH . '/assets/thumbs/' . $id . '.jpg';

			if (!$do_all && file_exists($tpjpg)) {continue;}

			if ($amime = $row['mime'] ) {
				$type = Defs::$asset_types[$amime];
			} else {
				$estatus = 'E';
				$b['errors'] .= logrec($id,$estatus,"No mime on id $id");
			}


		// check valid thumb source,

			if ($estatus != 'E') {
				//set thumb_url and computed thumb  src
				$thumburl = $row['thumb_url'];
					// compare with original link, since src may have been altered
				if (empty($thumburl) ) {
					$thumburl = '';  // blank for now.  will gt written back to the b array.
					$tsrc =  $row['asset_url']; // used to validate file
					// already knoiwn to be a valid url
				} else {
					$tsrc = $thumburl;  // different url
				}
				// now check validity
				 if (u\is_local($tsrc) ) {
					if (source_exists($tsrc,$check_yt) ) {
						//ok
					}
					else {
						$estatus = 'E';
						$b['errors'] .= logrec($id,$estatus,"Local thumb source does not exist",$tsrc);

					}
				} elseif ($videoid = u\get_youtube_id($tsrc) ) {
					// ok
					if ($check_yt) { // reconstruct youtube thumbs
					echo "reconstructing video thumb for $id" . BRNL;
						$yturl = "http://img.youtube.com/vi/$videoid/mqdefault.jpg" ;
						copy ($yturl , SITE_PATH . "/assets/thumbs/${id}.jpg" );
					}
				} elseif ( $type == 'Image' ) {
					// try creating thumb from remote url useing gd
					echo "Creating image for id $id from remote url $tsrc";
					$simage = null;
					try {
						$sizem = u\get_info_from_curl($tsrc)['size'] / 1000000; #MB
						if ($sizem > 32) { //MB
							throw new Exception ("Remote File too large for GD: " . (int) $sizem . 'MB');
						}

						switch ($amime) {
							case 'image/jpeg':
								$simage = imagecreatefromjpeg($tsrc);
								break;
							case 'image/gif':
								$simage = imagecreatefromgif($tsrc);
								break;
							case 'image/png':
								$simage = imagecreatefrompng($tsrc);
								break;
							default:
								$simage = null;
						}

					} catch (Exception $e) {
							echo "Nope" . BRNL;
							$estatus = 'E';
							$b['errors'] .= logrec($id,$estatus,$e->getMessage(),$tsrc);
							$simage = null;
					}

					if ($estatus != 'E' ) {
						if ($timage = imagescale($simage,Defs::$thumb_width['thumbs']) ) {
							imagejpeg($timage, $tpjpg, 90);
							imagedestroy($simage);
							imagedestroy($timage);
							echo "..Yup" . BRNL;

						} else {
							$estatus = 'E';
							$b['errors'] .= logrec($id,$estatus,"Could not create thumb with GD",$tsrc);
							echo "..Nope" . BRNL;
						}
					}


				} elseif ($icon = get_generic_thumb ($id,$amime) ) {
					// set thumb source to generic icon
						$thumburl = "/assets/graphics/icons/$icon"; // new thumb source
						if (! file_exists(SITE_PATH . $thumburl)) {
							$estatus = 'E';
							$b['errors'] .= logrec($id, $estatus,"Tried to set non-existent icon as thumb source: $icon");

						} else {
							$estatus = 'W';
							$b['errors'] .= logrec($id, $estatus, "Set generic $icon as thumb source");

						}
				} else {
					$estatus = 'E';
					$b['errors'] .=  logrec($id, $estatus,"Invalid thumb source.", $tsrc);

				}

				$b['thumb_url'] = $thumburl;
			}



			if ($estatus != 'E') {
			// now check existance of thumbs


				// not doing for galleries .. too complicated.
				$tpjpg = SITE_PATH . '/assets/thumbs/' . $id . '.jpg';
				$tppng = SITE_PATH . '/assets/thumbs/' . $id . '.png';

				if (file_exists($tpjpg)){
					#ok
				} elseif (file_exists($tppng) ) { // have a png, change to jpg
					$imaget = imagecreatefrompng($tppng);
					imagejpeg($imaget, $tpjpg, 90);
					imagedestroy($imaget);
					logrec ($id,'',"Created a jpeg from existing png");
				} else { // create a new thumb

					#echo "create_thumb($id,$tsrc,'thumbs')" . BRNL;
					if ($assets->saveThumb('thumbs',$id,$tsrc,$amime) ){
						#ok
						logrec($id,' ',"New Thumb from source" ,$tsrc);
 					} else {
 						$estatus = 'E';
						$b['errors'] .=  logrec($id, $estatus,"Cannot create thumb jpg",$tsrc);

					}
				}
				// one last check
				if ($estatus != 'E') {
					if (!file_exists($tpjpg)){
						$estatus = 'E';
						$b['errors'] .= logrec($id,$estatus,"No Thumb File exists");

					}
				}


				// copy old status if nothing changed.
				$b['astatus'] = $estatus ?: $ostatus;

			} #end if old status not error
			record_result($b);

		} #end while adb loop
	$next_id = $last_id + 1;
	} #end while !done loop
  } #end runit

function show_form() {
	global $logurl;
	$t =  <<<EOT
	<p>This program checks and attempt to correct all thumbs in the asset2 table,
	</p>
	<br>
	<p><a href='$logurl'>Click here</a> to look at log from last run.</p>
	<form method = 'GET'>
	<input type='hidden' name='filler' value='1'>
	Check to validate all youtube videos <input type = 'checkbox' name ='check_yt'>
	Check to recreate all thumbs from source <input type = 'checkbox' name ='do_all'>
	</p>
	<input type='submit'>
	</form>
EOT;
 return $t;

}

function get_generic_thumb($aid,$amime) {
	// if url is useable to geneatethumb

	$use_mime = array(
		'application/msword' 	=>	'doc.jpg',
		'application/pdf' 	=>	'pdf.jpg',
		'image/gif'	=>	'image.jpg',
		'image/jpeg'	=>	'image.jpg',
		'image/png'	=>	'image.jpg',
		'image/tiff'	=>	'image.jpg',
		'text/html'	=>	'web.jpg',
		'video/mp4'	=>	'mp4.jpg',
		'audio/mp3'	=>	'mp3.jpg',
		'audio/m4a'	=>	'm4a.jpg',
		'video/quicktime'	=>	'mov.jpg',
	);
	$icon = $use_mime[$amime] ?? 'default.jpg';
	return $icon;
}


function record_result($b) {
	global $pdo,$null;
	global $verbose;
	$mytrack = false; #this routine only
	$track = $mytrack || $verbose;
/**
   $prep = u\pdoPrep($post_data,array_keys($model),'id');

	$sql = "INSERT into `Table` ( ${prep['ifields']} )
			VALUES ( ${prep['ivals']} );

		$stmt = $this->pdo->prepare($sql)->execute($prep['data']);
		$new_id = $pdo->lastInsertId();

    $sql = "INSERT into `Table` ( ${prep['ifields']} )
    		VALUES ( ${prep['ivals']} )
    		ON DUPLICATE KEY UPDATE ${prep['update']};
    		";
       $stmt = $this->pdo->prepare($sql)->execute($prep['udata']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $this->pdo->prepare($sql)->execute($prep['udata']);

  **/

	static $stmti,$sqli;
		if (empty($stmti)){
			$prep = u\pdoPrep($b,[],'id');
//u\echor($prep);
			$sqlu = "UPDATE `assets2` SET ${prep['updateu']}  WHERE id = :pdokey ";
			echo "Setting update sql:<br>$sqlu" . BRNL;
			$stmtu = $pdo->prepare($sqlu);

			// only need to do once
		}

		try {
			$prep = u\pdoPrep($b,[],'id'); #no key field.  Must retain id

			if ( 0 || $track) u\echor ($prep['udata'],$sqlu);
			$stmtu->execute($prep['udata']) ;
		} catch (\PDOException $e) {
			echo "Error writing data". BRNL;
			echo $e->getMessage() . BRNL;
			u\echor ($prep['data'],$sqlu);
			return false;
		}
		return true;

	}

function logrec($aid,$e,$msg,$src='') {
	// logs errors in logfile reclog and also returns the message to go
	// into the new record array[errors]
	global $logpath;
	$local=localtime();
	$t = $local[1] . ':' . $local[0];

	echo "<p class='red'>$aid: $msg</p>";
	file_put_contents($logpath,
		sprintf("%6s %4s %1s %s\n",$t,$aid,$e,$msg),FILE_APPEND);
	if (!empty($src)) {
		file_put_contents($logpath,
		sprintf("%12s %s\n",'',$src),FILE_APPEND);
	}
	return "($e) $msg ". NL;
}

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
	if ($mime = u\is_local($src)) {
		if ($track)  echo "is local...";

			return $mime;

	} elseif ($ytid = u\get_youtube_id ($src)) {

		if ($track)  echo "is youtube $ytid... " ;
		if ($check_yt) {
			$ytapi = "https://www.googleapis.com/youtube/v3/videos?id=$ytid&part=status&key=AIzaSyAU30eOK0Xbqe4Yj0OMG9fUj3A9C_K_edU";

			 try {  // get the thumb from youtbue
				$result = u\get_url($ytapi);
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


	} elseif (! $mime = u\is_http($src) ) {
		return false;
	}
		if ($track)  echo "is url... ";


		foreach (array_keys(Defs::$asset_types) as $m) {
			if (strpos($mime,$m) !== false) {
				$mime = $m;
			} #eliminate other data
		}
		if ($track)  echo "yup $mime". BRNL;
		return $mime;
		} else { return false;}
}

//EOF
