<?php 
namespace digitalmx;
// same as MyPDO.class.php except this one in digitalmx ns.

use \Exception as Exception;

/* singleton instancts of PDO.  Uses constants for server config,
	or uses <repo>/config/config.ini  if not already set.
*/

class MyPDO
{
	
	
	protected  $db_ini;
    protected static $instance;
    protected $pdo;

    protected function __construct() {
    $this->db_ini = CONFIG_INI; #constant set in init
    
        $opt  = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE,
        );
        
         if (!	$dba = parse_ini_file($this->db_ini,true) ){
        	throw new Exception ("Unable to parse " . $this->db_in);
        }
     		$mode = 'dev';
     		$platform = $this->getPlatform();
     		$repo = REPO; #from init or cron-ini
        	if (in_array ($repo,['live','beta'])) {
        		$mode='production';
        	} 
        	
        	$dbname = $platform . '-' . $mode ;
        
        	$dbvars = $dba[$dbname];
        	if (empty($dbvars)){
        		throw new Exception ("No db vars for $dbname");
        	}
      	#print_r($dbvars);
      	
        $dsn = 
        	'mysql:host=' . $dbvars['DB_SERVER']
        	. ';dbname=' . $dbvars ['DB_NAME'] 
        	.';charset=' . $dbvars ['DB_CHAR'];
        	
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
	private function getPlatform(){
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
				throw new Exception( "MyPDO cannot determine platform from ROOT '$sig' or PWD '$sig2'");
		}
		return $platform;
	}
	
    // a proxy to native PDO methods
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->pdo, $method), $args);
    }

    // a helper function to run prepared statements smoothly
    public function run($sql, $args = [])
    {
        if (!$args)
        {
             return $this->query($sql);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}
