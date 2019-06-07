<?php
ini_set('display_errors', 1);
$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);


echo <<<EOT
<html>
<head><title>Varinfo 3</title></head>
<body>
varinfo.php - last updated $mtimet
<p>Known Issue: this outputs info before init runs, so it upsets the
session_start in init.  No biggie.</p>

EOT;


echo "<br><b>initial include_path: </b>" . get_include_path() ."<br><br>\n";

$sitedir = dirname(__DIR__); #...<repo>/
$projdir = dirname($sitedir);

echo "<br>";

echo "<b>sitedir:</b> " . $sitedir . "<br>\n";
echo "<b>projdir:</b> " . $projdir . "<br>";
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

$init_file = $_SERVER['REDIRECT_SITE_INIT'] ?? 'No Init in ENV';
$old_init_file = '../config/init.php';

echo "Looking for init from htaccess: $init_file <br>\n";
if (file_exists($init_file)){
	echo "Begin Site init from htaccess ... ";
	include "$init_file";
	echo "site init done.<br>";

} else {
	echo ".. not found, looking for old init $old_init_file <br>";
	if (file_exists($old_init_file)){
		include "$old_init_file";
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

echo "<br><b>post-init include_path: </b>" . get_include_path() ."<br><br>\n";

recho ($_ENV,'$_ENV');

recho ($server_changes,'Changed value in $_SERVER');
recho ($server_adds,'Added to $_SERVER');

echo "<hr>";
$htaccessm = date('d M H:i',filemtime('.htaccess'));

echo ".htaccess ($htaccessm) :<br><pre>";
echo file_get_contents('.htaccess');
echo '</pre><hr>';

try {
	echo "From Definitions<br>\n";
	echo "seclevel ma: " . Definitions::get_seclevel('MA').BRNL;

	#check code

	#echo sqldate('time')  . BRNL;;

	echo "days since Feb 30 " . days_ago('Feb 30, 2017') . BRNL;

} catch (Exception $e ){
echo "Errors: functions could not run.<br>";
echo $e;
}
