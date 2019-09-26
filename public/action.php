<?php
namespace digitalmx\flames;


/*
Script called by ajax.js
or called by a ajax.php?ajax=request GET command.


*/



require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

use digitalmx as u;
use digitalmx\flames as f;
use digitalmx\flames\Member;
use digitalmx\flames\Messenger;
use digitalmx\flames\DocPage;
#use digitalmx\flames\ActionCodes;
use digitalmx\flames\BulkMail;
use digitalmx\flames\StatusReport;

use digitalmx\flames\FileDefs;
use digitalmx\flames\Publish;
use digitalmx\flames\NewsIndex;
use digitalmx\MyPDO;





  $publish = new Publish();
 	
 	
  
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
         case 'P':
         	$page_title = 'Profile Editor';
				$page_options=['tiny','ajax']; #ajax, votes, tiny 
			case 'S':
				$page_title = 'Signup Verification';
				$page_options=['ajax'];
				break;
				
        default:
        	$page_title = 'AMD Flames Action Handler';
           
    }
   $page = new DocPage($page_title);
  
	echo $page->startHead($page_options); 
 	echo $page ->startBody(0);
 	
 	switch ($action) {
 		case 'V':
 			if ($r = verifyEmail($uid, $member)) {
				echo "Email Validated $r";
			}
			else {echo "Failed";}
 			break;
 		case 'P':
 			edit_profile($uid,$madmin,$templates);
         break;
      case 'S':
      	echo signup_verify($uid);
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
	  case 'verifyProfile':
	  		echo verifyProfile($_POST['uid'],$member);
	  		break;
		case 'sendLogin':
			return sendLogin($_POST['uid'], $member);
		  break;
		case 'verifyEmail':
			echo  verifyEmail($_POST['uid'], $member) ;
			
			break;
		case 'runStatus':
			echo runStatusReport($_POST['uid']);
			// uid used to transfer the starting date
			break;
		case 'indexNews':
			echo runNewsIndex();
			break;
			
		case 'xout':
			return xoutUser($_POST['uid'], $member);
		  break;
	  
	  case 'copyIndex':
	  		// copy news index template to new next
	  		echo copyIndex();
	  		break;

	  	case 'copyLatest':
	  		echo copyLatest();
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
	  case 'test':
	  		echo  atest();
	  		break;
	  	case 'move-next':
	  		echo $publish->copyNextToLatest();
	  		break;
	  	case 'publish':
	  		echo  $publish->publishNews();
	  		break;
		default:
			echo "Unknown attempt at ajax update : <pre>\n" . print_r($_POST, true);
	}
}
// else, just load the script so the functions can be used.

function atest($x=''){
	$ni = new NewsIndex();
	$ni->append_index('20191225','news_191215');
	return "adone";
	
}
function signup_verify($uid){
	// veirfy email in signup db
		$sql = "UPDATE `signups` SET status = 'A' WHERE id='$uid'";
		$pdo = MyPDO::instance();
		if ($pdo->query($sql)) {
			mail('admin@amdflames.org','New Signup Verified','New Signup');
			return "Success.  You will receive your login information within a few days.";
		} else {return "Failed";}
	}
function edit_profile($uid,$madmin,$templates) {
	
	$profile_data = $madmin->getProfileData($uid);
   echo  $templates->render('profile-edit', $profile_data);
	exit;
}


function verifyProfile($uid,$member) {

	$cdate = $member->verifyProfile($uid);
	return "Verified $cdate";
}
function getmess($type)
{
   // return text message for bulk mail setup script
    $tp_path = REPO_PATH . "/templates/${type}.txt";
   #return "Getting $tp_path";
    if (!$message = file_get_contents($tp_path) ) {
    	throw new Exception ("File $tp_path does not exist") ;
    }
    #$subject = strtok($message,"\n"); #first line	
	#$text = u\email_std($message);
    list ($subject,$text) = explode("\n",$message,2);
    
    $result['text']= $text;
    $result['subject']=$subject;
   #return "sub: " . $result['subject'] . 'mess: ' . $result['text'] . "\n";
    return json_encode($result);
}

function runNewsIndex() {
	
	$ni = new NewsIndex();
	$ni -> rebuildJson();
	
	$ni -> buildHTML();
	return "html done";
}

function copyLatest() {
	$latest = FileDefs::latest_dir;
	$latest_arch = trim(file_get_contents(FileDefs::latest_pointer)); 
	$archive = SITE_PATH . $latest_arch;
	u\full_copy($latest,$archive);
	return "Copied latest to $latest_arch";
	

}

function copyIndex() {
	$index = FileDefs::news_template;
	$nextindex = FileDefs::next_dir . "/index.php";

	echo "copying $index to $nextindex";
	copy ($index,$nextindex);
	return "Done";
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
    $queue = FileDefs::bulk_queue;
    $tfile = $queue . "/$job";
    if (file_exists($tfile)) {
        echo "Canceling queued job $job"  . BRNL;
        rename($tfile, $tfile . "-cancelled");
    }
    $tfile = $queue . "/$job-running";
    if (file_exists($tfile)) {
        echo "Canceling running job $job"  . BRNL;
        rename($tfile, str_replace('-running', '-cancelled', $tfile));
    }
    echo $bulkmail -> show_bulk_jobs();
}

function runStatusReport($var) {


	if (empty($var)){return "Error: no date supplied";}
	$since = date('Y-m-d H:i',strtotime($var));
	if ($sr = new StatusReport($since) ) {
		return "Report Run";

	} else {return "Failed";}
}

function sendLogin($tag, $member)
{
   //tag may be uid or email

    $login_msg = $member->getLogins($tag);
    $messenger = new Messenger(); 
    if ($messenger->sendLogins($tag, $login_msg)) {
        echo "Logins sent";
    } else {
        echo "Failed to send logins";
    }
}

function setNewsTitle($title)
{
    $title_file = SITE_PATH . '/news/next/title.txt';
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
  return "Verified " . $member->verifyEmail($uid) ;
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
