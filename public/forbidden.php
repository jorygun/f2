<?php

//BEGIN START
//ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
include_once './init.php';
$nav = new navBar(1);
	$navbar = $nav -> build_menu();

//END START
if (isset ($_SESSION['username'])){
  $member_type = $_SESSION['type'];
	$member_type_name = $_SESSION['typename'];
	$member_status = $_SESSION['status'];
	$member_name = $_SESSION['username'];
}
else {
    $member_type = "None";
    $member_type_name = "none";

    $member_name = "Nobody";

}


$alert_message = "Blocked: You are logged in as a $member_type_name ($member_type), which does not have access to this function. If
	 you believe this is an error, please contact admin@amdflame.org.";

	$stext = addslashes($alert_message);

?>
<html>
<head>
<title>Forbidden</title>
<link rel='stylesheet' href='/css/flames2.css'>

</head>


<body>
<?=$navbar?>

<h1>Sorry, You Can't Do That.</h1>
<p>You have attempted to access a page or function which is not
accessible to your member type (<?= $member_type_name ?>).
</p>
<p>Please use the menu at the left to continue. </p>
<p><b>If you believe
this is an error, please <a href='mailto:admin@amdflames.org'>contact the admin</a></b>.  Sometimes I break stuff.</p>

<hr>


    echo "<script type='text/javascript'>alert("$stext'");</script>";
</body></html>
