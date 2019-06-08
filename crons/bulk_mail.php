#!/usr/local/bin/php 
<?php
ini_set('display_errors', 1);
echo "Starting bulk mail.php\n";

/* This script looks for one or more jobs in the
    folder bulk_queue.  These are just job numbers
    that referene folders ~/bmail/working_job#
    These jobs contain the mail list, the subject,
    the interval, and the content of the email message.

    The script sends the mails out separated by interval seconds.

    The script is designed to be run from cron or at or some
    other process that isolates it from a running web sessions.

*/

//BEGIN START
    #set to true or false to delete the bulk_queue after running.
#   echo "Starting script\n";

	#set to true to delete queue when it is run.
	#set to false to reatin queue, as in testing.
	
    $DELETE_QUEUE = TRUE;
    $sitedir = dirname(__DIR__);

    defined ('HOME') or
		define ('HOME', '/usr/home/digitalm');
	defined ('SITEPATH') or
		define ('SITEPATH', "$sitedir/public");
	defined ('SITEURL') or
		define ('SITEURL','https://amdflames.org');

	defined ('BRNL') or
		define ('BRNL',"<br>\n");

	$job_directory = HOME ."/bmail";


	$starttime = time();
    $startdate = date('Y-m-d H:i',$starttime);
  
	$interval = 6; #seconds/msg

    $abort_file = SITEPATH ."/abort_mailing";
    $logdir = SITEPATH . "/logs/bulk_mail_logs";

     $queue = SITEPATH . "/bulk_queue";
 #   $queue = SITEPATH . "/bulk_queue_test";

    set_time_limit(86400);
	if (file_exists($abort_file)) {
		echo "Note: removing abort file.";
		unlink($abort_file);
	}

    

 
//END START


set_include_path(get_include_path() . ':/usr/home/digitalm/Sites/flames/libmx/phpmx:/usr/home/digitalm/Sites/flames/live/code');

// Load Composer's autoloader
require "$sitepath/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



ignore_user_abort(false);


#---Check for any jobs in the bulk queue

#echo "Checking for files in $queue" . BRNL;
/*
	queue redesigned as a directory holding files with 
	jobids.  The files are created by 'touch' with
	the date set to the scheduled run date/time.
*/
		
	if (empty($jobs = checkfiles($queue)) ){
		echo "No jobs for now" . BRNL; 
		exit;}


	echo " Running bulk_mail jobs" . BRNL;

	
	foreach ($jobs as $job){
			echo "Starting bulk mail run on pid $job. " . BRNL;
			#rename so it doesn't get started again.
			$job_running = "$queue/${job}-running";
			rename ("$queue/$job","$job_running");
			
			$working = "$job_directory/working_${job}";
			#open logfile in working directory
			$logfile = "$working/log.txt";
			if (! $logh = fopen($logfile,"w") ){
				die ("Cannot open logfie $logfile");
			}
   			fwrite ($logh,"Mail Log File opened at " . date('M d Y H:i') . "\n");
   			$run_started_at = time();
    		
    		
    		#subjerct, rate, message
    			
    
			 if (!list($sent,$elapsed) = runit($job,$working,$interval) ){
			 	end_it ($job, 0,  "Failed to run job $job. ",$starttime);
			 	continue; #try next job
			 } 
			 end_it($job,$sent,"Normal termination at $sent Sent.",$starttime);
			 fclose ($logh);
			 if (DELETE_QUEUE){ unlink ("$job_running"); 
			}
			else {echo "Job $job run but not unlinked (DELETE_QUEUE is false)". BRNL;}
			
			
			
	}


	exit;




##########################################


 function end_it($mypid,$sent,$reason,$starttime){
	global $logh; #log handle
	$elapsed = time() - $starttime;
	
	$endtimedate = date('Y-m-d H:s');
	$rate = intval(3600 * $sent/($elapsed + 1)); #prevent /0

 	echo "Batch email completed on batch $mypid at $endtimedate.\n: $reason\n";
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

}


function checkfiles($queue){
	$jfiles = [];
	$qfiles = scandir($queue);
	foreach ($qfiles as $f){
			if (! preg_match('/^(\d+)(-(\w+))?$/',$f,$matches) ){continue;}
			$jobid = $matches[1];
			$jstat = $matches[2];
			#echo "$f > $jobid, $jstat\n";
			
			if (!empty($jstat)){continue;}
			if (filemtime("$queue/$jobid") > time() ){ continue;}
			$jfiles[] = $jobid;
			
		}
		return $jfiles;
	}
		

 #####################################################

function runit($mypid,$working,$interval) {
	global $logh;
   $bmail_list = "$working/list.txt";
	
	$msg_file = "$working/message.txt";
	$mh = fopen("$msg_file", 'r');
	$subject = fgets($mh); #first line
	while (($line = fgets($mh)) !== false) {
		$message .= $line;
	}
	fclose ($mh);
	#detect if html or text
	if (stripos($message, '<html>') !== false){
		$html = true;
	} 
	else {$html = false;}
	
	$message = preg_replace('/\[image (\d+)\]/',"<img src='https://amdflames.org/assets/thumbs/$1.jpg' style='margin-right:auto;margin-left:auto;text-align:center;'>",$message);
	
	
    

  // Reset sent counter
    $sent = 0;
   

     #get address of newsletter
	$latest_pointer = trim(file_get_contents(SITEPATH . "/news/latest_pointer.txt") );
	
     #set up mail object
   $mail = new PHPMailer;
	$mail->isSendmail();
	$mail->setFrom('editor@amdflames.org','AMD Flames News');
	$mail->addCustomHeader('Errors-to','postmaster@amdflames.org');
	$mail->CharSet = 'UTF-8'; 

	#open reciepient file and loop over the contents
	$ml = fopen("$bmail_list",'r') or die ("Can't open list at $bmail_list");
	
	 $no_bulk_message = <<<EOT

   (We see you are not a subscriber to the weekly newsletter announcement.
   Please consider changing this setting on your profile so you can
   hear news about AMD and other AMD Flames. 
   Your personal login for the site is:
   ::link  )

EOT;


    while (($line = fgets($ml)) !== false) {
       	#create working copy of message
        $imessage = $message; #individual copy of message
        $isubject = $subject;
             
        #get the user vars from the file
        /*
         $mlarray = [
            $row['username'],
            $row['user_email'],
            $slink,
            $profile_age,
            $last_profile_date,
            $row['no_bulk'],
            $age_flag,
            $profile_validated
        ];
        */
        
          list(
			 $username,$user_email,$slink,
			$p_age,$last_profile,
			$no_bulk, $age_flag,$profile_validated
			) = explode("\t",$line);

		#creat personalized vars
            $logincode="s=$slink";
            $login_link = SITEURL . "/?s=$slink"; 
            
            $news_url = SITEURL . "/$latest_pointer" . "/?s=$slink";
            $news_link = "<a href='$news_url'>$news_url</a>";
            
            $profile_link = SITEURL . "/scripts/edit_profile.php/?s=$slink";
            
            
        #subsititute in imessage
        if ($no_bulk){$imessage = str_replace('::no_bulk',$no_bulk_message,$imessage);
            }
            else {
                $imessage = str_replace('::no_bulk','',$imessage);
            }

            $isubject = str_replace('::name',$username,$isubject);
            $imessage = str_replace('::name', $username, $imessage);
            $imessage = str_replace('::link', $login_link, $imessage);
            $imessage = str_replace('::plink', $preview_link, $imessage);
            $imessage = str_replace('::slink',$logincode,$imessage);
             $imessage = str_replace('::verify',$verify_link,$imessage);
             $imessage = str_replace('::uemail',$user_email,$imessage);
             $imessage = str_replace('::newslink',$news_link,$imessage);
             
            
		#set up  periodic reminders
            $profile_message = "
    Your profile was last updated on $last_profile.  
    To update: log in, then under your name at top right, select 'View/Edit Profile'.

        ";

            if ($age_flag > 0){
                $imessage = str_replace('::profile',$profile_message,$imessage);
            }
            else {
             $imessage = str_replace('::profile','',$imessage);
            }
		    $verify_link = <<<EOT
------------------------------------------------------------------
   Click to verify that this is your correct email address:

       https://amdflames.org/scripts/verify_email.php?s=$slink

   (If that's not right, please log in using the link below, and edit
   your profile with a new email address.)
-------------------------------------------------------------------
EOT;
			#if (!$html){
				$imessage = nl2br($imessage);
			#}
			
            $to = "\"$username\" <$user_email>";


        // Email this User


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
   			 echo "Mailer Error: " . $mail->ErrorInfo;
		} 
		
           $sent++; // Add to Sent Count

         if (file_exists($abort_file)) {
            end_it($mypid, $sent,"Aborted after $sent sent.",$starttime);
            exit;
        }

            // Wait the appropriate time
           sleep($interval);

      } // End of Loop
    $run_ended_at = time();
    $elapsed_time = $run_ended_at - $run_started_at;

    return array($sent,$elapsed_time);

}

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

