<?php
// script to validate emails for users on list

namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	#require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
	require_once '../crons/cron-ini.php';
	
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	



	
//END START
$list = $argv[1] ?? '';
if (empty($list )){
    die ("No file specified\n");
}

use digitalmx\MyPDO;
use digitalmx\flames\Member;

$pdo = MyPDO::instance();
$member = new Member();
$found = 0;

$lh = fopen($list,'r');
$sql_get = "SELECT user_id from `members_f2` WHERE user_email = ?";
$sql_get_prep = $pdo->prepare($sql_get);


 while(! feof($lh))  {
	$em = trim(fgets($lh));
	if (empty($em)){continue;}
	
	if (filter_var($em,FILTER_VALIDATE_EMAIL) === false){
		echo "Invalid email $em\n";
		continue;
	}
	$sql_get_prep->execute([$em]);
	$em_count = $sql_get_prep ->rowCount();
	if ($em_count != 1){
		echo "Got $em_count entries for email $em.\n";
		continue;
	}
	$uid = $sql_get_prep->fetchColumn();
	
	++$found;
	
	
	if ($r = $member->verifyEmail($uid)){
		echo "User $uid updated\n";
	}
	else {echo $r . "\n";}
	
	#if ($found > 3){exit;}
	
	
	
  }

  fclose($lh);
echo $found . " Found emails\n\n";

