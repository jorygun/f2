<?php
namespace digitalmx\flames;

#ini_set('display_errors', 1);


//BEGIN START
	require_once '../init.php';

	#require others
#	require_once 'BulkMail.php';
	
	use digitalmx\flames\DocPage;
	use digitalmx as u;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames as f;
	use digitalmx\flames\BulkMail;

	

	$page = new DocPage;
	$title = "Bulk Mail Setup"; 
	echo $page->startHead($title, 3,['ajax']);
	
	echo <<<EOT
	<style type='text/css'>
.highgreen {background-color:#9C6;}
#in_bulk_queue li.error {color:red;}
#in_bulk_queue li.queued {color:green;}
#in_bulk_queue li.cancelled{color:blue;}
#in_bulk_queue li.running {color:orange;}

.red {color:red;}
.
</style>


EOT;



	echo $page->startBody($title ,2);

//END START
	

	$bulkmail = new BulkMail();
	
	
	$bulk_processor = PROJ_PATH . "/crons/send_bulk.php";
	
	

//END START
	global $G_member_status_set;
	


	$select_all_valid	=
	    "  status in (" . Defs::getMemberInSet() . ")
	AND email_status NOT LIKE 'X%'
	AND email_status NOT LIKE 'L%'
	
	";

	
	$news_latest = SITE_PATH . "/news/news_latest";
	
	$teasers = $news_latest . "/teasers.txt";
	$headlines	= $news_latest . "/headlines.txt";
	$publish_file = $news_latest . "/publish.txt";
	$preview = SITE_PATH . "/news/news_next/headlines.txt";
	$updates	= $news_latest . "/updates.txt";
	$calendar	= $news_latest . "/calendar.txt";
	$opportunities = $news_latest . "/opportunities.txt";
	$assets = $news_latest . "/assets.txt";


	$queue = REPO_PATH . '/var/bulk_queue'; #directory.  put jobs in here

	
	$comments = $news_latest . "/current_comments.txt";
   

   

	$publish_file = $news_latest . "/publish.txt";
	if (file_exists($publish_file)){
   	$edition_name = get_publish_data('title',$publish_file);
   	$pointerfile = SITE_PATH . "/news/latest_pointer.txt";

   	if (file_exists($pointerfile)){
   	 	$lastest_pointer = trim(file_get_contents(SITE_PATH . "/news/latest_pointer.txt") );
   	 } else {
   	 	$latest_pointer = '';
   	 	echo "Latest pointer not found.  Do not know where latest news is.";
   	 }
    }
    else {
    	echo "No publish file";
    	$edition_name = 'no name';
    	$latest_pointer = 'news_';
    }
  	$interval = 6;

	

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


// get counts for the  mail sets

	$sql = "SELECT count(*) FROM `members_f2` WHERE  $select_all_valid ;";
	$count_valid = $pdo->query($sql)->fetchColumn();
	

	$sql = "SELECT count(*) FROM `members_f2` 
	WHERE  $select_all_valid AND  no_bulk = FALSE;";
		$count_bulk  = $pdo->query($sql)->fetchColumn();

	$sql = "SELECT count(*) FROM `members_f2` 
	WHERE  $select_all_valid AND  no_bulk = TRUE;";
		$count_nobulk = $pdo->query($sql)->fetchColumn();



// Detect any existing jobs in the queue.
    $jobs_in_queue = $bulkmail->show_bulk_jobs();

	$time_all = runtime_msg($count_valid,$interval);
	$time_bulk = runtime_msg($count_bulk,$interval);
    $time_nobulk = runtime_msg($count_nobulk,$interval);


	$now = date('M d, Y H:i T');



echo <<<EOT

<p><b>Create the Email</b></p>
<p>You may use the following placeholders:<ul>
<li>::link::  User's personal link to log in
<li>::slink:: s=users_code
<li>::newslink:: to link to latest newsletter for this user
<li>::name::  User's name in db
<li>::profile_date:: Date user's profile last updated if > 1 year<br>
<li>::donor_date:: Date of last contribution
<li>::teaser::  Combination of highlights from current newsletter
<li>::preview:: Headlines in Preview (News_Next) edition
<li>::verify:: URL to verify email
<li>::uemail:: User's email address
<li>::no_bulk:: Notice to users not subscribing to weekly email
<li> [image nnn] replaced by image link to thumb file nnn.jpg
<li> ::edition:: Edition name
</ul>

<div id='in_bulk_queue'>$jobs_in_queue</div>

<p>
<button  onclick="getMessage('bm-std-plain');">News Ready (html)</button>
<button onclick="getMessage('bm-lost');">Periodic Lost</button>

</p>
<form  method="post" name='sendchoices'>


<br><hr><br>

<p>Subject <input type="text" name="subject" size="100" id='msubject'  ></p>


Message Body<br>
<textarea name="body" rows="15" cols="78" id='mcontent'>


</textarea>
<br><br>

<p>Send to: (times calculated for 600 msgs/hour) <br>
<input type="radio" name="sendto" value="admin" checked>Test (test_status = M) <br>
<p class="highgreen"><input type="radio" name="sendto" value="req" > Only those with no_bulk = FALSE  (OK for bulk mail)(count:$count_bulk; time: $time_bulk)</p>


<p>
<input type="radio" name="sendto" value="nobulk">Users set to No Bulk (count: $count_nobulk; time: $time_nobulk) </p>
<p>
<input type="radio" name="sendto" value="all">All Valid Emails (count: $count_valid; time: $time_all) </p>


<p><input type="radio" name="sendto" value="atag">Only the admin statuses below:
<br>Set admin statuses (single chars): <input type="text" name='admin_status'></p>
<p><input type="radio" name="sendto" value="news">News contributors</p>
<p><input type="radio" name="sendto" value="aged_out">Lost - Aged Out </p>
<p><input type="radio" name="sendto" value="not_lost">Newly Found</p>
<p><input type="radio" name="sendto" value="this">Only This email address: <input type='text' name="sendtothis" ></p>

<br>
<!-- <p>Send Rate: <input type=text name='sendrate' value='360' size='6'> messages per hour</p> -->
<p>Send first cron after: <input type='text' name='start' value='$now'> (e.g., '10pm PST', 'tomorrow noon','4/15/19 2pm EDT')(If no timezone specified, will be pacific time. )</p>


<input type="submit" name="go" value="Schedule" class="highgreen">
<input type="submit" name="go" value="Run Now">
<input type="submit" name="go" value="Setup Only">


</form>


</body>
</html>

EOT;

 }
############## POST #####################
else { #IS POST
 
	
	#ge job id and set paths
	$working = REPO_PATH . "/bulk_jobs";
	$queue = REPO_PATH . "/bulk_queue";
	
	
	#set up job as datecode, and make sure it doesn't already exist
	$job = false; $c = 0;
	while (! $job){
		$job = date("YmdHi");
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
			mkdir ("$job_dir",0777,1);
		}

	}
	 $bmail_list = "$job_dir/list.txt";
    $bmail_msg = "$job_dir/message.txt";
   
    
   

    $subject = $_POST['subject'];

	$teaser = build_teaser(SITE_PATH . "/news/news_latest" );

	$message = $_POST['body'];
	$message = str_replace('::teaser::',$teaser , $message);
	$message = preg_replace('/\t/',"    ",$message);




$starttimestamp = strtotime($_POST['start']);

$dt = new \DateTime(); #sets to MDT because server
$dt->setTimestamp($starttimestamp);
echo "start date set to " . $dt->format('M d H:i T') .';' ;
#$dt->setTimeZone(new DateTimeZone('America/Los_Angeles'));

echo " scheduled for ". $dt->format('M d H:i T') . BRNL;



// Write message file
$msg_file = <<<EOT
${_POST['subject']}
$message
EOT;

file_put_contents("$bmail_msg",$msg_file) or die ("Can't write message to $bmail_msg ");
echo "Message saved .\n";


#now build mail list

$sql = get_send_list($select_all_valid);
if (!$result = $pdo->query($sql) ){
		echo "No results from query for ${_POST['sendto']} \n"; 
		exit;
}
	
$row_count = $result->rowCount();
echo "$row_count records selected.<br>";

$ml_handle = fopen ("$bmail_list",'w') or die ("Failed to open $bmail_list");

 //Loop over rows
 foreach ($result as $row) {
	 // Assemble the list
		
			$profile_updated_age = days_ago($row['profile_updated']);
			$profile_updated_date = u\make_date($row['profile_updated'],'human','date');
		
	  #list($profile_age,$last_profile_date) = age($row['profile_updated']);
	 # list($p_val_age,$profile_validated) = age($row['profile_validated']);
	  $profile_validated_age = days_ago($row['profile_validated']);
	$profile_validated_date = u\make_date($row['profile_validated'],'human','date');

	  $age_flag =  ($profile_updated_age > 365)?1:0; #flag to print age warning
	  
	  $slink = $row['upw'].$row['user_id'];

        $mlarray = [
            $row['username'],
            $row['user_email'],
            $slink,
            "$profile_updated_age",
            "$profile_updated_date",
            "${row['no_bulk']}",
            "$age_flag",
            "$profile_validated_date"
        ];
       #recho ($mlarray,'ML array'); 
/*
Jack Smith	jsmithseamill@yahoo.co.uk	5132W12318	2632	Oct 1, 2009		1	1	no_date
*/
    fprintf ($ml_handle,"%s\n",implode("\t",$mlarray));
    
  } // End of Loop

    fclose ($ml_handle);
    echo "Mail list saved." ; 
    
	echo "Job $job: Emails will be sent every $interval seconds.  This will take " . intval($row_count * $interval/60) . " minutes to complete.<br>\n";



    if ($_POST['go'] == 'Run Now'){
        touch ("$queue/$job"); #mtime = now

        echo "Queued for now.  Starting bulk_mail_processor.<br>\n";
       shell_exec ("/usr/local/bin/php " . "$bulk_processor");
       
    }
    elseif ($_POST['go'] == 'Schedule') {
        touch ("$queue/$job",$starttimestamp);
        echo "Added $job to bulk_queue after " . date('M d, Y H:i T',$starttimestamp). BRNL;;
       
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

######### Get the send list ##########
function get_send_list ($select_all_valid) {


$field_list = "*";
$sql = "SELECT $field_list FROM `members_f2` ";

 	
    // if selected admin only, then use admin code = J; otherwise, exclude these.
 	if ($_POST['sendto'] == 'admin'){

 		$sendlimit = 1;
 		$sendornot = 'Send';
 		$sql .= "WHERE test_status='M' ;";
		echo "Sending  email to test addresses<br>";

 	}


	elseif ($_POST['sendto'] == 'all'){
			$sql .= "WHERE $select_all_valid ;";
	 		echo "Sending to all valid emails <br>";

	}
	elseif ($_POST['sendto'] == 'req'){
			$sql .= "WHERE $select_all_valid AND no_bulk = FALSE
			ORDER BY user_id;";
	 		echo "Sending only to those without No_Bulk flag <br>";
	}
	elseif ($_POST['sendto'] == 'nobulk'){
			$sql .= "WHERE $select_all_valid AND no_bulk = TRUE;";
	 		echo "Sending only to those WITH No_Bulk flag<br>";

	}
	elseif ($_POST['sendto'] == 'atag'){
	    $admin_status_arr = str_split($_POST['admin_status']); #array of chars
	    #print_r ($admin_status_arr);
	    $admin_status_string = '';
	    foreach ($admin_status_arr as $char){
	        $admin_status_string .= "'" . $char . "',";
	    }
	    $admin_status_string = substr($admin_status_string,0,-1); #drop last ,
		$sql .= "WHERE $select_all_valid  AND admin_status in ($admin_status_string) ;";
	 		echo "Sending only to those with admin tag in $admin_status_string <br>";

	}
		elseif ($_POST['sendto'] == 'news'){
		$sql .= "WHERE $select_all_valid  AND status like 'M_';";
	 		echo "Sending only to those with status = M_ <br>";

	}
		elseif ($_POST['sendto'] == 'aged_out'){
		$sql .= "WHERE status in (" . Defs::getMemberInSet() . ") AND email_status = 'LA' ;";

	 		echo "Sending only to those marked as Lost - Aged Out <br>";
	}
	    elseif ($_POST['sendto'] == 'not_lost'){
	    #choosee records validated yesterday
	    $sql .= "WHERE DATE (email_last_validated) = SUBDATE(CURDATE(),1)
	        AND previous_ems in ('A4','LA','LE','LB');" ;

	        echo "Sending only to newly not_lost Flames.<br>\n";

	}
		elseif ($_POST['sendto'] == 'this'){
			if (is_valid_email($_POST['sendtothis'])){
				$sql .= "WHERE user_email = '${_POST['sendtothis']}'";
			}
			else {die ("Invalid email address requested");}
			
		}
	else {die ("Unknown sendto parameter: ${_POST['sendto']}");}

   

    return $sql;
}

function get_publish_data($var,$publish_file){
	
    if (isset($$var)){
    	return $$var;
    } 
   return '';
}


function build_teaser($dir) {

	$teaser_files = array(
	'headlines',
	'updates',
	'calendar',
	'opportunities',
	'articles',
	'assets'
	);
	$teaser = '';
	
	foreach ($teaser_files as $tease){
		$tfile = $dir . "/tease_" . $tease . ".txt";
		if (file_exists($tfile)){
			$teaser .= file_get_contents($tfile);
		}
	}
	
	return $teaser;
	
}



