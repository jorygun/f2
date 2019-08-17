<?php
namespace digitalmx\flames;

/*
Script called by ajax.js 
or called by a ajax.php?ajax=request GET command.


*/



require_once '../config/init.php';

use digitalmx\flames\Member;
use digitalmx\flames\Messenger;
use digitalmx as u;
use digitalmx\flames as f;
use digitalmx\flames\TakeAction;
#$actor = new TakeAction();
use digitalmx\flames\BulkMail;



if (!empty ($_GET['ajax']){
	$_POST['ajax'] = $_GET['ajax'];
	$_POST 
	

if (!empty($_POST['ajax'] )){
	echo "No ajax request";
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
		return sendLogin($_POST['uid']);
		break;
	case 'verifyEmail' :
		echo  verifyEmail($_POST['uid']);
		break;
	case 'xout' :
		return xoutUser($_POST['uid']);
		break;
		
	case 'getmess' :
		#echo 'at get mess'; 
		echo getmess($_POST['type']);
		break;
	case 'markContribute':
		return markContribute($_POST['uid']);
		break;
	case 'initNext': 
		echo initNext();
		break;
	case 'setNewsTitle':
		echo setNewsTitle($_POST['title']);
		break;
	case 'bounceEmail':
		echo bounceEmail($_POST['uid']);
		break;
		
	default:
		echo "Unknown attempt at ajax update : <pre>\n" . print_r($_POST, true); 
}
function getmess($type) {
	// return text message for bulk mail setup script
	$tp_path = REPO_PATH . "/templates/${type}.txt";
	#return "Getting $tp_path";
	$message = file_get_contents($tp_path);
	list($subject,$text) = explode("\n",$message,2);
	$result['text']=$text;
	$result['subject']=$subject;
	#return "sub: " . $result['subject'] . 'mess: ' . $result['text'] . "\n";
	return json_encode($result);
	
}

function initNext(){
	#clears the news_next diretory and copyies the model index into it.
	$news_dir = SITE_PATH . "/news";
	$nextnews_dir = $news_dir . '/news_next';
	try {
		u\deleteDir ($nextnews_dir);
		mkdir ($nextnews_dir);
		copy ("$news_dir/model-index.php","$nextnews_dir/index.php");
	} catch (Exception $e) {
		return "Error: "  . $e-getMessage();
	}
	return "Done.";
}
function vote_action($post){
	//post interesting/not interesting votes
	require_once 'Voting.php';
	$voting = new Voting();
	$user_id = $_SESSION['login']['user_id'];
	if(empty($item_id = $post['item_id'])) {
		return "request to post vote without item id";
	}
	$vote = $post['this_vote']??'';
	#echo "Recording vote  $vote for item $item_id" . BRNL;
	$new_panel = $voting->tally_vote($item_id,$user_id,$vote);
	echo $new_panel;
}

function cancel_bulk($job) {
	// changes jobid in the bulk mail quque to xx-cancelled.  Should halt running job.
	

	$bulkmail = new BulkMail;
	$queue = REPO_PATH . '/var/bulk_queue';
	$tfile = $queue . "/$job";
	if (file_exists($tfile)){
		echo "renaming $tfile to xx-cancelled";
		rename ($tfile , $tfile . "-cancelled");
	}
	$tfile = $queue . "/$job-running";
	if (file_exists($tfile)){
		echo "renaming $tfile to xx-cancelled";
		rename ($tfile , str_replace('-running','-cancelled',$tfile) );
	}
	echo $bulkmail -> show_bulk_jobs();
}

function sendLogin($tag) {
	//tag may be uid or email
	$member = new Member();
	$login_msg = $member->getLogins($tag);
	$messenger = new Messenger(); #true = test
	if ( $messenger->sendLogins($tag,$login_msg) ){
		echo "Logins sent";
	}else {
		echo "Failed to send logins";
	}
		
}

function setNewsTitle($title) {
	$title_file = SITE_PATH . '/news/news_next/title.txt';
	file_put_contents ($title_file,$title);
	return "Done";
}

function verifyEmail($uid) {
	//tag may be uid or email
	$member = new Member();
	
	if ($member->verifyEmail($uid)) {
		echo "Email Verified for member $uid";
	} else {
		echo "Failed";
	}
}

function markContribute($uid) {
	$member = new Member();
	if ($member->markContribute($uid)) {
		echo "Contribution Marked";
	} else {
		echo "Failed";
	}
}
	
function xoutUser($uid) {
	
	$member = new Member();
	if ( $member->xoutUser($uid) ){
		echo "User xed out";
	} else {
		echo "Failed User x-out";
	}
}

function bounceEmail($uid) {
	$member = new Member();
	if ($member->setEmailStatus($uid,'LB')) {
		echo "Email Bounced";
	} else {
		echo "Failed";
	}


}
