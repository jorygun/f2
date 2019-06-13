<?php
//BEGIN START

	require_once 'init.php';
    if (f2_security_below(7)){exit;}
	$nav = new navBar(1);
	$navbar = $nav -> build_menu();
	$pdo = MyPDO::instance();
	
	require 'BulkMail.class.php';
	$bulkmail = new BulkMail();
	

//END START
	global $G_member_status_set;
	global $G_stale_data_limit;


	$select_all_valid	=
	    "  status in ($G_member_status_set)
	AND email_status NOT LIKE 'X%'
	AND email_status NOT LIKE 'L%'
	
	";

	$select_bulk_only	=	$select_all_valid . " AND no_bulk = FALSE ";
	$select_nobulk_only	=	$select_all_valid . " AND no_bulk = TRUE ";

	$news_latest = SITEPATH . "/news/news_latest";
	
	$teasers = $news_latest . "/teasers.txt";
	$headlines	= $news_latest . "/headlines.txt";
	$publish_file = $news_latest . "/publish.txt";
	$preview = SITEPATH . "/news/news_next/headlines.txt";
	$updates	= $news_latest . "/updates.txt";
	$calendar	= $news_latest . "/calendar.txt";
	$opportunities = $news_latest . "/opportunities.txt";
	$assets = $news_latest . "/assets.txt";
	$bulk_processor = HOMEPATH . "/crons/bulk_mail.php";
	

	$bulk_queue = SITEPATH . "/bulk_queue"; #directory.  put jobs in here

	$mypid = time(); #unix time
	$comments = $news_latest . "/current_comments.txt";

    $working = HOMEPATH . "/bmail/working_$mypid";


    $bmail_list = "$working/list.txt";
    $bmail_msg = "$working/message.txt";
    

	$publish_file = $news_latest . "/publish.txt";
	if (file_exists($publish_file)){
   	$edition_name = get_publish_data('title',$publish_file);
   	 $lastest_pointer = trim(file_get_contents(SITEPATH . "/news/latest_pointer.txt") );
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
Here's your personal link directly to the newsletter:
	::newslink
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
Here's your personal link directly to the newsletter:
    ::newslink
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


header( 'Content-type: text/html; charset=utf-8' );
echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
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

// get counts for the  mail sets

	$sql = "SELECT count(*) FROM `members_f2` WHERE  $select_all_valid ;";
	$count_valid = $pdo->query($sql)->fetchColumn();
	

	$sql = "SELECT count(*) FROM `members_f2` WHERE  $select_bulk_only;";
		$count_bulk  = $pdo->query($sql)->fetchColumn();

	$sql = "SELECT count(*) FROM `members_f2` WHERE  $select_nobulk_only;";
		$count_nobulk = $pdo->query($sql)->fetchColumn();


	$sql = "SELECT count(*) FROM `members_f2`
WHERE status in ($G_member_status_set) AND email_status = 'LA';";
		$count_aged =  $pdo->query($sql)->fetchColumn();

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
<input type=hidden name='a' value='$abort_file'>

<br><hr><br>

<p>Subject <input type="text" name="subject" size="100" id='msubject'></p>


Message Body<br>
<textarea name="body" rows="15" cols="78" id='message'>


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
<p><input type="radio" name="sendto" value="aged_out">Lost - Aged Out (count: $count_aged)</p>
<p><input type="radio" name="sendto" value="not_lost">Newly Found</p>
<p><input type="radio" name="sendto" value="this">Only This email address: <input type='text' name="sendtothis" ></p>

<br>
<!-- <p>Send Rate: <input type=text name='sendrate' value='360' size='6'> messages per hour</p> -->
<p>Send first cron after: <input type='text' name='start' value='$now'> (e.g., '10pm PST', 'tomorrow noon','4/15/19 2pm EDT')(If no timezone specified, will be pacific time. )</p>


<input type="submit" name="go" value="Schedule" class="highgreen">
<input type="submit" name="go" value="Run Now">
<input type="submit" name="go" value="Setup Only">


</form>
<p>Note: to abort a mailing in progress, create file 'abort_mailing' at site root. 
<button type='button' onClick='window.open("/scripts/abort_bulk_mail.php");'>
Create Abort File</button>
</p>
</body>
</html>

EOT;

 }

else { #IS POST
 
	echo  "<HTML><head><title>Bulk Email Plan</title></head>";

#first build message

    if (!is_dir ("$working")){mkdir ("$working",0777,1) || die ("Failed to mkdir $working");}

    $subject = $_POST['subject'];

	$teaser = $teaser_heads = $teaser_updates = $teaser_calendar = $teaser_opportunities = $teaser_comments = $teaser_assets ='';
 	if (file_exists($headlines)){$teaser_heads =
			 file_get_contents($headlines) ;
			 $tease = 1;
	}

	if (file_exists($preview)){$preview_heads =
			 file_get_contents($preview) ;
			 $preview_mode = 1;

	}
	if (file_exists($updates)){$teaser_updates =
			 file_get_contents($updates) ;
			  $tease = 1;
	}
	if (file_exists($calendar)){$teaser_calendar =
			 file_get_contents($calendar) ;
			  $tease = 1;
	}
	if (file_exists($opportunities)){$teaser_opportunities =
			 file_get_contents($opportunities) ;
			  $tease = 1;
	}

	if (file_exists($comments)){$teaser_comments =
			 file_get_contents($comments) ;
			 $tease = 1;
	}
	if (file_exists($assets)){$teaser_assets =
			 file_get_contents($assets) ;
			 $tease = 1;
	}
	if ($tease){
        $test = '';
		$teaser .= $teaser_heads;
		$teaser .= $teaser_calendar;
		$teaser .= $teaser_updates;
		$teaser .= $teaser_opportunities;
		$teaser .= $teaser_comments;
		$teaser .= $teaser_assets;
	 }


	$message = $_POST['body'];
		$message = str_replace('::teaser', $teaser, $message);

        $message = str_replace('::preview',$preview_heads,$message);
		$message = preg_replace('/\t/',"    ",$message);



$starttime = $_POST['start'];
#deal with time zone
$notz = false;
if (! preg_match('/ \w\w\w\s*$/',$starttime) ){
	$no_tz = true;
}

$dt = new DateTime($starttime); #sets to MDT because user.ini set it.
echo "start date set to " . $dt->format('M d H:i T') .';' ;
$dt->setTimeZone(new DateTimeZone('America/Los_Angeles'));

$schedtime =  $dt->format('M d H:i T');
echo " scheduled for $schedtime" . BRNL;

$starttimestamp = (empty($_POST['start']))?time() : $dt->format('U');


// Write message file
$msg_file = <<<EOT
${_POST['subject']}
$message
EOT;

file_put_contents("$bmail_msg",$msg_file) or die ("Can't write message to $bmail_msg ");
echo "Message written to $bmail_msg<br>\n";


#now build mail list

$sql = get_send_list();
if (!$result = $pdo->query($sql) ){
		echo "No results from query for ${_POST['sendto']} \n"; 
		exit;
}
	
$row_count = $result->rowCount();
echo "$row_count records selected.<br>";

$ml = fopen ("$bmail_list",'w') or die ("Failed to open $bmail_list");

 //Loop over rows
 foreach ($result as $row) {
	 // Assemble the list

	  list($profile_age,$last_profile_date) = age($row['profile_updated']);
	  list($p_val_age,$profile_validated) = age($row['profile_validated']);

	  $age_flag =  ($profile_age > $G_stale_data_limit)?1:0; #flag to print age warning
	  $slink = $row['upw'].$row['user_id'];

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
/*
Jack Smith	jsmithseamill@yahoo.co.uk	5132W12318	2632	Oct 1, 2009		1	1	no_date
*/
    fprintf ($ml,"%s\n",implode("\t",$mlarray));
    
  } // End of Loop

    fclose ($ml);
	echo "Emails will be sent every $interval seconds.  This will take " . intval($row_count * $interval/60) . " minutes to complete.<br>\n";



    if ($_POST['go'] == 'Run Now'){
        touch ("$bulk_queue/$mypid"); #mtime = now

        echo "Queued for now.  Starting bulk_mail_processor.<br>\n";
       shell_exec ("php " ."$bulk_processor");
       
    }
    elseif ($_POST['go'] == 'Schedule') {
        touch ("$bulk_queue/$mypid",$starttimestamp);
        echo "Added $mypid to bulk_queue after " . date('M d, Y H:i T',$starttimestamp). BRNL;;
       
    }
    elseif ($_POST['go'] == 'Setup Only') {
        echo "Job $mypid created in bmail but not added to queue.";
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
function get_send_list () {
global $select_all_valid, $select_bulk_only, $G_member_status_set;
$pdo = MyPDO::instance();
$field_list = "*";
$sql = "SELECT $field_list FROM `members_f2` ";

 	if ($_POST['sendto'] == 'limited'){
        #doesn't send; but picks some random names and shows result
  			if (!$sendlimit){$sendlimit = 5;}
  		$sql .= "WHERE $select_all_valid ORDER BY RAND() LIMIT ${_POST['testnumber']};";
		echo "Simulating test send to limited number of  valid emails ($sendlimit)<br>";

	}
    // if selected admin only, then use admin code = J; otherwise, exclude these.
 	elseif ($_POST['sendto'] == 'admin'){

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
			$sql .= "WHERE $select_bulk_only ORDER BY user_id;";
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
		$sql .= "WHERE status in ($G_member_status_set) AND email_status = 'LA' ;";

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

function ml_script($text){
	    $js = str_replace("\n", "\\n", $text);
	    return $js;
	}
	
?>

