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

$page_title = 'Developer';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();




if (isset($_GET['run'])) {
	$run = $_GET['run'];
	switch ($run){
		case 'refresh':
			system (SITE_PATH . '/developer/reload_dev_live.sh');
			break;


		default:
			echo "Not Implemented";
			// do nothing

	}
}

###########################

?>
<h3>Developer Applications</h3>
<form method='GET'>
<ul>
<li><button name='run' value='refresh'>Refresh</button>
Refresh assets, news_items, read_table and index.json from live db to dev

<li><button name='run'
onClick = "window.open('/developer/gen_articles.php','genn');return false;" value='articles'>Build Articles</button>

Build new Articles table from old news_items

<li><button name='run'
onClick = "window.open('/developer/gen_pubs.php','genp');return false;" value='pubs'>Build Pubs</button>
Build new Pubs table from live version of index.json and Articles and read_table

<li><button name='run'
onClick = "window.open('/developer/gen_assets2.php','gena');return false;" value='assets'>Build Assets2</button>
Build new Assets2 table from Assets

</ul>
</form>



//EOF
