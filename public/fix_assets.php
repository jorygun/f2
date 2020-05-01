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


$sql = "SELECT * from `assets` LIMIT 10 ";

$adb = $pdo->query($sql);


$rc = 0;

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
	$e['id'] = $id;
	if (strpos($row['title'],'\\') != 0){
		$b['title'] = $e['title'] = stripslashes($row['title']);
	}
	if (strpos($row['caption'],'\\') != 0){
		$b['caption'] = $e['caption'] = stripslashes($row['title']);
	}
	//check link
	if (empty($src = $row['link'])) {
		echo "<p class='red'>No source specified on id $id </p>";
		$e['status'] = 'E';
	}
	
	if (substr($src,0,1) == '/'){
		if (substr($src,1,) == 'reunions'){
			$src = '/assets' . $src;
		}
		$s = SITE_PATH . $src;
		if (! file_exists($s)){
			echo "<p class='red'>Local source does not exist on id $id:<br>&nbsp;&nbsp;" . $s .  '</p>'; 
			$e['status'] = 'E';
		}
		
		
	} elseif (! u\url_exists($src) ){
		echo "<p class='red'>Remote source does not exist on id $id:<br>&nbsp;&nbsp;" . $src .  '</p>' ;
		$e['status'] = 'E';

	}
	$b['asset_url'] = $src;
	$e['link'] = $src;
	
	// check thumb
	$thm = $row['url'];
	if (!empty($thm) && $thm != $src) {
		$b['thumb_url'] = $thm;
		$e['url'] = $thm;
	} else {
		$b['thumb_url'] = '';
		$e['url'] = '';
	}
	
	if (! file_exists(SITE_PATH . '/assets/thumbs/' . $id . '.jpg')){
		if (create_thumb($id,$src,$ttype='thumbs') ){
			if (file_exists(SITE_PATH . '/assets/thumbs/' . $id . '.png')){
				unlink (SITE_PATH . '/assets/thumbs/' . $id . '.png');
			}
		} else {
			echo "<p class='red'>No thumb file for asset $id</p>";
			$e['status'] = 'E';
		}
	}
	
	// moD DATE
	$b['date_modified'] = $row['mod_date'];
	
	if (empty($mime = $row['mime'])){
		if (substr($src,0,1) == '/'){
			if (! $mime = $finfo->file(SITE_PATH . $src) ){
				echo "<p class='red'>Unable to get mime type from source $src" .'</p>';
				$e['status'] = 'E';
			}
		 } elseif (!$mime = get_url_mime_type($src) ) {
			echo "<p class='red'>Unable to get mime type from source $src" . '</p>';
			$e['status'] = 'E';
		} else {
			echo "<p class='red'>Unable to get mime type from source $src" . '</p>';
			$e['status'] = 'E';
		}
	}
	$b['mime'] = $mime;
	$e['mime'] = $mime;
	
		$prep = pdoPrep($b,$allowed_list,''); #no key field.  Must retain id
 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/

#u\echor ($prep,'prep');

	$sql = "INSERT into `assets2` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
     #  $stmt = $pdo->prepare($sql)->execute($prep['data']);
   
   $eprep = pdoPrep($e,$allowed_list,'id');
   $sql = "UPDATE `assets` SET ${eprep['update']} WHERE id = ${eprep['key']} ;";
     #  $stmt = $pdo->prepare($sql)->execute($eprep['data']);
#u\echor($eprep,'E Prep');

   
  ob_end_flush();

}
	
echo "done.";
