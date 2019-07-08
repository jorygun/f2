<?php
namespace digitalmx\flames;

ini_set('display_errors', 1);
ini_set('error_reporting', -1);
//BEGIN START
	require_once 'init.php';
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\MemberAdmin;
	
	$page = new DocPage();
	$admin = new MemberAdmin();
	
     echo $page->getHead('Member Admin',0,['ajax']);
 	echo $page ->startBody("Search for Member");

	
//END START
	
#display user data
if (!empty($uid = $_GET['id'] ?? '')){
echo "Got  $uid" . BRNL;

	echo $admin->member_edit($uid);
}

#display search results
elseif (isset($_POST['search'])){
		echo $admin->search ($_POST);
}

#show search screen
echo $admin->showSearch();
	

