#!/usr/local/bin/php
<?php
 set_time_limit(86400);
#script to run the bulk mail queue if it exists
$sitepath = '/usr/home/digitalm/public_html/amdflames.org';

$queue = "$sitepath/bulk_queue";
$brun = "$sitepath/scripts/bulk_mail_run.php";
$clog = "$sitepath/logs/bulk_mail_logs/cronlog";

$starttime = time();
    $startdate = date('Y-m-d H:i',$starttime);
#$clog = '/dev/null/';

echo "Starting run_queue at $startdate.\n";
if (file_exists($queue)){
	echo "Found queue\n";
	$q = fopen($queue,'r') or die ("Can't read queue");
	while (($mypid = fgets($q)) !== false){

		$bjob = "/usr/local/bin/php $brun $mypid > $clog &";
		echo "Starting job $mypid .\n";
		exec ($bjob) or die ("bjob failed\n$bjog\n");

	}
	fclose ($q);
	if (1){
	    unlink ($queue);
	     echo "bulk_queue deleted!\n";
	}
	else {
     echo "bulk_queue NOT deleted!\n";
    }
	#unlink ($queue);
}
else {echo " No bulk_queue";}

?>
