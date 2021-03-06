<?php
namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;


	use DigitalMx\Flames\Member;
	use DigitalMx\Flames\BulkMail;

if ($login->checkLogin(6)){
   $page_title = 'Bulk Mail Setup';
	$page_options=['ajax']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody(2);
}

//END START

$interval = 6; #seconds per email



	$bulkmail = new BulkMail();
	$member = $container['member'];

	$publish_file = FileDefs::latest_dir . "/publish.txt";



	$queue = FileDefs::bulk_queue; #directory.  put jobs in here



  	$rate = 3600/$interval; #messages per hour

##########################



/*
    This script now runs the mail sender in a separate
    background process, either putting a job in the bulk_queue,'
    where it will be run by cron,
    or by starting the run immedetialy.

    In either case, sending is via the script at
    bulk_mail_processor.php



*/

  #####################################################

if ($_SERVER['REQUEST_METHOD'] == 'GET'){


// Detect any existing jobs in the queue.
    $jobs_in_queue = $bulkmail->show_bulk_jobs();

// get counts for the  mail sets
// return [$active,$lost,$total,$bulk,$nobulk];

	$counts = array ();
	$counts = $member->getMemberCounts();
	if (!empty($counts)){
		// show time requeired for each category
		$time_all = runtime_msg($counts['active'],$interval);
		$time_bulk = runtime_msg($counts['bulk'],$interval);
		 $time_nobulk = runtime_msg($counts['nobulk'],$interval);

		 $time_aged = runtime_msg($counts['aged'],$interval);
	}

	$now = date('M d, Y H:i');

// get latest newsletter pointer
	$latest = $container['news']->getLatestIssue();
	$pointer = $latest['url'];


include ('../templates/bulk_form.php');
}
############## POST #####################
else { #IS POST; set up the job
#u\echor($_POST);

	$working = FileDefs::bulk_jobs;
	$queue = FileDefs::bulk_queue;



	$latest = $container['news']->getLatestIssue();
	$edition_name = $latest['title'] ?: $latest['date_published'];

 	echo "Edition name: $edition_name" . BRNL;


#set up job as datecode based on UTC and make sure it doesn't already exist
	$job = false; $c = 0;
	$assetv = $container['assetv'];
	while (! $job){
		$now_dt = new \DateTime();
		$now_dt->setTimestamp(time());

		$job = $now_dt->format("YmdHis");
		$job_dir = "$working/$job";
		if (file_exists($job_dir)){
			$job = false;
			sleep (2);
			++$c;
			if ($c>10){
				throw new Exception ("exceeded 10 attempts to create $job_dir");
			}
		}
		else {
			mkdir ("$job_dir");
			chmod ($job_dir,0775);
		}

	}
	 $bmail_list = "$job_dir/list.txt";
    $bmail_msg = "$job_dir/message.txt";


// get subject
    $subject = $_POST['subject']; // ?specchar??
    if (empty($subject)){echo "No Subject for Email.  Cancelled."; exit;}
    $subject = str_replace('::edition::',$edition_name,$subject);


// concatenate all the teaser file in news/latest
	$teaser = assemble_teaser();
// get the pointer
	$pointer = $_POST['pointer'] ?? '';

//build message
	$message = $_POST['body'];

	if (empty ($message)){throw new Exception ("No message ") ; }
	$message = str_replace('::teaser::',$teaser , $message);
	$message = str_replace('::edition::',$edition_name , $message);
	$message = str_replace('::pointer::',$pointer , $message);
	$message = preg_replace('/\t/',"    ",$message);

	// replacements in univeral message
	// replace ref to image with image
	$message = preg_replace_callback(
		'/\[image (\d+)\]/',
		function ($m) use ($assetv) {
			$th= $assetv->getThumb($m[1],'small');
			if (strpos($th,'**') !== false) {
				return '';
			}
			$imageurl = SITE_URL . $th;
			return "<img src='$imageurl' />";
		}, $message);


	$start_dt = new \DateTime(); #sets to PDT because server
	if (! $starttimestamp = strtotime($_POST['start']) ){
		throw new Exception ("illegal start time: " . $_POST['start']);
	}
	$start_dt->setTimestamp($starttimestamp);

	$start_dt->setTimeZone(new \DateTimeZone('America/Los_Angeles'));
	$jstarttime = $start_dt->format('M d H:i T');



// Write message file
$msg_file = <<<EOT
$subject
$message
EOT;

file_put_contents($bmail_msg,$msg_file) or die ("Can't write message to $bmail_msg ");
echo "Message saved:\n "; #<pre>$msg_file</pre> \n";
file_put_contents("$job_dir/pointer.txt",$pointer);


#now build mail list
$tag = $_POST['tag'] ?? '';
$list = $member->getSendList($_POST['sendto'],$tag);


if (!$list ){
		echo "No results from query for ${_POST['sendto']} \n";
		exit;
}

$row_count = count($list);
echo "$row_count records selected.<br>";

$ml_handle = fopen ("$bmail_list",'w') or die ("Failed to open $bmail_list");

 //Loop over rows
 foreach ($list as $row) {
	 // Assemble the list
    fprintf ($ml_handle,"%s\n",implode("\t",$row));
   /*
    $fields =
		'username, user_email, CONCAT(upw,user_id) as slink,profile_updated,no_bulk
	*/
  } // End of Loop

    fclose ($ml_handle);
    echo "Mail list saved." ;

	echo "Job $job: Emails will be sent every $interval seconds.  This will take " . intval($row_count * $interval/60) . " minutes to complete.<br>\n";

## wrap it up


    if ($_POST['go'] == 'Run Now'){
        touch ("$queue/$job"); #mtime = now

        echo "Queued for now.  Starting bulk_mail_processor.<br>\n";
        $phploc = shell_exec('which php');
        $cmd = "$phploc " . FileDefs::bulk_processor;

       # echo shell_exec($cmd);
       // for some reason shell exec is not working.
       require FileDefs::bulk_processor;



    }
    elseif ($_POST['go'] == 'Schedule') {
        touch ("$queue/$job",$starttimestamp);
        echo "Added $job to bulk_queue after $jstarttime" .  BRNL;;

    }
    elseif ($_POST['go'] == 'Setup Only') {
        echo "Job $job files created  but not added to queue.";
    }
    else {
        echo "Unknown run parameter ${_POST['go']}.";

    }

}



 function runtime_msg($count,$interval){
        $runtime = round($count * $interval / 60);
        $runtimeh = round($runtime/60,2);
        $msg = "$runtime mins; ($runtimeh hours).";
        return $msg;
}

 function assemble_teaser() {
		$teaser ='';

		$teaser_files = array(
			FileDefs::latest_dir . FileDefs::tease_calendar,
			FileDefs::latest_dir . FileDefs::tease_news,
			FileDefs::latest_dir . FileDefs::tease_opps,
			FileDefs::latest_dir . FileDefs::tease_status,
		);

		foreach ($teaser_files as $tfile){

			if (file_exists($tfile)){
				$teaser .= file_get_contents($tfile);
			}

		}
		return $teaser;
	}
