<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = '';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START

if (empty ($_GET['id']) ){
	show_form();
	exit;
}


$id = $_GET['id'];
echo "getting id $id" . BRNL;

$adata = $container['assets'] -> getAssetDataById($id);
//u\echor($adata);

$aurl = $adata['asset_url'];
$turl = $adata['thumb_url'];

echo "Current asset mime " . $adata['mime'] . BRNL;



$thumbs = new Thumbs($id,$aurl,$turl);

$ttype = 'thumbs';

$thumbs->createThumb($ttype);
echo "<a href='$aurl'><img src='/assets/" . $ttype . "/${id}.jpg' /> </a>" . BRNL;

show_form();
exit;
function show_form() {
		echo <<<EOT
<form method='GET'>
Get id: <input type=text name='id' > <button type='submit'>submit</button>
</form>

EOT;
}

//EOF
