<?php

function setGlobals () {
    #sets up values in GLOBAL array

    $site = 'amdflames.org';
    $sitesub = ''; #subdirectory within sitename if any
    $homepath      =   getenv('HOME'); #path to user home dir
#    echo "Homee: $homepathe <br>\n";

    $homepath = '/usr/home/digitalm';


    $sitepath = '/usr/home/digitalm/public_html/amdflames.org';

    $my_members = 'members_f2';
    $my_assets = 'assets';
    $admin  =   'AMD FLAME site Admin <admin@amdflames.org>';
    $my_db = 'digitalm_db1';

    $vars = array (

		'site'	=>	$site,
		'siteurl' => "http://$site",


		'homepath'	=>	$homepath, #path to user home dir
		'sitepath'	=>	$sitepath,
		'db'	=>	$my_db,
		'members_table'	=>	"$my_db.$my_members",
		'admin'		=> $admin,
		'from_admin'	=>	"From: $admin\r\n",
		
		'logs' => 	"$sitepath/logs",
		'abort_mailing' => "$sitepath/abort_mailing.txt",
		'navbar' 	=> "$sitepath/navbar_div.php",
		'assetdb' => "$my_assets",
		'assetdir' => "$sitepath/assets"
	);
    return $vars;
}  
