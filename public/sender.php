<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


	

require_once '../config/init.php';


use digitalmx\flames\Member;
use digitalmx\flames\Messenger;

$messenger = new Messenger(\MyPDO::instance());
$messenger->sendLogins('john@digitalmx.com','here is the text');

