<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


	

require_once '../config/init.php';


use digitalmx\flames\Member;
use digitalmx\flames\Messenger;
use digitalmx\flames\DocPage;

$page = new DocPage();
echo $page->getHead('Test Page');
echo $page->startBody('Hello');

$pdo = MyPDO::instance();
$messenger = new Messenger($pdo);
$email = 'springerj@yahoo.com';
$uid = '13085';

echo "Sending to email $email" . BRNL;
$messenger->sendLogins($email,'here is the text from sender for email $email at '. date('H:i') );

echo "Sending to the user id $uid " . BRNL;
$messenger->sendLogins($uid,'here is the text from sender for uid $uid at ' . date('H:i') );

