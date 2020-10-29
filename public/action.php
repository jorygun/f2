<?php
namespace DigitalMx\Flames;


/*
Script called by ajax.js
or called by a ajax.php?ajax=request GET command.


*/

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\Member;
use DigitalMx\Flames\MemberAdmin;
use DigitalMx\Flames\Messenger;
use DigitalMx\Flames\DocPage;
#use DigitalMx\Flames\ActionCodes;
use DigitalMx\Flames\BulkMail;
use DigitalMx\Flames\StatusReport;

use DigitalMx\Flames\FileDefs;
use DigitalMx\Flames\Publish;
use DigitalMx\Flames\NewsIndex;
use DigitalMx\MyPDO;


// dependencies
  $publish = $container['publish'];
  $member = $container['member'];
  	$templates = $container['templates'];
  	$article = $container['article'];



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
 			if ($r = verifyEmail($uid, $container)) {
 				list($username,$uid,$uem) = $container['member']->getMemberBasic($uid);
				echo "$username, thanks for verifying your email.";
			}
			else {echo "Failed";}
 			break;
 		case 'P':
 			// not used
 			edit_profile($uid,$container['membera'], $container['templates']);
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
			return vote_action($_POST,$container);
		  break;
   	case 'deleteAsset':
   		echo deleteAsset($_POST['uid'],$container);
   		break;
   	case 'markReviewed':
   		echo markReviewed($_POST['uid'], $container);
   		break;
		case 'bulkmail':
			return cancel_bulk($_POST['job']);
		  break;
	  case 'verifyProfile':
	  		echo verifyProfile($_POST['uid'],$container);
	  		break;
		case 'sendLogin':
			echo sendLogin($_POST['uid'], $container);
		  break;
		case 'verifyEmail':
			echo  verifyEmail($_POST['uid'], $container) ;

			break;
		case 'runStatus':
			$test = false;
			echo runStatusReport($_POST['ptime'], $container);
			// uid used to transfer the starting date
			break;
		case 'indexNews':
			echo runNewsIndex();
			break;

		case 'xout':
			return xoutUser($_POST['uid'], $container);
		  break;

	  case 'copyIndex':
	  		// copy news index template to new next
	  		echo copyIndex();
	  		break;

	  	case 'copyLatest':
	  		echo copyLatest();
	  		break;

		case 'getmess':
		   // retrieves a template from /templates directory
			echo getTemplate($_POST['type']);
			break;
		case 'markContribute':
			return markContribute($_POST['uid'], $container);
		  break;
		case 'initNext':
			echo initNext();
			break;
		case 'setNewsTitle':
			echo setNewsTitle($_POST['title'], $container);
			break;
		case 'bounceEmail':
			echo bounceEmail($_POST['uid'], $container);
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
	  	case 'preview':
	  		echo $publish->setPreview() ;
	  		break;
	  	case 'restore':
	  		echo system(REPO_PATH . '/crons/restore_dev.sh');
	  		break;
		case 'save-next':
			echo save_next($container);
			break;
		case 'click':
			echo count_click($_POST,$container);
			break;


		default:
			echo "Unknown attempt at ajax update : <pre>\n" . print_r($_POST, true);
	}
}
// else, just load the script so the functions can be used.

function count_click($post,$container) {
//echo "In count_click for $ref";
	$pdo = $container['pdo'];
	$ref = substr($post['ref'],0,255); // drop loong line
	$art = $post['art'];
	$sql = "INSERT INTO `links` (count,url,article_id)  VALUES (1,'$ref',$art)
		ON DUPLICATE KEY UPDATE count = count+1, article_id = $art";

	if ($pdo->query($sql) ){
		return 'Success';
	}
	return 'Failed';
}
function atest($x=''){
	$ni = new NewsIndex();
	$ni->append_index('20191225','news_191215');
	return "adone";

}
function signup_verify($uid){
	// veirfy email in signup db
		$pdo = MyPDO::instance();
		$sql = "SeleCT * from `signups` WHERE id='$uid'";
		$row = $pdo->query($sql)->fetch();
		if (empty($row)){ #no such record
			mail('admin@amdflames.org','New Signup Verify failed',
				"New Signup verify failed for id $uid . No such id in signups." );
			die ("An error has occured.  Please contact admin@amdflames.org");
		}

		$sql = "UPDATE `signups` SET status = 'A' WHERE id='$uid'";
		if ($pdo->query($sql)) {
			mail('admin@amdflames.org','New Signup Verified',
				'New Signup: id: ' . $uid . ' - ' . $row['username']  );
			return "Success.  You will receive your login information within a few days.";
		} else {return "Failed";}
	}
function edit_profile($uid,$madmin,$templates) {

	$profile_data = $madmin->getProfileData($uid);
   echo  $templates->render('profile-edit', $profile_data);
	exit;
}



function verifyProfile($uid,$container) {
	$member = $container['member'];
	$cdate = $member->verifyProfile($uid);
	return "Verified $cdate";
}
function getTemplate($type)
{
   // return text message for bulk mail setup script
    $tp_path = REPO_PATH . "/templates/${type}.txt";

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
	$ni -> rebuildAll();
	return "index done";
}

function copyLatest() {
	$latest = FileDefs::latest_dir;
	$latest_arch = trim(file_get_contents(FileDefs::latest_pointer));
	$archive = SITE_PATH . $latest_arch;
	u\full_copy($latest,$archive);
	return "Copied latest to $latest_arch";


}
function save_next($container) {
	$result = $container['publish']->setNextArticles();
	return ($result)? 'Saved' : 'failed';
}
function copyIndex() {
	$index = FileDefs::news_template;
	$nextindex = FileDefs::next_dir . "/index.php";

	echo "copying $index to $nextindex";
	copy ($index,$nextindex);
	return "$index copied";
}

function initNext()
{
   // clears the news_next diretory and copyies the model index into it.

    try {
        u\deleteDir(FileDefs::next_dir);
        mkdir(FileDefs::next_dir);
        copy(FileDefs::news_template, FileDefs::next_dir . "/index.php");
    } catch (Exception $e) {
        return "Error: "  . $e->getMessage();
    }
    return "Done.";
}
function vote_action($post,$container)
{
   //post interesting/not interesting votes
	$voting = $container['voting'];
    $user_id = $_SESSION['login']['user_id'] ?? '';
    if (empty($user_id)){return  "You are not logged in";}

    if (empty($item_id = $post['item_id'])) {
        return "request to post vote without item id";
    }
    $vote = $post['this_vote']??'';
    $voting->record_vote($item_id, $user_id, $vote);
    $new_panel = $voting->getVotePanel($item_id,$user_id);
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

function runStatusReport($ptime,$container) {
	if (empty($ptime)){return "Error: no date supplied";}
	$since = date('Y-m-d H:i',strtotime($ptime));
	if ($sr = new StatusReport($container, $since) ) {
		return "Report Run";

	} else {return "Failed";}
}

function sendLogin($tag, $container)
{
   //tag may be uid or email

   $login_msg = $container['member']->getLogins($tag);
   if (!empty($login_msg)){
		 if ($container['messenger']->sendLogins($tag, $login_msg)) {
			  return "Logins sent";
		 }
	}
	return "Nope."; #actually nothing was found

}

function setNewsTitle($title,$container)
{
	// title is sent using uriencode
	$title_decode = rawurldecode($title);
	return $container['publish']->setNextTitle($title_decode) ;

}

function verifyEmail($uid, $container)
{
	$r = $container['membera']->validate_email_with_notice($uid);

  return "Verified " . $container['member']->verifyEmail($uid) ;
}

function markContribute($uid, $container)
{

    if ($r = $container['member']->markContribute($uid)) {
        echo  $r;
    } else {
        echo "Failed";
    }
}
function deleteAsset ($aid,$container){
	$assets = $container['assets'];
	$assets->deleteAsset($aid);
	return "$aid Deleted";
}

function markReviewed($aid,$container) {
	$assets = $container['assets'];
	if($assets->updateStatus($aid,'O') ) return "Reviewed";
	return "Failed";
}

function xoutUser($uid, $container)
{
    if ($container['member']->xoutUser($uid)) {
        echo "User xed out";
    } else {
        echo "Failed User x-out";
    }
}

function bounceEmail($uid, $container)
{

    if ($container['member']->setEmailStatus($uid, 'LB')) {
        return  "LB";
    } else {
        return false;
    }
}


