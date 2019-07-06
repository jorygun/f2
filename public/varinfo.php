<?php
namespace digitalmx\flames;

$verbose = false; $test = false;

ini_set('display_errors', 1);
$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);
session_start();
#collect initial data prior to session_start running
$pre_out = '';
$repo = basename(dirname(__DIR__));

$req=$_SERVER['QUERY_STRING'];
if (!empty($req)){
	$test = (strpos($req,'t') !== false);
	$verbose = (strpos($req,'v') !== false);
}
$init_file = '../config/init.php';
require $init_file;

use digitalmx\flames\DocPage;

$page = new DocPage;
echo $page->getHead('my title');
echo $page ->startBody('page title' );

// echo <<<EOT
// <html>
// <head>
// <title>Varinfo ($repo)</title>
// <style>
// 	body {max-width:800px; 
// 		overflow-wrap: break-word;
// 	}
// 	.red {color:red;}
// 	
// </style>
// </head>
// <body >
// varinfo.php - last updated $mtimet<br>
// 
// 
// EOT;
echo "Test Mode: $test";


echo "<br><b>initial include_path: </b>" . get_include_path() ."<br><br>\n";

$sitedir = dirname(__DIR__); #...<repo>/
$projdir = dirname($sitedir);
$reponame = basename($sitedir);

echo "<br>";

echo "<b>sitedir:</b> " . $sitedir . "<br>\n";
echo "<b>projdir:</b> " . $projdir . "<br>";
echo "<b>repo:</b> " . $reponame . "<br>";
echo "<br>\n";

## show envir vars
$server_adds = array();
$server_changes = array();

foreach ($_SERVER as $k=>$v){
	if (!isset($_ENV[$k])){
		$server_adds[$k] = $v;
	}
	elseif ($_ENV[$k] !== $v){
		$server_changes[$k] = $v;
	}
}



echo "<p><b>post-init include_path: </b><br>" . str_replace(':','<br>:',get_include_path()) ."</p><br>\n";

if ($verbose) {
	recho ($_ENV,'$_ENV');

	recho ($server_changes,'Changed value in $_SERVER');
	recho ($server_adds,'Added to $_SERVER');
	recho ($_SESSION,'$_SESSION');

	recho ($GLOBALS,'$GLOBALS');




	if (file_exists('.htaccess')){
		$htaccessm = date('d M H:i',filemtime('.htaccess'));
		echo ".htaccess ($htaccessm) :<br><pre>";
		echo file_get_contents('.htaccess');
		echo '</pre>';
	} else {
	echo "No .htaccess";
	}
}
echo "<br><hr><br />";


use digitalmx\flames\Definitions as Defs;
use digitalmx as u;


try {
	echo "Defs: seclevel ma: " . Defs::getSecLevel('MA').BRNL ;
} catch (Error $e) {
	echo_red ("Defs not loaded") . BRNL;
}

try {
	echo "utilities/days_ago since Feb 30: " . days_ago('Feb 30, 2017') . BRNL;
} catch (Error $e) {
	echo_red ("utilities.php not loaded") . BRNL;;
}


try {
	echo "pretty_date (rfc): " . u\make_date(time(),'rfc') . BRNL;
} catch (Error $e) {
	echo_red ('pretty_date function not available.') . BRNL;
}

###33#####
function echo_red ($t) {
	echo "<p class='red'>$t</p>";
	
}
require 'SiteUtilities.php';
#require 'Member.php';
use digitalmx\flames\Member;
$member = new Member($pdo);

#$md = $member->getMemberList('john.scott.springer@gmail.com');
#u\echor ($md,'Member Data');

// $em = new Messenger ($pdo,$test); #pdo,true for test
// $event = 'em-found';
// $event_extra = array('informant'=>'Teddy Technjcal');
//  $em->sendMessages(11602,$event,$event_extra);
//  echo "Sent $event<br";

// require "MemberAdmin.php";
// $ma = new MemberAdmin($pdo);
// echo $ma->showSearch();
// if ($_SERVER['REQUEST_METHOD'] == 'POST'){
// 	echo $ma->search($_POST);
// }
use \digitalmx\flames\Login;
use digitalmx\flames\Menu;

// $md = $member->getMemberFromLogin('6kQ4k11602');
// u\echor ($md,'Member from Login');
// $nav = new Menu($md);
// $navbar = $nav->getMenuBar();
// echo $navbar;
// 

echo $_SESSION['menu'];

