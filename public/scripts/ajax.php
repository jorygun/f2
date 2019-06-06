<?php

/*
Script called by ajax js voting3.js
Will record the vote and return a new 
updated voting panel to the user.
*/


#ini_set('display_errors', 1);

include_once 'init.php';
if (empty($_SESSION['user_id'])){
	echo ("Not logged in.");
	exit;
}


if (empty($_POST['ajax'] )){
	echo "No ajax request";
	exit;
}

 
switch ($_POST['ajax']) {
	case 'vote' :
		echo vote_action($_POST);
		break;
	
	case 'bulkmail' :
		echo cancel_bulk($_POST['job']);
		break;
		
	default:
		echo ("Unknown attempt at ajax update ${_POST['ajax']}");
}
	
function vote_action($post){
	require_once 'Voting.class.php';
	$voting = new Voting();
	$user_id = $_SESSION['user_id'];
	if(empty($item_id = $post['item_id'])) {
		return "posting vote without item id";
	}
	$vote = $post['this_vote']??'';
	#echo "Recording vote  $vote for item $item_id" . BRNL;
	$new_panel = $voting->tally_vote($item_id,$user_id,$vote);
	return $new_panel;
}

function cancel_bulk($job) {
	require_once 'BulkMail.class.php';
	$bulkmail = new BulkMail;
	
	rename (SITEPATH . '/bulk_queue/' . $job , SITEPATH . '/bulk_queue/' . $job . '-cancelled');
	return $bulkmail -> show_bulk_jobs(SITEPATH . '/bulk_queue');
}
