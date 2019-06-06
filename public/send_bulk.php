#!/usr/local/bin/php -ddisplay_errors=E_ALL
<?php

//BEGIN START
    #set to true or false to delete the bulk_queue after running.
    DELETE_QUEUE = FALSE;

    $sitepath = '/usr/home/digitalm/public_html/amdflames.org';
	$siteurl = 'https://amdflames.org';
	$G_stale_data_limit = 360;

	$starttime = time();
    $startdate = date('Y-m-d H:i',$starttime);
    $batchrun = date('ymd_Hi',$starttime);


    $abort_file = "$sitepath/abort_mailing";
    $logdir = "$sitepath/logs/bulk_mail_logs";

    $queue = "$sitepath/bulk_queue_test";

    set_time_limit(86400);
	if (file_exists($abort_file)) {unlink($abort_file);}

    $interval = 10; #default delay between emails

    pcntl_signal(SIGTERM, "sig_handler");
    pcntl_signal(SIGHUP,  "sig_handler");
    pcntl_signal(SIGUSR1, "sig_handler");
    pcntl_signal(SIGINT, "sig_handler");

	$logfile = "$logdir/log-${batchrun}.txt";

        $logh = @fopen($logfile,"w");
        fwrite ($logh,"Mail Log File opened at $startdate.\n");


	echo "Starting bulk mail run on pid $mypid. \n";
	$admin_mail =  "Starting bulk mail run on pid $mypid. \n";

//END START

// set up signals

ignore_user_abort(false);
declare(ticks=10);

##########################################
function sig_handler($signo) {
    global $sent;
  switch ($signo) {
    case SIGTERM:
        $r = "SIG: handle shutdown tasks\n";
      break;
    case SIGHUP:
      $r = "SIG: handle restart tasks\n";
      break;
    case SIGUSR1:
      $r =  "SIG: Caught SIGUSR1\n";
      break;
    case SIGINT:
      $r =  "SIG: Caught CTRL+C\n";
      break;
    default:
      $r =  "SIG: handle all other signals\n";
      break;
  }
  end_it($sent,$r);
 wrapup();
}


 function end_it($sent,$reason){
    global $logh, $logdir, $logfile,$starttime,$mypid,$admin_mail;
    $endtime = time();
	$elapsed = $endtime - $starttime +1;
	$endtimedate = date('Y-m-d H:s',$endtime);
	$rate = intval(3600 * $sent/$elapsed);

 	echo "Batch email completed on batch $mypid at $endtimedate.\n: $reason\n";


	$admin_msg = "
	------------------------------------------------
	Batch email completed on job $mypid at $endtimedate.\n
	$reason\n
	$sent in elapsed time $elapsed seconds. ($rate/hour).\n
	Log at https://amdflames.org/logs/bulk_mail_logs/log-last.txt\n
	--------------------------------------------------
	\r\n";

    fprintf ($logh, "$admin_msg\n");
    $admin_mail .= $admin_msg;
}

function wrapup(){
    global $logh,$logdir,$admin_mail;
 	fclose ($logh);
 	$loglast = "$logdir/log-last.txt";
 	unlink ($loglast);
    link ($logfile,$loglast) or die ("Can't link loglast"); #create pointer to latest log file
    mail('admin@amdflames.org','Bulk Mail Completed',$admin_mail) or die ("Can't send email to admin");
  }




 #####################################################

#Check for anything in the bulk queue

#    $starttime = time();
#    $startdate = date('Y-m-d H:i',$starttime);

#$clog = '/dev/null/';

echo "Starting run_queue at $startdate.\n";
if (file_exists($queue)){
	echo "Found queue\n";
	$q = fopen($queue,'r') or die ("Can't read queue");
	while (!feof($q)){
	    $jobs[] = fgets($q);
	}
	fclose ($q);

	for ($jobs as $mypid){
		echo "Starting job $mypid .\n";
		runit($mypid);
	}

	if (DELETE_QUEUE){
	    unlink ($queue);
	     echo "bulk_queue deleted!\n";
	}
	else {
     echo "bulk_queue NOT deleted!\n";
    }

}
else {echo " No bulk_queue";}

wrapup();

exit;


function runit($mypid) {
// load up parameters for this job.
    $working = "/tmp/bmail/working_$mypid";

    $bmail_params = "$working/params.txt";
    $bmail_list = "$working/list.txt";

    global $G_stale_data_limit,$interval,$logh;

     fwrite($logh,"\n------------------------------------\n");
     fwrite ($logh,"Starting Job pid $mypid");

    $mp = fopen("$bmail_params",'r') or die ("Can't open $bmail_params file");
    /*
    From: $message_from
    Rate: $P_sendrate
    Subj: $P_subject
    Message:
    $message
    */

 	while (($line = fgets($mp)) !== false) {
        list($var,$val)=explode(':',$line,2);
        switch ($var) {
            case 'From':
                $P_from = $val;
                break;
            case 'Rate':
                $P_sendrate = $val;
                break;
            case 'Subj':
                $P_subj = $val;
                break;
            case 'Message':
                $message = '';
                 while (($line = fgets($mp)) !== false) {
                    $message .= $line;
                }
            }


    }
    fclose ($mp);

// echo <<<EOT
//
// From: $P_from
// Rate: $P_sendrate
// Subj: $P_subj
// Msg:
//     $P_body
//
// EOT;


  // Reset sent counter
    $sent = 0;
    $message_header = "From: $P_from";

// set interval to  something other than default  (messages/hour)
#sendrate is msgs per hour.  So calculate sleep interval for this.
 if (is_numeric($P_sendrate) && $P_sendrate>0 && $P_sendrate <= 400){
    $interval = 3600/$P_sendrate;

    } #overrides value set at beginning of script


 //Loop over rows
     $ml = fopen("$bmail_list",'r') or die ("Can't open list at $bmail_list");

    while (($line = fgets($ml)) !== false) {
        /*
         $mlarray = [
                $row[username],
                $row[user_email],
                $slink,
                $p_age,
                $last_profile_date,
                $row[last_donor_date],
                $row[no_bulk]
            ];
            */


        // Assemble the email
        # echo "Retrieved $line\n";
             $imessage = $message; #individual copy of message
            list($username,$user_email,$slink,$p_age,$last_profile,$last_donor,$no_bulk) = explode("\t",$line);


            $subject = str_replace('::name',$username,$P_subj);
            $imessage = str_replace('::name', $username, $imessage);

            $link = "$siteurl?s=$slink";
            $imessage = str_replace('::link', $link, $imessage);

            $imessage = str_replace('::donor_date',$last_donor,$imessage);
            $profile_message = "
    Your profile was last updated on $last_profile.
    If your life has changed, you might consider updating it.

        ";
            if ($p_age>$G_stale_data_limit){
                $imessage = str_replace('::profile',$profile_message,$imessage);
            }
            else {
             $imessage = str_replace('::profile','',$imessage);
            }
            $to = "\"$username\" <$user_email>";

        // Email this User


            mail($to, $subject, $imessage, $message_header,"-fpostmaster@amdflames.org");


           $sent++; // Add to Sent Count

            fwrite($logh,"$sent: $to\n");


         if (file_exists($abort_file)) {end_it($sent,"Aborted after $sent sent.");}

            // Wait the appropriate time
           sleep($interval);

      } // End of Loop

 end_it($sent,"Normal termination at $sent Sent.");

}


#######################ZEND MAIL USAGE ##############
/*
    require_once ‘Zend/Mail.php’;
    $mail=new Zend_Mail();
    require_once ‘Zend/Validate/EmailAddress.php’;
    $validator=new Zend_Validate_EmailAddress();

    if($validator->isValid($_POST[’email’]))
    {
    // text only version, so strip the tags
    $mail->setBodyText(strip_tags($_POST[‘message’]));
    // html version
    $mail->setBodyHtml($_POST[‘message’]);

    $mail->setFrom($_POST[’email’],’sender name’]);
    $mail->addTo(‘to@domain.com’,”receiver’s name”);
    $mail->setSubject(‘Subject goe heere’);
    // send email
    $mail->send();
    }
    else
    {
    foreach($validator->getMessages() as $errorMessage)
    {
    echo “$errorMessage”;
    }
    }

    The errors tell the user exactly what is wrong with the email if it’s not valid. You can strip tags you don’t want like javascript tags and/or links with the strip_tags function.

    $mail = new Zend_Mail();
$mail->setBodyText('My Nice Test Text');
$mail->setBodyHtml('My Nice <b>Test</b> Text');
$mail->setFrom('somebody@example.com', 'Some Sender');
$mail->addTo('somebody_else@example.com', 'Some Recipient');
$mail->setSubject('TestSubject');
$mail->send();
*/

#######################################################


?>
