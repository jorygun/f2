<?php

/*  STARTUP 
Does not use init/boot
Does not send session

*/

if (defined ('INIT')){return;} #already ran
else {$init=true; } #tentative

try {
	ini_set('display_errors', 1);

	$opts = getopt('tq',['repo:']);
	#var_dump($opts);
	$repo = $opts['repo'] ?? 'live';
	$test = isset($opts['t']) ? true:false;
	$test_state = $test ? 'true' : 'false';
	$quiet = isset($opts['q']) ? true:false;
	$quiet_state = $quiet ? 'true' : 'false';



	define ('REPO_PATH', dirname(__DIR__) ); # script is in .../project/crons
	define ('PROJ_PATH', dirname(REPO_PATH));
	echo "Project: " . PROJ_PATH . "\n";
	
	$platform=getPlatform();
	
	
	define ('REPO_PATH',PROJ_PATH . "/$repo");
	define ('SITE_PATH', REPO_PATH . "/public");
	define ('SITE', $_SERVER['SERVER_NAME']);
	define ('SITE_URL', 'https://' . SITE);


	ini_set('include_path', 
					PROJ_PATH . '/libmx'
		. ':' . REPO_PATH . '/config'
		. ':' . REPO_PATH . '/src'
	);

	require 'MxConstants.php'; #in libmx: NL, BRNL, etc.
	require_once PROJ_PATH . '/vendor/autoload.php';
	
	require 'Definitions.php';  #config is in path
#	use \digitalmx\flames\Definitions as Defs;

	require 'MxUtilities.php'; #in libmx
#	use digitalmx as dmx; #utils called with dmx\echor();
	
	require "SiteUtilities.php";
	

	require 'MxPDO.php';
	$pdo = new \digitalmx\MxPDO (PROJ_PATH . '/config/db.ini');
	
}catch (Exception $e){
	echo 'Error: '
	.$e->getMessage() .  BRNL;
	$init = false;
}


if (!$quiet)
echo 
	"[Cron-start on Site: " . SITE . "(platform $platform) " . BRNL
	. "   Repo: $repo" . "; Test: " . $test_state . "; Quiet: " . $quiet_state .  "]" . BRNL;

if ( $init){
	define ('INIT',1);
} else {
	echo "*****  INIT FAILED  *******" . NL;
	return;
}
function getPlatform(){
	// using PWD because it seems to alwasy work, even in cron
		$sig = $_SERVER['DOCUMENT_ROOT'];
		$sig2 = getenv('PWD');
		if (
			stristr ($sig,'usr/home/digitalm') !== false 
			|| stristr ($sig2,'usr/home/digitalm') !== false 
			) {	
				$platform = 'pair';
		} elseif (
			stristr ($sig,'Users/john') !== false 
			|| stristr ($sig2,'Users/john') !== false 
			) {	
				$platform = 'ayebook';
		} else {
				throw new Exception( "cron ini cannot determine platform from ROOT '$sig' or PWD '$sig2'");
		}
		return $platform;
	}
  
