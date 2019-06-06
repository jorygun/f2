<?php
ini_set('display_errors', 1);


echo <<<EOT
<html>
<head><title>Varinfo 3</title></head>
<body>
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
$init_file = $_SERVER['REDIRECT_SITE_INIT'];
$old_init_file = '../../config/init.php';

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
echo ".htaccess<br>:";
echo file_get_contents('.htaccess');
echo '<hr>';

if (! defined ('SITE')){die ("Init not run; stopping now.");}

$mtimet = date('d M H:i',filemtime(__FILE__));
$mtime = filemtime(__FILE__);

$mage=days_ago($mtime);
echo "<b>Me (__FILE__):</b>"  . __FILE__ . " -  $mtimet ($mage days ago) <br>\n";
echo "<hr>";
echo "From Definitions<br>\n";
echo "seclevel ma: " . Definitions::get_seclevel('MA').BRNL;

#check code

#echo sqldate('time')  . BRNL;;


echo "days ago " . days_ago('Feb 30, 2017') . BRNL;


