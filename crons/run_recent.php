<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


$repodir = dirname(__FILE__,2);

require_once $repodir . '/public/init.php';
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\FileDefs;
	use DigitalMx\Flames\Recent;




$recent = new Recent($container);


