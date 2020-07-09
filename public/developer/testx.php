<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);




require_once '../init.php';
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\FileDefs;


$news = $container['news'];

$opt = $news->getTopicOptions('spirit') ;
// $optesc = htmlspecialchars($opt);
// u\echopre ($optesc);



echo "<select name='topic'>$opt</select>";

