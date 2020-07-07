<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);




require_once '../init.php';
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\FileDefs;


$publish = $container['publish'];

$articlelist = array(
	2270,2271,2273
	);

$t = $publish->buildTeaser($articlelist);

u\echop ($t);

exit;
