<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;



if ($login->checkLogin(4)){
   $page_title = 'Bulk Bouncer';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$email_pattern = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
    preg_match_all($email_pattern, $_POST['bouncers'], $matches);
    $bouncers = $matches[0];

	u\echoR ($bouncers,'bouncers');



}
?>

<p>Enter text containing Emails to be bounced, one per line.</p>
<form method='post'>
<textarea name='bouncers'> rows=30 cols=80></textarea>
<input type='submit'>
</form>
