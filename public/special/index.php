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
	


if ($login->checkLogin(2)){
   $page_title = 'AMD Flames Special Pages';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START

?>
<p><a href="./hbwjs80.php">Jerry Sanders' 80th Birthday</a></p>
<p><a href="./anixter.php">Tribute to Ben Anixter</a></p>
<p><a href="./Upward.php">AMDers who have gone on to found or lead other companies</a>
<p><a href="/galleries.php/?4547" target="gallery">AMD Ad Reprints</a>
<p><a href="./spirit.php" >The Spirit of AMD</a>

</div>
</div>
</body></html>
