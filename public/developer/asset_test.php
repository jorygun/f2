<?php

namespace DigitalMx\Flames;
ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;
	


if ($login->checkLogin(4)){
   $page_title = 'asset test page';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START
$id = '5189';
$testa = array (
	'id' => 0,
	'asset_source' => '/assets/files/4936.png' ,
	'thumb_source' => '' ,
	'title' => 'flow chart' ,
	'caption' => '' ,
	'keywords' => '' ,
	'tags' => '' ,
	'source' => 'old files' ,
	'vintage' => '1966' ,
	'contributor_id' => '10621' ,
	'first_use_date' => '' ,
	'first_use_in' => '' ,
	'status' => 'N',
	'notes' => 'just a test',
	
	

);


$am = new Asset();
$aa = new AssetAdmin($am);

$id = $aa-> postAssetFromForm ($testa);


// $am -> saveThumbs($id,['galleries']);
#echo $am->updateStatus($id,'R');

$th =  "/assets/thumbs/$id.jpg";

echo <<<EOF

<image src='$th' >; 
EOF;
