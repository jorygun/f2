<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(-1)){exit;}
//END START

// used to send login codes to an email address.


		global $GV;
//END START


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$this_id = $_GET['id'];


}
$now = sql_now('date');

$sql = "UPDATE $GLOBALS['members_table'] SET contributed = '$now' WHERE id = $this_id;";


?>
<html>
<head>
<title>Setting Contribution date to $now</title>
</head><body onblur="self.close()">
<?
echo "<p><tt>$sql</tt></p>";
 $result = mysqli_query($GLOBALS['DB_link'],$sql);
if ($result){echo "User contribution marked for $now";}
else {die ("Contributon entry failed");}




?>
</body></html>
