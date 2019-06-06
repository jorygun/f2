<?php
ini_set('display_errors', 1);
$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);


echo <<<EOT
<html>
<head><title>Varinfo 3</title></head>
<body>
varinfo.php - last updated $mtimet
EOT;


echo "<b>include_path: </b>" . get_include_path() ."<br><br>\n";
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

$init_file = $_SERVER['REDIRECT_SITE_INIT'] ?? 'Not Found';
$old_init_file = '../config/init.php';

echo "Looking for $init_file <br>\n";
if (file_exists($init_file)){
	echo "Begin Site init ... ";
	include "$init_file";
	echo "site init done.<br>";

} else {
	echo ".. not found, looking for old init ... ";
	if (file_exists($old_init_file)){
		include "$old_init_file";
		echo "site init-old done.<br>";
	}
	else {
		echo "Init not found; skipped.";
		function recho($var,$title=''){
    		echo "<h4>$title:</h4>";
    		echo "<pre>" .  print_r($var,true) . "</pre>\n";
		}
	}
}


recho ($_ENV,'$_ENV');

recho ($server_changes,'Changed value in $_SERVER');
recho ($server_adds,'Added to $_SERVER');

echo "<hr>";
echo ".htaccess<br>:<pre>";
echo file_get_contents('.htaccess');
echo '</pre><hr>';

if (! defined ('SITE')){die ("Init not run; stopping now.");}


echo "<hr>";
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
