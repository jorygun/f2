<?php

$site_dir = dirname(__DIR__);
$sites_dir = substr($site_dir,0,strpos($site_dir,'Sites')) . '/Sites';
echo "
proj_dir: $site_dir<br>
sitesdir: $sites_dir
<br>\n";

#define paths
define ('SITE_PATH' , $site_dir); #this project site directory
define ('SITES', $sites_dir);
define ('SITE' , $_SERVER['SERVER_NAME']);
define ('SITEURL', "http://" . SITE);
define ('CONF', SITE_PATH . "/config");



