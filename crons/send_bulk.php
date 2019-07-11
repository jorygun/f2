#!/usr/local/bin/php 
<?php
ini_set('display_errors', 1);


#echo "Starting send_bulk.php\n";


/* This script looks for one job in the
    folder bulk_queue.  These are just job numbers
    that referene folders in /bulk_jobs
    These jobs contain the mail list, the subject,
    the interval, and the content of the email message.

    The script sends the mails out separated by interval seconds.

    The script is designed to be run from cron or at or some
    other process that isolates it from a running web sessions.
    
    It runs one job at a time; next job will run on the next cron cycle.

*/

//BEGIN START
    #set to true or false to delete the bulk_queue after running.
#   echo "Starting script\n";

	#run script with param 'keep'  to keep queue and not rename it when it is run.
	#  php send_bulk.php keep
	
$script = basename(__FILE__);
$dir = dirname(__FILE__);
include "$dir/cron-ini.php";
if (! @defined ('INIT')) { die ("$script halting. Init did not succeed \n");}

use \digitalmx\flames\Definitions as Defs;

   
	$bulk = REPO_PATH . "/var/bulk_jobs";
	$queue =  REPO_PATH . "/var/bulk_queue";
	
	#where info needed for bulk mail is located
	$news_info = PROJ_PATH . "/live/public/news";
	

#needed??
#set_include_path(get_include_path() . ':/usr/home/digitalm/Sites/flames/libmx/phpmx:/usr/home/digitalm/Sites/flames/live/code');

// Load Composer's autoloader
require PROJ_PATH . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



	ignore_user_abort(false);
	$interval = 6; #seconds/msg    
   set_time_limit(86400);
	


#---Check for any jobs in the bulk queue

if (!$quiet) 
echo "Checking for files in $queue" . BRNL;

/*
	queue redesigned as a directory holding files with 
	jobids.  The files are created by 'touch' with
	the date set to the scheduled run date/time.
*/
		
	if (empty($job = checkfiles($queue)) ){
	echo "Nothing";
		exit;
	}
	echo "Starting $job" . BRNL;
	
	#set job dir and record pid so it can be stopped if needed
	$job_dir = "$bulk/${job}";
	if (! is_dir($job_dir)){
		throw new Exception ("Cannot find bulk job directory $job_dir");
	}
	
	
   $bmail_list = "$job_dir/list.txt";
   $bmail_msg = "$job_dir/message.txt";
	
	#record pid so job can be killed if necessary.  Multiple runs
	# will record multiple pids.
	if (! file_put_contents("$job_dir/pid.txt",getmypid() . "\n", FILE_APPEND ) ){
	
		echo "Cannot write PID file: ";
		u\echor( stat ($job_dir),'Stat' );
		exit;
		
	
	$starttime = time();
	$startdate = date('Y-m-d H:i',$starttime);
	$start_dt = new DateTime();
	
	#start logfile
	$logfile = "$job_dir/log.txt";
	$logh = fopen($logfile,"w") or die ("Cannot open logfile $logfile");
	
	fwrite ($logh,"Job $job started at " . $startdate . " \n ") ;
	

	
	// [subject,content,html]
	$msg_array = read_msg_file($bmail_msg);
	$message = $msg_array['content'];
	
// replacements in univeral message
	// replace ref to image with image
	$message = preg_replace(
		'/\[image (\d+)\]/',
		"<img src='https://amdflames.org/assets/thumbs/$1.jpg' style='margin-right:auto;margin-left:auto;text-align:center;'>",
		$message
		);

#get address of current newsletter
	$latest_pointer = file_get_contents("$news_info/latest_pointer.txt") ;
	
 #set up mail object
   $mail = new PHPMailer;
	$mail->isSendmail();
	$mail->setFrom('editor@amdflames.org','AMD Flames News');
	$mail->addCustomHeader('Errors-to','postmaster@amdflames.org');
	$mail->CharSet = 'UTF-8'; 

#set some std paragraphs
	 $no_bulk_message = <<<EOT

   (We see you are not a subscriber to the weekly newsletter announcement.
   Please consider changing this setting on your profile so you can
   hear news about AMD and other AMD Flames. 
   Your personal login for the site is:
   ::link::  )

EOT;

  $profile_message = "
    Your profile was last updated on ::profile_update::.  
    To update: log in, then under your name at top right, select 'View/Edit Profile'.

        ";
        
	$verify_message = <<<EOT
------------------------------------------------------------------
   Click to verify that this is your correct email address:

       https://amdflames.org/scripts/verify_email.php?s=::slink::

-------------------------------------------------------------------
EOT;


  // Reset sent counter
    $sent = 0;
    
    
 #open reciepient file and loop over the contents
	$list_handle = fopen("$bmail_list",'r') or die ("Can't open list at $bmail_list");
    while (($line = fgets($list_handle)) !== false) {
    	#echo $line . "\n";
    	
       	#create individaul copy of message and subject
        $imessage = $message; 
        $isubject = $msg_array['subject'];
             
        #get the user vars from the file
        /*
          $mlarray = [
            $row['username'],
            $row['user_email'],
            $slink,
            $profile_updated_age,
            $profile_updated_date,
            $row['no_bulk'],
            $age_flag,
            $profile_validated_date
        ];
        */
        
          list(
			 $username,$user_email,$slink,
			$profile_updated_age,$profile_updated_date,
			$no_bulk, $age_flag,$profile_validated_date
			) = explode("\t",$line);

		#creat personalized vars
            $logincode="s=$slink";
            $login_link = SITE_URL . "/?s=$slink"; 
            
            $news_url = SITE_URL . "/news" . "/?s=$slink";
            $news_link = "<a href='$news_url'>$news_url</a>";
            
            $profile_link = SITE_URL . "/scripts/edit_profile.php/?s=$slink";
            
            
        #subsititute in imessage.  later subs can replace text in earlier subs
        if ($no_bulk){$imessage = str_replace('::no_bulk::',$no_bulk_message,$imessage);
            
         } else {
               $imessage = str_replace('::no_bulk::','',$imessage);
         }
			 if ($age_flag > 0){
               $imessage = str_replace('::profile::',$profile_message,$imessage);
            
         }else {
             $imessage = str_replace('::profile::','',$imessage);
         }
			if (false) {
				$imessage = str_replace('::verify::',$verify_message,$imessage);
		  } else {
				$imessage = str_replace('::verify::','',$imessage);
		  }
           
            $isubject = str_replace('::name::',$username,$isubject);
            $imessage = str_replace('::edition::', $username, $imessage);
            $imessage = str_replace('::name::', $username, $imessage);
            $imessage = str_replace('::link::', $login_link, $imessage);
            $imessage = str_replace('::slink::',$logincode,$imessage);
             $imessage = str_replace('::verify::',$verify_link,$imessage);
             $imessage = str_replace('::uemail::',$user_email,$imessage);
             $imessage = str_replace('::newslink::',$news_link,$imessage);
             $imessage = str_replace('::profile_update::',$profile_updated_date,$imessage);
             
            
	

           
			#if (!$html){
				$imessage = nl2br($imessage);
			#}
			
            $to = "\"$username\" <$user_email>";


        // Email this User

if ($test) {
echo <<<EOT
PHPMAIL:
To $user_email, $username
Sub: $isubject
Msg: $imessage

EOT;
} else {

           # mail($to, $subject, $imessage, $message_header,"-fpostmaster@amdflames.org");
			$mail->clearAddresses();
			$mail->addAddress($user_email,$username);
//Set the subject line
			$mail->Subject = $isubject;
//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
			$mail->msgHTML($imessage);
//Replace the plain text body with one created manually
			#$mail->AltBody = 'This is a plain-text message body';
		if (!$mail->send()) {
			fwrite ($logh,"*** Error on $user_email. " . $mail->ErrorInfo . "\n") ;
		} 
}	
           $sent++; // Add to Sent Count

         

            // Wait the appropriate time
           sleep($interval);

      } // End of Loop

	
// end job
	
	$end_dt = new DateTime();
	$elapsed = time() - $starttime;
	$endtimedate = $end_dt -> format('Y-m-d H:s');
	#$elapsed_ob =  = date_diff ($end_dt , $start_dt);
	$rate = intval(3600 * $sent/($elapsed + 1)); #prevent /0

    $human_elapsed = human_secs($elapsed);

	$admin_msg = "
------------------------------------------------
Batch email completed on job $mypid at $endtimedate.
$reason

$sent sent in $human_elapsed. ($rate/hour).
--------------------------------------------------
	\r\n";

    fprintf ($logh, "$admin_msg\n");
    
    $subject = "Bulk Completed. $sent sent at $endtimedate";
    mail('admin@amdflames.org',$subject,$admin_msg);

		
	fclose ($logh);
			 
	 if ($test){ 
			#restore queue job
			rename ("$queue/${job}-running","$queue/$job");
			echo "Job $job retained in queue; will run again.";
	 
	 } else {
		unlink ("$queue/${job}-running"); 
		if (!$quiet) 
		echo "Job $job removed from queue";
	 }
	
		
			
			
	


	exit;




##########################################


 
/* returns a list of jobs in queue that
	are due to run and do not have a tag associated 
	with them.  Only one job will be run.
	Next job will be picked up on next cron run.
*/

function checkfiles($queue){
	$jfiles = [];
	$qfiles = scandir($queue);
	foreach ($qfiles as $f){
			/* get job id for jobs, including  a status suffix (-cancelled)
				looking for ddddddd plus option -text
			*/
			
			if (! preg_match('/^(\d+)(-(\w+))?$/',$f,$matches) ){continue;}
			$jobid = $matches[1];
			$jstat = $matches[2] ?? '';
			#echo "$f > $jobid, $jstat\n";
			#skip files with a status tag
			if ($jstat = 'cancelled' && filemtime("$queue/$f") < (time() - 86400) ) { #more than 24 hours old
				unlink ("$queue/$f");
				continue;
			}
			if (!empty($jstat)){continue;} #do not process job with any status
			#only have files with just jobid now
			if (filemtime("$queue/$jobid") > time() ){ continue;} #not due yet
			
			#have a job to run
			#rename ("$queue/$jobid","$queue/${jobid}-running");
			return $jobid; 
			
		}
		return false;
	}
		

 #####################################################


function human_secs($esecs){
    // express time in secs in human readable

    $t = '';
    $edays = intval ($esecs/ 86400);
    if ($edays > 0){
        $esecs %= 86400;
        $t .= "$edays days, ";
    }
    $ehrs = intval ($esecs / 3600);
    if ($ehrs > 0) {
        $esecs %=  3600;
        $t .= "$ehrs hours, ";
    }
    $emins = intval ($esecs / 60);
    if ($emins > 0) {
        $esecs %= 60;
        $t .= "$esecs minutes,  ";
    }

    $t .= "$esecs seconds.";

    return $t;
}

function read_msg_file($msg_file) {
		$msg=[];
		$mh = fopen("$msg_file", 'r');
		$msg['subject'] = fgets($mh); #first line
		while (($line = fgets($mh)) !== false) {
			$message .= $line;
		}
		fclose ($mh);
		$msg['content'] = $message;
		$msg['html'] = false;
		if (stripos($message, '<html>') !== false){
			$msg['html'] = true;
		} 
		
		return $msg;
	}

