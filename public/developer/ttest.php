<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;

	use DigitalMx\Flames\Thumbs2 as Thumbs;


$login->checkLevel(0);

$page_title = 'Thumb tests';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START

$assetv = $container['assetv'];

if (empty ( $_POST['id']) ){
	show_form();
	exit;
}
$id =  $_POST['id'];

$id = $_POST['id'] ?? '';
echo "getting id $id" . BRNL;




$turl = $assetv->getThumb($id,'medium');
echo "<image src='$turl' />";


exit;


##################################
function show_form($id='') {
		echo <<<EOT
<form method='POST'>
Get id: <input type=text name='id' value = '$id' autofocus > <button type='submit'>submit</button>
</form>

EOT;
}

//EOF
