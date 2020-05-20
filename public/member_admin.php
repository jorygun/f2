<?php
namespace DigitalMx\Flames;

ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\MemberAdmin;
	
	$admin = new MemberAdmin();
	
	
	
    $login->checkLogin(6); 
    $page_title = 'Member Admin';
	$page_options = ['ajax'];
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	echo $page -> startBody();

	$dev_update_button = f\actionButton('Restore Dev','restore',0,'','resp');
	
//END START

#display user data

#display search results
if (isset($_POST['search'])){
	if (isset($_POST['status']) &&  $_POST['status'] == 'N'){ #new members
		$data['mdata'] = $pdo->query("SELECT * from `signups` ORDER BY status ;") -> fetchAll();
		$data['heads'] = Defs::$signup_status_names;
		echo $templates->render('new_member_list',$data);
		exit;
	}
	
	$mdata = $admin->listMembers ($_POST);
	
	$data = [
		'mdata' => $mdata,
		'info' => count ($mdata) ?? 0
	];
	
	echo $templates->render('user_admin_list',$data);

}
elseif (isset($_POST['Update'])){
	if (empty($uid = $_POST['uid'])){
		throw new Exception ('Attempt update member with no uid in post');
	}
	
	echo $admin->updateMember($_POST);
	$mdata = $admin->showUpdate($uid);
	echo $templates->render('member_edit',$mdata);

}

elseif (isset($_POST['Process'])){
	#process signups
	echo $admin->processSignups($_POST);
	echo "done";
	$data['mdata'] = $pdo->query("SELECT * from `signups` ORDER BY status ;") -> fetchAll();
	$data['heads'] = Defs::$signup_status_names;
	echo $templates->render('new_member_list',$data);
	exit;
}

elseif ($uid = $_GET['uid'] ?? '' ){
	 $mdata = $admin->showUpdate($uid);
 	echo $templates->render('member_edit',$mdata);
}

#show search screen
echo $admin->showSearch();
echo '<hr>';
echo $dev_update_button;
echo "<p><a href='bulk_bounce.php' target='bouncer'>Bulk Bounce</a></p>";


