<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	


if ($login->checkLogin(4)){
   $page_title = 'test quick asset';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
$asseta = new AssetAdmin();

	
	echo <<<EOT
	<button onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >New asset</button>
	
EOT;

echo "<div class='asset-top' >";
echo $asseta->getAssetBlock(5230,'thumb',false);

echo $asseta->getAssetBlock(3169,'gallery',false);
echo "</div>" 
;
echo "<div class='clear'></div>";

echo "<div class='asset-left' >";
echo $asseta->getAssetBlock(5230,'thumb',true);

echo $asseta->getAssetBlock(3169,'gallery',true);
echo "</div>
";

echo "
<div class='article'>
<p>copy copy copy</p>
<div class='asset-center'>";
	echo $asseta->getAssetBlock(3726,'gallery',false);
echo "</div>
</div>
<div class='clear'></div>
";


	
//END START
