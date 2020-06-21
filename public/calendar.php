<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;

	$Calendar = $container['calendar'];
	$Templates = $container['templates'];


$login->checkLevel(0);

$page_title = 'Calendar';
$page_options=['tiny']; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here
echo "<link rel=stylesheet href='/css/calendar.css'>";
echo $page->startBody();


//END START
$edit = $_GET['edit'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$Calendar->saveEvent($_POST);

} elseif (! empty($edit) ){
	$data['citems'] = $Calendar->getItems($edit);

	//u\echor($data);
	echo $Templates->render('calendar_edit',$data);
	exit;
}

$data['citems'] = $Calendar->getItems();

//u\echor($data);

echo $Templates->render('calendar',$data);


//EOF

