#!/usr/local/bin/php
<?php

/*
    Script to review user records and monitor validation of
    email addresses.  Sends out verification emails and
    adjusts email status, according to a schedule defined below.

    Script also runs the recent.php update to show comments on older articles.

*/

//BEGIN START
	require_once '/usr/home/digitalm/Sites/flames/live/config/boot.php';
	include_once '/usr/home/digitalm/public_html/amdflames.org/scripts/email_status_messaging.php';


//END START


$pdo = MyPDO::instance();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


$mode = '';
//
// SET MODE TO REAL OR TEST //
/// can do by uncommenting line below, or by
// calling the script with GET ?mode=test
// or by running from cli with sweep.php test
//

#$mode = 'Test';
$modes = array('Test','Real');
if (empty($mode)){
   if(isset($argv) && sizeof($argv)==2){$mode = $argv[1];}
   elseif(isset($_GET['mode'])) {$mode = $_GET['mode'] ;}
    else{$mode = 'Real';}
}
#echo "Mode $mode<br>\n";
$testmode=($mode=='Test')?true:false;

if (! in_array($mode,$modes)){die ("Sweeps did not receive a valid mode: $mode");}

/* can restrict the run to a single ID by setting onlyid.
    * this is for testing operation by a real run on one test user
*/
$onlysql = $onlyid = '';
if (isset($_GET) &&  isset($_GET['onlyid']) && $_GET['onlyid'] > 0){
    $onlyid = trim($_GET['onlyid']);
    $onlysql = " AND id = '$onlyid' ";
}

$sleep_interval = 10;
// delay in seconds between emails.
set_time_limit(300); // 30sec. is default 0 is none;

global $G_member_status_set;

$sql_now = date('Y-m-d');
$timestamp = date('Ymd_his');
$english_now = date ("M j, h:i a");
$now_obj = date_create();

#echo "Running Sweeps at $english_now\n";

$sweep_log = SITE_PATH . "/logs/sweep_logs/${mode}_${timestamp}.txt";
echo "Logging to $sweep_log" . BRNL;


$log_record =  sprintf ("Sweep run at %s\n\n",Date ("Y-m-d H:i"));
$log_format1 = "(%s) %-25s  %-20s (id %4d) status %2s %4d days. Change to %s.\n";
$log_format2 = "(%s) %-25s  %-20s (id %4d) Ems %2s. Change to %s. \n\tLogin %10s; Em-valid %10s; Prof-valid %10s.\n";

// Time limits in days for each email status before something happens
// used to control change to next status by sweeps and bounce processor
	$ems_limits = array(

	'B1'	=>	7,
	'B2'	=>	7,

	'A1'	=>	7,
	'A2'	=>	7,
	'A3'	=>	7,
	'A4'	=>	7,
	'E1'	=>	3,
	'E2'	=>	7,
	'N1'	=>	3,
	'N2'	=>	3,
	'D'     =>  30
);

// Sequence for email status codes.
// XX means change both email and user status codes (i.e., delete)
//
// LL means change both email and user status codes (Lost)

$next_ems_codes = array(
	'B1'	=>	'B2',
	'B2'	=>	'LB', #stays

	'A1'	=>	'A2',
	'A2'	=>	'A3',
	'A3'	=>	'A4',
	'A4'	=>	'LA', #stays
	'E1'	=>	'E2',
	'E2'	=>	'LE',   #stays here for admin action
	'N1'	=>	'N2',
	'N2'	=>	'LS',  #stays
	'D'     =>  'LA' #not used
);

$ems_limits_test = array(

	'B1'	=>	2,
	'B2'	=>	2,
	'A1'	=>	1,
	'A2'	=>	1,
	'A3'	=>	1,
	'A4'	=>	1,
	'E1'	=>	2,
	'E2'	=>	2,
	'N1'	=>	1,
	'N2'	=>	1,
	'D'     =>  1
);

$incidents = 0;  #actual number of incidents to report


// set this to force all times to 1 day for testing purposes.
// comment out when running for real
if ($mode == 'Test'){$ems_limits = $ems_limits_test;}

####################



//remove x-ed out records
$testmode && print "testing x'd out records";

$sql = "SELECT * FROM members_f2 WHERE
    status like 'X%' $onlysql ;";
$log_record .=  "\n#### Testing x-ed out records\n";
    if ($mode == 'Test' ) {$log_record .= " $sql \n";}

if ( $result = $pdo->query($sql) ){
		$incident_count = $result->rowCount();
        $log_record .=  " ($mode)    Deleting $incident_count records\n";
        $sql = "DELETE FROM `members_f2` WHERE status like 'X%' $onlysql;";
   if ($mode == 'Real' ){
        $result = $pdo->query($sql);
       $incidents += $incident_count;
    }

}

// test for each of the transitional status ages
#sequence is critical! or you'll catch one you just changed.
$ems_test_sequence = array ('N2','N1','E2','E1','A4','A3','A2','A1','B2','B1','D');
$main_test = "SELECT * FROM `members_f2` WHERE
	status in ($G_member_status_set)
	AND email_status = ?
	AND email_status_time < ?
	 $onlysql
	 ;";
$main_stmt = $pdo->prepare($main_test);

$log_record .= "Preparing main:\n$main_test.\n";

foreach ($ems_test_sequence as $this_ems){
	## MAIN TEST
	$next_ems = $next_ems_codes[$this_ems];

	#$testmode && $log_record .= " $sql \n";
	$testmode && print "testing $this_ems age<br>\n";

	$this_ems_limit = $ems_limits[$this_ems];
   $limit_date = date('Y-m-d', strtotime( "- $this_ems_limit days" ));

    $log_record .=  "\n---- Testing $this_ems more than $this_ems_limit days ($limit_date)----\n";
    $testmode && print "Selecting $this_ems time before $limit_date<br>\n";
	$main_stmt -> execute([$this_ems,$limit_date]) ;

    $tags=0;
	foreach($main_stmt as $row){
			++$tags;
			 $id = $row['id'];
			 $username=$row['username'];
			 $log_record .=  "retrieving user id $id $username<br>\n";
				 list($ems_age,$ems_date) = age($row['email_status_time']);
				 if (
							($subject = update_email_status($id,$next_ems,$mode))!== false) {
							$log_record .= "\t($mode email: $subject.)\n";
				 }
				 else{
					  echo "Failed attempt to update email status on id $id to status $next_ems on ${row['user_email']}.";
				 }
				 $logline =  sprintf ( $log_format1, $mode, $row['user_email'],$row['username'],$id,$this_ems,$ems_age,$next_ems);
				 $log_record .=  $logline;

	  }
	  if ($mode == 'Real'){$incidents += $tags;}
		$log_record .= "    $tags found.\n";
}


// test for new members verified but not welcomed
#echo "Line " . __LINE__ . "\n";

$log_record .= "\n#### Testing new users validated but not welcomed\n";
$sql = "SELECT * FROM `members_f2` WHERE
			email_status = 'Y'
			and status = 'N' ;
		";
    if ($mode == 'Test' ) {$log_record .= " $sql \n";}
	 $result = $pdo->query($sql);
	$rows_found = $result->rowCount();
	$testmode && print "Users to welcome: $rows_found" . BRNL;
    if ($rows_found > 0){
		foreach($result as $row){
			$id = $row['id'];

			list($ems_age,$ems_date) = age($row['email_status_time']);

			$log_record .=  sprintf ( $log_format1, $mode, $row['user_email'],$row['username'],$id,$this_ems,$ems_age,'Send Welcome');

			$msg = "Sweeps has encountered members with validated email, but status = N.
			This user has not received welcome message.\n\n
			${row['username']}:
			    " . SITEURL . "/scripts/update_member.php?id=$id
			";


		}
		if ($mode == 'Real'){
		 mail('admin@amdflames.org',"User needs welcome - ${row['username']}", $msg,"From: admin@amdflames.org\n\r");
		 $incidents += $rows_found;
		 }
	}

#echo "Line " . __LINE__ . "\n";

// Finally test last contact a long long time ago

    $limit = 365; #days without activity
    $limit2=$limit*2;


	$log_record .=  "\n#### Testing last activity more than $limit days\n";
	$sql = "SELECT * FROM `members_f2` WHERE
		STATUS in ($G_member_status_set)
		AND email_status in ('Q','Y')
		AND
			(last_login IS NULL
			OR
			last_login < NOW() - INTERVAL $limit DAY
			)
		AND
            (
                email_last_validated IS NULL
                OR
                email_last_validated < NOW() - INTERVAL $limit2 DAY
            )

		AND
			(
				profile_validated IS NULL or
				profile_validated  <  NOW() - INTERVAL $limit2 DAY
			)
         $onlysql

			ORDER BY profile_validated
			LIMIT 2
			;";
			#echo $sql,"<br>";
    if ($mode == 'Test' ) {$log_record .= " $sql \n";}
	if ( $result = $pdo->query($sql) ){
		$rows_found = $result->rowCount();
		$testmode && print "Users aged out: $rows_found\n";
        $incidents += $rows_found;

		foreach ($result as $row){
			$id = $row['id'];

			$this_email = $row['user_email'];
			$next_ems = 'A1';

			list($this_age,$this_date) = age($row['email_status_time']);


				if (
				    ($subject = update_email_status($id,$next_ems,$mode)) !== false){
				    $log_record .= "\t($mode email sent $subject.)\n";
				    !$testmode && sleep ($sleep_interval);
				}
				else{
				    echo "failed attempt to update email status on id $id to status $next_ems for email $this_email.\n";
				}

			$last_log_date = substr($row['last_login'],0,10);
			/*
			$log_format2 = "(%s) %-25s  %-20s (id %4d) Ems %2s. Change to %s. \n\tLogin %10s; Em-valid %10s; Prof-valid %10s.\n";
			*/
			$log_record .=  sprintf ( $log_format2, $mode, $row['user_email'],$row['username'],$id,$row['email_status'],$next_ems,$last_log_date,$row['email_last_validated'],
			$row['profile_validated']);
		}
	}
#echo "Line " . __LINE__ . "\n";

$log_record .= "---------------\nRunning Recent Article Update\n";
include 'recent.php';
#echo "Line " . __LINE__ . "\n";

$log_record .= "---------------\nRunning Recent Assets Update\n";
include 'recent_assets.php';

#echo "Line " . __LINE__ . "\n";

$log_record .= "-----------------\nSyncing Assets to AWS\n";
echo `aws s3 sync /usr/home/digitalm/Sites/flames/assets s3://amdflames/assets  --profile aws-web`;




#####################


file_put_contents($sweep_log,$log_record);


$admin_notice =<<<EOF
<pre>
        Sweeps Events reported: $incidents
    --------------------------------------------
    $log_record
</pre>
EOF;
if (true or isset($_ENV['RUN_BY_CRON'])) { echo $admin_notice;}

if (1 && $incidents > 0){ #get the echo so don't need an email too
    mail('admin@amdflames.org',"Cron - $sql_now: $incidents incidents",$log_record);
}


exit;
########################################################


function thank_not_lost($mode){
/*  Now look for fresh email validations and send thank you
bulk_mail_setup.php

sendto = 'not_lost'
go = 'Run Now'

// where are we posting to?
$url = 'http://foo.com/script.php';

// what post fields?
$fields = array(
   'field1' => $field1,
   'field2' => $field2,
);

// build the urlencoded data
$postvars = http_build_query($fields);

// open connection
$ch = curl_init();

// set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);

// execute post
$result = curl_exec($ch);

// close connection
curl_close($ch);
*/
    $log = "Looking for newly Not Lost members\n";
    $post_url = 'https://amdflames.org/scripts/bulk_mail_setup.php';
    $post_go = ($mode == 'Real')?'Run Now':'Setup Only';
    #$post_go = 'Run Now';
    $post_fields = array(
        'sendto' => 'not_lost',
        'go' => $post_go,
        'curl' =>true,
        'Mode' =>$mode
    );
    $postvars = http_build_query($post_fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_POST, count($post_fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

        $post_result = curl_exec($ch);
        $post_info = curl_getinfo($ch);
        if ($post_result===false || $post_info['http_code'] != 200){
            $log .= "Curl Failed:\n"
            . curl_error($ch)
            . 'Info: ' . $post_info['http_code']
            ;
        }
        else {$log .= $post_result;}


    curl_close($ch);
    return $log;
}


?>

