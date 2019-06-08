<?
/*   verify script receives email and get session data from user login  from link on verify email:
		 $GV[siteurl]/scripts/verify_email.php?s=$login&m=$uemenc [ = rawurlencode($user_email) ]

*/


//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	#if (f2_security_below(0)){exit;}
//END START



	$sql_today = sql_now('date');




 ?>
 <!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">

	 <meta name="viewport" content="width=device-width, initial-scale=1">
	 <meta http-equiv="X-UA-Compatible" content="IE=edge">
	 <link rel="apple-touch-icon" href="apple-touch-icon.png">
			<!-- Place favicon.ico in the root directory -->
	 <link rel="stylesheet" href="../css/normalize.css">
	 <link rel="stylesheet" href="../css/main.css">
	 <link rel="stylesheet" href="../css/flames2.css">

	<script src="../js/vendor/modernizr-2.8.3.min.js"></script>

	<script type="text/javascript" src="../js/f2js.js"></script>
<title>FlameSite Profile Verification</title>
<meta name="generator" content="BBEdit 11.0" />


</head>


<?php

	$my_id = $_GET['r'];
	if (!$my_id){die ("No id to verify profile script");}



	$sql = "UPDATE $GV[members_table] SET profile_validated = '$sql_today' WHERE id = $my_id;";

	 $result = mysqli_query($GLOBALS['DB_link'],$sql);
	// if ($_SESSION['DB'][id] = $my_id){
// 		$_SESSION['DB'][profile_updated] = $sql_today;
// 	}
?>
<body onblur='self.close();'>

Your profile has been marked current.

</body></html>

