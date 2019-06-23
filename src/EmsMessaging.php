<?php
namespace digitalmx\flames;

use digitalmx\flames\Definitions as Defs;
use digitalmx as dmx;

use \Exception as Exception;

/*
	provides text for messages when email status is changed.
	$code is new status code

	start with $em = new EmsMessaging($pdo);
	
*/

#don't know if I need any init.  boot will already have run

class EmsMessaging 
{
private static $lost_reasons = array(


	'A4' => "There has been no response to several requests to verify email address.",
	'LA' => "Gave up trying to contact",
	'B2' => "Email has repeatedly bounced.",
	'E2' => "The user changed email address but
	but has not confirmed receiving email at the new address.",
	'LO' => "Lost other. No code supplied.",
	'LS' => "New signup failed to confirm email.",
	'LE' => "User never confirmed change of email."
	);


private static $user_messages = array(

'N1'	=>	array(
	'subj' => "AMD Alumni FLAMEs Signup Verification - Action Required!",
	'msg' => "

	Thanks for signing up for the FLAMEs AMD Alumni News
	site, ::name::.

	To confirm your signup and receive a temporary password,
	click on the link below:
		::verify_url::

	You must confirm within 3 days to activate this signup.

	After you have clicked the link above, an administrator
	will review your signup and send you an email to confirm
	your membership. This could take a day or two.

	You will receive your personal login and have full access then.
	"),

'N2'	=>	array(
	'subj' => "Please Confirm Your Email for AMD FLAMEs",
	'msg' => "

	::name::, a few days ago we sent you an email asking you
	to confirm this email for your signup on the AMD Alumni
	FLAMEs site. We haven't heard back from you.

**************************************************
	To confirm this is your correct email, please click on the
	link below:
		::verify_url::
***************************************************

	Otherwise, your signup will be cancelled.
	"),

'B1'	=>	array(
	'subj' => "Your AMD FLAMEs Email Bounced - Action Needed",
	'msg' => "

	::name::,  The AMD Alumni FLAMES  site recently
	sent you an email at this address that bounced.
	Can you please confirm that this email is correct for you?

***************************************************
	If this email <::current_email::> is correct, please just click on the
	link below to confirm:
		::verify_url::
***************************************************

	
	"),

'B2'	=>	array(
	'subj' => "AMD FLAMEs Email Bouncing - Second Request!",
	'msg' => "

	::name::,  we recently sent you an email advising you that
	email sent to this address <::current_email::>
	from the AMD FLAMEs site was bouncing.

	We didn't hear back from you, we're trying again.

***************************************************
	If this message gets through to you, please click on
	the link below to confirm:

		::verify_url::
***************************************************

	Otherwise we will have to mark you as a Lost Member.

	
	"),

'E1'	=>	array(
	'subj' => "Confirm your new email on the AMD Alumni Site",
	'msg' => "

	::name::, the email for your membership on the FLAMEs AMD Alumni
	site has been updated from ::prior_email:: to ::current_email::.

***************************************************
	YOU NEED TO CONFIRM THIS CHANGE BY CLICKING THE LINK BELOW NOW!

		::verify_url::
***************************************************

	

	"),

'E2'	=>	array(
	'subj' => "AMD FLAMEs Email Confirmation Required - Second Request",
	'msg' => "

	::name::, about a week ago, your email
	on the AMD FLAMEs site was changed, and we sent
	you an email asking you to verify the change.

	You haven't confirmed the change, so now we're wondering if
	this was a mistake?

***************************************************
	If this is your correct new email, please
	CLICK ON THE LINK BELOW to confirm:

		::verify_url::
***************************************************

	
	"),

'A1'	=>	array(
	'subj' => "Confirm your email for AMD Flames.",
	'msg' => "

    This is an email from the AMD Alumni Flames site to confirm that
	we have your correct email in our member list.

***************************************************
  JUST CLICK THE LINK BELOW NOW to confirm that
  this <::current_email::>  is your email:

       ::verify_url::

***************************************************


	(Note: You can avoid these messages by logging into the web site
	at least once a year.)
	"),

'A2'	=>	array(
	'subj' => "Confirm your email for AMD Flames - Second Request",
	'msg' => "

	::name::, about a week ago we sent you an email
	asking you to confirm that this is still your correct email.
	We haven't heard back from you, so we're giving it another shot.

	Please click on the link below to simply verify that this
	email still works for you.

  	::verify_url::

  	"),

'A3'	=>	array(
	'subj' => "::name::, please confirm your email address",
	'msg' => "

		::name::, about 2 weeks ago we sent you an email asking you to confirm
	that this is your correct email.  We still haven't heard back from you.

***************************************************************
	IF YOU RECEIVE THIS EMAIL, please click on the link below:

  	::verify_url::
***************************************************************

  

	"),


'A4'	=>	array(
	'subj' => "AMD FLAMEs Email confirmation - Final Request",
	'msg' => "

	::name::, we have sent several emails over a few weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we think you may not be using this address.

**************************************************************************
  IF THIS IS YOUR CORRECT EMAIL, PLEASE CLICK ON THE LINK BELOW:
  	::verify_url::
**************************************************************************

	If you have any questions or concerns, please contact the administrator.
	We don't want to lose track of you.  If you do not verify,
	your user status will be set to 'Lost' and you won't receive any
	more emails from us.
	"),

'D'	=>	array(
	'subj' => "AMD Alumni FLAMEs Email Verification",
	'msg' => "

	::name::, We have been trying to contact you for several weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we aren't sure you are stilling using this address.

	If this is your correct email, please click on the link below:

  	::verify_url::


")

    );
   private $replacements = array (
   '::no_bulk::' => "You don't subscribe to the weekly email.  Please reconsider.",
   
   
   );
   
    
	private $pdo;
	private $test;
	
	public function __construct($pdo,$test=false) {
		$this->pdo = $pdo;
		$this->test = $test;
	}
	
	private function get_user($uid) 
	{
	$sql = "SELECT user_id,username,user_email,prior_email,no_bulk,upw from `members_f2` 
			WHERE user_id = $uid;";
			
		
		 if (!$row = $this->pdo->query($sql)->fetch()){
		 	throw new Exception ("No user at uid $uid");
		}
		// enhance the info from the record
		$login = $this->get_login_from_row($row);
		$this->replacements ['::login::'] = $login;
		$this->replacements ['::verify::'] = SITE_URL . "/scripts/verify_email.php?s=$login";
		$this->replacements['::name::'] = $row['username'];
		$this->replacements['::current_email::'] = $row['user_email'];
		$this->replacements ['::prior_email::'] = $row['prior_email'];
		
	return $row;
	}
	private function send_mail($data) {
	$data['headers'] = "From: AMD Flames Admin <admin@amdflames.org> \r\n";
	 mail ($data['to'],$data['subj'],$data['msg'],$data['headers']);
	
}

	private function get_login_from_row($row) 
	{
		$login = $row['upw'] . $row['user_id'];
		return $login;
	}
	private function show_message($data)
	{
		dmx\echor ($data,'email message data');
	}
	private function replace_placeholders ($msg) 
	{
		foreach ($this->replacements as $key=>$val){
			$msg = str_replace($key,$val,$msg);
		}
	
	}
	public function update_ems($uid,$mstatus,$test='')
	{
	 
		 // do not update if mode = Test
		 #echo "Updating status for id $id, status $mstatus\n";
	

		 $em_subj = '';
		 #updates status, sends emails
		 if(empty($mstatus)){
			  throw new Exception ('bad update email call',"empty status with uid $uid");
		 }
		$row = $this->get_user($uid);
		dmx\echor($this->replacements,'replacement data User');
		#extract($row, EXTR_PREFIX_ALL, 'u');
		
#exit;
		
		/* Get email template for user
			no point in emailing if the user is marked as lost 
		*/
		 if (substr($mstatus,0,1) != 'L'){ #not lost
		 	if (!$msg = $this->get_user_text($mstatus,$row) ){
		 		throw new Exception ( "Unrecognized ems $mstatus");
		 	}
		 	if (empty($msg['subj'])){ #if empty, there is no user message
		 		break;
		 	}
			$em['subj'] = $msg['subj'];
			$em['msg'] = $this->replace_placeholders($msg['msg']);
			  
			$em['to'] = $row['user_email'];
			  
					if (! $this->test ){
						$this->send_mail($em);
					} else {
						$this->show_message($em);
					}

				}
		  }
exit;

		 if (in_array($mstatus, array_keys($lost_reasons))) {
			  $msg =  get_admin_text($mstatus,$row);
			  $em_subj = $msg['subj'];
			  $em_msg = $msg['msg'];
			  if (!empty($em_subj)){
					send_admin($em_subj,$em_msg,$row['user_email']);
				}
		 }

			  $sqla = array();
			  #update the email status in the db.
					// also sets user status if second char on status
			  $sqla[] = "email_status = '$mstatus'";
			  if (!empty($ustatus)){ #second char
					$sqla[] = "status = '$ustatus' ";
			  }
			  if ($mstatus == 'Y'){$sqla[] = "email_last_validated = NOW()";}

			  if (!empty($sqla)){
					$sqlj = implode(',',$sqla);
					$sql = "UPDATE `members_f2`
					SET $sqlj
					WHERE id = '$id';";
					if ($mode=='Real'){
					 $result = mysqli_query($GLOBALS['DB_link'],$sql);
					}
			  }

		return $em_subj;
	}


	private function getLostReason($code) {
		return self::$lost_reasons[$code];
	}
	
	
private function get_user_text($code,$row){
	#echo "starting get_user_text id $id, code $code. <br>";
	/* returns array of subj,msg for this user for this code.
	    returns empty array if no user message.
	*/



	#preset these variables

	$login = get_login_from_row($row);
	$verify_url = SITE_URL . "/scripts/verify_email.php?s=$login";
	$login_url = get_login_from_row($row,'link');
	$name = $row['username'];


	$profile_text = get_profile_message($row,'text');
#echo "<br>retreiving profile message: $profile_text<br><br>";

$subscriber = $row['no_bulk']?'No':'Yes';
$email = $row['user_email'];

if ($row['no_bulk']){$bulk_warn =	"
	The FLAMEsite sends out an email whenever a new newsletter is
    published, typically once a week.  YOU ARE NOT CURRENTLY RECEIVING
    THIS.  If you'd like to keep informed about AMD alumni, go to your
    profile using the link below, and UNcheck the box 'No Email Updates'.
	";
}
else {$bulk_warn='';}

$closing =  "
	If you've already verified your email, or you think this message
	is in error, please email the admin by replying to this email, so
	I can fix the problem. This email was sent by a automated program
	but your reply will be read by a human, namely me.

	Also, if you want to change your email, just log into the site
	and change it in your profile.

--
	Regards,
	AMD FLAME site administrator
	admin@amdflames.org

";


############################################



###################################


    #echo "<br>";
    if (in_array($code, array_keys($user_messages))){
        return $user_messages[$code];
    }
    else {
    #echo "No user message";
        send_admin('Unrecognized email code $code',"An unrecognized code $code was sent to get_user_text.");
         return false;
    }
}

function get_admin_text($code,$row){
    // will only be lost status codes here
$null_msg =  array(
    'subj' => '',
    'msg' => ''
    );

     $lost_reasons = define_lost_reasons();

    if (! in_array($code,array_keys($lost_reasons))){
        return $null_msg;
    }
    #otherwise...

	$login = get_login_from_row($row);
	$verify_url = "${GLOBALS['siteurl']}/scripts/verify_email.php?s=$login";
	$login_url = get_login_from_row($row,'link');
	$name = $row['username'];
	$profile_url = get_profile_message($row,'text');
    $login_link = "https://amdflames.org?s=$login";

$subscriber = $row['no_bulk']?
    "No"
    :
    'Yes';


$please_subscribe =  $row['no_bulk']?
 "   Please consider subscribing to the short weekly email update.
     Log in, edit profile, and UNCLICK 'Opt out of Weekly Update'.\n"
    :
    '';

$user_dataset = "
User: ${row['username']}
---------------------
   Email: ${row['user_email']} (Previously: ${row['prior_email']})
   Receives weekly newsletter: $subscriber

Activity
---------------------
   Last login: ${row['last_login']}
   Email last validated: ${row['email_last_validated']} (changed on: ${row['email_chg_date']})
   Profile last validated: ${row['profile_validated']} (updated on: ${row['profile_updated']})

-----------------------
";

$last = '';
if (substr($code,0,1)=='L'){$last = "FINAL ATTEMPT";}

$admin_message =	array(
	'subj' => "Lost AMD Alumni ${row['username']} ($code)",
	'msg' => "



 Click to verify your email:
    $verify_url

-----------------------------------------------------
	Alert to FLAMES administrator :

    Email to FLAMES user ${row['username']} is apparently not getting through.
	${lost_reasons[$code]}.  The user has been set to Lost Status $code.

	Please attempt to manually reconnect with this user.

	$user_dataset
	"
	);


#same messsage for all L codes
 return $admin_message;
 }

}
