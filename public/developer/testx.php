<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);




require_once '../init.php';
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\FileDefs;
	use DigitalMx\Flames\Recent;




$recent = new Recent($container);
$recent -> run();

