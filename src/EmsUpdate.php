<?php
#

/*
	provides text for messages when email status is changed.
	$code is new status code

	requires
		PDO
		an init??
	
	Everything is done inside the function to keep namespace
	separate
*/
class EmsUpdate  {

/*
	variables to b replace after messages retrieved:
		_name_
		_verify_url_
		_prior_email_
		_email_
*/


	private static $lost_reasons = array (
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
	site, _name_.

	To confirm your signup and receive a temporary password,
	click on the link below:
		_verify_url_

	You must confirm within 3 days to activate this signup.

	After you have clicked the link above, an administrator
	will review your signup and send you an email to confirm
	your membership. This could take a day or two.

	You will receive your personal login and have full access then.
	"),

'N2'	=>	array(
	'subj' => "Please Confirm Your Email for AMD FLAMEs",
	'msg' => "

	_name_, a few days ago we sent you an email asking you
	to confirm this email for your signup on the AMD Alumni
	FLAMEs site. We haven't heard back from you.

**************************************************
	To confirm this is your correct email, please click on the
	link below:
		_verify_url_
***************************************************

	Otherwise, your signup will be cancelled.
	"),

'B1'	=>	array(
	'subj' => "Your AMD FLAMEs Email Bounced - Action Needed",
	'msg' => "

	_name_,  The AMD Alumni FLAMES  site recently
	sent you an email at this address that bounced.
	Can you please confirm that this email is correct for you?

***************************************************
	If this email <_email_> is correct, please just click on the
	link below to confirm:
		_verify_url_
***************************************************

	
	"),

'B2'	=>	array(
	'subj' => "AMD FLAMEs Email Bouncing - Second Request!",
	'msg' => "

	_name_,  we recently sent you an email advising you that
	email sent to this address <_email_>
	from the AMD FLAMEs site was bouncing.

	We didn't hear back from you, we're trying again.

***************************************************
	If this message gets through to you, please click on
	the link below to confirm:

		_verify_url_
***************************************************

	Otherwise we will have to mark you as a Lost Member.

	
	"),

'E1'	=>	array(
	'subj' => "Confirm your new email on the AMD Alumni Site",
	'msg' => "

	_name_, the email for your membership on the FLAMEs AMD Alumni
	site has been updated from _prior_email_ to _email_.

***************************************************
	YOU NEED TO CONFIRM THIS CHANGE BY CLICKING THE LINK BELOW NOW!

		_verify_url_
***************************************************

	
	"),

'E2'	=>	array(
	'subj' => "AMD FLAMEs Email Confirmation Required - Second Request",
	'msg' => "

	_name_, about a week ago, your email
	on the AMD FLAMEs site was changed, and we sent
	you an email asking you to verify the change.

	You haven't confirmed the change, so now we're wondering if
	this was a mistake?

***************************************************
	If this is your correct new email, please
	CLICK ON THE LINK BELOW to confirm:

		_verify_url_
***************************************************

	

	"),

'A1'	=>	array(
	'subj' => "Confirm your email for AMD Flames.",
	'msg' => "

    This is an email from the AMD Alumni Flames site to confirm that
	we have your correct email in our member list.

***************************************************
  JUST CLICK THE LINK BELOW NOW to confirm that
  this (_email_) is your email:

       _verify_url_

***************************************************

	

	(Note: You can avoid these messages by logging into the web site
	at least once a year.)
	"),

'A2'	=>	array(
	'subj' => "Confirm your email for AMD Flames - Second Request",
	'msg' => "

	_name_, about a week ago we sent you an email
	asking you to confirm that this is still your correct email.
	We haven't heard back from you, so we're giving it another shot.

	Please click on the link below to simply verify that this
	email still works for you.

  	_verify_url_

  

  	"),

'A3'	=>	array(
	'subj' => "_name_, please confirm your email address",
	'msg' => "

		_name_, about 2 weeks ago we sent you an email asking you to confirm
	that this is your correct email.  We still haven't heard back from you.

***************************************************************
	IF YOU RECEIVE THIS EMAIL, please click on the link below:

  	_verify_url_
***************************************************************

  

	"),


'A4'	=>	array(
	'subj' => "AMD FLAMEs Email confirmation - Final Request",
	'msg' => "

	_name_, we have sent several emails over a few weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we think you may not be using this address.

**************************************************************************
  IF THIS IS YOUR CORRECT EMAIL, PLEASE CLICK ON THE LINK BELOW:
  	_verify_url_
**************************************************************************

	If you have any questions or concerns, please contact the administrator.
	We don't want to lose track of you.  If you do not verify,
	your user status will be set to 'Lost' and you won't receive any
	more emails from us.
	"),

'D'	=>	array(
	'subj' => "AMD Alumni FLAMEs Email Verification",
	'msg' => "

	_name_, We have been trying to contact you for several weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we aren't sure you are stilling using this address.

	If this is your correct email, please click on the link below:

  	_verify_url_

	

")

);

	private static $bulk_warn =	"
	The FLAMEsite sends out an email whenever a new newsletter is
    published, typically once a week.  YOU ARE NOT CURRENTLY RECEIVING
    THIS.  If you'd like to keep informed about AMD alumni, go to your
    profile using the link below, and UNcheck the box 'No Email Updates'.
	";


	private static $closing =  "
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

	private static $please_subscribe =  "   
	Please consider subscribing to the short weekly email update.
     Log in, edit profile, and UNCLICK 'Opt out of Weekly Update'.
     ";
    
    private $em_headers = "From: admin@amdflames.org\r\nErrors-to:postmaster@amdflames.org\r\n";
    
    private $pdo;
    
########################################################################

	public function __construct ()
	{
		$this->pdo = MyPDO::instance();
	
	}
	
	
	public function update_email_status($uid,$mstatus,$mode='')
	{
		// mode = null (Real) | Silent | or Test
		 // update silently if mode = silent
		 // do not update db if mode = Test
		 echo "ems_update: $uid, $mstatus, $mode <br>";
		 
			if (empty($uid)){throw new Exception ("No id for update_emial_status");}
			if (empty($mstatus)){throw new Exception ("No ems for update_emial_status");}

			// get the existing user data
			$sql = "SELECT 
				username,user_id, upw, email_status,user_email,prior_email,profile_updated,
				last_login, email_last_validated, profile_validated, no_bulk
				from `members_f2` 
				WHERE user_id = '$uid'";
			if (! $row = $this->pdo->query($sql)->fetch()){
				throw new Exception ("No such user: uid $uid");
			}
	
	
	echo "ems update for ${row['username']} profile upate ${row['profile_updated']} <br>";


		if ($mode !== 'Test'){
			echo "starting update_db. ";
			if (! $this->update_db($uid,$mstatus) ) {
				throw new Exception ("DB update failed");
			}
		}
	
		 if (substr($mstatus,0,1) != 'L'){ #not lost
		 	echo "starting email_user";
			$this->email_user($mstatus, $row);
		 }
	 	

		if ($mode !== 'Silent'){
			return "Your email has been verified";
		}
		return "";

    }
    private function define_subs($row)
    	{
    		$login = $row['upw'] . $row['user_id'];
    		return array(
				'_name_' => $row['username'],
				'_email_' => $row['user_email'],
				'_verify_url_' => SITEURL . "/scripts/verify_email.php?s=$login",
				'_login_url_' => SITEURL . "/?s=$login",
				'_prior_emai_' => $row['prior_email'],
			);
	}
    private function email_user ($mstatus, $row)
     {
			// row has return from db fetch
			// set user vars
			
			$name = $row['username'];
			$email = $row['user_email'];

			
			$profile_text =';';


			if (empty($msg =  self::$user_messages[$mstatus] )){
				echo "Error getting user message for status $mstatus";exit;
			}
 
			$em_subj = $msg['subj'];
			$message = $msg['msg'];
			
			// add extra texts
			if ($row['no_bulk']){
				$message .= self::$bulk_warn;
			}
			
			$message .= self::$closing;

			$substitutions = $this->define_subs($row);
			
			$message = 
				str_replace(array_keys($substitutions),array_values($substitutions),$message);
			
			$headers = $this->em_headers;
			
			$admin_list = ['A4'];
			if (in_array($mstatus,$admin_list)){
				$headers .= "Cc: admin@amdflames.org\r\n";
			}
			#mail ('admin@amdflames.org',$em_subj,$message,$headers);
			mail ($email,$em_subj,$message,$headers);
	
     }
     
	private function update_db($uid,$ems) 
	{
		//update the email status in the db.
         // also sets user status if ems = LD
        $sql = "UPDATE `members_f2`
            SET email_status = '$ems' ";
         if ($ems == 'LD'){
         	$sql .= ", status = 'D' ";
         }
         if ($ems == 'Y'){
         	$sql .= ", email_validated = NOW() ";
         }
         $sql .= " WHERE user_id = '$uid';";
        
       if ( $result = $this->pdo->query($sql) ){
       	return true;
       }
       return false;
        
      }

	private function build_summary($row)
	{
		$subscriber = ($row['no_bulk'])?'no':'yes';
		
		return "
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

	}
	
	

}
