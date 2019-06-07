<?php
ini_set('display_errors', 1);
$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);

$output = <<<EOT
<html>
<head><title>Varinfo 3</title></head>
<body>
varinfo.php - last updated $mtimet
EOT;


$output .=  "<br><b>initial include_path: </b>" . get_include_path() ."<br><br>\n";

$sitedir = dirname(__DIR__); #...<repo>/
$projdir = dirname($sitedir);

$output .= <<<EOT
<br>
<b>sitedir:</b>  $sitedir  <br>
<b>projdir:</b> $projdir<br>
<br>
EOT;

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

$output .=  "Looking for init from htaccess: $init_file <br>\n";
if (file_exists($init_file)){
	$output .=  "Begin Site init from htaccess ... ";
	include "$init_file";
	$output .=  "site init done.<br>";

} else {
	$output .=  ".. not found, looking for old init $old_init_file <br>";
	if (file_exists($old_init_file)){
		include "$old_init_file";
		$output .=  "site init-old done.<br>";
	}
	else {
		$output .=  "Init not found; skipped.<br>";
		function recho($var,$title=''){
    		echo "<h4>$title:</h4>";
    		echo "<pre>" .  print_r($var,true) . "</pre>\n";
		}
	}
}

$output .=  "<br><b>post-init include_path: </b>" . get_include_path() ."<br><br>\n";

echo $output;

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
