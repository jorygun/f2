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
class Exception extends \Exception { }

$conf_ini = 'config.ini';

#use Pimple\Container;
use digitalmx\MxPDO;


$init = new Init($conf_ini);

$init->setConstants();

#add vendor autoload
	if (file_exists(REPO_PATH . "/vendor/autoload.php")){
		$loader = require_once PROJ_PATH . "/vendor/autoload.php";
		$loader->add('digitalmx\\', REPO_PATH .'/libmx/');
		$loader->add('digitalmx\\flames\\', REPO_PATH . '/lib/');
		$loader->add('digitalmx\\flames\\', REPO_PATH . '/src/');
		
	} else {
		throw new Exception ( "no vendor autoload file.  " );
	}


 //require_once 'MyPDO.class.php'; #uses envir constants for config; sets from db.ini if not already set
// require_once 'MxPDO.php'; 
//require_once 'NavBar.php';

#ns digitalmx\flames
require_once 'Definitions.php';
require_once 'MxUtilities.php';
require_once 'SiteUtilities.php';

require_once "utilities.php";
require_once 'setGlobals.php';



$pdo = $init->setPDO(); #guarantees db values are set
$init->setRequired(); #f2 connect needs db values

use digitalmx\flames\Login;
$s = $_GET['s'] ?? '';
$login = new Login($pdo,$s);

$page = new DocPage;
	
// #build db
// $container = new Container();
// 
// 	$container['pdo_dev'] = function ($c)  {
// 		return new MxPDO('dev',$init->platform,$init->db_ini);
// 	};
// 	$container['pdo_prod'] = function ($c)  {
// 		return new MxPDO('production',$init->platform,$init->db_ini);
// 	};


//       CLASS INIT        //

class Init 
{
	private static $homes = array(
		'pair' => '/usr/home/digitalm',
		'ayebook' => '/Users/john'
	);
	protected  $ini; # all the connection params 
	protected $platform;
	protected $home;
	private $config_message;
	private $site; #/beta.amdflames.org
	private $repo_dir;
	private $proj_dir;
	
	public $pdo;
	
	
	
	public function __construct ($ini) 
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
		$this->ini = __DIR__ . "/$ini";
		
	
	
		$repo_dir = __DIR__; #---/flames/<repo>/ - where this repo is      *
		$this->repo_dir = $repo_dir;
		
		$project_dir = dirname($repo_dir); #---/flames - where shared stuff is          *
		$this->proj_dir = $project_dir;
		
		
		$this->repo = basename($repo_dir); #-- repo name    
	
		$this->platform = $this->setPlatform();
		define ('PLATFORM',$this->platform);
		
	
		$this->home = self::$homes[$this->platform];
	
		$this->config_msg = "init.php: \n  platform - $this->platform; repo_dir - $repo_dir; repo: $this->repo \n\n";
	
	
		if ($this->repo == 'live'){
			ini_set('display_errors',0);
		}
		else {
			ini_set('display_errors',1);
		}
		$this->setSite();
		$this->setIncludes($this->proj_dir,$this->repo_dir);
		
		
	// if (! function_exists('\f2_security_below')){
// 		include 'f2_proxy.php';
// 
// 		}
		
		define ('INIT',1);
	
	}
	
	public function setRequired(){
		$platform = $this->platform;
		if ($platform == 'pair'){
			require_once "f2_security.php";
		} elseif ($platform == 'ayebook') {
			require_once "f2_security.php";
		} else {
			throw new Exception ("Platform not known $platform");
		}
		return true;
	}
	
	public function setPDO(){
		$platform = $this->platform;
		
		if (! $pdo = new MxPDO($this->platform,'production',$this->ini) ){
			throw new Exception ("Platform not known $platform");
		}
		$this->pdo = $pdo;
		return $pdo;
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
		
		define ('PROJ_PATH',$this->proj_dir);

		define ('REPO_PATH',$this->repo_dir);
		define ('REPO', $this->repo);
		
		define ('SITE_PATH', REPO_PATH . "/public");

		define ('SITE', $this->site);
		define ('SITE_URL', 'https://' . SITE);

	}
		
	private function setPlatform(){
	// using PWD because it seems to alwasy work, even in cron
		$sig = $_SERVER['DOCUMENT_ROOT'];
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
	
	private function setIncludes($proj_dir, $repo_dir){
	#initial include path set in .user.ini to include this folder.
	#add other paths here so can just call <repo>/config/init.php for shell scripts.

	ini_set('include_path',
		  '.'
		. ':' . '/usr/local/lib/php'
		

		. ':' . $proj_dir . '/libmx'
		. ':' . $repo_dir . '/lib'
		. ':' . $repo_dir. '/config'
		. ':' . $repo_dir. '/src'
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
	public function get_pdo() {
		return $this->pdo;
	}
} #end class init


	
