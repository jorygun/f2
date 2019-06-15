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

// set up for longer session lifes
# ini_set('session.cookie_lifetime', 86400);
# ini_set('session.gc_maxlifetime', 86400);

if (defined ('INIT')){ return; } //some init has already run

// test if session already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

   $repo_dir = dirname(__DIR__); #---/flames/<repo>/ - where this repo is      *
   $project_dir = dirname($repo_dir); #---/flames - where shared stuff is          *
   $repo_name = basename($repo_dir); #-- repo name    
   $platform = get_platform();
	$con_msg = "boot.php found: \n  platform: $platform;\n  repo_dir: $repo_dir; \n  repo_name: $repo_name;\n\n";
	
if ($repo_name == 'live'){
	ini_set('display_errors',0);
}
else {
	echo $con_msg;
	ini_set('display_errors',1);
	
}
	

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

#add vendors 
if (file_exists("$repo_dir/vendor/autoload.php")){
	require_once "../vendor/autoload.php";
} else {
	echo "no file.  " ; exit;
}

#these are all namespaced
#ns digitalmx\
#require_once "mx-constants.php";
#require_once "mx-utilities.php";

#ns digitalmx\flames
require_once 'Definitions.php';


require_once 'f2_constants.php'; 
require_once "utilities.php";

#build db
require_once 'MxPDO.php'; 
$db_ini = './db.ini'; # all the connection params 

#old pdo

if ($platform == 'pair'){
	require_once 'setGlobals.php';
	require_once 'MyPDO.class.php'; #uses envir constants for config
	$pdo = \MyPDO::instance();
	$GV = $GLOBALS = setGlobals();
	require_once  "f2_connect.php";
	$DB_link = Connect_DB();
	$GLOBALS['DB_link'] = $DB_link;
	require_once "f2_security.php";
	
	
} else {
	require_once 'setGlobals.php';
	require_once 'MyPDO.class.php'; #uses envir constant
	$pdo = new \digitalmx\MxPDO('production',$platform,$db_ini);
	$GV = $GLOBALS = setGlobals();
	require_once "f2_security.php";
}

defined ('PROJECT_PATH') or
	define ('PROJECT_PATH',$project_dir);
defined ('REPO_PATH') or
	define ('REPO_PATH',$repo_dir);
require_once 'nav.class.php';

#create container

use Pimple\Container;
$container = new Container();

$container['pdo_dev'] = function ($c) use ($db_ini,$platform) {
	return new MxPDO('dev',$platform,$db_ini);
};
$container['pdo_prod'] = function ($c) use ($db_ini,$platform) {
	return new MxPDO('production',$platform,$db_ini);
};

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
		$G_stale_data_limit = 365;



	
define ('INIT',1);
#################################################
// using ENV and HOME because they work in all circumstances: cron, cli, etc.
function get_platform(){
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
