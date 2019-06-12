<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


	

require_once '../config/boot.php';


$thispdo = $container['pdo_dev'];

$sql = 'SELECT count(*) from `members_f2` ';
$val = $thispdo->query($sql)->fetchColumn();
echo "Count of members: $val \n";

