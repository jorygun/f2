<?php
require 'init.php';

#testing email status changes, to test sweep function

?>
<html>
<head>
<title>Emai Status Test</title>
</head>
<body>
<h3>Email Status Test</h3>
Script changed email status and calls update_email_status
to test email notices.
<hr>

<?
$id = '4950';
if ($_SERVER[REQUEST_METHOD] == 'POST'){
	post();
}

show_form($id);

exit;

function show_form($id){
	$result = query("SELECT email_status from members_f2 where id='$id';");
	$row = mysqli_fetch_assoc($result);
	$oldstatus = $row[email_status];

	echo <<<EOT
	<div>
	<form method=post>
	<input type='hidden' name='id' value='$id'>
	New Email Status <input type='text' name='newstatus' value='$oldstatus'>
	<br>
	<input type='submit'>
	</form>
	</div>
EOT;
}

function post(){
	$newstatus = $_POST[newstatus];
	$id = $_POST[id];
	echo "Posting $newstatus to $id<br>";
	update_email_status($id,$newstatus);



}
?>
