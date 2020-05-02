<?php
namespace digitalmx\flames;
ini_set('default_socket_timeout', 10);
//BEGIN START
	require_once 'init.php';

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


require_once 'scripts/asset_functions.php';
$finfo = new \finfo(FILEINFO_MIME_TYPE);


$adb = array(
	'id',	'status',	'title',	'caption',	'keywords',	'mime',	'type',	'url',	'thumb_file',	'link',	'vintage',	'source',	'contributor',	'contributor_id',	'date_entered',	'mod_date',	'height',	'width',	'sizekb',	'notes',	'has_thumb',	'has_gallery',	'has_toon',	'review_ts',	'skip_ts',	'first_use_date',	'first_use_in',	'tags',	'reviews',	'up_votes',	'down_votes',	'votes',	'comment_count',	'gallery_items',	'user_info',	'temptest',
	);
$same = array(
	'id',	'status',		'keywords',		'type',		'vintage',	'source',		'contributor_id',	'date_entered',			'sizekb',	'notes',		'review_ts',	'skip_ts',	'first_use_date',	'first_use_in',	'tags',	
	);

$altered = array (
'title',	'caption','url',	'thumb_file',	'link','mod_date','mime',
);
$removed = array (
'contributor','height',	'width','has_thumb',	'has_gallery',	'has_toon',
'reviews',	'up_votes',	'down_votes',	'votes',	'comment_count',	'gallery_items',	'user_info',	'temptest',
);

		$allowed_list = [];
echo "starting" . BRNL;

// empty the existing data
$sql = 'DELETE from `assets2`;';
$pdo->query($sql);
echo "Clearing db" . BRNL;


$sql = "SELECT * from `assets` WHERE temptest != 'OK' and status not in ('D','X','T') ORDER BY id  ";

$adb = $pdo->query($sql);


$rc = 0;
$newOKs = $notOKs = 0;
while ($row = $adb->fetch() ){
	++$rc; if (is_integer($rc/25)) echo "$rc <br>";
	$id = $row['id'];

	$status = $row['status'];
	if (in_array($status,['X','T','D'])){continue;}
	// make new array 'b'

	$e = $b = array(); // e for error corrections
	foreach ($same as $v){
		$b[$v] = $row[$v];
	}
	
	if (strpos($row['title'],'\\') != 0){
		$b['title'] = $e['title'] = stripslashes($row['title']);
	}
	if (empty($row['title'])){
		$b['title'] = $e['title'] = 'Untitled';
	}
	if (strpos($row['caption'],'\\') != 0){
		$b['caption'] = $e['caption'] = stripslashes($row['title']);
	}
	//check link
	if (empty($src = trim($row['link']))) {
		echo "<p class='red'>No source specified on id $id </p>";
		$e['temptest'] = 'no_source';
		
	}
	$osrc = $src;
	if (substr($src,0,1) == '/'){
		if (substr($src,1,8) == 'reunions'){
			$src = '/assets' . $src;
			
		}
		elseif (preg_match('|^/newsp/SalesConf/(.*)|',$src,$m)){
			$src = '/assets/sales_conferences/' . $m[1];
		}
		elseif (preg_match('|^/sales_conferences/(.*)|',$src,$m)){
			$src = '/assets/sales_conferences/' . $m[1];
		}
		$s = SITE_PATH . $src;
		if (! file_exists($s)){
			echo "<p class='red'>Local source does not exist on id $id:<br>&nbsp;&nbsp;" . $s .  '</p>'; 
			$e['temptest'] = 'nno local source';
		}
	} elseif (! url_exists($src) ){
		echo "<p class='red'>Remote source does not exist on id $id:<br>&nbsp;&nbsp;" . $src .  '</p>' ;
		$e['temptest'] = 'no remote source';
	}
	$b['asset_url'] = $src;
	if ($osrc != $src){
		$e['link'] = $src;
	}
	
	// check thumb source
	$thm = trim($row['url']);
	
	if (!empty($thm)) {
		
		if ($thm != $src) {
			$b['thumb_url'] = $thm;
		} else {
			$b['thumb_url'] = '';
			$e['url'] = '';
		}
	}
	
	if (!isset($e['temptest'] ) ){ 
	if (! file_exists(SITE_PATH . '/assets/thumbs/' . $id . '.jpg')){
		if (create_thumb($id,$src,$ttype='thumbs') ){
			if (file_exists(SITE_PATH . '/assets/thumbs/' . $id . '.png')){
				unlink (SITE_PATH . '/assets/thumbs/' . $id . '.png');
			}
		} else {
			echo "<p class='red'>No thumb file for asset $id</p>";
			$e['temptest'] = 'no thumb';
		}
	}
	}
	// moD DATE
	$b['date_modified'] = $row['mod_date'];
	
	if (!isset($e['temptest'] ) ){ 
	if (empty($mime = $row['mime'])){
		if (substr($src,0,1) == '/'){
			if (! $mime = $finfo->file(SITE_PATH . $src) ){
				echo "<p class='red'>Unable to get mime type from source $src" .'</p>';
				$e['temptest'] = 'no mime';
			}
		 } elseif (!$mime = get_url_mime_type($src) ) {
			echo "<p class='red'>Unable to get mime type from source $src" . '</p>';
			$e['temptest'] = 'no mime';
		} else {
			echo "<p class='red'>Unable to get mime type from source $src" . '</p>';
			$e['temptest'] = 'no mime';
		}
	}
	$b['mime'] = $mime;
	$e['mime'] = $mime;
	}
	
	$b['id'] = $id;
	
		
	
 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
  if (0) {
	$prep = pdoPrep($b,$allowed_list,''); #no key field.  Must retain id
	#u\echor ($prep,'prep');
	$sql = "INSERT into `assets2` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
  $stmt = $pdo->prepare($sql)->execute($prep['data']);
  }
  
  

	$e['id'] = $id;
	if(!isset($e['temptest'])) {
		$e['temptest'] = 'OK';
		++$newOKs;
	}
	else {++$notOKs;}
   $eprep = pdoPrep($e,$allowed_list,'id');
   $sql = "UPDATE `assets` SET ${eprep['update']} WHERE id = ${eprep['key']} ;";
 #u\echor($eprep,'E Prep');

 	$stmt = $pdo->prepare($sql)->execute($eprep['data']);

	
	
}

echo "done. $rc records. $newOKs new OKs; $notOKs not OKs.";

##############
function url_exists($url){
   if ($headers=get_headers($url) ) {
   	if (stripos($headers[0]," 40") === false) {
   		return true;
   	}
   }
   echo 'Bad Header ' . $headers[0];
   return false;
}
