<?php
namespace DigitalMx\Flames;
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
#ini_set('display_errors', 1);

session_start();

use DigitalMx\MyPDO;
use DigitalMx as u;
use DigitalMx\Flames\Definitions as Defs;

use DigitalMx\Flames\Initialize;
use DigitalMx\Flames\Login;


   /**
    *  Initialize all the services and constnats.
    *
    *  This file must be in the server home directory (i.e., public_html)
    *  This file is run by
    *  -  require $_SERVER['DOCUMENT_ROOT'] . '/init.php';
    *  or by another way to define location if there is no server
    *
    */


// test to avoid re-running.  cron-ini  also sets this var.
if (defined ('INIT')){ return; } //some init has already run



// set up for longer session lifes
    #ini_set('session.cookie_lifetime', 86400);
    #ini_set('session.gc_maxlifetime', 86400);


// need to get autoload wihtout any _SERVER data
// bercause it has to run from cron as well.
$repoloc = dirname(__FILE__,2);  #repo directory
if (! file_exists( $repoloc .  "/vendor/autoload.php")) {
   throw new Exception ( "no vendor autoload file.  " );
}
require $repoloc . "/vendor/autoload.php";



// set up exceptions under my namespace.  Just so I don't have to put \ in front
class Exception extends \Exception {}
class RuntimeException extends \RuntimeException {}

// sets paths, constants, requires
$init = new Initialize();

//creates container and services for most of the classes
require  REPO_PATH . "/config/services.php";

if (REPO == 'live'){
    ini_set('display_errors', 0);
} else {
    ini_set('display_errors', 1);
}


if (!empty($_SERVER)) {
	//login checks for an secode and updates Session['login']
	$login = new Login($container);
	$login->checkLogin();
	// use $login->checkLevel(val) for minimum levels on pages

	// easiest to retrieve menu bar from session array.
	// docpage doesn;t use the container.
	$menu = new Menu ();
	$_SESSION['menubar'] = $menu-> getMenuBar();
}

require $repoloc . "/config/f2_transition.php";

$pdo = $container['pdo'];

 define ('INIT',1);

//EOF
