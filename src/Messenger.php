<?php
namespace digitalmx\flames;

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

use digitalmx\flames\Definitions as Defs;
use digitalmx as u;
use digitalmx\flames\Member;

$proj_dir = dirname(__DIR__); #flames
require_once "$proj_dir/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception ;





/*
	Sends an email message to a user based on an event.
	
	$msg = new Messenger ($pdo,$test); (if test is true, doesn't send mail)
	$result = sendMessages($uid,$event,$exta)
	
	@ uid is the user_id of the user
	@ event is the code for the event, such as 'ems-A4'
	@ extra is array of key->value for additional text replacements
	
	There are text files in templates for each event code.
	
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

	'ems-A4' => "No response to several requests to verify email address.",
	'ems-LA' => "Gave up trying to contact",
	'ems-B2' => "Email has repeatedly bounced.",
	'ems-E2' => "New email address not confirmed",
	'ems-LO' => "Lost other. No code supplied.",
	'ems-LS' => "New signup failed to confirm email.",
	'ems-LE' => "User never confirmed change of email.",
	);


    private static $admin_message =	<<<EOT
	Notice: ::name:: ::code_description:: 

	Alert to FLAMES administrator 
	There is a problem with this user's information on AMD Flames.
	Please attempt to manually reconnect with this user.

	::user_dataset::
	
	::event::
EOT;


	private static $templates = REPO_PATH . '/templates';
	
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
	private $test;
	private $member;
	private $mailer;
	
	private $user_dataset;
	
	// std header for outgoing emails, but can be changed before using
	private static $email_header_array = array(
		"Errors-to" => "postmaster@amdflames.org",
		"Content-type" => 'text/plain; charset=utf8',
		"From" => 'AMD Flames Admin <admin@amdflames.org>'
		);
	
	
	public function __construct() {
		#$this->pdo = $pdo;
		$this->pdo = \MyPDO::instance();
		
		$this->test = false; #use setMode(true) to set test=false
		#$this->member = $member;
		$this->member = new Member();
		
		$this->mailer = new PHPMailer();
		
		$this->mailer->setFrom('admin@amdflames.org',"AMD Flames Admin");
		$this->mailer->addCustomHeader('Errors-to','postmaster@amdflames.org');
		$this->mailer->CharSet = 'UTF-8'; 
		$this->mailer->isSendmail();
	}
	public function setMode($mode=true ) {
		// false = test mode
		$this->test = ! $mode;
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
			$email = $this->member->getMemberEmail($tag);
		}
		elseif (u\isValidEmail($tag)){
			$email = $tag;
		} else {
			throw new Exception ("Cannot decipher tag $tag to get email address");
		}
		$em['subject'] = $message_subject;
		$em['message'] = $message;
		$em['to'] = $email;
		$em['header'] =  self::$email_header_array;
		$em['header']['BCC'] = 'admin@amdflames.org';
	

		$response = $this->send_mail($em) ;
		return $response;
		
	}
	
	public function sendMessages($uid,$event,$extra_data=[])
	{
		// main routine.  Determines if applicable.  sends user, sends admin.
		 // do not send if mode = Test
		 // can be called with extra data which will be added to replacements table.
		 // ::key:: => val
	
		 if(empty($event)){
			  throw new Exception ('bad Messenger call',"empty event with uid $uid");
		 }
		 #get all the user data from member
		$row = $this->getUser($uid);
		
		// add codes to placeholders
#echo "Preparing to send message to user $uid ${row['username']} for event $event" . BRNL;
			$this->replacements ['::code_description::'] = self::$admin_codes[$event] ?? '';
			$this->replacements ['::event::'] = $event;
			
			//note: 'informant' should be 'you',member name, or 'another member'
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
				$em['header'] = self::$email_header_array;
#u\echor ($em, 'message'); exit;
			$this->send_mail($em);
			}
		
		  /* prepare email to admin
		 statuses requiring admin email are identified
		in the $lost_reasons array
		*/
		if (!empty(self::$admin_codes[$event])){
			
			$message = $this->replacePlaceholders(self::$admin_message); 	
			$message_subject = strtok($message,"\n"); #first line
			
			$message = u\email_std($message);
		 	
	
				$em['subject'] = $message_subject;
				$em['message'] = $message;
				$em['to'] = 'admin@amdflames.org';
				$em['header'] =  self::$email_header_array;
				$em['header']['From'] = $row['user_email'];



			echo $this->send_mail($em);
		}
		
	
		return true;
	}
	// retrieves enhanced user dat a from Member,
	// builds the data set for admin reporting
	// builds all the placeholders for replacing in message
	private function getUser($uid) 
	{
		$md = $this->member->getMemberData($uid);
		if (empty($md)){
			throw new Exception ( "Messenger failed to getMemberData on uid $uid .");
			return [];
		}
		if (!empty($md['error']) ){
			throw new Exception ("Error retrieving member: " . $md['error']);
			return [];
		}
		elseif ($md['count'] == 0){
			throw new Exception ( "Messenger failed to getMemberData on uid $uid: not found");
			return [];
		}
		
		$this->user_dataset = $this->buildDataset ($md['data']);
		$this->placeholders = $this->buildPlaceholders ($md['data']);
		
		return $md['data'];
	
		
	}	
		
		
	private function buildPlaceholders ($row) {
		$login = $row['login_string'] ;
		$this->replacements ['::login::'] = 'https://amdflames.org/?s=' . $login;
		$verify_code =  SITE_URL . "/scripts/verify_email.php?s=$login";
		$this->replacements ['::verify::'] = $verify_code;
		$this->replacements['::name::'] = $row['username'];
		$this->replacements['::current_email::'] = $row['user_email'];
		$this->replacements ['::prior_email::'] = $row['prior_email'];
		$this->replacements ['::informant::'] = "another member" ;
		$this->replacements ['::user_dataset::'] = $this->user_dataset;
		$this->replacements ['::join_date::'] = date('d M, Y',strtotime($row['join_date']));
		$this->replacements ['::closing::'] = self::$closing;
		
		$this->replacements ['::profile::'] =  
			($row['profile_age'] > Defs::$old_profile_limit ) ?
			self::$profile_message : '' ;
		
		$this->replacements ['::bulk::'] = 
			($row['no_bulk'] == true) ?
			self::$bulk_warn : '' ;

	}
	
	
	private function buildDataset($row) {

#u\echor($row,'For dataset');

	$dataset = "
User: ${row['username']}
---------------------
   Email: ${row['user_email']} 
      (Previously: ${row['prior_email']})
   Receives weekly newsletter: ${row['subscriber']}
   Current Email Status: ${row['email_status']} ${row['email_status_name']}

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
			$response =  "<h3>send email Test:</h3>" ;
			$response .= "Header:";
			$response .= u\echopre ($header) ;
			$response .= u\echor ($data,'data array');
			return $response;
			
		} else  {	
			#mail ($data['to'],$data['subject'],$data['message'],$header);
			
			$this->mailer->addAddress($data['to']);
			$this->mailer->Subject = $data['subject'];
			$this->mailer->Body = $data['message'];
			try{
			$this->mailer->send();
			} catch (Exception $e) {
				echo "Error in mailer: " . $e->getMessage(); 
			}
			
			return "Mailed: " . $data['subject'] . "\n";
		}

		
	}
	
		
	private function replacePlaceholders ($msg) 
	{
		foreach ($this->replacements as $key=>$val){
			$msg = str_replace($key,$val,$msg);
		}
		
		return $msg;
	}
	

		
	private function getUserText($code){
		// code will be like ems-A1
		$msg_file = self::$templates . '/' . $code . '.txt';
		$message = false;
		if ( file_exists($msg_file)){
			$message = file_get_contents($msg_file);
		}
		return $message;
	 
	
	}


}
