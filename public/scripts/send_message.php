<?php

//BEGIN START
	require_once 'init.php';
	if (f2_security_below(1)){exit;}
//END START



$senderid = $_SESSION[recid];
$sender_data = get_member_by_id($senderid);
$sender_name = $sender_data['username'];
$sender_info = "$sender_name <$sender_data[user_email]>";
$sender_info_h = h($sender_info);



if ($_SERVER[REQUEST_METHOD] == 'POST'){


	$recipient_id = $_POST['recip'];
	$recip_data = get_member_by_id($recipient_id);
	$recip_name = $recip_data['username'];
	$recip_email = $recip_data['user_email'];

	$recipient_to = "$recip_name <$recip_email>";
	$recipient_to_h = h($recipient_to);

	$from = $GLOBALS['from_admin'];
	$from_h = h($from);
	$subject = "Message from $sender_name sent via the AMD FLAMEsite";
	$clean_message = stripslashes($_POST['message']);
	$message = <<< EOT
	This is an email message sent to you using the AMD FLAMEsite system.
	It's from: $sender_info

	-------------------------------------------------------------------------
	$clean_message
	-------------------------------------------------------------------------
EOT;

 	$display = "\n$from\nTo: $recipient_to\nSubject: $subject\n\n";
	$display .= "Message:\n$message\n\n";


	mail($recipient_to,$subject,$message,$from);
echo "Message Sent<br>";
echo '<pre>',h($display),'</pre>';
	exit;

}

else{
	$recipient_id = $_GET[r];
$recipient_name = $_GET[n];
if (!$recipient_id){die ("No id supplied to send_message");}


?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="iso-8859-1" />

	 <meta name="viewport" content="width=device-width, initial-scale=1">
	 <meta http-equiv="X-UA-Compatible" content="IE=edge">
	 <link rel="apple-touch-icon" href="apple-touch-icon.png">
			<!-- Place favicon.ico in the root directory -->
	 <link rel="stylesheet" href="../css/normalize.css">
	 <link rel="stylesheet" href="../css/main.css">
	 <link rel="stylesheet" href="../css/flames2.css">

	<script src="../js/vendor/modernizr-2.8.3.min.js"></script>
	<script src="../js/f2js.js"></script>

	<style type="text/css">


		</style>


		<title>FlameSite Message</title>
		<meta name="generator" content="BBEdit 11.0" />


</head>
<body >
<h1>FLAMESite Messenger</h1>
You can send a message to any member using this tool.
The recipient will receive your name and email along with the message.


<form method = "POST">
<input type=hidden name="recip" value = "<?=$recipient_id?>" >
<p>Sending a message <br>to: <?=$recipient_name?><br>From you: <?=$sender_info_h?></p>
<p>Enter your message here: (use carriage returns to keep lines from getting too long.)</p>
<textarea name="message" rows="10" cols="80">
</textarea>
<br>
<input type=submit name=submit value="Send">
</form>
</body></html>
<?
}
?>
