<?php
namespace digitalmx\flames;

$verbose = false; $test = false;

ini_set('display_errors', 1);
$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);

#collect initial data prior to session_start running
$pre_out = '';
$repo = basename(dirname(__DIR__));

$req=$_SERVER['QUERY_STRING'];
if (!empty($req)){
	$test = (strpos($req,'t') !== false);
	$verbose = (strpos($req,'v') !== false);
}
$initial_include = get_include_path();

$init_file = $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require $init_file;

use digitalmx\flames\Definitions as Defs;
use digitalmx as u;

use digitalmx\flames\Login;
use digitalmx\flames\Menu;
use digitalmx\flames\DocPage;
use digitalmx\flames\Member;



$pagetitle="";
$pageoptions=[]; #ajax, votes, tiny 

if ($login->checkLogin(0)){
	$page = new DocPage($page_title);
	echo $page -> startHead($pageoptions);
	echo $page->startBody();
}

u\echor ($_SESSION,'Session file at after page setup');






echo " Mode: $test";


echo "<p><b>initial include_path: </b> <br>" .
	str_replace(':','<br>',$initial_include) ."</p><br>\n";

echo "<p><b>post-init include_path: </b><br>" . str_replace(':','<br>:',get_include_path()) ."</p><br>\n";

$sitedir = dirname(__DIR__); #...<repo>/
$projdir = dirname($sitedir);
$reponame = basename($sitedir);

echo "<br>";

echo "<b>sitedir:</b> " . $sitedir . "<br>\n";
echo "<b>projdir:</b> " . $projdir . "<br>";
echo "<b>repo:</b> " . $reponame . "<br>";
echo "<br>\n";

if (!empty($ldata = $_SESSION['login'])){
	u\echor($ldata, 'Logged In User');
} else {
	echo "No logged in User" . BRNL;
}

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





if ($verbose) {
	u\echor ($_ENV,'$_ENV');

	u\echor ($server_changes,'Changed value in $_SERVER');
	u\echor ($server_adds,'Added to $_SERVER');
	u\echor ($_SESSION,'$_SESSION');

	u\echor ($GLOBALS,'$GLOBALS');




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
	echo "make_date (now,rfc): " . u\make_date('now','rfc') . BRNL;
} catch (Error $e) {
	echo_red ('make_date function not available.') . BRNL;
}

###33#####
function echo_red ($t) {
	echo "<p class='red'>$t</p>";
	
}



$member = new Member();

$membertag = 'john@digitalmx.com';
$md = $member->getMemberList($membertag);
u\echor ($md,'Member Data for ' . $membertag);

// $em = new Messenger ($pdo,$test); #pdo,true for test
// $event = 'em-found';
// $event_extra = array('informant'=>'Teddy Technjcal');
//  sendMessages(11602,$event,$event_extra);
//  echo "Sent $event<br";

// require "MemberAdmin.php";
// $ma = new MemberAdmin($pdo);
// echo $ma->showSearch();
// if ($_SERVER['REQUEST_METHOD'] == 'POST'){
// 	echo $ma->search($_POST);
// }



// $md = $member->getInfoFromLogin('6kQ4k11602');
// u\echor ($md,'Member from Login');
// $nav = new Menu($md);
// $navbar = $nav->getMenuBar();
// echo $navbar;
// 

// use digitalmx\flames\SendLogin;
// $sender = new SendLogin($pdo);
// $sender->sendLink('john.scott.springer@gmail.com');

