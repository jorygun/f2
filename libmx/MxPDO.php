<?php 
namespace digitalmx;

/* non static version of MyDPD.
	extends PDO
	determines platform
	call with $pdo = new MxPDO(config_file,mode)
		mode = production (default) or test
	config file is an ini file at REPO/config.ini
*/
use digitalmx as u;

class MxPDO extends \PDO
{
   //create pdo object using database sepcified in dbconfig
	
   
	protected $db_settings = array() ;
	protected $pdo;
	protected $platform;
	protected $dbmode;
	
	
    public function __construct($config,$dbmode='production' ) {
    	// platform = pair or ayebook
    	// type = production or dev
    	$this->platform = $this->getPlatform();
    	$this->dbmode = $dbmode;
    	
    	$db_name = $this->platform . '-' . $this->dbmode; #pair-production
    
      $config_data = parse_ini_file($config,true);
      #u\echor ($config_data,'config');
      
        if (! $db_settings = $config_data[$db_name] ){
        	throw new \Exception("Unknown db name $db_name in new MxDPO");
        }
		$this->db_settings = $db_settings;
     
        $dsn = 'mysql:host=' . $db_settings['DB_SERVER'] . ';'
        		. 'dbname=' . $db_settings['DB_NAME'] . ';'
        		.  'charset=' . $db_settings['DB_CHAR'] . ';'
        		;
        	$dbuser =  $db_settings['DB_USER'];
        	$dbpass  =  $db_settings['DB_PASSWORD'];
        	
        	$dbopt  = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE
        );
        
        parent::__construct ($dsn,  $dbuser, $dbpass , $dbopt);
        
     
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
				throw new \Exception( "MyPDO cannot determine platform from ROOT '$sig' or PWD '$sig2'");
		}
		return $platform;
	}
  
    
}
