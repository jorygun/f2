<?php

namespace DigitalMx\Flames;

ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = 'Set Admin Status';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();

//END START
/*
This script adds an admin status code to each user identified in the
file 'uname_list.txt'.
This sets a code that can be used to pull records for bulk email.

It initially sets the admin_status to '' on all records; then sets the
code for any username appearing the txt file.

*/



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$unames = texttolist($_POST['names']);
$acode = $_POST['acode'] ?? '';
if (empty($acode)){die ("no admin code specified");}

$sql = "UPDATE members_f2 set admin_status = '$acode' WHERE username = ? ";
$sqlupd = $pdo->prepare($sql);

$sqlget = $pdo->prepare("SELECT user_id from members_f2 where username = ? ");

$pdo->query("UPDATE members_f2 set admin_status = '' ");
$success = 0;

foreach ($unames as $uname){
	//echo "Getting $uname... ";
	$uname = trim($uname);
	if (! $sqlget -> execute([$uname]) ) {die ("oops");}
 	if (!$uid = $sqlget->fetchColumn() ) {
 		echo "User $uname not found" . BRNL;
 		continue;
 	}
 	//echo "found" . BRNL;

	if (! $sqlupd->execute([$uname]) ){
		echo "Failed on $uname" . BRNL;
	} else {
		echo "Updated $uname" . BRNL;
		++ $success;
	}
}
echo BR . "$success users set.  All other users set to admin_status = ' '. " . BRNL;
exit;
}

function texttolist($text) {
	if (empty($text)){die ("No names in text");}
	$nlist = explode("\r",$text);
	$nlistt = array_filter(array_map('trim',$nlist));

	if (empty($nlistt)){die ("No names in list");}
	$ncnt = count ($nlistt);
	echo "$ncnt names in list" . BR . BRNL;
	return $nlistt;
}

?>

<form method='post'>

<p>Use this to set an admin status code for a list of user names. The primary use of this is to set a list of users for a bulk email list. </p>
<p>Enter the desired single character <b>admin_status code</b> here:
 <input type='text' name='acode' size='2' maxsize='1' value='G'> </p>

Then enter a list of usernames, one name per line, in the textarea below. The listed names will be set to admin_status you specify, if found. All other
users will be set to an empty admin_status.  </p>

<b>Usernames:</b> <br><textarea name='names' rows='30' cols='60'></textarea>
<br>
<input type=submit>
</form>



