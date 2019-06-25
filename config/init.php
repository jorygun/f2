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
	#add other paths here so can just call <repo>/config/boot.php for shell scripts.

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

###### everything below is suspect ######



// SET MEMBER DEFINITIONS
	// Mmeber names
	$G_member_desc = array (
	'N' => 'New Signup',
	
	'G' => 'Guest',
	'GA'	=> 'Anonymous Guest',
	'M' => 'Member',

	'MC' => 'Contributor',
	'MN' => 'News Admin',
	'MU' => 'User Admin',
	'MA' => 'Admin Admin',
	'MI' => 'Inactive Member',

	'I' => 'Inactive',
	'T' => 'test user (like member)',
	'L' => 'lost ',
	'D' => 'deceased',

	'H' => 'hidden',
	'X' => 'to be deleted',
	'Y' => 'Non-member',
	'YA' => 'Alumni non-member'
	
	);


	/* sets security level as a function of member status
	0 = no privileges; access to only home page and signup
	1 = access to current news only
	2 = acceess to current news, edit and view own profile
	3 = spare
	4 = access to all news, view all profiles, edit own
	5 = spare
	6 = access to previews and ability to contribute
	7 = news admin (publish news)
	8 = member admin (update members)
	9 = admin admin (create admins)
	*/


$G_member_sec = array(
	'N' => 1,
	
	'G' => 2,
	'M' => 4,


	'MC' => 6,
	'MN' => 7,
	'MU' => 8,
	'MA' => 9,
	'MI' => 2,
	
	'YA' => 0,
	'Y' => 0,

	'I' => 2,
	'T' => 4,
	'L' => 1,
	'D' => 0,

	'GA' => 1,
	'H' => 4
	);


#these member status codes are considered members
$G_member_status_array = array('M', 'MA','MN','MC','MU','R','G');
#text version of member list for use in sql IN(list) statement
$G_member_status_set = "'" . implode("','",$G_member_status_array) . "'";




// EMAIL DEFINIITIONS
	// definition of email statuses
	$G_ems_defs = array(
	'Y'	=>	'Validated',
	'Q'	=>	'Believed Good',
	'XX'	=>	'To be removed',
	'LA'	=>	'Lost - No Response',
	'LB'	=>	'Lost - Bounced',
	'LO'	=>	'Lost - Other',
	'LN'	=>	'Lost - No Email Address',
	'LE'	=>	'Lost - After email change',
	'LS'    =>  'Lost at signup',
	'LD'    =>  'Lost - Deceased',
	'B1'	=>	'May be bouncing',
	'B2'	=>	'Bounced twice',
	'A1'	=>	'Being revalidated',
	'A2'	=>	'Being revalidated (2nd attempt)',
	'A3'	=>	'Being revalidated (3rd attempt)',
	'A4'	=>	'Being revalidated (Final attempt)',
	'E1'	=>	'Email change being validated',
	'E2'	=>	'Email change being validated (2nd)',
	'N1'	=>	'New Signup',
	'N2'	=>	'New Signup (2nd)',
	'D'     =>  'Lost but logging in. (Deferred lost)'

	);


$G_decades = array(
	'A'	=>	'1960s',
	'B'	=>	'1970s',
	'C'	=>	'1980s',
	'D'	=>	'1990s',
	'E'	=>	'2000s',
	'F'	=>	'2010s',
);

$G_locations = array(
	'A'	=>	'Sunnyvale',
	'B'	=>	'Austin',
	'C'	=>	'San Antonio',
	'U'	=>	'US Field',
	'V'	=>	'Europe',
	'W'	=>	'Asia',
	'X' =>	'Other'
);

$G_departments = array (
	'A'	=>	'Design',
	'B'	=>	'Marketing/Sales',
	'C'	=>	'Production',
	'D'	=>	'Finance/HR',
	'X' =>	'Other'
);


// age limits in days before warnings appear
	#	$G_profile_age_limit = 365;
	#	$G_email_age_limit = 365;
#	$G_stale_data_limit = 365;



	
