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
// user will receive warning if profile update is over this number of days
private static $profile_age_limit_days = 10;

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
	'subj' => "AMD FLAMEs Signup Verification",
	'msg' => "

	Thanks for signing up for the FLAMEs AMD Alumni News
	site, ::name::.

	To confirm your signup, use the link below:
		::verify::

	You must confirm within 3 days to activate this signup.

	After you have clicked the link above, an administrator
	will review your signup and send you an email to confirm
	your membership. This could take a day or two.

	"),

'N2'	=>	array(
	'subj' => "Confirm Your Signup for AMD FLAMEs",
	'msg' => "

	::name::, a few days ago we sent you an email asking you
	to confirm this email for your signup on the AMD Alumni
	FLAMEs site. We haven't heard back from you.

**************************************************
	To confirm this is your email, click here:
		::verify::
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
	If this email <::current_email::> is correct, 
	just use the link below to confirm:
		::verify::
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
	If this message gets through to you, please use
	the link below to confirm:
		::verify::
***************************************************

	Otherwise we will have to mark you as a Lost Member.

	
	"),

'E1'	=>	array(
	'subj' => "Important: confirm your new email on the AMD Alumni Site",
	'msg' => "

	::name::, the email for your membership on the FLAMEs AMD Alumni
	site has been updated from ::prior_email:: to ::current_email::.

***************************************************
	CONFIRM THIS CHANGE BY CLICKING THIS LINK!
		::verify::
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
		::verify::
***************************************************
	
	"),

'A1'	=>	array(
	'subj' => "Confirm you're still around .",
	'msg' => "

    This is an email from the AMD Alumni Flames site.  It has been
    a long time since you logged in and we want to confirm that
	 we have your correct email in our member list.  

***************************************************
  JUST CLICK THE LINK BELOW NOW to confirm that
  you still get email at ::current_email::
       ::verify::
***************************************************

	(Note: You can avoid these messages by logging into the web site
	occasionally .)
	"),

'A2'	=>	array(
	'subj' => "Confirm your email for AMD Flames - Second Request",
	'msg' => "

	::name::, about a week ago we sent you an email
	asking you to confirm that this is still your correct email.
	We haven't heard back from you, so we're giving it another shot.

	Please use the link below to simply verify that this
	email still works for you.

  	::verify::

  	"),

'A3'	=>	array(
	'subj' => "::name::, please confirm your email address",
	'msg' => "

		::name::, about 2 weeks ago we sent you an email asking you to confirm
	that this is your correct email.  We still haven't heard back from you.

***************************************************************
	IF YOU RECEIVE THIS EMAIL, please use the link below:

  	::verify::
***************************************************************

  

	"),


'A4'	=>	array(
	'subj' => "AMD FLAMEs Email confirmation - Final Request",
	'msg' => "
	::name::, we have sent several emails over a few weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we think you may not be using this address.

*******************************************************************
  IF THIS IS YOUR CORRECT EMAIL, PLEASE CLICK ON THE LINK BELOW:
  	::verify::
********************************************************************

	If you have any questions or concerns, please contact me.
	We don't want to lose track of you.  If you do not verify,
	your user status will be set to 'Lost' and you won't receive any
	more emails from us.  Maybe.
	"),

'D'	=>	array(
	'subj' => "AMD Alumni FLAMEs Email Verification",
	'msg' => "

	::name::, We have been trying to contact you for several weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we aren't sure you are stilling using this address.

	If this is your correct email, please use the link below:

  	::verify::


")

    );
    
    private static $admin_message =	array(
	'subj' => "Lost AMD Alumni",
	'msg' => "

	Alert to FLAMES administrator :

    FLAMES user ::name:: is apparently not getting email.
    ::reason:: 

	Please attempt to manually reconnect with this user.

	::user_dataset::
	"
	);

    private static $profile_message = "
    Your profile has not been updated in more than 2 years.
    Anything new?  Log in, go to profile, and edit.
    ";
    
    private static $bulk_warn =	"
	The FLAMEsite sends out an email whenever a new newsletter is
    published, typically once a week.  YOU ARE NOT CURRENTLY RECEIVING
    THIS.  If you'd like to keep informed about AMD alumni, log in,
    go to your profile, edit,  and UNcheck the box 'No Email Updates'.
	";
	
	private static $closing =  "
	
	If you've already verified your email, or you think this message
	is in error, please email the admin by replying to this email, so
	I can fix the problem. This email was sent by a dumb computer 
	program but your reply will be read by a human, namely me.

	Also, if you want to change your email, just log into the site
	and change it in your profile.

--
	Regards,
	AMD FLAME site administrator
	admin@amdflames.org
";


    // many more replacements added when a specific user is selected
   private $replacements = array ();
   
	private $pdo;
	private $test;
	
	private $user_dataset;
	
	// std header for outgoing emails, but can be changed before using
	private $email_header_array = array(
		"Errors-to" => "postmaster@amdflames.org",
		"Content-type" => 'text/plain; charset=utf8',
		"From" => 'AMD Flames Admin <admin@amdflames.org>'
		);
	
	
	public function __construct($pdo,$test=false) {
		$this->pdo = $pdo;
		$this->test = $test;
	}
	
	private function get_user($uid) 
	{
	$sql = "SELECT user_id,username,user_email,prior_email,no_bulk,upw,profile_updated,profile_validated,
	last_login,email_last_validated,email_chg_date
		from `members_f2` 
		WHERE user_id = $uid;";
			
		
		 if (!$row = $this->pdo->query($sql)->fetch()){
		 	throw new Exception ("No user at uid $uid");
		}
		// enhance the info from the record
		$login = $this->get_login_from_row($row);
		$row['login'] = $login;
		$row['profile_age'] = dmx\days_ago($row['profile_updated']);
		
			
		
		$this->replacements ['::login::'] = $login;
		$verify_code =  SITE_URL . "/scripts/verify_email.php?s=$login";
		$this->replacements ['::verify::'] = $verify_code;
		$this->replacements['::name::'] = $row['username'];
		$this->replacements['::current_email::'] = $row['user_email'];
		$this->replacements ['::prior_email::'] = $row['prior_email'];
		

		$this->replacements ['::user_dataset::'] = $this->build_dataset($row);

	return $row;
	}
	
	private function build_dataset($row) {
	$subscriber  = ($row['no_bulk']) ? 'No' : 'Yes' ;
	$dataset = "
User: ${row['username']}
---------------------
   Email: ${row['user_email']} 
      (Previously: ${row['prior_email']})
   Receives weekly newsletter: $subscriber

Activity
---------------------
   Last login: ${row['last_login']}
   Email last validated: ${row['email_last_validated']} 
      (changed on: ${row['email_chg_date']})
   Profile last validated: ${row['profile_validated']} 
      (updated on: ${row['profile_updated']})

-----------------------
";
	return $dataset;
}

	private function send_mail($data) {
		//$data arry for to,subj,msg, and header
		//header is array to be joined to make header
		$header='';
		$header_array = $data['header'];
		foreach ($header_array as $key=>$val){
			$header .= $key . ': ' . $val . "\r\n";
		}
	
		if ($this->test) {
			echo "<h3>send_email data</h3>" ;
			echo "Header:";
			dmx\echopre ($header) ;
			dmx\echor ($data,'data array');
			
		} else  {	
			mail ($data['to'],$data['subj'],$data['msg'],$header);
		}

		return true;
	}
	
	private function get_login_from_row($row) 
	{
		$login = $row['upw'] . $row['user_id'];
		return $login;
	}
	
	
	private function replace_placeholders ($msg) 
	{
		foreach ($this->replacements as $key=>$val){
			$msg = str_replace($key,$val,$msg);
		}
		return $msg;
	}
	public function update_ems($uid,$mstatus,$test='')
	{
	 
		 // do not update if mode = Test
		 #echo "Updating status for uid $uid, status $mstatus\n";
	
		 if(empty($mstatus)){
			  throw new Exception ('bad update email call',"empty status with uid $uid");
		 }
		$row = $this->get_user($uid);
		# $this->test && dmx\echor($this->replacements,'replacement data User');
		
		
		/* Get email template for user
			no point in emailing if the user is marked as lost 
		*/
		if (substr($mstatus,0,1) != 'L') {
			if (! is_array($msg = $this->get_user_text($mstatus,$row) )){
				throw new Exception ( "Unrecognized ems $mstatus");
			}
		
			if (!empty($msg['subj'])
				&&  substr($mstatus,0,1) != 'L'){ 
				#if empty or lost, there is no user message
				
				// produce user email
				$message = $this->replace_placeholders($msg['msg']);
				$message = dmx\email_std($message);
			
				$em['subj'] = $msg['subj'];
				$em['msg'] = $message;
				$em['to'] = $row['user_email'];
				$em['header'] = $this->email_header_array;

				$this->send_mail($em);
			}
		}
		  /* prepare email to admin
		 statuses requiring admin email are identified
		in the $lost_reasons array
		*/
		if (! is_array($msg = $this->get_admin_text($mstatus,$row) )){
			throw new Exception ( "Error getting admin message for  $mstatus");
		}
		 if (!empty($msg['subj'])
		 	&& in_array($mstatus, array_keys(self::$lost_reasons)) ) { #otherwisee no admin msg
			
			  	$message = $this->replace_placeholders($msg['msg']);
			  	$message = str_replace('::reason::',$msg['reason'],$message);
				$message = dmx\email_std($message);
		 	
			$em['subj'] = $msg['subj'];
			$em['msg'] = $message;
			$em['to'] = 'admin@amdflames.org';
			$em['header'] =  $this->email_header_array;
			$em['header']['From'] = $row['user_email'];

			$this->send_mail($em);
		}
		
		$this->update_db($uid,$mstatus);
		
	}
	
	private function update_db($uid,$mstatus){

			 
			  #update the email status in the db.
					// also sets user status if second char on status
			 
			  $validate = ($mstatus == 'Y') ? 
			  	", email_last_validated = NOW()" : '';

				$sql = 
				"UPDATE `members_f2` 
				SET email_status = '$mstatus' $validate
				WHERE user_id = '$uid';";
					
				if ($this->test){
					echo "SQL (not done): $sql" . BRNL;
				} else {
				$result = $this->pdo->query($sql);
			  }
}
	
	
private function get_user_text($code,$row){

	if (! array_key_exists($code,self::$user_messages)){
		return 'error';
	}
	$message = self::$user_messages[$code]; #array of msg, subj
	
	if ($row['profile_age'] > self::$profile_age_limit_days ){
		$message = str_replace('::profile::', self::$profile_message, $message);
	}
	if ($row['no_bulk'] == true){
		$message = str_replace('::bulk::', self::$bulk_warn, $message);
	}
	$message['msg'] .= self::$closing;
		
	// is array of subj and msg
   return $message;
    
    
}

function get_admin_text($code,$row){
    // 
    if (! in_array($code,array_keys(self::$lost_reasons))){
        return array('subj'=>''); #no message
    }
   $message = self::$admin_message; #array of msg, subj
	$message['reason'] = self::$lost_reasons[$code];
	$message['subj'] .= " " . $row['username'] . " ($code) ";
	

 	return $message;
 }

}
