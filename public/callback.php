<?php
	require_once 'init.php';
	if (security_below(6)){exit;}


// page to show callback

  $callers=debug_backtrace();
	#echo print_r($callers);
	$caller =  $callers[2]['file'];
	preg_match('/.*\/(.*)$/',$caller,$m);
	$file = $m[1];

?>
<html><head>
<title>Callbacks</title>
</head>
<body>
	<h3>Callback</h3>
	<hr>
	<pre>
	$callers
	</pre>

</body></html>
