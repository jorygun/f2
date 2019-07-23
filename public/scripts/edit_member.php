<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(6)){exit;}
//END START

//Script displays and updates member record without anyt ancillary actions, other than triggers

$edit_field_list = array(
    'username',
	'status','admin_status',
	'user_email','email_status','no_bulk','prior_email','test_status',
	'linkedin', 'user_amd','user_current','user_web','upwards'
	);
$show_field_list = array(
    'id','user_id','upw','record_updated','status_updated', 'last_login','profile_updated','profile_validated','email_status_time','email_last_validated', 'previous_ems','email_chg_date','join_date',);




?>
<html>
<head>
<title>Edit Member Record</title>
<style type='text/css'>
	table {border:1px solid black; border-collapse:collapse;}
	tr,td {border:1px solid gray;}
	td {vertical-align:top;width:20%;}
	td.data {width:40%;}
	thead {font-weight:bold;}
</style>

</head>
<body>
<h3>Edit Member Record</h3>
<p>This script is used to update a specific member record without causing any
additional actions, except those controlled by automatic triggers.
<br>Call with ?id=nn
</p>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    if (!$id = $_GET['id']){die( "No ?id=n");}
    
    show_form($member, $id,$show_field_list,$edit_field_list);

}

elseif ($_SERVER[REQUEST_METHOD] = 'POST'){
	$UPD= array();
    $id = $_POST[id];

	list($CLEAN,$SAFE) = clear_safe($_POST);
	foreach (array_keys($_POST) as $field){
		if ($SAFE[$field]){
			$UPD[$field] = $SAFE[$field];
		}
		#echo "$field, ";
	}
	update_record_for_id($id,$UPD);

	show_form($member,$id,$show_field_list,$edit_field_list);
}


function show_form($member, $id,$showfields,$editfields){
		$md = $member->getMemberData($id);
    if ($md['count'] > 0 ){
    	$row = $md['data'];
    }
    else {throw new Exception ("No user for id $id");
    }

    echo "<br><hr><form method='POST'>";
    echo "<input type='hidden' name='id' value='$id'>";
	echo "<table>
		<thead><td>Field</td><td  class='data'>Current</td><td '>Change to</td></tr>
		</thead>
		<tbody>\n";

    foreach ($showfields as $field){
        echo "<tr><td>$field</td><td>$row[$field]</td><td>(not editable)</td></tr>\n";
    }



	foreach ($editfields as $field){
		echo "<tr><td >$field</td><td class='data'>$row[$field]</td>
		<td class='data'><input type='text' name='$field'></td></tr>
		\n";
	}


	echo "</tbody></table><input type='submit'></form>\n";
}

?>
</body></html>
