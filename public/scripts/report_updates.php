<?PHP

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(4)){exit;}
//END START

/* Script to retrieve recenbt updates to user data and produce html for newsletter
	Also generates calendar file from the events source file to incorporate
	into newsletter.
	Also produces "teaser" file which is plain text names and events
	to include in newsletter.

*/


 $nowsql = date("Y-m-d H:i:s"); #sql-ready
 $nowh	=	date('M d, Y'); #human ready
 $pdo = MyPDO::instance();

 global $G_member_status_set;
 $member_status_set = $G_member_status_set;
#$G_member_status_array = array('M', 'MA','MN','MC','MU','R','G');

 $event_table = SITEPATH . "/news/events.txt";
 $rtime_file = SITEPATH . "/news/last_update_run.txt";
 $ptime_file = SITEPATH . "/news/last_update_published.txt";
 $updates_html=SITEPATH . "/news/news_next/updates.html";
 $headline_file = SITEPATH . "/news/news_next/headlines.txt";


 //teasers for newsletter and home page.  Created in news_next.
 // Can be retrieved from news_latest after publish.
 $updates_text = SITEPATH . "/news/news_next/updates.txt";
 $opportunities_text=SITEPATH . "/news/news_next/opportunities.txt";
 $opportunities_html=SITEPATH . "/news/news_next/opportunities.html";

 //this file has all open opps in it.


//Find date to begin looking from //
if($ptime = $_GET['ptime']){
    $ptime1 = strtotime($ptime);
    $p_time = date('Y-m-d H:i:s', $ptime1);
}
if (!$p_time){ #get from last published file
    $p_time = get_start_time();
}
if (!$p_time){die ("No p_time found ");}
$php_ptime = strtotime($p_time);
$nice_ptime = date('M d, Y',$php_ptime);

/* List All Updates Since Last Newsletter */

$hcode = "
<!-- show_updates.php run at $nowsql  -->

";

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

$hcode .= "";
$name_list = array();
$name_fields = "username,user_amd,user_current,user_from,id, user_greet,user_about,join_date, user_email,email_hide,email_status,profile_validated";

//Get New Members
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status != 'M'
	AND join_date > '$p_time'
	ORDER BY username;
	";
	echo ("q: $q<br>");
	$stmt = $pdo->query($q);
	$new_member_count = $stmt->rowCount();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'new');
	$hcode .= $report_data;
	$name_list = array_merge($name_list,$name_data);


//Get Member Updates /., excluding new members
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND profile_updated > '$p_time'
	AND  join_date <= '$p_time'
	AND username not like 'Flames %'
	ORDER BY username
	";

	 $result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);


	list ($report_data,$name_data) = report_changes($result,'updates');
	$hcode .= $report_data;
	$name_list = array_merge($name_list,$name_data);


// GET NEW EMAILS  (email changes are not profile cha nges)
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND email_chg_date > '$p_time'
	AND join_date <= '$p_time'
	ORDER BY username";

	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'email');
	$hcode .= $report_data;
	$name_list = array_merge($name_list,$name_data);


// deceased
	$q = "SELECT $name_fields FROM members_f2
	WHERE status_updated > '$p_time'
	AND status like 'D'
	AND test_status = ''

	";
	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'deceased');
	$hcode .= $report_data;
	$name_list = array_merge($name_list,$name_data);

// new lost
	$q = "SELECT $name_fields FROM members_f2
	WHERE status in ($member_status_set)
	AND test_status = ''
	AND email_status_time >= '$p_time'
	AND email_status like 'L%'
	AND previous_ems not like 'L%'
	ORDER BY username
	";
	$result = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);

	list ($report_data,$name_data) = report_changes($result,'lost');
	$hcode .= $report_data;
#	$name_list = array_merge($name_list,$name_data);



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
	$hcode .= $report_data;
	#$name_list = array_merge($name_list,$name_data);




$update_message = <<< EOT

<div class='update_data'>
<h3>FLAMEs membership</h3>
<p>As of $nowh. Updates since $nice_ptime.<br>
Active: $active_members; Lost: $lost_members; Total: $total_members.
</p>

<p>View a member's profile by clicking on their names.<br><br>
Lost members are ones that we have no email for, or whose email has
repeatedly bounced.  A sample of "long lost" members is shown at
the bottom.  If you have any information about any of our "Lost"
members, please let me know and I'll try to update their
information.</p>



EOT;

$hcode = $update_message . $hcode . "</div>\n";
file_put_contents($updates_html, $hcode);


// now prepare name report

	if ($name_list){
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
	}

// prepare teaser report
	$teaser_report = '';
	$teaser_report .=  $name_report;
	file_put_contents($updates_text,$teaser_report);


//build opportunity report
    $newopp_report_h = "";
    $newopp_report_t = '';
   // $sql = "
   //      SELECT title,owner,owner_email,location,created,link
//         FROM opportunities
//         WHERE created > '$p_time'
//         AND
//         (expired = '0000-00-00' OR expired > NOW())
//         ;";
//
    $sql = "
        SELECT title,owner,owner_email,location,created,link
        FROM opportunities
        WHERE
        (expired = '0000-00-00' OR expired > NOW())
        ORDER BY
        created desc
        ;";


    $results = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $opps = count($result);
    if ($opps  > 0 ){
        $opp_report_h = "<table>
        <tr style='font-size:0.9em;'>
        <th></th><th>Posted</th><th>Description</th><th>Location</th></tr>
        ";
        $p_time_tics = strtotime($p_time);

        foreach ($results as $row){
            $oppclass=''; $oppnew='';$opp_is_new=false;
             $created_tics = strtotime($row['created']);

            if ($created_tics > $p_time_tics) {
                $oppclass='yellow';
                $oppnew='<b>New</b>';
                $opp_is_new = true;
            }
            if ($opp_is_new){
                $newopp_report_t .= "${row['title']} - ${row['location']}\n";
            }
            $opp_report_h .= "<tr style='font-size:0.9em;'>
            <td>$oppnew</td><td>${row['created']}</td>
            <td>${row['title']}</td><td>${row['location']}</td></tr>";
        }
        $opp_report_h .= "</table>\n";
    }

    if (!empty($newopp_report_t)){$newopp_report_t =
        "\nNew Opportunities Posted\n--------------------------------\n$newopp_report_t\n";
        file_put_contents($opportunities_text,$newopp_report_t);
      }
    if (!empty($opp_report_h)){
        file_put_contents($opportunities_html,$opp_report_h );
    }



    echo "Listed $new_opps opportunities<br>";

    if (file_exists($headline_file)){
        $headline_report = file_get_contents($headline_file);
    }
    else {$headline_report = 'Headlines not prepared' ."\n";}

// update the last run file
$ph = fopen($rtime_file,'w');
fprintf ($ph,"Run at %s\n",$nowsql);
fclose ($ph);

?>


<html><head><title>Show Updates</title>

</head><body>
<p>Showing updated since <?=$p_time?></p>
<h3>Update HTML Version</h3>
<?= $hcode ?>
<hr>
<h3>Update Text Version</h3>
<pre>
<?= $teaser_report ?>
</pre>
<hr>
<h3>Opportunity Reports</h3>
<?=$opp_report_h ?>
<hr>
<pre>
<?=$newopp_report_t ?>
</pre>
<hr>
<h3>Headlines if run now</h3>
<pre>
<?= $headline_report ?>
</pre>
</body></html>

<? function report_changes (&$result,$type){
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
				$joined = age($row['join_date'])[1];
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
                    <td class='username'><a href= " . SITEURL . "/scripts/profile_view.php?id=$id' target = '_blank'>$name</a></td>
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

function get_start_time(){
	// gets last published time from ptime and returns sql date/time
	$ptime_file = SITEPATH . "/news/last_update_published.txt";
	$p_time_s = trim(file_get_contents($ptime_file));
	if(preg_match('/.*?([\d\:\-]+ [\d\:\-]+)/',$p_time_s,$m)){
	    $p_time = ($m[1]);
	}
	#echo "p_time found: $p_time<br>";
	else {die ("No valid p_time $p_time_s");}
	return $p_time;
}

