<?php
namespace digitalmx\flames;
ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	




if ($login->checkLogin(4)){
   $page_title = 'Change report';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START

   
    use digitalmx\flames\Member;
    
    $since = '2019-07-01';
    
    $cr = new ChangeReport($since);
    $report = 'start report';
    
    $list = $member->getUpdatedEmails($since);
#    u\echor ($list, 'email updates');
    
	$report .= $cr->report_changes($list,'email');

	$list = $member->getDeceased($since);
	$report .= $cr->report_changes($list,'deceased');
	
	echo $report;
	

##########################

class ChangeReport {

	private $namelist = array();
	private $member;

	private static 	$type_titles = array(
			'email'	=>	'Updated Email Addresses',
			'new'	=>	'New Members',
			'deceased'	=>	'Deceased',
			'updates'	=>	'Profile Updates',
			'bounces'	=>	'Broken Emails',
			'lost'	=> 'Recently Lost Contact',
			'long lost'	=>	'Long Lost - sample of members with no contact info'

		);
		
	private static $subtitles = array(
				  'lost' => 'We gave up attempting to contact these people this week.',
				  'long lost' => 'Here is a random sample of people that we have no
				  contact information for. If you know anything about them, please
				  <a href="mailto:admin@amdflames.org">contact the admin</a>.',
				  'new' => 'If you recognize a new member, send them a welcome!  Click their name to get contact info.'
				 );


	private $since; // start date in Y-m-d format
	
	public function __construct($since) {
		if (! u\validateDate($since )){
			throw new Exception ("$since is not a valid sql date");
		}
		$this->since = $since;
		$this->member = new Member();
	}
	
	
	public function report_changes (&$result,$type){
	 // print info on updated users, given query result and type of report


		$num_rows = count($result);
		$num_rows_display = ($num_rows == 0)? 'No ' : $num_rows;

		$namelist = array();
		$title = "$num_rows_display " . self::$type_titles[$type] ;
		if ($type == 'deceased' && $num_rows_display == 0){$title .= "<small>(whew)</small>";}

		echo $title,"<br>\n";
		$report = "<h3>$title</h3>";
		 if ($num_rows_display > 0 && isset(self::$subtitles[$type]) ) {$report .= "<p style='font-style:italic;margin-left:3em;'>" . self::${subtitles[$type]} . "</p>";}

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
					$this->namelist[] = $name;
					$contact = $row['email_public'];
					$profile_year = substr($row['profile_date'],0,4);

					switch ($type){
						case 'deceased':
							$note = $current;
							$contact = '';
							break;
						case 'new':
							$note = pickbest($greeting,"<p class='greeting'>$greeting</p>",'');

							break;
						case 'updates':
							$note = pickbest($greeting,"<p class='greeting'>$greeting</p>",'');
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
	
	public function getNamelist(){
		#sort and make uniuqe
		$list = array_unique($this->namelist);
		return sort ($list);
	}
}
