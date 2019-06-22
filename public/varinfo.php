<?php


ini_set('display_errors', 1);
$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);
session_start();
#collect initial data prior to session_start running
$pre_out = '';
$repo = basename(dirname(__DIR__));

echo <<<EOT
<html>
<head>
<title>Varinfo ($repo)</title>
<style>
	body {max-width:800px; 
		overflow-wrap: break-word;
	}
	.red {color:red;}
	
</style>
</head>
<body >
varinfo.php - last updated $mtimet


EOT;


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

#$init_file = "../init.php"; #at site level, ie., Sites/flames/f2

#$init_file = $_SERVER['REDIRECT_SITE_INIT'] ?? 'No Init in ENV';
$init_file = '../config/boot.php';

#	$old_init_file = '../config/init.php';



echo "Looking for boot file: $init_file <br>\n";
if (file_exists($init_file)){
	echo "Begin Site init from boot ... ";
	try {
		if (! include "$init_file"){
			throw new Exception ("$init_file failed to load");
		}
	}catch (Exception $e){
		echo "$init_file failed to execute <br>" . $e->getMessage();
	}
	echo "site init done.<br>";

} else {
	echo ".. not found, looking for old init $old_init_file <br>";
	if (file_exists($old_init_file)){
		try {
		if (! include "$old_init_file"){
			throw new Exception ("$old_init_file did not load");
		}
		}catch (Exception $e){
		echo "$old_init_file failed to execute <br>" . $e->getMessage();
		}
		echo "site init-old done.<br>";
	}
	else {
		echo "Init not found; skipped.<br>";
		function recho($var,$title=''){
    		echo "<h4>$title:</h4>";
    		echo "<pre>" .  print_r($var,true) . "</pre>\n";
		}
	}
}

echo "<p><b>post-init include_path: </b><br>" . str_replace(':','<br>:',get_include_path()) ."</p><br>\n";

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

echo "<br><hr><br />";


use digitalmx\flames\Definitions as Defs;
use digitalmx as mx;

try {
	echo "Defs: seclevel ma: " . Defs::get_seclevel('MA').BRNL ;
} catch (Error $e) {
	echo_red ("Defs not loaded") . BRNL;
}

try {
	echo "utilities/days_ago since Feb 30: " . days_ago('Feb 30, 2017') . BRNL;
} catch (Error $e) {
	echo_red ("utilities.php not loaded") . BRNL;;
}

try {
	echo "Mx\pretty_date (rfc): " . mx\pretty_date('rfc') . BRNL;
} catch (Error $e) {
	echo_red ('pretty_date function not available.') . BRNL;
}

###33#####
function echo_red ($t) {
	echo "<p class='red'>$t</p>";
}
