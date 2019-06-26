<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(1)){exit;}
//END START
    include_once "verify_utilities.php";

	$test = '';
#	$test = 'NOT'; #NOT means don't update the db. blank means go

	$now = date ("M j, h:i a");
	$timestamp = date('Ymd_his');

  	$log_loc = "/logs/bounce_logs/bouncelog-".$timestamp.".txt"; #log file
  $logfile = "$GLOBALS[sitepath]/$log_loc";

 	

 function show_email_def($sarray){
		
		$t='';
		foreach ($sarray as $s){
			$t .= "<tr><td> $s</td><td>". Defs::getEmsName($s) . "</td></tr>\n";
		}
		return $t;
	}


	$rt=TRUE;

	$fields = array ('id','username','status','admin_status','user_email','email_status','email_status_time');
	$select_fields = '';

	$table_headings = '';
	#do headings
	 $select_fields = '';
	$table_headings = "<tr>";
	 $tformat = "<tr>";
	 foreach ($fields as $k){
		$table_headings .= "<th>$k</th>";
		$tformat .= "<td>%s</td>";
		$select_fields .= "$k, ";
	 }
	 $table_headings .= "<th>Action</th>";
	 $tformat .= "<td>%s</td>";
	$table_headings .= "</tr>";

	 $select_fields = substr($select_fields,0,-2); #lop off the last ,
	 $tformat .= "</tr>\n";



?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
	<head>
		<title>Bounce Processor</title>
		<style type="text/css">
			table {border:2px solid black;border-collapse:collapse;}
			table tr {  border-bottom:1px solid #ccc;}
			tr td {padding-right: 1em;}
		</style>

	</head>

	<body>


<?
	if ($_SERVER[REQUEST_METHOD] == 'GET'){
	echo <<< EOT

	<h3>Bounce Processor</h3>
	<form method = 'post'>
	Enter the emails to process, one per line. You may precede
	the email with a single letter + space: 'H ' for immediate hard bounce to preset
	the outcome choice to LB.<br>
	<textarea rows=10 cols=60 name='bounces'></textarea>
	<input type=submit name='submit' value="Review">
	</form>

EOT;


	}
elseif ($_POST[submit] == 'Review' and $_POST[bounces] <> ''){
		$source = stripslashes($_POST['bounces']);
		$source_array = explode("\n",$source);
		#print_r ($source_array);

		echo  <<< EOF

	<div style="float:left margin:4px;">
	<table>
	<tr><th colspan='2'>Email Status</th></tr>
	<tr><th colspan='2'>Live Stable Settings</th></tr>
EOF;

	 echo show_email_def(array('Y','Q'));
	echo "<tr><th colspan='2'>Dead Stable Settings</th></tr>";
	echo show_email_def(array('LA','LB','LN','LE','LO','XX'));
	echo <<< EOF
	<tr><th colspan='2'>Transitional Settings</th></tr>
	<tr><td>Bn</td><td>Bounce process</td></tr>
		<tr><td>En</td><td>Changed Email process</td></tr>
		<tr><td>Nn</td><td>New signup process</td></tr>
		<tr><td>An</td><td>Schedule validation</td></tr>
	</table>
	<br>
	</div>

	<div style="float:left margin:4px;">
	<table>
	<tr><th colspan='2'><b>On an email bounce</b> </th></tr>
	<tr><td>if user_status = N</td><td>set status to XX (initial validate bounced, delete)</td></tr>

	<tr><td >if em_status = B1</td><td>do nothing for xx days, otherwise revalidate and set to B2</td></tr>
	<tr><td >if em_status = B2</td><td>do nothing for xx days, then set to LB</td></tr>
	<tr><td >Otherwise</td><td>send validate and set to B1</td></tr>
	<tr><td colspan='2' >Note: set to 'XX' to cause both email status and user status to be X.</td></tr>
	</table>
	<br>
		</div>
EOF;

	echo "$info";
	echo "<form action = '$_SERVER[PHP_SELF]' method = 'POST'  style='clear:both;margin:4px;'>";
	echo "<input type=hidden name = 'bounces' value = '$source'>";
	echo "<br><table>";
	echo "$table_headings";
	$count = 0;
		foreach ($source_array as $source){
			$source = trim($source);
			if ($source == ''){continue;}


			// Now get the email out of it.
			preg_match('/^(.\s+)?.*?([\w\.\-]+@[\w\.\-]+)/',$source,$m);
			$email = $m[2];
			$preset = trim($m[1]);
			if (! $email){echo "No email detected in $source<br>"; continue;}
			if (! is_valid_email($email)){echo "$email is not valid<br>";continue;}

			++$count;

			$qv = "SELECT $select_fields FROM $GLOBALS[members_table] WHERE user_email = '$email' AND status NOT LIKE 'X';";
				#if ($rt){print ">>>>>>\$qv: $qv<br><br>\n";}

			 $qr = mysqli_query($GLOBALS['DB_link'],$qv);


			if (mysqli_num_rows($qr) == 0){
					echo "<tr>
					<td></td><td></td><td></td>
					<td>$email</td>
					<td colspan='5' style='color:red'>Email Not Found</td>
					</tr>";
			}
			while ($row = mysqli_fetch_assoc($qr)){
				$values = array ();
				$llog  = $row['last_login'];


				foreach ($fields as $k){$values[] = $row[$k];}
				$id = $row['id'];
				$ems = $row['email_status'];

				list ($ems_age,$sdate)=age($row['email_status_time']);
				$action_note='';

				$clb = $cx=$cxx=$cb1=$cb2=$cc=$cstet='';

				if ($row['status'] == 'N'){
					$cxx = 'checked';
					$action_note = 'New signup. Should be deleted';
				}
				elseif ($preset == 'H'){$clb='checked';}

				elseif ($ems == 'B1'){

					if ($ems_age<7){
						$action_note = 'Status within 7 days No action.';
						$cstet='checked';
					}
					else {
						$action_note = 'Move to B2';
						$cb2='checked';
					}


				}
				elseif ($ems == 'B2'){
					if ( $ems_age < 7){
						$action_note = 'Status within 7 days. No action.';
						$cstet='checked';
					}
					else {
						$action_note = 'Move to B2';
						$clb='checked';
					}
				}
				else {

					$cb1='checked';
				}



				$action = "

					<input type='radio' name='outcome_$id' value= 'B1' $cb1>B1
					<input type='radio' name='outcome_$id' value= 'B2' $cb2>B2
					<input type='radio' name='outcome_$id' value= 'LB' $clb>LB
					<br>
					<input type='radio' name='outcome_$id' value= 'Y' $cy>Y
					<input type='radio' name='outcome_$id' value= 'Q' $cq>Q
					<input type='radio' name='outcome_$id' value= 'XX' $cxx>XX
					<br>
					<input type='radio' name='outcome_$id' value= 'stet' $cstet>No change to ems
					<br>
                    <input type='radio' name='outcome_$id' value='AR'> Admin R
                    <input type='radio' name='outcome_$id' value='A '> Admin cleared

					<br>$action_note";
				$values[] = $action;
				vprintf ("$tformat", $values);

			}
		}
		echo "</table>";
		echo "<p>$count emails in list</p>";
		echo "<input type=radio name='testmode' value='test' >Test Run Only";
		echo "<input type=radio name='testmode' value='real' checked >Update database";
		echo "<br>";
		echo "<input type=radio name='set_all' value='LB'>Mark ALL LB now ";
		echo "<input type=radio name='set_all' value = 'B1'>Mark ALL B1 now ";
		echo "<input type=radio name='set_all' value='AR'>Set Admin Status to R ";
		echo "<input type=radio name='set_all' value='A '>Clear Admin Status";
		echo "<br><input type=submit name='submit' value='Update'>\n";
		echo "</form>";




}

elseif ($_POST[submit] == 'Update') {
		if ($_POST[testmode] == 'test'){$test = 'NOT';}
		elseif ($_POST[testmode] == 'real'){$test = 'Really';}
		else {die ("Error in test mode input");}

		if ($_POST[set_all] <>''){$set_all = $_POST['set_all'];}


		$source = stripslashes($_POST['bounces']);
		$source_array = explode("\n",$source);

		$logh = fopen($logfile,'w');
		fprintf ($logh,"Bounce processor run at %s\n\n",Date ("Y-m-d H:i"));
		#print_r ($_POST);

		// Get the desired new ems values for each id found
		$new_stat = $new_admin = array();

		foreach ($_POST as $key => $val){
				if(preg_match('/outcome_(\d+)/',$key,$m)){
				    $id = $m[1];
				    if (isset($set_all)){$val = $set_all;}

				    /* val sets admin status if first letter is an A,
				    otherwise, it sets the email status to whatever it is.
				    */

                    if (substr($val,0,1) == 'A'){
                        $new_admin[$id] = substr($val,1,1);
                        $new_stat[$id] = 'stet';
                    }
                    else {$new_stat[$id] = $val;}

				}

		}

		// updatethe database with the new values.

		foreach ($new_stat as $id => $new_em_status){

				if ($new_em_status == 'stet'){
					continue;
				}

				$row = get_member_by_id($id);
				echo "$test Updating user $row[username] record $row[id] to email_status $new_em_status <br>\n";

				if ($test == "Really") {
					if ($new_em_status == 'LB'){
						update_record_for_id(
						    $id,array('email_status' => 'LB')
						);
					}
					else{
						send_verify($id,$new_em_status);
					}

				}

				fprintf ($logh, '%s',"Updating user $row[username] record $row[id] to email_status $new_em_status $test\n");

		}
		foreach ($new_admin as $id => $new_admin_status){

				$row = get_member_by_id($id);
				echo "$test Updating user $row[username] record $row[id] to admin_status $new_admin_status <br>\n";

				if ($test == "Really") {
					//update record
					if (empty($new_admin_status)){$new_admin_status='';}
					update_record_for_id(
						    $id,array('admin_status' => $new_admin_status)
						);
				}

				fprintf ($logh, '%s',"Updating user $row[username] record $row[id] to admin_status $new_admin_status $test\n");

		}

	echo "<p><a href='$log_loc' target='_blank'>Show Log</a></p>\n";
}

?>

</body></html>

