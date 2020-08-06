<?php

#test document to make sure cron is working

$dt = new DateTime();
$now = $dt->format('d M y H:i');

$msg =  'This is the crontest.php running at ' . $now . "\n";

$msg .= "ENV:\n";
$msg .= print_r(getenv());

echo $msg;


$home = getenv('HOME');
file_put_contents($home . '/etc/last_crontest.txt' , $msg);


