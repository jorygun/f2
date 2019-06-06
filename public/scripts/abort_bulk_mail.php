<?php

defined ('SITEPATH') or
	define ('SITEPATH', '/usr/home/digitalm/public_html/amdflames.org');
	
$abort_file = SITEPATH . "/abort_mailing";

echo <<<EOT
<html><head><title>Create Abort File</title></head>
<body >
Creating Abort file to halt bulk email<br>

EOT;

if (touch ($abort_file) ){
	echo "File created";
}
else {echo "File creation failed!";}



