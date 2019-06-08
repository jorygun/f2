<?php

#init file at known spot in web root to run the bootup file.
$dir = dirname(__DIR__);
echo "including $dir/config/boot.php";

include_once("$dir/config/boot.php");
