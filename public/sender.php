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
$messenger = new Messenger($pdo,true);
$messenger->sendLogins('john@digitalmx.com','here is the text');

