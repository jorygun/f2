<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(-1)){exit;}
//END START

// used to send login codes to an email address.


//END START

if (isset($_GET['email'])){$this_email =  $_GET['email'];}
elseif (isset($_POST['email'])){$this_email =  $_POST['email'];}
else {$this_email = '';}

?>
<html>
<head>
<title>Sending Lost Link</title>
</head><body onblur="self.close()">
<?


$sender = new digitalmx\flames\SendLogin($pdo);
$sender->sendLink($this_email);


