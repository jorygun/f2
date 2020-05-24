<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLogin(4)){
   $page_title = 'Bulk Bouncer';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START
$bounce_text = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$member_admin = $container['membera'];

	$email_pattern = '/[a-z0-9_\-\+\.]+@[a-z0-9\-\.]+\.([a-z]{2,4})(?:\.[a-z]{2})?(?=\b)/i';
	$bounce_text = $_POST['bouncers'];
	$bounce_array =  array_map('trim',explode("\n", $bounce_text));

	#u\echoR ($bounce_array);

	foreach ($bounce_array as $emline){
	#echo "testing " . htmlentities($emline) . " <br>";
		if (empty($emline)){continue;}
		if (preg_match($email_pattern,$emline,$matches)){
			$em = $matches[0];
			if (! $emv = filter_var($em,FILTER_VALIDATE_EMAIL) ){
				echo htmlentities($em) . " is not valid email" . BRNL;
				continue;
			}

			#echo "Bouncing $emv" . BRNL;

			if ($member_admin->bounce_by_email($emv) ){
				echo "$em bounced" . BRNL;
			}
			else {echo "<p class='red'>Failed to bounce $em" . "</p>";}
		}
		else {
			echo "<p class='red'>No email found in " . htmlentities($emline)  . '</p>';
			continue;
		}
 	}




}
?>

<p>Enter text containing Emails to be bounced, one per line.</p>
<form method='post'>
<textarea name='bouncers' rows=30 cols=80><?=$bounce_text?></textarea>
<input type='submit'>
</form>
