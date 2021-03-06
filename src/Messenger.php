<?php
namespace DigitalMx\Flames;


#ini_set('display_errors', 1);


use DigitalMx\Flames\Definitions as Defs;
use DigitalMx as u;
use DigitalMx\Flames\Member;
	use DigitalMx\MyPDO;
use DigitalMx\Flames as f;

$proj_dir = dirname(__DIR__); #flames
require_once "$proj_dir/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception ;



/*
	Sends an email message to a user based on an event.

	$msg = new Messenger ();
	$msg ->setMode (false) ; changes it to test mode.  show and don't send

	$result = sendMessages($uid,$event,$exta)

	@ uid is the user_id of the user
	@ event is the code for the event, such as 'ems-A4'
	@ extra is array of key->value for additional text replacements

	There are text files in templates for each event code.

	Script requires pdo, member, and phpmailer classes.
	Requires a preceeding init or cron-init to set paths

	The system retrieves the user data from Member, builds an arrasy
	of 'replacement' vawriables, then retrieves requested message text,
	substitutes any replaement variables, and then sends message

	If there is no template for the code, it does nothing.

	If there is a "admin code" for the same code, then it will
	notify the admin.



*/

#don't know if I need any init.  boot will already have run

class Messenger
{
// user will receive warning if profile update is over this number of days
private static $profile_age_limit_days = 600;

private static $admin_codes = array(
// only these codes get an admin notice


	'B2' => "Email has repeatedly bounced.",
	'E2' => "New email address not confirmed",

	'LA' => "Gave up trying to contact",
	'LO' => "Lost other. No code supplied.",
	'LS' => "New signup failed to confirm email.",
	'LE' => "User never confirmed change of email.",
	'D' => "Code D undefined",

	'EV' => "Email Validated",

	);


    private static $admin_message_lost =	<<<EOT
Lost AMD Alumni: ::name:: - ::event::

Alert to FLAMES administrator:
	There is is no response to automated email messages sent to
	::name:: using ::current_email::

	Please manually confirm that this address still works.

User Info:
------------------------

	::user_dataset::

EOT;

    private static $admin_message_verified =	<<<EOT
 Email Verified ::name:: -  was ::ems::

EOT;
	private static $template_dir = REPO_PATH . '/templates';

    private static $profile_message = "
Your profile has not been updated in more than 2 years.
Anything new?  Log in, go to profile, and edit.
    ";

    private static $bulk_warn =	"
The FLAMEsite sends out an email about once a week.
	YOU ARE NOT CURRENTLY SUBSCRIBED TO THIS.
If you'd like to keep informed about AMD alumni, log in,
go to your profile, edit,  and UNcheck the box 'No Email Updates'.
	";

	private static $closing =  "

If you think this message is in error, please email the admin by
replying to this email, so I can fix the problem. This email was
sent  by a dumb computer program but your reply will be read by
a human, namely me.

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
	private $test=false;
	private $member;
	private $mailer;

	private $user_dataset;

	/* if setTestMode(true) then it will report emails, but not actually
		send them.
	*/


	public function __construct($container) {
		#$this->pdo = $pdo;
		$this->pdo = $container['pdo'];


		#$this->member = $member;
		$this->member = $container['member'];
		$this->mailer = new PHPMailer();

		$this->mailer->addCustomHeader('Errors-to','postmaster@amdflames.org');
		$this->mailer->CharSet = 'UTF-8';
		$this->mailer->isSendmail();
		$this->mailer->SMTPKeepAlive = true;

		// don't send emails from local site unless
		// setTestMode is run to turn it on.

	}
	public function setTestMode($mode) {
		$this->test = $mode;
	}

	public function sendHiddenEmail ($id,$subject,$message) {
		// send an email from a user to another user.
		// used for hidden emails
		// from: data comes for current user login
		$fromemaillinked = $_SESSION['login']['user_email_linked'];
		$from_email = $_SESSION['login']['user_email'];
		$from = $_SESSION['login']['username'];
		list ($toname,$toid,$toemail) = $this->member->getMemberBasic($id);

		$msg = <<<EOF
The message below was sent to you through the AMD Flames site
by another AMD Flames member.  (Your email on the site is hidden.)
-----------------------
From: $fromemaillinked
Subject: $subject
$message

-----------------------
If you have any concerns, please email the admin at admin@amdflames.org.

EOF;
	$em['subject'] = 'Email from AMD Flames member: ' . $from;
	$em['message'] = $msg;
	$em['to'] = $toemail;
	$em['name'] = $toname;
	$em['from'] = $from_email;


	$response = $this->send_mail($em) ;
		return $response;
}


	public function sendLogins($tag,$msg){
		 $message = $this->getUserText ('logins');
		if (empty($message )){
			throw new Exception ("Failed to retreive message template");
		}

		$message = str_replace('::logins::', $msg, $message);
		$message_subject = strtok($message,"\n"); #first line
		#$message = u\email_std($message);

		if (is_numeric($tag)){
			list($name,$uid,$email) = $this->member->getMemberBasic($tag);
		}
		elseif (u\isValidEmail($tag)){
			$email = $tag;
			$name = 'Flames Member';

		} else {
			throw new Exception ("Cannot decipher tag $tag to get email address");
		}
		$em['subject'] = $message_subject;
		$em['message'] = $message;
		$em['to'] = $email;
		$em['name'] = $name;
		$em['header'] = array('BCC' => 'admin@amdflames.org');


		$response = $this->send_mail($em) ;
		return $response;

	}

	public function sendMessages($uid,$event,$extra_data=[])
	{
		// main routine.  Determines if applicable.  sends user, sends admin.
		 // do not send if mode = Test
		 // can be called with extra data which will be added to replacements table.
		 // ::key:: => val
		 // event is next ems

		 if(empty($event)){
			  throw new Exception ('bad Messenger call',"empty event with uid $uid");
		 }

		 #get all the user data from member
		$row = $this->getUser($uid);

		// add codes to placeholders
#echo "Preparing to send message to user $uid ${row['username']} for event $event" . BRNL;
			$this->replacements ['::code_description::'] = self::$admin_codes[$event] ?? '';
			$this->replacements ['::event::'] = $event;

			//note: 'informant' should => 'you',member name, or 'another member' or 'profile change'
			// on em-found
			if (!empty($extra_data)){
				foreach ($extra_data as $key=>$val){
					$this->replacements['::' . $key . '::'] = $val;
				}
			}


		/* Get email template for event
			no point in emailing if the user is marked as lost
			or if there is no message template for this event
		*/

		if (substr($event,0,1) !== 'L'
			&& $message = $this->getUserText($event) ) {
			#message to user
			#first do replacements, then formatting, then send

			$message = $this->replacePlaceholders($message);

			$message_subject = strtok($message,"\n"); #first line
			$message = u\email_std($message);


				$em['subject'] = $message_subject;
				$em['message'] = $message;
				$em['to'] = $row['user_email'];
				$em['name'] = $row['username'];

#u\echor ($em, 'message');
			$this->send_mail($em);
			}

		  /* prepare email to admin
		 statuses requiring admin email are identified
		in the $admin_codes array
		*/
		if (!empty(self::$admin_codes[$event])){
			if ($event == 'EV') {
				$message = $this->replacePlaceholders(self::$admin_message_verified);
			}
			else {
				$message = $this->replacePlaceholders(self::$admin_message_lost);
			}
			$message_subject = strtok($message,"\n"); #first line

			$message = u\email_std($message);


				$em['subject'] = $message_subject;
				$em['message'] = $message;
				$em['to'] = 'admin@amdflames.org';
				$em['name'] = 'Flames Admin';

				$em['from'] = 'admin@amdflames.org';
				$em['replyto'] = $row['user_email'];



			 $this->send_mail($em);
		}


		return true;
	}




	// retrieves enhanced user dat a from Member,
	// builds the data set for admin reporting
	// builds all the placeholders for replacing in message
	private function getUser($uid)
	{
		$md = $this->member->getMemberRecord($uid,true);
		if (empty($md)){
			echo "Messenger failed to getMemberData on uid $uid .";
			return [];;
		}


		$this->user_dataset = $this->buildDataset ($md);
		$this->placeholders = $this->buildPlaceholders ($md);

		return $md;


	}


	private function buildPlaceholders ($row) {
		$login = $row['login_string'] ;
		$uid = f\splitLogin($login)[0];
		$this->replacements ['::login::'] = SITE_URL . '/?s=' . $login;

		$this->replacements ['::verify::'] = SITE_URL . "/action.php?V" . $uid;
		$this->replacements ['::signup::'] = SITE_URL . "/action.php?S" . $uid;
	#	$this->replacements['::profile_edit::'] = SITE_URL . "/action.php?P" . $login;
		$this->replacements['::ems::'] = $row['email_status'];
		$this->replacements['::name::'] = $row['username'];
		$this->replacements['::current_email::'] = $row['user_email'];
		$this->replacements ['::prior_email::'] = $row['prior_email'];
		$this->replacements ['::informant::'] = "another member" ;
		$this->replacements ['::user_dataset::'] = $this->user_dataset;
		$this->replacements ['::join_date::'] = date('d M Y',strtotime($row['join_date']));
		$this->replacements ['::closing::'] = self::$closing;

		$this->replacements ['::profile_warn::'] =
			($row['profile_age'] > Defs::$profile_warning ) ?
			self::$profile_message : '' ;

		$this->replacements ['::bulk::'] =
			($row['no_bulk'] == true) ?
			self::$bulk_warn : '' ;



	}


	private function buildDataset($row) {

#u\echor($row,'For dataset');
	$subscriber = ( $row['subscriber'])? 'yes':'no';
	$uid = $row['user_id'];
	$verify =  SITE_URL . "/action.php?V" . $uid;
	$last_login_date = u\makeDate($row['last_login']);
	$dataset = "
User: ${row['username']}
---------------------
   Email: ${row['user_email']};
   Last Email Status: ${row['email_status']} ${row['email_status_name']}
   Receives weekly newsletter: $subscriber

   Previous emails:
   ${row['prior_email']}


Activity
---------------------
   Last login: $last_login_date
   Email last validated: ${row['email_valid_date']}
      (changed on: ${row['email_chg_date']})
   Profile last validated: ${row['profile_valid_date']}
      (updated on: ${row['profile_date']})

Click to Confirm
-----------------------
	 $verify

";
	return $dataset;
}

	private function send_mail($data) {
		#$this->test = true;
		//$data arry for to,subj,msg, and header
		//header is array to be joined to make header

		if (!empty($data['header'])){
			foreach ($data['header'] as $key=>$val){
				 $this->mailer->addCustomHeader($key,$val);
			}
		}
		// don't send if in test mode, OR
		//  if running locally and not one of my email adderesses
		if ($this->test
			||
			(SITE == Defs::$local_site && (! in_array($data['to'],Defs::$safe_emails) ) )
			) {
				echo '<br><p>Mode: ';
				echo  $this->test? 'true':'false' . '</p>' . BRNL;
		 	 u\echor ($data,'Messenger in test mode ' );


		 	 //u\echor (Defs::$safe_emails,'Safe EMails');

		 	 	;
		 	 return;
		}

			#mail ($data['to'],$data['subject'],$data['message'],$header);
			$from = $data['from'] ?? 'admin@amdflames.org';
			$replyto = $data['replyto'] ?? $from;
			$this->mailer->setFrom($from);

			$this->mailer->addAddress($data['to'],$data['name']);
			$this->mailer->Subject = $data['subject'];
			$this->mailer->Body = $data['message'];
			// for admin messages, change replyto to the users email
			$this->mailer->addReplyto ($replyto);

			try{
				$this->mailer->send();
			} catch (Exception $e) {
				echo "Error in mailer: " . $e->getMessage();
			}
			$this->mailer->clearAddresses();
			$this->mailer->clearReplyTos();


			return "Mailed: " . $data['subject'] . "\n";
	}


	private function replacePlaceholders ($msg)
	{
		foreach ($this->replacements as $key=>$val){
			$msg = str_replace($key,$val,$msg);
		}

		return $msg;
	}


	private function getUserText($code){
		// code will be like A1
		$msg_file = self::$template_dir . '/' . $code . '.txt';
		$message = false;
		if ( file_exists($msg_file)){
			$message = file_get_contents($msg_file);
		}
		return $message;


	}


}
