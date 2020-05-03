<?php
namespace digitalmx\flames;
ini_set('default_socket_timeout', 15);
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

$write_new_db = false;

$old_to_new =  array(
	'id'	=>	'id',
	'status'	=>	'status',
	'title'	=>	'title',
	'caption'	=>	'caption',
	'keywords'	=>	'keywords',
	'mime'	=>	'mime',
	'type'	=>	'type',
	'url'	=>	'thumb_url',
	'thumb_file'	=>	'',
	'link'	=>	'asset_url',
	'vintage'	=>	'vintage',
	'source'	=>	'source',
	'contributor'	=>	'',
	'contributor_id'	=>	'contributor_id',
	'date_entered'	=>	'date_entered',
	'mod_date'	=>	'date_modified',
	'height'	=>	'',
	'width'	=>	'',
	'sizekb'	=>	'sizekb',
	'notes'	=>	'notes',
	'has_thumb'	=>	'',
	'has_gallery'	=>	'',
	'has_toon'	=>	'',
	'review_ts'	=>	'review_ts',
	'skip_ts'	=>	'skip_ts',
	'first_use_date'	=>	'first_use_date',
	'first_use_in'	=>	'first_use_in',
	'tags'	=>	'tags',
	'reviews'	=>	'',
	'up_votes'	=>	'',
	'down_votes'	=>	'',
	'votes'	=>	'',
	'comment_count'	=>	'',
	'gallery_items'	=>	'',
	'user_info'	=>	'',
	'temptest'	=>	'temptest',

	);


echo "starting" . BRNL;


$pdo->query("delete from `assets`");
$pdo->query("insert into `assets` select * from `assetsback`");

if ($write_new_db){
// empty the existing data
	$sql = 'DELETE from `assets2`;';
	$pdo->query($sql);
	echo "Clearing assets2" . BRNL;
}

// set up pdo tatement.  Use old to new to get fields; ignore data
$eprep = pdoPrep($old_to_new,[],'id');
$sql = "UPDATE `assets` SET ${eprep['update']} WHERE id = ${eprep['key']} ;"
echo "eprep $sql" . BRNL;
$estmt = $pdo->prepare($sql);

// set up new db statement. 
$b = set_new_b($old_to_new); // new array with old-to-new values as keys, empty data
$bprep = pdoPrep($b,[],''); #no key field.  Must retain id
$sql = "INSERT into `assets2` ( ${bprep['ifields']} ) VALUES ( ${bprep['ivals']} );";
$bstmt = $pdo->prepare($sql);



$sql = "SELECT * from `assets` WHERE 
status not in ('O''X','T','F') 
 ";

$adb = $pdo->query($sql);

$rc = 0;
$newOKs = $notOKs = 0;

while ($row = $adb->fetch() ){
	++$rc;# if (is_integer($rc/25)) echo "$rc <br>";
	$id = $row['id'];
	$status = $row['status'];
	$edit_me = "<p style='background:#CFC;border=1px solid green;'>
	<a href='/scripts/asset_edit.php?id=$id' target='asset_editor'>
	Edit $id</a></p>";
	// make new array 'b'

	$e = array(); // e for error corrections
	
	
	if (strpos($row['title'],'\\') != 0){
		 $e['title'] = stripslashes($row['title']);
	}
	if (empty($row['title'])){
		 $e['title'] = 'Untitled';
	}
	if (strpos($row['caption'],'\\') != 0){
		$e['caption'] = stripslashes($row['title']);
	}
	//check link
	if (empty($src = trim($row['link']))) {
		echo "<p class='red'>No source specified on id $id </p>";
		$e['temptest'] = 'no_source';
		
	}
	if (! isset ($e['temptest']) ) {
		$osrc = $src;
		$omime = $row['mime'];
		
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
			if (! file_exists(SITE_PATH . $src)){
				echo "<p class='red'>Local source does not exist on id $id:<br>&nbsp;&nbsp;" . $src .  '</p>'; 
				$e['temptest'] = 'no local source';
			} elseif  (! $mime = $finfo->file(SITE_PATH . $src) ){
				echo "<p class='red'>ID $id Unable to get mime type from source $src" .'</p>';
				$mime = '';
				$e['temptest'] = 'cannot get mime';
			}
		} elseif (substr($src,0,4) == 'http') {
			if ($h = get_headers($src,1) ){
				if (strpos($h[0],' 40') > 0){
					echo "<p class='red'>ID $id Remote source does not exist <br>&nbsp;&nbsp;" . $src .  '</p>' ;
					$e['temptest'] = 'no remote source';
				} elseif  (! $mime = $h['Content-Type']){
					echo "<p class='red'>ID $id Cannot retreive content-type.</p>";
					$e['temptest'] = 'no content-type';
					$mime = 'text/html';
					
				} else {
					$mime = substr($mime,0,strpos($mime,';'));
				}
			} else {
				echo "<p class='red'>ID $id cannot get headers from source.</p>";
				$e['temptest'] = 'cannot get headers';
				}
				
		}
		else {
			echo "<p class='red'>ID $id Uknown service on $src </p>";
			$e['temptest'] = 'unknown service';
		}
	
		if ($osrc != $src){
			$e['link'] = $src;
		}
	}
	
	
	// check thumb source
	$thm = trim($row['url']);
	
	if (!empty($thm)) {
		
		if ($thm == $src) {
			$e['url'] = '';
		}
	}
	
	if (!isset($e['temptest'] ) ){ 
		if (! file_exists(SITE_PATH . '/assets/thumbs/' . $id . '.jpg')){
				echo "<p class='red'>No thumb file for asset $id</p>";
				$e['temptest'] = 'no thumb';
		}
	}
	
		
		if ($mime && $mime != $omime){
			$e['mime'] = $mime;
		}
	

	$e['id'] = $id;
	if(!isset($e['temptest'])) {
		$e['temptest'] = 'OK';
		++$newOKs;
		if ($status != 'R'){$e['status'] = 'O';}
	}
	else {
		$e['status'] = 'E';
		++$notOKs;
		echo $edit_me;
		
	}
	// if any changes, merge with original data and rewrite record
	if (!empty($e)){
		$new_row = array_merge($row,$e);
		$estmt ->execute($new_row);
	}

	if ($write_new_db) { #move to b array
		$b = copy_to_new ($new_row,$old_to_new);
	  	$bstmt ->execute($prep['$b']);
  	}
  
	
}

echo "done. $rc records. $newOKs new OKs; $notOKs not OKs.";

##############
function copy_to_new ($row,$old_to_new){
	$new = array ();
	foreach ($old_to_new as $a => $b){
			if ($b) $new[$b] = $row[$a];
	}
	return $new;
}
  
function url_exists2($id,$url){
	if(filter_var($url, FILTER_VALIDATE_URL) === FALSE)
	{
        echo "Invalid URL: ". $url;
			return false;
	}
   if ($headers=get_headers($url) ) {
   	if (stripos($headers[0]," 40") === false) {
   		return true;
   	}
   }
   echo "ID $id Bad Header " . $headers[0] . $headers[8];
   return false;
}

function set_new_b($old_to_new){
	foreach (array_values($old_to_new) as $val){
		if ($val) { $b[$val] = ''; }
	}
	return $b;
}
