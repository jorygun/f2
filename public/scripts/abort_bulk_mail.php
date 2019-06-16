<?php

defined ('SITE_PATH') or
	define ('SITE_PATH', '/usr/home/digitalm/public_html/amdflames.org');
	
$abort_file = SITE_PATH . "/abort_mailing";

echo <<<EOT
<html><head><title>Create Abort File</title></head>
<body >
Creating Abort file to halt bulk email<br>

EOT;

if (touch ($abort_file) ){
	echo "File created";
}
else {echo "File creation failed!";}



