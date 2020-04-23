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
    
    
/**
	Report to generate list of new members, updated emails, etc.
	to tag onto end of newsletter.  Also produces text file for use 
	in weekly emal.
	
**/

##########################

class StatusReport {

	private $namelist = array();
	private $lostlist = array();
	private $sadlist = array();
	
	private $member;

	private static 	$type_titles = array(
			'email'	=>	'Updated Email Addresses|',
			'new'	=>	'New Members|If you recognize a new member, send them a welcome!  Click their name to get contact info.',
			'deceased'	=>	'Deceased|',
			'profile'	=>	'Profile Updates|',
			'bounces'	=>	'Broken Emails|',
			'lost'	=> 'Recently Lost Contact|We gave up attempting to contact these people this week.',
			'long lost'	=>	'Long Lost - sample of members with no contact info|Here is a random sample of people that we have no contact information for. If you know anything about them, please <a href="mailto:admin@amdflames.org">contact the admin</a>.'

		);

	
	private $since; // start date in Y-m-d format
	
	
	
	public function __construct($since,$test=false) {
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		$this->since = (string)$since; #is UTC timestamp
		$this->test = $test;
		
		$this->member = new Member();
		
		$report = $this->createReport($this->since);
		file_put_contents(FileDefs::status_report,$report);
		
		$profile_report = $this->report_profiles($this->since);
		$directory = SITE_PATH . '/news/next';
		$section = "profile_updates.html";
		file_put_contents("$directory/$section",$profile_report);
		
		$name_report  = $this->createNameReport();
		file_put_contents(FileDefs::status_tease,$name_report);
		
		
		
		
		
		#echo "Saving run time to " . FileDefs::rtime_file . BRNL;
		file_put_contents(FileDefs::rtime_file,time());
	
	}
	
	
	
	private function createReport ($since) {
		$report = "<div class='inner'><p>Member Status Report " . date('d M Y') . "<br />";
		$report .= "Changes since $since.</p>";
    	$since = (string)$since;
    	
		$report .= $this->report_members();
   $test = true;
		$list= $this->member->getNewMembers($since,$this->test);
   	$report .= $this->report_changes($list,'new');
   	
		$list = $this->member->getUpdatedEmails($since,$this->test);
#    u\echor ($list, 'email updates');
		$report .= $this->report_changes($list,'email');

#		$list = $this->member->getUpdatedProfiles($since,$this->test);
#    u\echor ($list, 'email updates');
#		$report .= $this->report_changes($list,'profile');

		
		$list = $this->member->getDeceased($since,$this->test);
		$report .= $this->report_changes($list,'deceased');

		$list = $this->member->getNewLost($since,$this->test);
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
    
    
    private function report_profiles ($since) {
    	/* builds a story file from updated profiles */
    	$list = $this->member->getUpdatedProfiles($since,$this->test);
    	$count = count($list);
    	echo "$count profile updates" . BRNL;
    	if (empty ($list)){return;}
    	list ($titletext,$subtitle) = explode ('|',self::$type_titles['profile']);
    	$story = "";
    	
    	foreach ($list as $row){
    		$story.= <<<EOT
    <div class='article'>
		<div class='head'>
		<p class='headline'>${row['username']}<br>
		<span class='normal'> ${row['user_current']} ... in ${row['user_from']}
EOT;
			if (! empty ($row['user_greet'])){
			$story .= "<br /><i>${row['user_greet']}</i>";
		}
			$story .= "</span></p>\n";
		
		$story .= "
		</div>
		<div class='content'>
		";

		if (! empty ($row['user_about'])) {
			$story .= "<p class='subhead'>About:</p>
				<div class='subarticle'>${row['user_about']}</div>";
		}
		if (! empty ($row['user_interests']) ){
			$story .= "<p class='subhead'>Interests:</p> 
			<div class='subarticle'>${row['user_interests']}</div>";
		}
		if (! empty ($row['user_memories']) ){
			$story .= "<p class='subhead'>Memories:</p> 
			<div class='subarticle'>${row['user_memories']}</div>";
		}
		$story .= <<<EOT
	</div></div>
	
	</div>
EOT;
	$this->namelist[] = $row['username'];
		}
		
		return $story;
		
		
    }
    
    
	private function report_changes ($result,$type){
	 // print info on updated users, given query result and type of report
	 // result is list of members+data supplied from members class


		$num_rows = count($result);
		$num_rows_display = ($num_rows == 0)? 'No ' : $num_rows;

		list ($titletext,$subtitle) = explode ('|',self::$type_titles[$type]);
		
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
					
					$contact = $row['email_public'];

					$profile_year = date('Y',strtotime($row['profile_date'])) ?? 'none';

					switch ($type){
						case 'deceased':
							$note = $current;
							$contact = '';
							$this->sadlist[] = $name;
							break;
						case 'new':
							$note = $greeting;
							$this->namelist[] = $name;
							break;
						case 'profile':
							$note = $greeting;
							$this->namelist[] = $name;
							break;
						case 'long lost':
						case 'lost':
							$this->lostlist[] = $name;
							$note = '';
							break;
						case 'email':
							$note='';
							$this->namelist[] = $name;
							break;
						
						default:
							$note = '';
					}
						 $report .= <<<EOT
						 <tr class='brow'>
							  <td class='username'><a href= '/profile.php?uid=$id' target = '_blank'>$name</a></td>
							  <td >$location</td>
							  <td>$contact</td></tr>
EOT;

						 if (in_array($type,array('new','profile','lost') )){
						  if ($greeting != ''){$report .= "
								<tr><td class='tright'></td><td colspan = '2' >&ldquo;$greeting&rdquo;</td></tr>\n";
							  $report .= "
							  <tr><td class='tright'>As of $profile_year: </td>
							  <td class='current' colspan='2'>$current in $location</td></tr>\n
							   <tr class='atamd'><td class='tright'>At AMD: </td><td colspan='2'>$amd</td></tr>
							   <tr><td colspan='3'>&nbsp;</td></tr>\n";
							   
							 
								}
						
							}
						 }
					
					$report .= "\n\n";

				 #end while
				$report .= "</table>";
			}

			$report .= "\n";
		return $report;
	}
	
	public function createNameReport(){
		#sort and make uniuqe
		$name_report = '';
		$list = array_unique($this->namelist);
		if (!empty($list)) {
			sort($list);
			$name_count = 0;
			$last_name = '';
			$name_report .= "New or updated information about these AMD Alumni:
----------------------------
    ";
			foreach ($list as $name){
					$name_report .= $name;
					++$name_count;
						 #line break every 4 names
					if ($name_count%4){$name_report .= ", ";}
						else {$name_report .= "\n    ";}
			}
			$name_report = rtrim(rtrim($name_report),',') . "\n\n";
		}
		
		$list = array_unique($this->sadlist);
		if (!empty($list)) {
			sort($list);
			$name_count = 0;
			$last_name = '';
			$name_report .= "Sad News
----------------------------
    ";
			foreach ($list as $name){
					$name_report .= $name;
					++$name_count;
						 #line break every 4 names
					if ($name_count%4){$name_report .= ", ";}
						else {$name_report .= "\n    ";}
			}
			$name_report = rtrim(rtrim($name_report),',') . "\n\n";
		}
		
		$list = array_unique($this->lostlist);
		if (!empty($list)) {
			sort($list);
			$name_count = 0;
			$last_name = '';
			$name_report .= "We've Lost Contact with These AMD Alumni:
(If you know how to contact them, please let me know by replying
to this email)
----------------------------
    ";
			foreach ($list as $name){
					$name_report .= $name;
					++$name_count;
						 #line break every 4 names
					if ($name_count%4){$name_report .= ", ";}
						else {$name_report .= "\n    ";}
			}
			$name_report = rtrim(rtrim($name_report),',') . "\n\n";
		}
		
		return $name_report;
	}
		
	
}
