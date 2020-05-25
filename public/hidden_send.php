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



if ($login->checkLogin(1)){
   $page_title = 'Send Email To User';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
	$templates = $container['templates'];
}

//END START
if ($_SERVER['REQUEST_METHOD'] != 'POST'){
	$mid = $_GET['id']; #id of member you are sending to.

	if (empty($mid)) die ("No member requested");
	if (!$mdata = $member->getMemberBasic($mid) ){
		die ("Member does not exist");
	}
	$tdata['username'] = $mdata[0];
	$tdata['user_id'] = $mdata[1];
	$tdata ['user_email'] = $mdata[2];

	#u\echor($mdata); exit;
	echo $templates->render('hidden_send',$tdata);
}
else {
	$messenger = $container['messenger'];
	echo $messenger->sendHiddenEmail($_POST['to_id'],$_POST['subject'], $_POST['message'] );
}
