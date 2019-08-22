<?php

/* preload these files:
	Messenger
	Member
	MyPDO.php
	Definitions
	MxUtilities
	
*/

ini_set('display_errors', 1);

/*  STARTUP */
$script = basename(__FILE__);
$dir=dirname(__FILE__);

if (! @defined ('INIT')) {
	include "$dir/cron-ini.php";
}
/* sets BRNL, etc; 
	REPO_PATH, PROJ_PATH, SITE, SITE_URL, SITE_PATH, CONFIG_INI
	$pdo, $test, $quiet  
	include_path
	requires Definitions
*/
if (! @defined ('INIT')) { die ("$script halting. Init did not succeed " . INIT . " \n");}

use digitalmx\flames\Definitions as Defs;
use digitalmx as u;
use digitalmx\flames\Messenger;
use digitalmx\flames\Member;

// set older test flags
$mode = ($test)? 'Test':'Real';
$test_status_select = ($test)? " AND test_status != '' " : '' ;

$member = new Member();
$messenger = new Messenger(); 
$messenger->setTestMode($test); #true = test mode



$sleep_interval = 10;// delay in seconds between emails.
set_time_limit(300); // 30sec. is default 0 is none;

$limit = 365; #days without activity
    $limit2=$limit*2;
    
$member_status_set = Defs::getMemberInSet();


#sequence is critical! or you'll catch one you just changed.
$ems_test_sequence = array ('N2','N1','E2','E1','A4','A3','A2','A1','B2','B1','D');


#frields from members needed for processing.
// $sweep_fields = '
// 	id,
// 	user_id,
// 	username,
// 	UNIX_TIMESTAMP(email_status_time) as email_status_time,
// 	user_email,
// 	last_login,
// 	email_status,
// 	UNIX_TIMESTAMP(email_last_validated) as email_last_validated,
// 	UNIX_TIMESTAMP(profile_validated) as profile_validated
// ';

$sweep_fields = '
	id,
	user_id,
	username,
	email_status_time,
	user_email,
	last_login,
	email_status,
	email_last_validated,
	profile_validated
';
$dt = new DateTime();
$now_sql = $dt->format('Y-m-d');
$now_datestamp = $dt->format('Ymd_His');
$now_human = $dt->format("M j, H:i a");


$sweep_log_dir = REPO_PATH . "/var/logs";
$sweep_log = $sweep_log_dir . '/sweep-' . "${now_datestamp}-${mode}.txt";
if (! $quiet)
echo "Logging to $sweep_log" . BRNL;




$log = sprintf ("Sweep run %s at %s\n\n",$mode,$now_human);
$log_format1 = "(%s) %-25s  %-20s (id %4d) status %2s %4d days. Change to %s.\n";
$log_format2 = "(%s) %-25s  %-20s (id %4d) Ems %2s. Change to %s. \n\tLogin %10s; Em-valid %10s; Prof-valid %10s.\n";


$incidents = 0;  #actual number of incidentst
####################
//remove x-ed out records
$log .=  "\n#### Testing x-ed out records\n";

$sql = "SELECT $sweep_fields FROM members_f2 WHERE
    status like 'X%'  ;";

$result = $pdo->query($sql);
$incident_count = $result->rowCount();

if ( $incident_count ){
        $sql = "DELETE FROM `members_f2` WHERE status like 'X%' ";
   if (! $test ){
   	$log .=  "Deleting $incident_count records\n";
        $result = $pdo->query($sql);
    }
    else { 
    	$log .= "Test mode: $incident_count records found but not deleted\n";
    }
}
 echo "$incident_count X-ed out records found.\n\n";
 $incidents += $incident_count;
$incident_count = 0;

// test for each of the transitional status ages
#sequence is critical! or you'll catch one you just changed.

$log .= "##### MAIN TEST SEQUENCE ###\n";

$main_test = "SELECT $sweep_fields FROM `members_f2` 
	WHERE
	status in ($member_status_set)
	$test_status_select
	AND email_status = ?
	AND email_status_time <  DATE_SUB(NOW(), INTERVAL ? day)
	 ;";
$main_stmt = $pdo->prepare($main_test);

foreach ($ems_test_sequence as $this_ems){
	## MAIN TEST
	list ($ems_name,$next_ems,$ems_life) = Defs::getEmsData($this_ems);
	
	$log .= "...Testing ems $this_ems age $ems_life; next $next_ems.\n";
	
   $limit_date = date('Y-m-d', strtotime( "- $ems_life days" ));
	$main_stmt -> execute([$this_ems,$ems_life]) ;

    $tags=0;
	foreach($main_stmt as $row){
	# u\echor($row,'data row');
	
			++$tags;
			
			 $id = $row['id'];
			 $uid = $row['user_id'];
			 $username=$row['username'];
			 #$log .=  "retrieving user id $uid $username<br>\n";
			 $ems_date = u\make_date($row['email_status_time'],'human' ); #is a time stamp
			$ems_age = u\days_ago($row['email_status_time']);
				
			if (! $test
				&& $messenger->sendMessages($uid,$next_ems)
				 && $member->setEmailStatus($uid,$next_ems) 
				 
				){
					$log .= "\t($mode) Updated $uid $username to $next_ems\n";
				 }
				else {
					  $log .=  "Failed update_ems  on uid $uid to status $next_ems.\n" ;
				 }
	  }
	  $incident_count += $tags;
		$log .= "    $tags aged out records $this_ems found.\n";
}
 
 $incidents += $incident_count;
echo "$incident_count records with aged out ems found\n\n";
$incident_count = 0;

// test for new members verified but not welcomed


$log .= "\n#### Testing new users validated but not welcomed\n";
$sql = "SELECT $sweep_fields FROM `members_f2` WHERE
			email_status = 'Y'
			and status = 'N' ;
		";
    
	 $result = $pdo->query($sql);
	$rows_found = $result->rowCount();
	$log .=  "Users to welcome: $rows_found" . BRNL;
    if ($rows_found > 0){
		foreach($result as $row){
			$uid = $row['user_id'];

			 $ems_date = u\make_date($row['email_status_time'],'human'); #is a now_datestamp
			$ems_age = u\days_ago($row['email_status_time']);

			$log .=  sprintf ( $log_format1, $mode, $row['user_email'],$row['username'],$uid,$this_ems,$ems_age,'Send Welcome');

			$msg = "Sweeps has encountered members with validated email, but status = N.
			This user has not received welcome message.\n\n
			${row['username']}:
			   
			";


		}
		if (!$test){
		 mail('admin@amdflames.org',"User needs welcome - ${row['username']}", $msg,"From: admin@amdflames.org\n\r");
		 
		 }
		 $incidents += $rows_found;
	}
echo "$rows_found new users needing welcome\n";

#echo "Line " . __LINE__ . "\n";

// Finally test last contact a long long time ago

$log .=  "\n#### Testing last activity more than $limit days\n";
	if (!$test){
	$sql = "SELECT $sweep_fields FROM `members_f2` WHERE
		STATUS in ($member_status_set)
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
         
			ORDER BY profile_validated
			LIMIT 2
			;";
			#echo $sql,"<br>";
	
	} else { //simpler sql for testing
	  $sql = "SELECT $sweep_fields FROM `members_f2` WHERE
			STATUS in ($member_status_set)
			AND email_status in ('Q','Y')
		
			AND
					(
						 email_last_validated IS NULL
						 OR
						 email_last_validated < NOW() - INTERVAL $limit DAY
					)

		
			$test_status_select
				ORDER BY profile_validated
		
		;";
		}
	if ( $result = $pdo->query($sql) ){
		$rows_found = $result->rowCount();
		$log .= "Users aged out: $rows_found\n";
		echo "$rows_found aged out users found.\n";
        $incidents += $rows_found;

		foreach ($result as $row){
			#echo "Processing ${row['username']}\n";
			$uid = $row['user_id'];

			$this_email = $row['user_email'];
			$next_ems = 'A1';

			$ems_date = u\make_date($row['email_status_time'],'human'); #is a now_datestamp
			$ems_age = u\days_ago($row['email_status_time']);

			if (!$test 
				 && $messenger->sendMessages($uid,$next_ems)
				 && $member->setEmailStatus($uid,$next_ems) 
				 ){
				#ok
			 } else {

				  echo "update_ems failed on uid $uid to status $next_ems.\n" ;
			 }
			$last_login_date = substr($row['last_login'],0,10);
			/*
			$log_format2 = "(%s) %-25s  %-20s (id %4d) Ems %2s. Change to %s. \n\tLogin %10s; Em-valid %10s; Prof-valid %10s.\n";
			*/
			$log .=  sprintf ( $log_format2, $mode, $row['user_email'],$row['username'],$uid,$row['email_status'],$next_ems,$last_login_date,$row['email_last_validated'], $row['profile_validated']
			);
		}
	}
		$incidents += $rows_found;
	echo "Total incidents: $incidents\n\n";


#####################


file_put_contents($sweep_log,$log);
echo "Saving file $sweep_log \n";

echo exec("
	unlink $sweep_log_dir/last_sweep;
	ln -s $sweep_log $sweep_log_dir/last_sweep;
	");

$admin_notice =<<<EOF

        Sweeps Events reported: $incidents
    --------------------------------------------
    $log

EOF;
if ($test) {
	echo $admin_notice;
} elseif ($incidents > 0) {
    mail('admin@amdflames.org',"Cron - $now_sql: $incidents incidents",$admin_notice);
}

exit;
########################################################

