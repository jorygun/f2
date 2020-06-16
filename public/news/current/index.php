<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
 use DigitalMx as u;

$login->checkLevel(0);

$latest = $container['news']->getLatestIssue();

$latest_url = $latest['url'];
//echo "location:$latest_url"; exit;
header("location:$latest_url");
