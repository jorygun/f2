<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
 use DigitalMx as u;

$login->checkLevel(0);

$latest = $container['news']->getLatestIssue();
//u\echor($latest); exit;
$latest_url = $latest['url'];

// if latest newsletter doesn't exist, switch to home page
if (file_exists(SITE_PATH . $latest_url)) {
//echo "location:$latest_url"; exit;
	header("location:$latest_url");
} else {
	echo "The newsletter $latest_url is not present on this site." . BRNL;
}

