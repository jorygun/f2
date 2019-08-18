<?php
namespace digitalmx\flames;

/*
Script called by ajax.js
or called by a ajax.php?ajax=request GET command.


*/



require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

use digitalmx\flames\Member;
use digitalmx\flames\Messenger;
use digitalmx as u;
use digitalmx\flames as f;
#use digitalmx\flames\ActionCodes;

use digitalmx\flames\BulkMail;

// if request came in from a get, the
// query string tells you what to do.
if (!empty($_SERVER['QUERY_STRING'])) {
	$q =$_SERVER['QUERY_STRING'];
	#list ($action,$uid) = ;
	$action = substr($q,0,1);
	$uid = substr($q, 1); #rest of string
	$page_options = []; # ['ajax','tiny','votes']
	
	#echo "Action: " . $a . BRNL;
	
    switch ($action) {
        case 'V':
            $page_title = "AMD Flames Email Validation";
            break;
            
        default:
        	$page_title = 'AMD Flames Action Handler';
           
    }
    
	echo $page->startHead($page_title,$page_options); 
 	echo $page ->startBody($page_title,0);
 	
 	switch ($action) {
 		case 'V':
 			if ($r = verifyEmail($uid, $member)) {
				echo "Email Validated $r";
			}
			else {echo "Failed";}
 			break;
 		default:
 			echo "No Action Requested";
 
 	}
 		
}

// from ajax, it's a post
if (! empty ($_POST)) {
	switch ($_POST['ajax']) {
		case 'vote':
			return vote_action($_POST);
		  break;
   
		case 'bulkmail':
			return cancel_bulk($_POST['job']);
		  break;
	  
		case 'sendLogin':
			return sendLogin($_POST['uid'], $member);
		  break;
		case 'verifyEmail':
			echo  verifyEmail($_POST['uid'], $member) ;
			
			break;
		 
			
		case 'xout':
			return xoutUser($_POST['uid'], $member);
		  break;
	  
		case 'getmess':
		   #echo 'at get mess';
			echo getmess($_POST['type']);
			break;
		case 'markContribute':
			return markContribute($_POST['uid'], $member);
		  break;
		case 'initNext':
			echo initNext();
			break;
		case 'setNewsTitle':
			echo setNewsTitle($_POST['title']);
			break;
		case 'bounceEmail':
			echo bounceEmail($_POST['uid'], $member);
			break;
	  
		default:
			echo "Unknown attempt at ajax update : <pre>\n" . print_r($_POST, true);
	}
}
// else, just load the script so the functions can be used.

function getmess($type)
{
   // return text message for bulk mail setup script
    $tp_path = REPO_PATH . "/templates/${type}.txt";
   #return "Getting $tp_path";
    $message = file_get_contents($tp_path);
    list($subject,$text) = explode("\n", $message, 2);
    $result['text']=$text;
    $result['subject']=$subject;
   #return "sub: " . $result['subject'] . 'mess: ' . $result['text'] . "\n";
    return json_encode($result);
}

function initNext()
{
   #clears the news_next diretory and copyies the model index into it.
    $news_dir = SITE_PATH . "/news";
    $nextnews_dir = $news_dir . '/news_next';
    try {
        u\deleteDir($nextnews_dir);
        mkdir($nextnews_dir);
        copy("$news_dir/model-index.php", "$nextnews_dir/index.php");
    } catch (Exception $e) {
        return "Error: "  . $e-getMessage();
    }
    return "Done.";
}
function vote_action($post)
{
   //post interesting/not interesting votes
    require_once 'Voting.php';
    $voting = new Voting();
    $user_id = $_SESSION['login']['user_id'];
    if (empty($item_id = $post['item_id'])) {
        return "request to post vote without item id";
    }
    $vote = $post['this_vote']??'';
   #echo "Recording vote  $vote for item $item_id" . BRNL;
    $new_panel = $voting->tally_vote($item_id, $user_id, $vote);
    echo $new_panel;
}

function cancel_bulk($job)
{
   // changes jobid in the bulk mail quque to xx-cancelled.  Should halt running job.
   

    $bulkmail = new BulkMail;
    $queue = REPO_PATH . '/var/bulk_queue';
    $tfile = $queue . "/$job";
    if (file_exists($tfile)) {
        echo "renaming $tfile to xx-cancelled";
        rename($tfile, $tfile . "-cancelled");
    }
    $tfile = $queue . "/$job-running";
    if (file_exists($tfile)) {
        echo "renaming $tfile to xx-cancelled";
        rename($tfile, str_replace('-running', '-cancelled', $tfile));
    }
    echo $bulkmail -> show_bulk_jobs();
}

function sendLogin($tag, $member)
{
   //tag may be uid or email

    $login_msg = $member->getLogins($tag);
    $messenger = new Messenger(); #true = test
    if ($messenger->sendLogins($tag, $login_msg)) {
        echo "Logins sent";
    } else {
        echo "Failed to send logins";
    }
}

function setNewsTitle($title)
{
    $title_file = SITE_PATH . '/news/news_next/title.txt';
    file_put_contents($title_file, $title);
    return "Done";
}

function verifyEmail($uid, $member)
{
	$ems = $member->getEmailStatus($uid);
	if (substr($ems,0,1) == 'L'){
		 $messenger = new Messenger(); #true = test
		 $messenger->sendMessages($uid,'not-lost');
		}
  return $member->verifyEmail($uid) ;
}

function markContribute($uid, $member)
{

    if ($r = $member->markContribute($uid)) {
        echo  $r;
    } else {
        echo "Failed";
    }
}
   
function xoutUser($uid, $member)
{
   

    if ($member->xoutUser($uid)) {
        echo "User xed out";
    } else {
        echo "Failed User x-out";
    }
}

function bounceEmail($uid, $member)
{

    if ($member->setEmailStatus($uid, 'LB')) {
        return  "LB";
    } else {
        return false;
    }
}
