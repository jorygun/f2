<?php
namespace digitalmx\flames;
/**
 Every script run from web must start by running this script
	(scripts run from cron must take care of their own stuff)
	
  Sets 
	constants
	$pdo
	$page
	$member
	$login
	
	Sets $_SESSION['menu'],['level'], and ['login'] array
	


**/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

use digitalmx\MyPDO;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;
use digitalmx\flames\DocPage;


// test to avoid re-running.  cron-ini  also sets this var.
if (defined ('INIT')){ return; } //some init has already run

class Exception extends \Exception {}
class RuntimeException extends \RuntimeException {}


   /**
   	SESSION SETUP
    * test if session already started                                         *
    * every protected script needs session to get                             *
    * logiin user info                                                        *
   **/


	// set up for longer session lifes
		#ini_set('session.cookie_lifetime', 86400);
		#ini_set('session.gc_maxlifetime', 86400);


	if (session_status() == PHP_SESSION_NONE) {
		 session_start();
	}

#use Pimple\Container;
#use digitalmx\

// sets up everything.  var is name of config file with db stuff et al,
// and is located in config dir

$init = new Init();
echo implode("<br>",$init->getNotices() );



$pdo = $init->pdo;
$member = new Member();
$login = new Login();
$page = new DocPage();
	
if ($init->getRepo() == 'live'){
	ini_set('display_errors',0);
} else {
	ini_set('display_errors',1);
}


require REPO_PATH . "/config/f2_transition.php";



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
	// translate platform into home page
	private static $homes = array(
		'pair' => '/usr/home/digitalm',
		'ayebook' => '/Users/john'
	);
	protected  $config_ini; # all the connection params 
	protected $platform;
	
	protected $repo;
	protected $site; #/beta.amdflames.org

	protected $paths;
	public $pdo;
	
	protected $loader; #from vendor/autoload
	
	private $notices = array (); #collect notices before session start
	
	public function __construct () 
	{
		
		$this->config_ini = '/config/config.ini'; 
		// relative to repo
		
		$this->platform = $this->setPlatform();
		$this->paths = $this->setPaths($this->platform);
	
		$this->repo  = basename($this->paths ['repo'] ); # live
		$this->site = $this->setSite();
		$this->setIncludes($this->paths['repo'] );
		require "MxConstants.php"; #in libmx; in inc
		// BR, NL, BRNL, CRLF, LF, URL_REGEX //

		$this->setConstants($this->paths );
		$this->setRequires() ;
		$this->setAutoload() ;
		$this->pdo = MyPDO::instance();
		define ('INIT',1);
	
	}
	
	private function setAutoload() {
		if (file_exists(REPO_PATH . "/vendor/autoload.php")){
			$this->loader = require_once REPO_PATH . "/vendor/autoload.php";
		} else {
			throw new Exception ( "no vendor autoload file.  " );
		}
	}

	private function setPaths($platform) {
		$paths = array();
		$my_dir = __DIR__;
		$paths['repo'] = dirname($my_dir);  #/usr/home...flames/live
		$paths['proj'] = dirname($paths['repo']);  #/usr/home...flames
		$paths['home'] = self::$homes[$platform];
		$paths['config_ini'] = $paths['repo'] . $this->config_ini; 
		
	
		return $paths; //array
	}
	
	
	public function setRequires(){

		require_once 'Definitions.php';
		require_once 'MxUtilities.php';
		require_once 'SiteUtilities.php';
		#require_once 'Member.php';
		require_once "utilities.php";
		#require_once 'MyPDO.php'; #in digitalmx\flames
		#require_once 'MyPDO.class.php'; #not in namespace
		#require_once 'DocPage.php';
		require_once 'navBar.php';
		
		return true;
	}
	
	
	
	private function setSite() {
		$site = $_SERVER['SERVER_NAME'];
		if (empty($site)){ #not web, e.g, from cron
			$site = "amdflames.org";
			$this->notices[] = "Site not determined; setting to $site";
		}
		
		return $site;
	}
	
	public function setConstants($paths)
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
		define ('REPO', $this->repo);
		
		define ('SITE_PATH', REPO_PATH . "/public");

		define ('SITE', $this->site);
		define ('SITE_URL', 'http://' . $this->site);
		define ('PLATFORM',$this->platform);
		define ('CONFIG_INI',$paths['config_ini']);

	}
		
		private function setPlatform(){
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
	
	
	
	private function setIncludes($repo_dir){
	#initial include path set in .user.ini to include this folder.
	#add other paths here so can just call <repo>/config/init.php for shell scripts.
	$proj_dir = dirname($repo_dir);
	
	ini_set('include_path',
		  '.'
		. ':' . '/usr/local/lib/php'

		. ':' . $repo_dir . '/libmx'
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
	public static function get_db_ini() {
		return $this->config_ini;
	}
	public function get_site() {
		return $this->site;
	}
	public function getPath($label) {
		return $this->paths[$label];
	}
	public function getRepo(){
		return $this->paths['repo'];
	}
	public function getNotices() {
		return $this->notices;
	}
	
} #end class init


class Login
{
	private $pdo;
	private $members;

	
	public function __construct () 
	{
		
		$this->pdo = MyPDO::instance();
		$this->member = new Member();
	}
	

	/*
		receives login/security request checks s= for login, logout, no change
		check for get[s].
		if empty, is there a current login
			if yes, go on
			if no, log in as non-member.
		else
			is login same as existing login
				if yes, go on
				if not, logout old user; log in new uyser
		check min level
		
			
		
	*/
	public function checkLogin ($min=0) 
	{		
		$login_code = $_GET['s'] ;
		#$current_login = $_SESSION['login']['user_id'] ?? 0;
		
		#echo "code: $login_code; current: $current_login" . BRNL;

		if ($login_code == 'logout' or $login_code == '-1'){
			#echo "code -1. Logging Out." . BRNL;
			$this->logout();
			
		}
		
		elseif ($login_code == 'relogin'  or $login_code == '1') {
			#relogin current user
		#	echo "Code 1.  Relogin.";
			$this->logout();
			$log_info = $this->member->getLoginInfo($current_login);
			$this->setLogin($log_info);
		}	
		
		elseif (empty($login_code) && empty ($current_login)){ 
		#	echo "no login; no current.  Non-member login";
			#login as not a mmeber
			$log_info = $this->member->getLoginInfo(0);
			$this->setLogin($log_info);
		}
		elseif (empty($login_code) && ! empty ($current_login)){
		#	echo "No code; already logged in";
			#do nothing
		} 
		
		elseif (!empty ($login_code) && empty ($current_login)){
		#	echo "Fresh code.  logging in.";
			$uid = $this->member->checkPass($login_code);
			echo "uid $uid.";
			$log_info = $this->member->getLoginInfo($uid);	
			$this->setLogin($log_info);
		}
		
		elseif (!empty($login_code) && !empty ($current_login)){
		#	echo "code and current. ";
			$uid = $this->member->checkPass($login_code);
			if ($uid == $current_login){
				echo "no change;";
				#same user; do nothing
			}
			else {
			#	echo "different user to log in" . BRNL;
				$log_info = $this->member->getLoginInfo($uid);
				u\echoAlert("Changing logged in user to " . $log_info['username']);
				$this->setLogin($log_info);
			}
		}
		
		return $this->checkLevel($min);
		
			
	}
	
	//checks security level and issues 403
	public function checkLevel($min)  {
		$user_level = $_SESSION['level'] ?? 0;
		if ($user_level < $min) {
			#failed security      
				#u\echor ($_SESSION['login'], 'login');
				$header = "location:/403.html";
				header($header);
				exit;

			}
		return true;
}
		
	private function setLogin ($log_info) {
		// sets vars in session
		#echo "setLogin for " . u\echor($log_info, 'log info') ;
		$menu = new Menu($log_info);
		
		$_SESSION['login'] = $log_info;
		$_SESSION['menu'] = $menu -> getMenuBar();
		$_SESSION['level'] = $log_info['seclevel'];
		$_SESSION['listring'] = '';
		return true;
	}

	private function logout(){
// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
		echo "Logging out now."; 
		$_SESSION = array();
		if (ini_get('session.use_cookies'))
		{
			 $p = session_get_cookie_params();
			 setcookie(session_name(), '', time() - 31536000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
		 }
		session_unset();
		session_destroy();
		session_start();
		header ("Location: /?s=0\n\n");

		#"<script>window.location.href='/';</script>\n";
	
	
	}

}

	
