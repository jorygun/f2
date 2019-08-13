<?php
//BEGIN START
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;
	use digitalmx\MyPDO;
   if (f2_security_below(7)){exit;}
	$nav = new navBar(1);
	$navbar = $nav -> build_menu();
	

class BulkMailSetup 
{

 	
	
	require 'BulkMail.php';
	$bulkmail = new BulkMail();
	
	$project_path = '/usr/home/digitalm/Sites/flames';
	$bulk_processor = $project_path . "/crons/send_bulk.php";
	
	

//END START



	

	
	$news_latest = SITE_PATH . "/news/news_latest";
	
	$teasers = $news_latest . "/teasers.txt";
	$headlines	= $news_latest . "/headlines.txt";
	$publish_file = $news_latest . "/publish.txt";
	$preview = SITE_PATH . "/news/news_next/headlines.txt";
	$updates	= $news_latest . "/updates.txt";
	$calendar	= $news_latest . "/calendar.txt";
	$opportunities = $news_latest . "/opportunities.txt";
	$assets = $news_latest . "/assets.txt";


	$bulk_queue = $project_path . '/bulk_jobs/queue'; #directory.  put jobs in here

	
	$comments = $news_latest . "/current_comments.txt";
   

   

	$publish_file = $news_latest . "/publish.txt";
	if (file_exists($publish_file)){
   	$edition_name = get_publish_data('title',$publish_file);
   	 $lastest_pointer = trim(file_get_contents(SITE_PATH . "/news/latest_pointer.txt") );
    }
    else {
    	echo "No publish file";
    	$edition_name = 'no name';
    	$latest_pointer = 'news_';
    }
  	$interval = 6;

	

##########################
$standard_message = <<<EOT
Dear ::name,

The AMD FLAMEs News, $edition_name edition, is ready. 
Here's your personal link directly to the most current newsletter:
	::newslink

(To view any of the nearly 1000 back issues, 
log in and choose Dig In > Newsletter Index.)

::profile

IN THIS ISSUE

::teaser

We send these messages to all of our members that have not
asked to opt out. If you want to stop receiving these emails,
log in and change the setting in your profile,
or email the admin by replying to this email.

EOT;

$js_standard = ml_script($standard_message);
$subj_standard = "FLAME News $edition_name for ::name";
####################
$html_standard = <<<EOT
<html><body>
Dear ::name,
<table style='border:0;width:90%;'><tr><td >
<img src='https://amdflames.org/graphics/logo69x89.png' /></td><td>
The AMD FLAMEs News, $edition_name edition, is ready. 
Here's your personal link to the most current newsletter:
    ::newslink

(To view any of the nearly 1000 back issues, 
log in and choose Dig In > Newsletter Index.)

</td></tr><tr><td colspan='2'>::profile</td></tr>
<tr><td></td><td>
<b>IN THIS ISSUE</b>
<pre>
::teaser
</pre>

</td></tr><tr><td colspan='2'>
We send these messages to all of our members that have not asked to opt out. If you want to stop receiving these emails, log in and change the setting in your profile, or email the admin by replying to this email.
</td></tr></table>
EOT;
$js_html_standard = ml_script($html_standard);
$subj_standard = "FLAME News $edition_name for ::name";

######################
$periodic_lost_message = <<<EOT
::name,

I'm sending this email because you are listed on the AMD alumni site
amdflames.org as Lost.

It means we tried to contact you to see if your email address was
still right, the email didn't bounce, but we did not receive a reply.
Sometimes those emails don't get through or you may not have noticed.

This email was sent to: ::uemail

If you get this email and are NOT LOST, please just click on the link
below to verify your email.
   ::verify

If you would like to update your status or change to a different email,
just log in using this link, and edit your profile.
   ::link

I've listed below the headlines in the current newsletter.
If you weren't lost you would have received this in an email.
-------------------------------------------------------
::teaser

EOT;

$js_periodic_lost = ml_script($periodic_lost_message);
$subj_periodic_lost = "AMD Flames Site Thinks You're Lost.";

#################



/*
    This script now runs the mail sender in a separate
    background process, either putting a job in the bulk_queue,'
    where it will be run by cron,
    or by starting the run immedetialy.

    In either case, sending is via the script at
    bulk_mail_processor.php



*/

  #####################################################



echo <<<EOT
<html>
<head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Bulk Mail Setup</title>
<style type='text/css'>
.highgreen {background-color:#9C6;}
#in_bulk_queue li.error {color:red;}
#in_bulk_queue li.queued {color:green;}
#in_bulk_queue li.cancelled{color:blue;}
#in_bulk_queue li.running {color:orange;}

.red {color:red;}
.
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js">
</script>
<script src='/js/ajax.js'></script>
<script type='text/javascript'>

var standard_message = "$js_html_standard";
var standard_subject = "$subj_standard";

var pl_message = "$js_periodic_lost";
var pl_subject = "$subj_periodic_lost";

function set_message(type){
   
     if (type=='standard'){
        document.getElementById('message').value = standard_message;
        document.getElementById('msubject').value = standard_subject;
         document.sendchoices.sendto[0].checked=true;
    }
    else if (type=='periodic_lost'){
        document.getElementById('message').value = pl_message;
        document.getElementById('msubject').value = pl_subject;
         document.sendchoices.sendto[0].checked=true;
    }
    else {
        document.getElementById('message').value = '';
        document.getElementById('msubject').value = '';
         document.sendchoices.sendto[0].checked=true;
    }
}

</script>
</head>
<body>
$navbar



EOT;


if ($_SERVER['REQUEST_METHOD'] == 'GET'){
$pdo = MyPDO::instance();

	// set up the sql
	$select_all_valid	=
	"  status in (" . Defs::getMemberInSet() . ")
	AND email_status NOT LIKE 'X%'
	AND email_status NOT LIKE 'L%'
	
	";
// get counts for the  mail sets
	$sqlselect = "SELECT count(*) FROM `members_f2` WHERE ";
	
	$sqlwhere['valid'] =  "$select_all_valid ;";
	$sqlwhere['bulk'] = "$select_all_valid AND  no_bulk = FALSE;";
	$sqlwhere['nobulk'] = " $select_all_valid AND  no_bulk = TRUE;";
	$sqlwhere['admin'] = "$select_all_valid AND  test_status='M';";
	$sqlwhere['all'] = '';
	$sqlwhere['author'] = ;
	$sqlwhere['lost'] = ;
	$sqlwhere['test'] = ;
	
	

	foreach (['valid','bulk','nobulk','admin'] as $grp){
		$count[$grp] = $pdo->query($sqlselect . $sqlwhere[$grp]) ->fetchColumn();
	}
// Detect any existing jobs in the queue.
    $jobs_in_queue = $bulkmail->show_bulk_jobs();

	$time_all = runtime_msg($count_valid,$interval);
	$time_bulk = runtime_msg($count_bulk,$interval);
    $time_nobulk = runtime_msg($count_nobulk,$interval);


	$now = date('M d, Y H:i T');



echo <<<EOT

<p><b>Create the Email</b></p>
<p>You may use the following placeholders:<ul>
<li>::link  User's personal link to log in
<li>::slink s=users_code
<li>::newslink to link to latest newsletter for this user
<li>::name  User's name in db
<li>::profile_date Date user's profile last updated if > 1 year<br>
<li>::donor_date Date of last contribution
<li>::teaser  Combination of highlights from current newsletter
<li>::preview Headlines in Preview (News_Next) edition
<li>::verify URL to verify email
<li>::uemail User's email address
<li>::no_bulk Notice to users not subscribing to weekly email
<li> [image nnn] replaced by image link to thumb file nnn.jpg

</ul>

<div id='in_bulk_queue'>$jobs_in_queue</div>

<p>
<button  onclick="set_message('standard');">News Ready (html)</button>
<button onclick="set_message('not_lost');">Not Lost</button>
<button onclick="set_message('periodic_lost');">Periodic Lost</button>
<button  onclick="set_message('');">Blank</button>
</p>
<form  method="post" name='sendchoices'>


<br><hr><br>

<p>Subject <input type="text" name="subject" size="100" id='msubject'></p>


Message Body<br>
<textarea name="body" rows="15" cols="78" id='message'>


</textarea>
<br><br>

<p>Send to: (times calculated for 600 msgs/hour) <br>
<input type="radio" name="sendto" value="admin" checked>Test (test_status = M) <br>
<p class="highgreen"><input type="radio" name="sendto" value="bulk" > Only those with no_bulk = FALSE  (OK for bulk mail)(count:$count_bulk; time: $time_bulk)</p>


<p>
<input type="radio" name="sendto" value="nobulk">Users set to No Bulk (count: $count_nobulk; time: $time_nobulk) </p>
<p>
<input type="radio" name="sendto" value="all">All Valid Emails (count: $count_valid; time: $time_all) </p>


<p><input type="radio" name="sendto" value="atag">Only the admin statuses below:
<br>Set admin statuses (single chars): <input type="text" name='admin_status'></p>
<p><input type="radio" name="sendto" value="author">News contributors</p>
<p><input type="radio" name="sendto" value="lost">Lost - Aged Out </p>



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
 
	echo  "<HTML><head><title>Bulk Email Plan</title></head>";

	#ge job id and set paths
	$working = $project_path . "/bulk_jobs";
	$queue = $project_path . "/bulk_jobs/queue";
	
	
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
	$message = str_replace('::teaser',$teaser , $message);
	$message = preg_replace('/\t/',"    ",$message);




$starttimestamp = strtotime($_POST['start']);

$dt = new DateTime(); #sets to MDT because server
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
			$profile_updated_date = u\make_date('human','date',$row['profile_updated']);
		
	  #list($profile_age,$last_profile_date) = age($row['profile_updated']);
	 # list($p_val_age,$profile_validated) = age($row['profile_validated']);
	  $profile_validated_age = days_ago($row['profile_validated']);
	$profile_validated_date = u\make_date('human','date',$row['profile_validated']);

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
       shell_exec ("php " ."$bulk_processor");
       
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

$pdo = MyPDO::instance();
$field_list = "*";
$sqlselect = "SELECT $field_list FROM `members_f2` WHERE ";

	foreach (['valid','bulk','nobulk','admin'] as $grp){
		$count[$grp] = $pdo->query($sqlselect . $sqlwhere[$grp]) ->fetchColumn();
	}
 	
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
			if (u\is_valid_email($_POST['sendtothis'])){
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

function ml_script($text){
	    $js = str_replace("\n", "\\n", $text);
	    return $js;
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


