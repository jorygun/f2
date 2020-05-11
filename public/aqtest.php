<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	


if ($login->checkLogin(4)){
   $page_title = 'test quick asset';
	$page_options=['ajax']; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo  "<script src='/js/aq.js'></script>";

	echo $page->startBody();
}
	
	$abutton = <<<EOT
<button onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >New asset</button>
EOT;

$asseta = new AssetAdmin();
// set default arrangement
$left_checked = 'checked'; $top_checked = '';
$ta = $aids = '';

if (isset($_POST['submit']) ){
	$aids = $_POST['aids'];
	$nlist = u\number_range($aids);
	$ta = $_POST['ta'];
	$left_checked = $top_checked = '';
	if ($_POST['aarrange'] == 'left'){
		$adiv = 'asset-left';
		$left_checked = 'checked';
	} elseif ($_POST['aarrange'] == 'top'){
		$adiv = 'asset-top';
		$top_checked = 'checked';
	} else {die ("No asset arrangement");}
	
	
	echo "
<div class='article'>
";
echo "<div class='head'>
	<p class='headline'>This a test article</p>
	</div>" . NL;

echo "<div class='content'>";

echo "<div class='$adiv' >";
	foreach ($nlist as $aid) {
		echo $asseta->getAssetBlock($aid,'thumb',false);
	}
echo "</div>" . NL;
if ($adiv == 'top') {
	echo "<div class='clear'></div> " . NL;
}
echo "<div class='article'>" . NL;
echo "<p>$ta</p>" . NL;

echo "
</div>
</div>
</div>

";

	
}
echo <<<EOT
<hr>
<form method='post'>
Content: <textarea name='ta' class='useredit'>$ta</textarea>
Assets: <input type='text' id='assetids' name='aids' value = '$aids' >
Arrange: 
<input type='radio' name='aarrange' value='top' $top_checked>Top
<input type='radio' name='aarrange' value='left' $left_checked>Left
<br>
<input type='submit' name='submit' value='submit'>
</form>

$abutton

EOT;










	
//END START
