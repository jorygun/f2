<?php

/* preload these files:
	Messenger
	Member
	MyPDO.php
	Definitions
	MxUtilities
	
*/

ini_set('display_errors', 1);
#set_time_limit(300); // 30sec. is default 0 is none;

$script = basename(__FILE__);
$dir=dirname(__FILE__);



use digitalmx\flames\Definitions as Defs;
use digitalmx as u;
use digitalmx\flames\Messenger;
use digitalmx\flames\Member;
use digitalmx\MyPDO;


if (! defined ('INIT')) { echo "Running cron-ini \n";
	include "$dir/cron-ini.php";
}
if (! defined ('INIT')) { die ("$script halting. Init did not succeed ");}



$sweep = new Sweep($dir,$test,$pdo);
$sweep -> runSweep();



class Sweep{

	private $pdo;
	private $messenger;
	private $members;
	private $test;
	private $mode;
	private $age_limit;
	private $member_status_set;
	private $limit;
	
	private $now_sql;


	
	private $log; #var used to capture log records
	private $sweep_log; #file path for log file
	
	private static $log_format1 = "(%s) %-25s  %-20s (id %4d) status %2s %4d days. Change to %s.\n";
	private static $log_format2 = "(%s) %-25s  %-20s (id %4d) Ems %2s. Change to %s. \n\tLogin %10s; Em-valid %10s; Prof-valid %10s.\n";

	
	private static $sleep_interval = 10;// delay in seconds between emails.
	private static $ems_test_sequence = array ('N1','E1','A3','A2','A1','B1','D');


	
// set older test flags

	public function __construct($dir,$test, $pdo) {
		#	$test = true;
		$this->test = $test;
		$mode = ($test)? 'Test':'Real';
		$this->mode = $mode;
		$this->member = new Member();
		$this->messenger = new Messenger(); 
		$this->messenger->setTestMode($test); #true = test mode
		//$this->pdo = MyPDO::instance();
		$this->pdo = $pdo;
	

		$this->age_limit = Defs::$age_limit; #limit for aging out in days
		$this->member_status_set = Defs::getMemberInSet();

		$dt = new DateTime();
		$this->now_sql = $dt->format('Y-m-d');
		$now_datestamp = $dt->format('Ymd_His');
		$now_human = $dt->format("M j, H:i a");
		
		$this->limit = Defs::$age_limit;
		
		$sweep_log_dir = REPO_PATH . "/var/logs";
		$this->sweep_log = $sweep_log_dir . '/sweep-' . "${now_datestamp}-${mode}.txt";
		$this->log = sprintf ("Sweep run %s at %s\n\n",$this->mode,$now_human);

		
	}

    
	public function runSweep(){
		$incidents = 0;  #actual number of incidentst
		//remove x-ed out records
		$count = $this->removex(); 
		echo "$count X-ed out records found.\n\n";
		$incidents += $count;

		// test for each of the transitional status ages
		$count = $this->main(); 
		echo "$count expired email status records found.\n\n";
		$incidents += $count;

	//	test for new members verified but not welcomed
		$count = $this->new_members();
		echo "$count new members found.\n\n";
		$incidents += $count;

		// Finally test last contact a long long time ago
		$count = $this->aged_out();
		echo "$count aged out members found.\n\n";
		$incidents += $count;

		$this->close_sweep($incidents);
		exit;
		
		
	}	

function close_sweep($incidents) {
	$sweep_log_dir = dirname($this->sweep_log);
	echo "Saving file $this->sweep_log \n";
	file_put_contents($this->sweep_log,$this->log);

	exec("
unlink $sweep_log_dir/last_sweep;
/bin/ln -s $this->sweep_log $sweep_log_dir/last_sweep;
");

	$admin_notice =<<<EOF
        Sweeps Events reported: $incidents
    --------------------------------------------
    $this->log

EOF;
	if ($this->test) {
		echo $admin_notice;
	} elseif ($incidents > 0) {
		$subj = "Cron - " . date('d M Y') . ": $incidents incidents";
		# mail('admin@amdflames.org',$subj,$admin_notice);
	}


}


########################################################
	function new_members() {
		$this->log .= "\n#### Testing new users validated but not welcomed\n";
		$sql = "SELECT id, username, user_email 
			FROM `signups` WHERE
				 status = 'A' ;
			";
	 
		$result = $this->pdo->query($sql);
		$rows_found = $result->rowCount();
		$this->log .=  "Users to welcome: $rows_found\n" ;
		if ($rows_found > 0){
			$msg = "
Sweeps has encountered entries in Signups with validated email, 
These users have not been processed.\n
";
		
			foreach($result as $row){
				$uid = $row['id'];
				$username = $row['username'];
				$user_email = $row['user_email'];
		
				$this->log .=  sprintf ( self::$log_format1, $this->mode, $user_email,$username,$id,'','','Approve');

				$msg .= sprintf ("   %10s %-15s \n" ,$id, $username);
		
			}

			mail('admin@amdflames.org',"New users need welcome", $msg,"From: admin@amdflames.org\n\r");
		}
		return $rows_found;
	}

function aged_out() {
	// changed 9/2019 to simpler test: last login > 1year
	$this->log .=  "\n#### Testing last activity more than $this->limit days\n";
	$limit = $this->limit;
	$lost_warning = Defs::$lost_warning;
	$sweep_fields = '
		id,
		user_id,
		username,
		status,
		email_status_time,
		user_email,
		last_login,
		email_status,
		email_last_validated,
		profile_validated
	';
	


	$sql = "SELECT $sweep_fields FROM `members_f2` WHERE
		STATUS in ($this->member_status_set)
		AND email_status in ('Q','Y')
		AND
			(last_login IS NULL
			OR
			last_login < NOW() - INTERVAL $lost_warning DAY
			)
		AND
			(
			email_last_validated is NULL
			OR
			email_last_validated < NOW() - INTERVAL $lost_warning DAY
			)
	
		";
		if ($this->test){$sql .= " AND test_status != '' ";}
	
      $sql .= "
			ORDER BY profile_validated
			LIMIT 2
			;";
			
		#echo $sql,"<br>";
	
	
		$result = $this->pdo->query($sql) ;
		$rows_found = $result->rowCount();
		$this->log .= "Users aged out: $rows_found\n";
		#echo "$rows_found aged out users found.\n";
      
		foreach ($result as $row){
			#echo "Processing ${row['username']}\n";
			$uid = $row['user_id'];
			$username = $row['username'];
			

			$user_email = $row['user_email'];
			$next_ems = 'A1';

			//$ems_date = u\make_date($row['email_status_time'],'human'); #is a datestamp
			$ems_age = u\days_ago($row['email_status_time']);
			 $this->messenger->sendMessages($uid,$next_ems);
			if ($this->test ){
				echo "Test only: $username ($user_email) age $ems_age\n";
			} elseif (
				 $this->member->setEmailStatus($uid,$next_ems) 
				 ){
				#ok
			 } else {
				  echo "update_ems failed on uid $uid to status $next_ems.\n" ;
			 }
			 
			$last_login_date = substr($row['last_login'],0,10);
			
			$this->log .=  sprintf ( self::$log_format2, $this->mode, $row['user_email'],$row['username'],$uid,$row['email_status'],$next_ems,$last_login_date,$row['email_last_validated'], $row['profile_validated']
			);
		}
		return $rows_found;
	}
	
	
	function main () {
		$sweep_fields = '
			id,
			user_id,
			username,
			status,
			email_status_time,
			user_email,
			last_login,
			email_status,
			email_last_validated,
			profile_validated
		';
		$this->log .= "##### MAIN TEST SEQUENCE ###\n";

		$sql = "SELECT $sweep_fields FROM `members_f2` WHERE
			status in ($this->member_status_set)
			AND email_status = ?
			AND email_status_time <  DATE_SUB(NOW(), INTERVAL ? day)
			";
		 if ($this->test){ 
			$sql .=  " AND test_status != '' ";
		 }
		$main_stmt = $this->pdo->prepare($sql);
		$count = 0;
		foreach (self::$ems_test_sequence as $this_ems){
			list ($ems_name,$next_ems,$ems_life) = Defs::getEmsData($this_ems);
			$this->log .= "...Testing ems $this_ems age $ems_life; next $next_ems.\n";
			$main_stmt -> execute([$this_ems,$ems_life]) ;
			$tags=0;
			foreach($main_stmt as $row){
	#	u\echor($row,'data row');exit;
				++$tags;
				 $uid = $row['user_id'];
				 $username=$row['username'];
		
				 $ems_date = u\make_date($row['email_status_time'],'human' ); #is a time stamp
				$ems_age = u\days_ago($row['email_status_time']);
				$this->messenger->sendMessages($uid,$next_ems);
				if ($this->test){
					echo "testing: would update $uid $username to $next_ems\n";
				} elseif (
				 $this->member->setEmailStatus($uid,$next_ems) 
					){
						$this->log .= "\t($this->mode) Updated $uid $username to $next_ems\n";
				} else {
						$this->log .=  "Failed update_ems  on uid $uid to status $next_ems.\n" ;
				}
			}
			
			$count += $tags;
			$this->log .= "    $tags aged out records $this_ems found.\n";
		}
		return $count;
	 
 	}
 	
 	
	function removex() {
		$this->log .=  "\n#### Testing x-ed out records\n";
		$where = "status like 'X%' ";
		if ($this->test){$where .= " AND test_status != '' ";}
	
		$sql = "SELECT count(*) FROM members_f2 WHERE $where";
		$incident_count = $this->pdo->query($sql)->fetchColumn();
	
		if ( $incident_count ){
			 
			if (! $this->test ){
				$this->log .=  "Deleting $incident_count records\n";
				$sql = "DELETE from `members_f2` WHERE $where";
				 $this->pdo->query($sql);
			 }
			 else { 
				echo "Test mode: $incident_count records found but not deleted\n";
			 }
		}
		return $incident_count;
	}

}
