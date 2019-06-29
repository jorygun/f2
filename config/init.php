<?php
namespace digitalmx\flames;
/**
 Every script run from web must start by running this script
	(scripts run from cron must take care of their own stuff)
	
	boot sets 
		session lifetimes
		include_path
		autoload: vendor +
			PSR4:
			files: Definitions
			
		adds std constnats/utilities (mx)
		requires Definitions


**/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);



if (defined ('INIT')){ return; } //some init has already run

// put Exceptin into this namespace
use \Exception as Exception;

use Pimple\Container;
#use digitalmx\

$init = new Init();

$init->setConstants();

#add vendor autoload
	if (file_exists(REPO_PATH . "/vendor/autoload.php")){
		require_once REPO_PATH . "/vendor/autoload.php";
	} else {
		throw new Exception ( "no vendor autoload file.  " );
	}


require_once 'setGlobals.php';
$GV = $GLOBALS = setGlobals();

require_once 'MyPDO.class.php'; #uses envir constants for config; sets from db.ini if not already set


if ($init->get_platform() == 'pair'){
	$pdo = \MyPDO::instance();
	require_once  "f2_connect.php";
	$DB_link = Connect_DB();
	$GLOBALS['DB_link'] = $DB_link;
	require_once "f2_security.php";
	
} elseif ($init->get_platform() == 'ayebook') {
	$pdo = new \digitalmx\MxPDO('production',$platform,$db_ini);
	require_once "f2_security.php";
}
else {
	throw new Exception ("Platform not known $platform");
}

#ns digitalmx\flames
require_once 'Definitions.php';

require_once "utilities.php";
require_once 'MxPDO.php'; 
require_once 'MxUtilities.php';

require_once 'nav.class.php';



#build db
$container = new Container();

	$container['pdo_dev'] = function ($c)  {
		return new MxPDO('dev',$init->platform,$init->db_ini);
	};
	$container['pdo_prod'] = function ($c)  {
		return new MxPDO('production',$init->platform,$init->db_ini);
	};


//       CLASS INIT        //

class Init 
{
	private static $homes = array(
		'pair' => '/usr/home/digitalm',
		'ayebook' => '/Users/john'
	);
	protected  $db_ini = './db.ini'; # all the connection params 
	protected $platform;
	protected $home;
	private $config_message;
	private $site; #/beta.amdflames.org
	private $repo_dir;
	private $project_dir;
	
	
	
	public function __construct () 
	{
	
/*
	// set up for longer session lifes
	ini_set('session.cookie_lifetime', 86400);
	ini_set('session.gc_maxlifetime', 86400);
*/

	
		// test if session already started
		if (session_status() == PHP_SESSION_NONE) {
			 session_start();
		}

	
	
		$repo_dir = dirname(__DIR__); #---/flames/<repo>/ - where this repo is      *
		$this->repo_dir = $repo_dir;
		
		$project_dir = dirname($repo_dir); #---/flames - where shared stuff is          *
		$this->project_dir = $project_dir;
		
		$repo_name = basename($repo_dir); #-- repo name    
		$this->platform = $this->setPlatform();
		
	
		$this->home = self::$homes[$this->platform];
	
		$this->config_msg = "init.php: \n  platform - $this->platform; repo_dir - $repo_dir; repo: $repo_name;\n\n";
	
	
		if ($repo_name == 'live'){
			ini_set('display_errors',0);
		}
		else {
			ini_set('display_errors',1);
		}
		$this->setSite();
		$this->setIncludes($repo_dir);
		
		
		define ('INIT',1);
	
	}
	
	private function setSite() {
		$site = $_SERVER['SERVER_NAME'];
		if (empty($site)){
			$site = "trial.amdflames.org";
			echo "Site not determined; setting to $site";
		}
		$this->site = $site;
	}
	
	public function setConstants()
	{
		
		define ('HOME', $this->home);
		

	// BR, NL, BRNL, CRLF, LF, URL_REGEX //
		require_once "MxConstants.php";
		

		/* Define site constants
			PROJ_PATH (..../flames)
			REPO_PATH (..../flames/beta)
			SITE_PATH (..../flames/beta/public
			SITE (amdflames.org)
			SITE_URL (http://SITE)
		*/
		
		define ('PROJ_PATH',$this->project_dir);

		define ('REPO_PATH',$this->repo_dir);

		define ('SITE_PATH', REPO_PATH . "/public");

		define ('SITE', $this->site);
		define ('SITE_URL', 'https://' . SITE);

	}
		
	private function setPlatform(){
	// using PWD because it seems to alwasy work, even in cron
		$sig = getenv('PWD');
		if (stristr ($sig,'usr/home/digitalm') !== false ) {	
				$platform = 'pair';
		} elseif (stristr ($sig,'Users/john') !== false ) {	
				$platform = 'ayebook';
		} else {
				echo "Cannot determine platform from '$sig'";
				echo "ENV:\n";
				print_r (getenv());
				exit;
		}
		return $platform;
	}
	
	private function setIncludes($repo_dir){
	#initial include path set in .user.ini to include this folder.
	#add other paths here so can just call <repo>/config/init.php for shell scripts.

	ini_set('include_path',
		  '.'
		. ':' . '/usr/local/lib/php'

		. ':' . $repo_dir . '/libmx'
		. ':' . $repo_dir . '/lib'
		. ':' . $repo_dir. '/config'
		. ':' . $repo_dir. '/code'
		. ':' . $repo_dir . '/public'
		. ':' . $repo_dir . '/public/scripts'


		);

	}
	
	#################################
	public function get_platform() {
		return $this->platform;
	}
	public function get_home() {
		return $this->home;
	}
	public function get_db_ini() {
		return $this->db_ini;
	}
	public function get_site() {
		return $this->site;
	}
	
} #end class init


	
