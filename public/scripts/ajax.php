<?php
namespace digitalmx\flames;


ini_set('error_reporting', E_ALL);

/*
Script called by ajax js voting3.js
Will record the vote and return a new 
updated voting panel to the user.
*/


$repo_path = dirname(dirname(__DIR__));
require_once $repo_path . '/init.php';

use digitalmx\flames\Member;
use digitalmx\flames\Messenger;
use digitalmx as u;


if (empty($_SESSION['login']['user_id'])){
	echo ("Not logged in.");
	exit;
}


if (empty($_POST['ajax'] )){
	echo "No ajax request";
	u\echor($_POST,'post');
	exit;
}

 
switch ($_POST['ajax']) {
	case 'vote' :
		return vote_action($_POST);
		break;
	
	case 'bulkmail' :
		return cancel_bulk($_POST['job']);
		break;
		
	case 'sendLogin' :
		return sendLogin($_POST['uid'],$pdo);
		break;
	case 'verifyEmail' :
		return verifyEmail($_POST['uid'],$pdo);
		break;
	case 'xout' :
		return xoutUser($_POST['uid'],$pdo);
		break;
		
	case 'getmess' :
		return getmess($_POST['type']);
		break;
		
	default:
		echo "Unknown attempt at ajax update : <pre>\n" . print_r($_POST, true); 
}
function getmess($type) {
	// return text message for bulk mail setup
	$tp_path = REPO_PATH . "/templates/${type}.txt";
	#return "Getting $tp_path";
	$message = file_get_contents($tp_path);
	list($subject,$text) = explode("\n",$message,2);
	$result['text']=$text;
	$result['subject']=$subject;
	return $result;
	
}

function vote_action($post){
	require_once 'Voting.class.php';
	$voting = new Voting();
	$user_id = $_SESSION['login']['user_id'];
	if(empty($item_id = $post['item_id'])) {
		return "posting vote without item id";
	}
	$vote = $post['this_vote']??'';
	#echo "Recording vote  $vote for item $item_id" . BRNL;
	$new_panel = $voting->tally_vote($item_id,$user_id,$vote);
	echo $new_panel;
}

function cancel_bulk($job) {
	require_once  'BulkMail.php';
	$bulkmail = new BulkMail;
	$queue = PROJ_PATH . '/bulk_jobs/queue';
	rename ($queue . "/$job" , $queue . "/${job}-cancelled");
	echo $bulkmail -> show_bulk_jobs(SITE_PATH . '/bulk_queue');
}

function sendLogin($tag,$pdo) {
	//tag may be uid or email
	$member = new Member($pdo);
	$login_msg = $member->getLogins($tag);
	$messenger = new Messenger($pdo); #true = test
	if ( $messenger->sendLogins($tag,$login_msg) ){
		echo "Logins sent";
	}else {
		echo "Failed to send logins";
	}
		
}

function verifyEmail($uid,$pdo) {
	//tag may be uid or email

	
	$member = new Member($pdo);
	if ($member->verifyEmail($uid)) {
		echo "Email Verified";
	} else {
		echo "Failed Email Verify";
	}
	
}
function xoutUser($uid,$pdo) {
	
	$member = new Member($pdo);
	if ( $member->xoutUser($uid) ){
		echo "User xed out";
	} else {
		echo "Failed User x-out";
	}
}
