<?php
namespace digitalmx\flames;

//BEGIN START
	require_once 'init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\Assets;
	#use digitalmx\flames\DocPage;
	

// 	$page_title = 'Asset Viewer';
// 	$page_options = [];
// 	
// 	
//     $login->checkLogin(1); 
// 	$page = new DocPage($page_title);
// 	echo $page -> startHead($page_options);


// script to copy assets to assets, and to check values




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

$pdo = MyPDO::instance();


$sql = "SELECT * from `assets` ";
$adb = $pdo->query($sql);
$rc = 0;
while ($row = $adb->fetch(){
	++$rc;
	$id = $row['id'];
	// make new array 'b'
	$b = array();
	foreach ($same as $v){
		$b[$v] = $row[$v];
	}
	$b['title'] = stripslashes($row['title']);
	$b['caption'] = stripslashes($row['title']);
	
	//check url
	if (empty($src = $row['url'])) {
		echo "No source specified on id $id" . BRNL;
	}
	
	if ($substr($src,0,1) == '/'){
		if (! file_exists(SITE_PATH . $src)){
			echo "Source does not exist on id $id:<br>&nbsp;&nbsp;" . $src .  BRNL; 
		}
	} elseif (! u\url_exists($src) ){
		echo "Source does not exist on id $id:<br>&nbsp;&nbsp;" . $src .  BRNL; 
	}
	
	
}
	
	
echo "done.";
