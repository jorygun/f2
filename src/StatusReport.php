<?php
namespace DigitalMx\Flames;
ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;

	use DigitalMx\Flames\FileDefs;
    use DigitalMx\Flames\Member;


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



	public function __construct($container,$since,$test=false) {
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		$this->since = (string)$since; #is UTC timestamp
		$this->test = $test;

		$this->member = $container['member'];
		$this->member_admin = $container['membera'];


	// create report of member status updates
		$report = $this->createReport($this->since);
		file_put_contents(FileDefs::next_dir . '/status_report.html',$report);

	// create html article of profile updates
		$report = $this->report_profiles($this->since,20); #no more than 20 in one report
		file_put_contents(FileDefs::next_dir . '/profile_updates.html',$report);

	// create list of updates for email teasers
		$report  = $this->createNameReport();
		file_put_contents(FileDefs::tease_status,$report);

		// update timestamp on next pub record
		$container['publish']->setLastScan();

	}



	private function createReport ($since) {
		$report = "<div class='inner'><p>Member Status Report " . date('d M Y') . "<br />";
		$report .= "Changes since $since.</p>";

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


   private function report_profiles ($since,$limit) {
    	/* builds a story file from updated profiles */
    	// get list of user_ids of updated profiles
    	$list = $this->member->getUpdatedProfiles($since,$this->test);
    	$count = count($list);
    	echo "$count profile updates" . BRNL;
    	if (empty ($list)){return;}
    	list ($titletext,$subtitle) = explode ('|',self::$type_titles['profile']);

		//prepare marker for profile_reported.


    	// now create a story fle to drop into news/next
    	$limitm = min($count,$limit);
    	$story = "<p class='subhead'>Reporting on $limitm of $count recent profile updates. More in next newsletter.</p>";

		$r = 0;
    	foreach ($list as $uid){
    		++$r;
    		$row = $this->member_admin->getProfileData($uid);
    		$story.= <<<EOT
    <div class='article'>
		<div class='head'>
		<p class='headline'>${row['username']} <span class='normal'>${row['email_public']} ${row['hidden_emailer']} </span></p>
		At AMD: ${row['user_amd']}

		</p>
EOT;

			$story .= "
			</div>
			<div class='content'>
			";
			$story .= "<div class='subarticle'><u> Currently:</u>
			Living in ${row['user_from']}<br>
				${row['user_current']}</div>";

			if (! empty ($row['user_interests']) ){
				$story .= "<div class='subarticle'><u>Interests:</u>
				${row['user_interests']}</div>";
			}

			if (! empty ($row['user_about'])) {
				$story .= "<div class='subarticle'><u>About:</u>
					${row['user_about']}</div>";
			}

			$story .= "<p><a href='/profile.php?id=$uid'>View Full Profile</a></p>" . NL;

		// if (! empty ($row['user_memories']) ){
// 			$story .= "<p class='subhead'>Memories:</p>
// 			<div class='subarticle'>${row['user_memories']}</div>";
// 		}
			$story .= <<<EOT
	</div></div>

	</div>
EOT;
			$this->namelist[] = $row['username'];
			// mark reported.
			$this->member->setProfileReported($uid);

			if ($r >= $limitm) {return $story;}
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

			$report .= "<table >";
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
							  <td class='username'><a href= '/profile.php?uid=$id' target = 'profile'>$name</a></td>
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
