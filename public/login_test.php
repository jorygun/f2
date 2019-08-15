<?php
namespace digitalmx\flames;
$init_file = $_SERVER['DOCUMENT_ROOT'] . '/init.php';
use digitalmx\flames\Login;
use digitalmx as u;

ini_set('display_errors', 1);


require $init_file;

if (isset ($_SESSION['login'])){
	echo 'Start Page <pre>' . print_r($_SESSION['login'],true) . '</pre>';
} else {
	echo "No session yet.";
}


$user =  $_SESSION['login']['user_id'];
echo "<p><b>Before Login:</b> $user </p> " . BRNL; 

u\echoAlert('user was ' . $user);



echo "Query: " . $_SERVER['QUERY_STRING'] . BRNL;
echo '<hr>';



$login = new Login();
// $page = new DocPage();
// $member = new Member();



$login->checkLogin(0);
echo '<hr>';

u\echor ($_SESSION['login'],'Session Login');

