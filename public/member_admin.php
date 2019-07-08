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
$uid = $_GET['id'] ?? '' ;
if (!empty($uid)){
	echo $admin->showUpdate($uid);
}

#display search results
elseif (isset($_POST['search'])){
		echo $admin->listMembers ($_POST);
}
elseif (isset($_POST['Update'])){
	if (empty($uid = $_POST['uid'])){
		throw new Exception ('Attemp t update member with no uid in post');
	}
	echo $admin->updateMember($_POST);
	echo $admin->showUpdate($uid);
}

#show search screen
echo $admin->showSearch();
	

