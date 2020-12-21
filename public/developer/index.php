<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(7);

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
$repo_file = REPO_PATH . '/var/repo_warning.txt';
if (isset($_POST['repo_warn'])){
	file_put_contents($repo_file, $_POST['repo_warn']);
}
###########################
$repo_warn = (file_exists($repo_file)) ? file_get_contents( $repo_file ) : '';
?>
<h3>Developer Applications</h3>
<form method='GET'>
<ul>
<li><button name='run'
onClick = "window.open('/developer/update_galleries.php','geng');return false;" value='galleries'>Add galleries to first used.</button>

<li><button name='run' value='refresh'>Refresh</button>
Refresh all tables from live db to dev
<li><button name='run' onClick = "window.open('/developer/set_astatus.php','genn');return false;"
>Set admin_status</button>
Sets the admin status for all listed members.


<li><button name='run'
onClick = "window.open('/developer/gen_articles.php','genn');return false;" value='articles'>Build Articles</button>

Build new Articles table from old news_items

<li><button name='run'
onClick = "window.open('/developer/gen_pubs.php','genp');return false;" value='pubs'>Build Pubs</button>
Build new Pubs table from live version of index.json and Articles and read_table

<li><button name='run'
onClick = "window.open('/developer/gen_assets2.php','gena');return false;" value='assets'>Build Assets2</button>
Build new Assets2 table from Assets

<li><button name='run'
onClick = "window.open('/developer/varinfo.php/?v','vars');return false;" value='varinfo'>Run Varinfo</button>

</ul>
</form>
<form method='post'>
Warning message to display at top of each page in this repo:<br>
<input type=text name='repo_warn' size='60' value='<?=$repo_warn?>'>
<input type=submit>
</form>




//EOF
