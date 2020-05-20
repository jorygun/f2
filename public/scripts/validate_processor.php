<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	global $G_members;


	if (f2_security_below(1)){exit;}
//END START


	$test = '';
#	$test = 'NOT'; #NOT means don't update the db. blank means go
    $db_members = 'members_f2';
	$now = date ("M j, h:i a");
	$timestamp = date('Ymd_his');

  	$log_loc = "/logs/validation_logs/validated-".$timestamp.".txt"; #log file
  $logfile = "$GV_pathd/$log_loc";



 function show_email_def($sarray){
	
		$t='';
		foreach ($sarray as $s){
			$t .= "<tr><td> $s</td><td>" . Defs::$getEmsName($s) . "</td></tr>\n";
		}
		return $t;
	}

 $count['all']=0;
	$rt=TRUE;

	$fields = array ('id','user_id','username','status','user_email','email_status','email_status_time','last_login');
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
		<title>Validation Processor</title>
		<style type="text/css">
			table {border:2px solid black;border-collapse:collapse;}
			table tr {  border-bottom:1px solid #ccc;}
			tr td {padding-right: 1em;}
		</style>

	</head>

	<body>


<?php
	if ($_SERVER[REQUEST_METHOD] == 'GET'){
	echo <<< EOT

	<h3>Validation Processor</h3>

	<p>This script <b>silently</b> validates emails.</p>
	<p> Accepts emails, text with email in angle brackets, and user ids</p>

	<form method = 'post'>

	Enter the emails OR member id (all numeric) to validate, one per line:<br>
	<textarea rows=10 cols=60 name='validate'></textarea>
	<input type=submit name='submit' value="Review">
	</form>

EOT;


	}


elseif ($_POST[submit] == 'Review' and $_POST[validate] <> ''){
		$source = stripslashes($_POST['validate']);
		$source_array = explode("\n",$source);
		#print_r ($source_array);



	echo <<< EOT
	$info
		<h2>Operating on Database: $db_members</h2>
	<form action = '$_SERVER[PHP_SELF]' method = 'POST'  style='clear:both;margin:4px'>
	<input type=hidden name = 'validate' value = '$source'>

	<br><table>
EOT;
	echo "$table_headings";
		foreach ($source_array as $email){
			$values = array ();

			if (! $email){continue;}
			$email = trim($email);
			if (preg_match('/.*<(.*?@.*)>/',$email,$m)){
				//email in angle brackets
				$email = $m[1];
			}

			// is it a user id instead of an email?
			if (preg_match('/^\d+$/',$email)){
				$id = $email;
				$email = '';
				$row['user_id'] = $id;
			}
			else{
				$row['user_email']=$email;

				if (! is_valid_email($email)){
					foreach ($fields as $k){$values[] = $row[$k];}
					$action="<span style='color:red'>not valid</span>";
					$values[] = $action;
					vprintf ("$tformat", $values);
					continue;
				}
			}

			if ($email){$qv = "SELECT $select_fields FROM $db_members WHERE user_email = '$email' AND status NOT LIKE 'X';";}
			elseif ($id){$qv = "SELECT $select_fields FROM $db_members WHERE user_id = '$id' AND status NOT LIKE 'X';";}
				#if ($rt){print ">>>>>>\$qv: $qv<br><br>\n";}
			$pdo = DigitalMx\MyPDO::instance();
			 $row = $pdo->query($qv)->fetch();

			
				foreach ($fields as $k){$values[] = $row[$k];}
				$action = " <span style='color:red'>not in database</span>";
				$values[] = $action;
				vprintf ("$tformat", $values);
				
			

			
				$values = array ();
				$llog  = $row['last_login'];

				$row['last_login'] = $llog;

				foreach ($fields as $k){$values[] = $row[$k];}

				$id = $row['id'];
				$ems = $row['email_status'];

				list ($ems_age,$sdate)=age($row['email_status_time']);
				$action_note='';

				$cy=$cstet='';

				if ($row['status'] == 'N'){

					$action_note = 'New signup. Send Welcome';
				}

				else {
					$cy='checked';
				}



				$action = "

					<input type='radio' name='outcome_$id' value= 'Y' $cy>Y
					<input type='radio' name='outcome_$id' value= 'XX' $cxx>XX
					<br>
					<input type='radio' name='outcome_$id' value= 'stet' $cstet>No change
					<br>$action_note";
				$values[] = $action;
				vprintf ("$tformat", $values);

			
		}
		echo "</table>";
		echo "<input type=radio name='testmode' value='test' checked>Test Run Only";
			echo "<input type=radio name='testmode' value='real' >Update database";

		echo "<br><input type=submit name='submit' value='Update'>\n";
		echo "</form>";




}

elseif ($_POST[submit] == 'Update') {
		if ($_POST[testmode] == 'test'){$test = 'NOT';}
		elseif ($_POST[testmode] == 'real'){$test = 'Really';}
		else {die ("Error in test mode input");}

		$source = stripslashes($_POST['validate']);
		$source_array = explode("\n",$source);



		$logh = fopen($logfile,'w');
		fprintf ($logh,"Validate run at %s\n\n",Date ("Y-m-d H:i"));
		#print_r ($_POST);


		foreach ($_POST as $key => $val){

				preg_match('/outcome_(\d+)/',$key,$m);
				if($id = $m[1]){
					$new_stat[$id] = $val;

				}
		}



		foreach ($new_stat as $id => $new_em_status){

				if ($new_em_status == 'stet'){
					continue;
				}

				$row = get_member_by_id($id);
				echo "$test Updating user $row[username] record $row[id] to email_status $new_em_status $u_status_too <br>\n";

				if ($test == "Really") {
					set_mu_status($id,$new_em_status);

				}

				fprintf ($logh, '%s',"Updating user $row[username] record $row[id] to email_status $new_em_status $test\n");

		}
	echo "<p><a href='$log_loc' target='_blank'>Show Log</a></p>\n";
}

?>

</body></html>

