<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLevel(4)){
   $page_title = 'test quick asset';
	$page_options=['ajax']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo  "<script src='/js/aq.js'></script>";

	echo $page->startBody();
}



$asseta = $container['asseta'];
// set default arrangement

$ta = $aids = '';

if (isset($_POST['submit']) ){
	$aids = $_POST['aids'];
	$nlist = u\number_range($aids);
	$nlistcnt = count($nlist);

	$ta = $_POST['ta'];
	if ($nlistcnt >2){
		$adiv = 'asset-row';
	} elseif ($nlistcnt > 0){
		$adiv = 'asset-column';
	} else {$adiv = '';}


	echo "
<div class='article clearafter'>
";
echo "<p class='topic'>This is the topic</p>" . NL;
echo "<p class='headline'>This a test article</p> " .NL;

if ($adiv) {
	echo "<div class='$adiv'>";
	foreach ($nlist as $aid) {
		echo $asseta->getAssetBlock($aid,'thumb',false);
	}
	echo "<div class='clear'></div>" . NL;
	echo "</div>" . NL;
}


echo "<div class='content'>";
echo $ta;
echo "<p class='source'> From: Source <span class='contributor'> --Contibributed by username</span></p>" ;
echo "<div class='ed_comment'>Ed comment here</div>";
echo "</div>" . NL;






echo "</div>" . NL; #end article





}

$abutton = <<<EOT


EOT;

echo <<<EOT
<hr>
<form method='post'>
Content: <textarea name='ta' cols = '60' rows='4'  class='useredit'>$ta</textarea><br>
Assets: <input type='text' id='assetids' name='aids' value = '$aids' >

$abutton
<br>
<input type='submit' name='submit' value='submit'>
</form>



EOT;











//END START
