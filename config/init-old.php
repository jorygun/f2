<?
// ini_set('session.cookie_lifetime', 86400);
// ini_set('session.gc_maxlifetime', 86400);

if (defined ('INIT')){ return; } #already ran

#test if session already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//ini_set('error_reporting',E_ALL);

$sitedir = dirname(__DIR__); #...<repo>/
$projdir = dirname($sitedir);


#echo "sitedir $sitedir ";

#initial include path set in .user.ini to include this folder.
#add other paths here so can just call init.php for shell scripts.

ini_set('include_path',
	'/usr/local/lib/php'
	. ':' . $projdir . "/libmx/phpmx"
	. ':' . $sitedir . '/public'
	. ':' . $sitedir . '/public/scripts'
	. ':' . $sitedir. '/config'
	. ':' . $sitedir. '/code'
	);

require_once  "f2_connect.php";
require_once ('MyPDO.class.php');
require_once ('nav.class.php');
#require_once ($libphp . '/vendor/autoload.php');
require_once ('setGlobals.php');
require_once ('f2_constants.php'); 


$pdo = MyPDO::instance();

define ('INIT',1);

// set more global values
		// date object
		$DT_zone = new DateTimeZone('America/Los_Angeles');

		$DT_now = new DateTime('now',$DT_zone);


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






#set global value aarray:
	$GV = $GLOBALS = setGlobals();

		// sets up the old mysqli db connect

		$DB_link = Connect_DB();
		$GLOBALS['DB_link'] = $DB_link;

		require_once SITE_PATH. "/scripts/utilities.php";
	#echo "utils loaded. ",im_here();

		require_once "f2_security.php";

