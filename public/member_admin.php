<?php
namespace digitalmx\flames;

ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\MemberAdmin;
	
	$admin = new MemberAdmin();
	
	$page_title = 'Member Admin';
	$page_options = ['ajax'];
	
	
    $login->checkLogin(); 
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	echo $page -> startBody(3);

	
//END START
	
#display user data

#display search results
if (isset($_POST['search'])){
		echo $admin->listMembers ($_POST);
}
elseif (isset($_POST['Update'])){
	if (empty($uid = $_POST['uid'])){
		throw new Exception ('Attemp t update member with no uid in post');
	}
	
	echo $admin->updateMember($_POST);
	echo $admin->showUpdate($uid);
}
elseif ($uid = $_GET['id'] ?? '' ){
	echo $admin->showUpdate($uid);
}

#show search screen
echo $admin->showSearch();

