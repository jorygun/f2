<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

#test documeent

require 'init.php';

echo "inited" .BRNL;

require '../src/EmsUpdate.php';

$emsu = new EmsUpdate();

$result = $emsu->update_email_status(11602,'LX','Test');
