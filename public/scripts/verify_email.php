<?php
/*   verify script receives email and get session data from user login  from link on verify email:
		 $GV[siteurl]/scripts/verify_email.php?s=$login&m=$uemenc [ = rawurlencode($user_email) ]

*/


//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames as f;
	#if (f2_security_below(0)){exit;}
//END START


$error_msg = "No record found. Please contact administrator at admin@digitalmx.com";


	$page_title = "Email Validation";
	$page_options = []; # ['ajax','tiny','votes']
	
   echo $page->startHead($page_title,$page_options); 
 	echo $page ->startBody($page_title);

if ($ident = $_GET['s']){
  		$uid = substr($ident,-5); #last 5
  	}

	include $_SERVER['DOCUMENT_ROOT'] . '/action.php';
	if ($r = f\verifyEmail($uid, $member)) {
			echo "Email Validated $r";
		}
		else {echo "Failed";}

	

	
