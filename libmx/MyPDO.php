<?php
namespace DigitalMx;
// same as MyPDO.class.php except this one in DigitalMx ns.

use \Exception as Exception;

/* singleton instance of PDO.
Uses DB_INI constant for path to  db params,

*/

class MyPDO
{



    protected static $instance;
    protected $pdo;
    protected static $db_ini_path = DB_INI; #constant set in init
	 protected static $repo = REPO; #from init or cron-ini
	 protected static $platform = PLATFORM;


    protected function __construct() {


        $opt  = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE,
        );
        if (empty(self::$db_ini_path)) {
        		throw new Exception ("No db_ini_path defined");
        	}
        	if (empty(self::$repo)) {
        		throw new Exception ("No REPO defined");
        	}
		if (empty(self::$platform)) {
        		throw new Exception ("No PLATFORM defined");
        	}

         if (!	$dba = parse_ini_file(self::$db_ini_path,true) ){
        		throw new Exception ("Unable to parse db ini " . DB_INI);
        }


        	if (in_array (self::$repo,['live','beta'])) {
        		$mode='production';
        	} else {
        		$mode = 'dev';
        	}

        	$dbname = self::$platform . '-' . $mode ;

        	$dbvars = $dba[$dbname];
        	if (empty($dbvars)){
        		throw new Exception ("No db vars defined for db $dbname");
        	}
      	#print_r($dbvars);

        $dsn =
        	'mysql:host=' . $dbvars['DB_SERVER']
        	. ';dbname=' . $dbvars ['DB_NAME']
        	. ';charset=' . $dbvars ['DB_CHAR'];

        $this->pdo = new \PDO($dsn, $dbvars ['DB_USER'], $dbvars ['DB_PASSWORD'], $opt);


    }

    // a classical static method to make it universally available
    public static function instance()
    {
        if (self::$instance === null)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }


    // a proxy to native PDO methods
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->pdo, $method), $args);
    }

    // a helper function to run prepared statements smoothly

}
