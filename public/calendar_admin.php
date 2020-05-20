<?php

namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;
	
	use DigitalMx\Flames\Calendar;
	
	

if ($login->checkLogin(4)){
   $page_title = 'Calendar Admin';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START


$calendar = new Calendar();


if ($_SERVER['REQUEST_METHOD'] == 'POST'){


	if ($_POST['Submit'] == 'Enter'){
		$calendar->save_event($_POST);
	}
	elseif ($_POST['Submit'] == 'Build'){
		$calendar->build_calendar();
		exit;
	}
	else {die ("Request not understood");}

	
}
else { #edit item
	$id = $_GET['id']  ?? 0;
	if ($id == 'New Event'){$id = 0;}
	echo $calendar->show_event($id);
	exit;
}

echo '<hr>';
echo $calendar->show_item_list();
echo "<form method='post'><input type=submit name='Submit' value='Build'></form>";


