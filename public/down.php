<?php

#copy this page to index-down.php to bring the site down.

function down_notice (){

	
	$msg = "Notice: 
	amdflames.org is currently down. 
	Access was attempted at " . date('h:i a') . "\n"
	. "From: " . $_SERVER['REMOTE_HOST'] . "\n"
	. " Query: " . $_SERVER['QUERY_STRING'] . "\n"
	. "URI: " . $S_SERVER['REQUEST_URI']
	. "\n";
	
	mail ('admin@amdflames.org','Site down notice', $msg);
}

$mod_date = date('M d h:i a T',filemtime(__FILE__) );

down_notice();

?>
<html>
<head>
<title>AMD Flames is Down</title>
</head>
<body style='text-align:center;'>
<div style="border:4px solid red;padding:5px; width:800px; margin:50px;">
<h2>Oh No!</h2>
<p>The Site is Down because apparently I broke something.  :-( <br />
-- <a href='mailto:admin@amdflames.org'>The Error-Prone Admin</a><p>
<p id="update"><?=$mod_date?></p>

<br />
<img src='/graphics/code_toon.jpg' style='margin-left:auto;margin-right:auto;'>
<br />
</div>
</body></html>
