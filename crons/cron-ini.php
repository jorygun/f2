<?php
namespace digitalmx\flames;

/*  STARTUP
Does not use init/boot
Does not send session

Opens required files
creates
$pdo object
Modeled after init.php

*/

if (defined ('INIT')){return;} #already ran
else {$init=true; } #tentative



	$opts = getopt('tq',['repo:']);
	/* looks for start up options
		-q = quiet
		-t = test mode
		--repo <repo-name>
	*/

	#var_dump($opts);


use digitalmx\flames as f;
use digitalmx as u;

// get platfomr first
$platform = setPlatform();
$paths = setPaths($platform);
$repo = basename($paths['repo']);

if ($platform == 'ayebook') $site = 'f2.local';
elseif ($repo == 'live') $site = 'amdflames.org';
else $site = "${repo}.amdflames.org";

// $site = ($platform == 'ayebook')? 'f2.local' :
// 	($repo == 'live') ? 'amdflames.org' :
// 	"$repo.amdflames.org";

	define ('REPO', $repo);
	define ('SITE',$site);
	define ('SITE_URL', 'http://' . $site);
	define ('PLATFORM',$platform);

setConstants($paths);

	$test = isset($opts['t']) ? true:false;
	$test_state = $test ? 'true' : 'false';
	$quiet = isset($opts['q']) ? true:false;
	$quiet_state = $quiet ? 'true' : 'false';

	ini_set('display_errors', ! $quiet);



	ini_set('include_path',
		'.'
		. ':' . REPO_PATH . '/libmx'
		. ':' . REPO_PATH . '/config'
		. ':' . REPO_PATH . '/code'
		. ':' . REPO_PATH . '/src'
	);

try {
	require 'MxConstants.php'; #in libmx: NL, BRNL, etc.
	require REPO_PATH . '/vendor/autoload.php';
	require 'Member.php';
	require 'Messenger.php';
	require 'Definitions.php';  #config is in path
#	use \digitalmx\flames\Definitions as Defs;
	require 'MxUtilities.php'; #in libmx

	require "SiteUtilities.php";


// 	$pdo = new \digitalmx\MxPDO ('production',$platform,PROJ_PATH . '/config/db.ini');
	require 'MyPDO.php';
	$pdo = u\MyPDO::instance();

}catch (Exception $e){
	echo 'Error: '
	.$e->getMessage() .  NL;
	$init = false;
}


if (!$quiet)
echo
	"[Cron-ini on Site: " . SITE . "(platform $platform) " . NL
	. "   REPO: " . REPO .  "; Test: " . $test_state . "; Quiet: " . $quiet_state .  "]" . NL
	. "REPO_PATH: " . REPO_PATH . NL;

if ( $init){
	define ('INIT',1);
} else {
	echo "*****  INIT FAILED  *******" . NL;
	return;
}


#################
function setPlatform(){
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
				throw new Exception( "Init cannot determine platform from ROOT '$sig' or PWD '$sig2'");
		}
		return $platform;
	}

	 function setPaths($platform) {
		$paths = array();
		$my_dir = __DIR__;
		 $homes = array(
		'pair' => '/usr/home/digitalm',
		'ayebook' => '/Users/john'
		);
		$paths['repo'] = dirname($my_dir);  #/usr/home...flames/live
		$paths['proj'] = dirname($paths['repo']);  #/usr/home...flames
		$paths['home'] = $homes[$platform];
		$paths['db_ini'] = $paths['repo'] . '/config/db.ini';


		return $paths; //array
	}

function setConstants($paths)
	{

		/* Define site constants
			HOME
			PROJ_PATH (..../flames)
			REPO_PATH (..../flames/beta)
			SITE_PATH (..../flames/beta/public
			SITE (amdflames.org)
			SITE_URL (http://SITE)
			REPO (beta)
			PLATFORM (ayebook or pair)
		*/
		define ('HOME', $paths['home']);
		define ('PROJ_PATH',$paths['proj']);

		define ('REPO_PATH',$paths['repo']);

		define ('SITE_PATH', REPO_PATH . "/public");
		define ('DB_INI',$paths['db_ini']);

	}
