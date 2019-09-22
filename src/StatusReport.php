<?php
namespace digitalmx\flames;
ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	
	use digitalmx\flames\FileDefs;
    use digitalmx\flames\Member;
    
    
  

##########################

class StatusReport {

	private $namelist = array();
	private $member;

	private static 	$type_titles = array(
			'email'	=>	'Updated Email Addresses|',
			'new'	=>	'New Members|If you recognize a new member, send them a welcome!  Click their name to get contact info.',
			'deceased'	=>	'Deceased|',
			'updates'	=>	'Profile Updates|',
			'bounces'	=>	'Broken Emails|',
			'lost'	=> 'Recently Lost Contact|We gave up attempting to contact these people this week.',
			'long lost'	=>	'Long Lost - sample of members with no contact info|Here is a random sample of people that we have no contact information for. If you know anything about them, please <a href="mailto:admin@amdflames.org">contact the admin</a>.'

		);

	

	private $since; // start date in Y-m-d format
	
	
	
	public function __construct($since,$test=false) {
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		$this->since = $since; is UTC timestamp
		$this->test = $test;
		
		$this->member = new Member();
		
		$report = $this->createReport($since);
		file_put_contents(FileDefs::status_report,$report);
		$name_report  = $this->createNameReport();
		file_put_contents(FileDefs::status_tease,$name_report);
		
		#echo "Saving run time to " . FileDefs::rtime_file . BRNL;
		file_put_contents(FileDefs::rtime_file,time());
	}
	
	
	
	private function createReport ($since) {
		$report = "<div class='inner'><p>Member Status Report " . date('d M Y') . "<br />";
		$report .= "Changes since $since.</p>";
    
		$report .= $this->report_members();
   
		$list= $this->member->getNewMembers($since);
   	$report .= $this->report_changes($list,'new');
   	
		$list = $this->member->getUpdatedEmails($since);
#    u\echor ($list, 'email updates');
		$report .= $this->report_changes($list,'email');

		$list = $this->member->getDeceased($since);
		$report .= $this->report_changes($list,'deceased');

		$list = $this->member->getNewLost($since);
		$report .= $this->report_changes($list,'lost');

		$list = $this->member->getOldLost(8);
		$report .= $this->report_changes($list,'long lost');
	
		return $report;
	}
	
	private function report_members () {
	$counts =  $this->member->getMemberCounts();
 return "<h3>Membership</h3><p>Active Members: ${counts['active']}, plus ${counts['lost']} lost contact. 
    Total ${counts['total']}. </p>";
    }
    
	private function report_changes ($result,$type){
	 // print info on updated users, given query result and type of report
	 // result is list of members+data supplied from members class


		$num_rows = count($result);
		$num_rows_display = ($num_rows == 0)? 'No ' : $num_rows;

		list ($titletext,$subtitle) = explode ('|',self::$type_titles[$type]);
		$namelist = array();
		$title = "$num_rows_display " . $titletext ;
		if ($type == 'deceased' && $num_rows_display == 0){$title .= "<small>(whew)</small>";}

		echo $title,"<br>\n";
		$report = "<h3>$title</h3>";
		#u\echor(self::$subtitles,'subtitles'); exit;
		 if ($num_rows > 0 &&  !empty($subtitle) ) {
		 	$report .= "<p style='font-style:italic;margin-left:3em;'>" . $subtitle . "</p>";
		 }

		if ($num_rows >0){

			$report .= "<table class='update_data'>";
				foreach ($result as $row ) {
					$name = $row['username'];

					$amd = $row['at_amd'] ;
					$current = $row['user_current'];

					$location = $row['user_from'];
					$id = $row['user_id'];
					$greeting = $row['user_greet'];
				
					$joined = $row['join_date'];
					if ($type <> 'long lost') $this->namelist[] = $name;
					$contact = $row['email_public'];

					$profile_year = date('Y',strtotime($row['profile_date'])) ?? 'none';

					switch ($type){
						case 'deceased':
							$note = $current;
							$contact = '';
							break;
						case 'new':
							$note = "<p class='greeting'>$greeting</p>";

							break;
						case 'updates':
							$note = "<p class='greeting'>$greeting</p>";
							break;

						default:
							$note = '';
					}
						 $report .= <<<EOT
						 <tr class='brow'>
							  <td class='username'><a href= '/scripts/profile_view.php?id=$id' target = '_blank'>$name</a></td>
							  <td class='location'>$location</td>
							  <td>$contact</td></tr>
EOT;

						 if (in_array($type,array('new','updates','lost') )){
							  $report .= "
							  <tr class='atamd'><td class='tright'>At AMD: </td><td colspan='2'>$amd</td></tr>
							  <tr><td class='tright'>As of $profile_year: </td>
							  <td class='current' colspan='2'>$current</td></tr>\n";
						 }
					if ($note != ''){$report .= "
						<tr><td></td><td colspan = '2' class='notes'>$note</td></tr>
						";
					}
					$report .= "\n\n";

				} #end while
				$report .= "</table>";
			}

			$report .= "\n";
		return $report;
	}
	
	public function createNameReport(){
		#sort and make uniuqe
		$list = array_unique($this->namelist);
		if (empty($list)) {return false;}
		sort($list);
		$name_count = 0;
		$last_name = '';
		$name_report = "New or updated information about these AMD Alumni:
----------------------------
    ";
		foreach ($list as $name){
				$name_report .= $name;
				++$name_count;
				    #line break every 4 names
				if ($name_count%4){$name_report .= ", ";}
					else {$name_report .= "\n    ";}
		}
		$name_report = rtrim(rtrim($name_report),',') . "\n";
		return $name_report;
	}
		
	
}
