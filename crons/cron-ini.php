<?php

/*  STARTUP 
Does not use init/boot
Does not send session

Opens required files
creates 
$pdo object

*/

if (defined ('INIT')){return;} #already ran
else {$init=true; } #tentative

ini_set('display_errors', 1);

	$opts = getopt('tq',['repo:']);
	#var_dump($opts);

define ('REPO_PATH' , dirname(__DIR__) );
define ('PROJ_PATH', dirname(REPO_PATH) ); # script is in .../project/crons
$repo = basename(REPO_PATH);

// get platfomr first
if (stristr(PROJ_PATH,'/usr/home/digitalm') !== false) {
		$platform = 'pair';
		$site = 'amdflames.org';
		
	} else {
		$platform = 'ayebook';
		$repo = $opts['repo'] ?? 'f2';
		$site = 'f2.local';
	}
	echo "Platform: $platform; $site, $repo \n";
try {
	
	
	$test = isset($opts['t']) ? true:false;
	$test_state = $test ? 'true' : 'false';
	$quiet = isset($opts['q']) ? true:false;
	$quiet_state = $quiet ? 'true' : 'false';



	
	define ('SITE_PATH', REPO_PATH . "/public");
	define ('SITE', $site);
	define ('SITE_URL', 'http://' . SITE);



	if (empty($site_ini = parse_ini_file(REPO_PATH . '/config/config.ini') )){
		throw new Exception("Cannot open site ini file");
	}
	
	

	ini_set('include_path', 
		'.'
		. ':' . PROJ_PATH . '/libmx'
		. ':' . REPO_PATH . '/config'
		. ':' . REPO_PATH . '/code'
		. ':' . REPO_PATH . '/src'
	);

	require 'MxConstants.php'; #in libmx: NL, BRNL, etc.
	require PROJ_PATH . '/vendor/autoload.php';
	require 'Member.php';
	require 'Messenger.php';
	require 'Definitions.php';  #config is in path
#	use \digitalmx\flames\Definitions as Defs;
	require 'MxUtilities.php'; #in libmx
	
	require "SiteUtilities.php";

	// require 'MxPDO.php';
// 	$pdo = new \digitalmx\MxPDO ('production',$platform,PROJ_PATH . '/config/db.ini');
	require 'MyPDO.class.php';
	$pdo = \MyPDO::instance();
	
}catch (Exception $e){
	echo 'Error: '
	.$e->getMessage() .  NL;
	$init = false;
}


if (!$quiet)
echo 
	"[Cron-start on Site: " . SITE . "(platform $platform) " . NL
	. "   Repo: $repo" . "; Test: " . $test_state . "; Quiet: " . $quiet_state .  "]" . NL;

if ( $init){
	define ('INIT',1);
} else {
	echo "*****  INIT FAILED  *******" . NL;
	return;
}


