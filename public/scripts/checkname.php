<?php
// thIS SCRIPT NOT CURRENTLY IN USE

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(8)){exit;}
//END START



#uncomment to override default database
	#	$GV[members_table] = 'bigb8480_db1.beta';


//useful php functions
function HTP_tablerow($data,$instruction,$names){
	//$names is a one dimmensional array of variable names
	//$data is a two-dimmensional aassoc array of names and values
	//$instruction is 'names' or 'values' to print
	// if $names is empty, the print all in data

	if ($names ){$ar_names=$names;}
		else {$ar_names = array_keys($data);}


	$row_string = '<tr>';
	if ($instruction == 'names'){
		foreach ($ar_names as $name){
			$row_string .= "<th>$name</th>";
		}
	}
	elseif ($instruction == 'values'){
		foreach ($ar_names as $name){
			$row_string .= "<td>$data[$name]</td>";
		}
	}
	$row_string .= '</tr>';
	return $row_string;

}
//
?>
<head>

<title>List Matching Names</title>
<style type="text/css">
	table {border-collapse: collapse;}
	tr td,th {border:1px solid black;padding:3px;vertical-align:top;}
	tr.y_row {background:#ff0;}
	tr.condense {height:4em;}
</style>

<script type="text/javascript">


</script>

</head>

<body onfocus='profile.close()';> <!-- Close any open Profile window-->
<h3>Matching Names</h3>


<?

/*
 Search DB for appended last name and list results
 e.g. checkname.php?s=Smith

*/

//
// get vars
	$email = $_GET['e'];
	$target_id = ($_GET['id']);
		 if (!$target_id){ $target_id = $_POST['id'] ;}

	$urlname = $_GET['name'] ;
 		if (!$urlname){ $urlname = $_POST['name'] ;}
	$name = trim(rawurldecode($urlname));

	$last_name = parse_name ($name,'last');


// query for the target record
	$sql1 = "
	 SELECT *
	FROM $GLOBALS[members_table]
	WHERE id = $target_id
	;
	";
EOT;


// query for the matching records
	$sql2 = "
	 SELECT *
	FROM $GLOBALS[members_table]
	WHERE (username = '$name' or username LIKE '%${last_name}%' or user_email LIKE '$email') AND id <> '$target_id'
	ORDER BY id
	LIMIT 20;
	";

#echo "<p>target: $sql1<br>matches: $sql2</p>\n";

// Start a display table

  print <<<EOT
  <br>
  <form action="$_SERVER[PHP_SELF]" method="POST">
  <input type='hidden' name='id' value='$target_id'>
  <input type='hidden' name='name' value='$urlname'>

  <table border='1' cellpadding='2' cellspacing='0'>

EOT;
// Get headings
	$cn_fields = array(
		'username','status','status_updated','user_email','prior_email','email_status','admin_note','profile_updated');


			//print heading row
				echo '<tr>';
				foreach (array('act on','merge','edit','login') as $v){
					echo "<th>$v</th>";
				}
				foreach ($cn_fields as  $v){
					echo "<th>$v</th>";
				}
				echo "</tr>\n";




//Get target record   #skip this if id not supplied; just get all matching rows
if ($target_id){
	$this_row=0;
	 $result1 = query($sql1);$num_rows1= mysqli_num_rows($result1);
	 echo "target: $sql1<br> $num_rows1<br>\n";
	 if (mysqli_num_rows($result1) == 0) {print "Error: Target not found"; exit;}
	  while($row  = mysqli_fetch_assoc($result1)){
		  ++$this_row;

		  $welcome_pointer="<a href='send-welcome.php?id=$id'>Send Welcome</a>";
		  $target_email = $row[user_email];
		  if ($target_email){$show_email = "<a href='mailto:$target_email'>$target_email</a>";}
		  else {$show_email = '';}
		$id = $row['id'];
		$login = $row['upw'] . $row['user_id'];

		$target_status = $row['status'];
			// now show target data
				echo "<tr class='y_row'>";

				echo "<td><a href='update_member.php?id=$id' target='$row{'username'}'>Update Member</a></td>";

				echo "<td><input type=checkbox name='merge' value='$id'></td>";
				echo "<td><a href='edit_member.php?id=$id' target='_blank'>Edit Member Record</a></td>";
				echo "<td><a href='$GLOBALS[siteurl]?s=$login' target='_blank'>Log In As</a></td>";

				foreach ($cn_fields as $k){
					echo "<td>$row[$k]</td>";
				}
				echo "</tr>\n";

	  }


}
	// Loop through matches

	$thisrow = 0;
	 $result2 = query($sql2);$num_rows2= mysqli_num_rows($result2);
	  if (mysqli_num_rows($result2) == 0) {print "</table><h3>No other matches found</h3>"; exit;}
	  echo "<tr><td colspan=5><b>Matching records</b></td></tr>\n";

 	while ($row = mysqli_fetch_assoc($result2))  {
		$row_style="w_row"; // Highlight the record of interest
		$welcome_pointer="send-welcome.php?id=$row[id]";
		$usermail = $row['user_email'];

		if ($usermail){$show_email = "<a href='mailto:$usermail'>$usermail</a>";}
 			else {$show_email = '';}
		$id = $row['id'];
		$login = $row['upw'] . $row['user_id'];

		echo "<tr class = 'condense'>";
		echo  "<td><a href='update_member.php?id=$id' target='$row{'username'}'>Update Member</a><br>";

		echo "<td><input type=checkbox name='merge' value='$id'></td>";
		echo "<td><a href='edit_member.php?id=$id' target='_blank'>Edit Member Record</a></td>";
		echo "<td><a href='$GLOBALS[siteurl]?s=$login' target='_blank'>Log In As</a></td>";


		foreach ($cn_fields as $k){
			echo "<td>$row[$k]</td>";
		}
		echo "</tr>\n";
		++$thisrow;
	}






// Close the display table
  print "</table>";

  echo "</form>\n";

// Leave

	 mysqli_close($DB_link);
 exit;
?>
</body>
</html>
