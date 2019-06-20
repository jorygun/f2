<?PHP

//BEGIN START
	/*  STARTUP */
ini_set('display_errors', 1);

$root = $_SERVER['DOCUMENT_ROOT'];
if (empty($root)){$root = '/usr/home/digitalm/Sites/flames/beta/public';}
 require_once $root . '/init.php';;
 
$repo_path = REPO_PATH;
//END START

/* Script to retrieve recenbt updates to user data and produce html for newsletter
	Also generates calendar file from the events source file to incorporate
	into newsletter.
	Also produces "teaser" file which is plain text names and events
	to include in newsletter.

*/


 $nowsql = date("Y-m-d H:i:s"); #sql-ready
 $nowh	=	date('M d, Y'); #human ready
 
 
 $member_status_set = Defs::get_member_set();
 
#$G_member_status_array = array('M', 'MA','MN','MC','MU','R','G');

 
 $rtime_file = "$repo_path/public/news/last_update_run_ts.txt";
 $ptime_file = "$repo_path/public/news/last_update_published_ts.txt";
 
 $updates_html_file= "$repo_path/public/news/news_next/news_updates.html";
 $updates_text_file =  "$repo_path/public/news/news_next/tease_updates.txt";
 # not used.  Only list of names is used.
 
 $updates_html = $updates_text = ''; #containers for building reports in
 $opportunity_html_file = "$repo_path/public/news/news_next/news_opportunities.html";
 
#report status changes
//Find date to begin looking from //
if(! empty($ptime = $_GET['ptime'] )){
    $ptimex = strtotime($ptime);
    
}
if (!$ptimex){ #get from last published file  timestamp!
    $ptimex = get_start_time($ptime_file);
}
if (!$ptimex){
	echo "No starting time in either parameter or last published file. ";
	echo "Setting to one week ago.";
	$ptimex = strtotime('-7 days');
}

$ptimeh  = date('M d, Y',$ptimex);
$ptimes = date('Y-m-d H:i', $ptimex);

#echo "Start times:<br>$ptimex = $ptimeh = $ptimes<br>\n";

/* List All Updates Since Last Newsletter */



//Get total membership
$q = "SELECT count(*) as count FROM members_f2
	WHERE status in ($member_status_set)
	;";
	 $total_members = $pdo->query($q)->fetchColumn();


$q = "SELECT count(*) as count FROM members_f2
	WHERE status in ($member_status_set) AND
	email_status LIKE 'L_'
	;";

	$lost_members = $pdo->query($q)->fetchColumn();
	$active_members = $total_members - $lost_members;

	echo "<br>Active Members: $active_members.  (Plus $lost_members lost contact; total $total_members.) <br> ";



// list of people who appear in updates.  For teaser
$name_list = array();

$updates_html = <<< EOT

<div class='update_data'>
<h3>FLAMEs membership</h3>
<p>As of $nowh. Updates since $ptimeh.<br>
Active: $active_members; Lost: $lost_members; Total: $total_members.
</p>

<p>View a member's profile by clicking on their names.<br><br>
Lost members are ones that we have no email for, or whose email has
repeatedly bounced.  A sample of "long lost" members is shown at
the bottom.  If you have any information about any of our "Lost"
members, please let me know and I'll try to update their
information.</p>

EOT;


$name_fields = "username,user_amd,user_current,user_from,id, user_greet,user_about,join_date, user_email,email_hide,email_status,profile_validated";

//Get New Members
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status != 'M'
	AND join_date > '$ptimes'
	ORDER BY username;
	";
// 	echo "sql: $q" . BRNL;
// 	exit;
	$stmt = $pdo->query($q);
	$new_member_count = $stmt->rowCount();
   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'new');
	$updates_html .= $report_data;
	$name_list = array_merge($name_list,$name_data);


//Get Member Updates /., excluding new members
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND profile_updated > '$ptimes'
	AND  join_date <= '$ptimes'
	AND username not like 'Flames %'
	ORDER BY username
	";

	 $result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'updates');
	$updates_html .= $report_data;
	$name_list = array_merge($name_list,$name_data);


// GET NEW EMAILS  (email changes are not profile cha nges)
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND email_chg_date > '$ptimes'
	AND join_date <= '$ptimes'
	ORDER BY username";

	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'email');
	$updates_html .= $report_data;
	$name_list = array_merge($name_list,$name_data);


// deceased
	$q = "SELECT $name_fields FROM members_f2
	WHERE status_updated > '$ptimes'
	AND status like 'D'
	AND test_status = ''

	";
	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'deceased');
	$updates_html .= $report_data;
	$name_list = array_merge($name_list,$name_data);

// new lost
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND email_status_time >= '$ptimes'
	AND email_status like 'L%'
	AND previous_ems not like 'L%'
	ORDER BY username
	";
	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'lost');
	$updates_html .= $report_data;

// old lost
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND email_status_time < NOW() - INTERVAL 90 DAY
	AND email_status in ('LB','LA','LN')
		ORDER BY RAND()
		LIMIT 5
		;
	";
	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'long lost');
	$updates_html .= $report_data;
	#$name_list = array_merge($name_list,$name_data);





$updates_html	.= "</div>\n";

file_put_contents($updates_html_file, $updates_html);

echo "Saving member updates to $updates_html_file" . BRNL;



// prepare teaser report
	$teaser_report = prepare_headline_report($pdo);
	$teaser_report .=  prepare_name_report ($name_list);
	$teaser_report .= prepare_opp_report($pdo,$ptimes);
	
	
	
	
	file_put_contents($updates_text,$teaser_report);







// update the last run file
file_put_contents($rtime_file,time());


######################
$opportunities_html = file_get_contents( $opportunity_html_file);
echo <<<EOT
<html><head> 
<title>Show Updates</title>
<link rel='stylesheet' href='/css/news2.css'>

</head><body>
<p>Showing updated since $ptimeh</p>
<h3>recent_articles</h3>

<h3>Recent_assets</h3>

<h3>Member Updates</h3>
$updates_html 

<h3>Opportunities</h3>
$opportunities_html

<hr>

<h3>Teaser File</h3>
<pre>
$teaser_report
</pre>

</body></html>

EOT;

###############################################################

function prepare_name_report($name_list){
	if (empty($name_list)) {return '';}
	
		sort($name_list);
		$name_count = 0;
		$last_name = '';
		$name_report = "New or updated information about these AMD Alumni:
----------------------------
    ";
		foreach ($name_list as $name){
			if ($name <> $last_name){ #dedup
				$name_report .= $name;
				$last_name = $name;
				++$name_count;
				    #line break every 4 names
				if ($name_count%4){$name_report .= ", ";}
					else {$name_report .= "\n    ";}
			}
		}

		$name_report = preg_replace('/,\W+$/',"\n",$name_report); #remove trailling ,
		$name_report .= "\n";
	
	return $name_report;
}

function report_changes (&$result,$type){
 // print info on updated users, given query result and type of report


 	$type_titles = array(
 		'email'	=>	'Updated Email Addresses',
 		'new'	=>	'New Members',
 		'deceased'	=>	'Deceased',
 		'updates'	=>	'Profile Updates',
 		'bounces'	=>	'Broken Emails',
 		'lost'	=> 'Recently Lost Contact',
 		'long lost'	=>	'Long Lost - sample of members with no contact info'

 	);
 	    $subtitles = array(
 	        'lost' => 'We gave up attempting to contact these people this week.',
 	        'long lost' => 'Here is a random sample of people that we have no
 	        contact information for. If you know anything about them, please
 	        <a href="mailto:admin@amdflames.org">contact the admin</a>.',
 	        'new' => 'If you recognize a new member, send them a welcome!  Click their name to get contact info.'
 	       );


 	$num_rows = count($result);
 	$num_rows_display = ($num_rows == 0)? 'No ' : $num_rows;

 	$namelist = array();
	$title = "$num_rows_display $type_titles[$type]  ";
	if ($type == 'deceased' && $num_rows_display == 0){$title .= "<small>(whew)</small>";}

	echo $title,"<br>\n";
	$report = "<h3>$title</h3>";
    if ($num_rows_display > 0 && isset($subtitles[$type]) ) {$report .= "<p style='font-style:italic;margin-left:3em;'>${subtitles[$type]}</p>";}

	if ($num_rows >0){

		$report .= "<table class='update_data'>";
			foreach ($result as $row ) {
				$name = $row['username'];

				$amd = $row['user_amd'] ;
				$current = $row['user_current'];

				$location = $row['user_from'];
				$id = $row['id'];
				$greeting = $row['user_greet'];
				$aboutme = $row['user_about'];
				$joined = _age($row['join_date'])[1];
				$namelist[] = $name;
				$contact = display_email($row);
				$profile_year = substr($row['profile_validated'],0,4);

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
                $report .= "<tr class='brow'>
                    <td class='username'><a href= " . SITE_URL . "/scripts/profile_view.php?id=$id' target = '_blank'>$name</a></td>
                    <td class='location'>$location</td>
                    <td>$contact</td></tr>

                    ";

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
	return array ($report,$namelist);
}

function pickbest($val,$best,$alt){
	$string = (!isSet( $val ) || empty( $val ) )? "$alt" : $best;
	return $string;
}

function _age($date_str) {
	//takes a date and returns the age from today in days and a formatted version of date
	
	if (!$date_str){ #blank or NULL??
		return array('99999','no_date');
	}
	$DT_now = new DateTime();
	$vd = new DateTime($date_str);
	$diff = $vd -> diff($DT_now);
	$diff_str = $diff->format('%a');
	$last_val = $vd->format ('M j, Y');
	#echo "$date_str, $diff_str, $last_val<br>\n";
	return array ($diff_str,$last_val);
}

//build opportunity report
function prepare_opp_report ($pdo,$ptimes){
	#saves new opps  to news_opps.html; returns text version for teser.
    $newopp_report_h = "";
    $newopp_report_t = '';
   
    $opportunities_html = SITE_PATH. "/news/news_next/news_opportunities.html";
    $dtp = new DateTime($ptimes);
    
    $sql = "
        SELECT title,owner,owner_email,location,created,link
        FROM `opportunities`
        WHERE
        expired > NOW();
        
        ;";
#created > '$ptimes'
#echo "opp sql: $sql <br>\n";

    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $opps = count($result);
   # echo "Got $opps opps to report. ";
    if ($opps  > 0 ){
        $opp_report_h = "<table>
        <tr style='font-size:0.9em;'>
        <th></th><th>Posted</th><th>Description</th><th>Location</th></tr>
        ";
       

        foreach ($result as $row){
            $oppclass=''; $oppnew='';$opp_is_new=false;
            $dtr = new DateTime($row['created']);
   			if ($dtr > $dtp){
                $oppclass='yellow';
                $oppnew='<b>New</b>';
                $opp_is_new = true;
            
                $newopp_report_t .= "    ${row['title']} - ${row['location']}\n";
            }
            $opp_report_h .= "<tr style='font-size:0.9em;'>
            <td>$oppnew</td><td>${row['created']}</td>
            <td>${row['title']}</td><td>${row['location']}</td></tr>";
        }
         $opp_report_h .= "</table>\n";
    }
	if (!empty($opp_report_h)){
		file_put_contents($opportunities_html,$opp_report_h);
	}
    if (!empty($newopp_report_t)){$newopp_report_t =
        "\nNew Opportunities Posted\n--------------------------------\n$newopp_report_t\n";
        
      }
   
    return $newopp_report_t;
}

## NOW get headlines from articles
function prepare_headline_report ($pdo) {
	
	
	
	$sql = "SELECT title,contributor FROM `news_items` WHERE
		status != 'P' and use_me > 0;";
		
	$arts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	$cont = '';
	
	$hl_report = 'News Articles' . "\n------------------------\n";
	foreach ($arts as $article){
		if (! in_array($article['contributor'] , ['FLAMES_editor'])){
			$cont = " (${article['contributor']})";
		}
		$hl_report .= '    ' . $article['title'] . ' ' . $cont . "\n";
	}
	$hl_report .= "\n";
	return $hl_report;
}

function get_start_time($ptime_file){
	// gets last published time from ptime and returns sql date/time
	#is timestamp
	$p_time_s = trim(file_get_contents($ptime_file));
	
	return $p_time_s;
}

