<?php


  /*
   *  site configuration                                                     *
   *  must have config files in this diretory!                                *
   *  site.config                                                             *
   *  Mac.config                                                              *
   *  Pair.config  
   
   *  produces constants for
   		SITEPATH /....public_html/xx
   		PROJPATH / ..../fx/config|code|...
   		SITEURL  http://sitename.org/
   		
   	  generates $cfg inserted into any class or used by any function
   	  	{pdo (obj),
   	  	tablenames (array),
   	  	paths (array),
   	  	defs (obj)
   	  	logger (obj)
   	  	}
   */


#use MonologLogger;


class Configuration
{
   
    private $db_tables = array(); #names to tablenames
    private $cfg = [];
    

 /**
  *  set host based on text in this __DIR__                                  *
  *  Used to choose the appropriate host.config file                         *
  *  mostly to get paths.                                                    *
  **/
  
  
    public function __construct($params)
    {
        /*
        params = (
        	project=f2|f3...
        	mode=test|prod|dev, 
        	server=mac|pair1|pair2...',	
        */
        
        	
     	$project_path = dirname(__DIR__); #one folder up.  Like 'f2'
     	preg_match('#^(.*?)/Sites/(.w+)$#',$project_path,$m);
     	$root = $m[1];
     	$project_name = $m[2];
     	$myhost = $this->get_host($root);
     	
     	
        
    // load hostvars from appropratie configuration file in this dir
        $hostconfig = __DIR__ . '/' . $myhost . '.config.txt';
        if (!file_exists($hostconfig)) {
            throw new Exception("No config file $hostconfig.");
        }  
        
        $hostvars = $this->arrayFromText($hostconfig);
        $sitevars = $this->arrayFromText(__DIR__ . '/Site.config.txt');
       
        $appvars  = array_merge ($hostvars,$sitevars);

        $this->config = $appvars;
        
        
        
        // set PHP ini
        if ($mode == 'dev'){
            ini_set ('display_errors',1);
        }
//          foreach ($hostvars['PHP'] as $var => $val) {
//             ini_set($var, $val);
//         }
        
    // set constants,   set paths.
        $this->setConstants($hostvars['HOST']);
       
        
    
        $this->setConstants($sitevars['SITE']);
        $this->setPathConstants($root, $sitevars['PATHS']);
        
    // set dbtables array in this
        $this->db_tables = $sitevars['DB_TABLES'];
    } 
    
    private function getHost($root){
    	switch ($root) {
    		case '/usr/home/digitalm':
    			return 'pair';
    			break;
    		case 'Users/john/':
    			return 'mac';
    			break;
    		default:
    			die ("Cannot determine host from $root");
    	}
    }
    
   
    private function setConstants($arr)
    {
        // DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME, DB_CHAR
        // skip any entries starting with .
        foreach ($arr as $k => $v) {
            if (substr($k, 0, 1) == '.') {
                continue;
            }
            if (defined($k)) {
                throw new Exception("Constant $k already defined!");
            } else {
                define($k, $v);
            }
        }
    }
    

   
    private function setPathConstants($root, $vars)
    {
       // sets UPPERCASE constants for vars in the site config file
        $var_list = ['sitepath','logpath','templates','includes','sitecode','data', 'config','navdata'];
        
        foreach ($var_list as $v) {
            $val = $vars[$v];
            $ucv = strtoupper($v);
            if (empty($val)) {
                echo "Warning: no value set for constant $v" . BRNL;
            }
            // if (!is_dir($root . $vars[$v])) {
//                 throw new Exception("Configuration path for $ucv is not valid directory.");
//             }
           
            if (defined($ucv)) {
                throw new Exception("Constant $ucv already defined.");
            }
            define($ucv, $root . $val);
        }
    
          //  define('SITEPATH', ROOT . $vars['sitepath']);
   //         define ('LOGPATH' , ROOT .$vars['logs']);
   //         define ('TEMPLATES' ,ROOT . $vars['templates']);
   //         define ('INCLUDES' , ROOT .$vars['includes']);
   //         define ('SITECODE' , ROOT .$vars['sitecode']);
   //
    }
   
   

    private function arrayFromText($text_file)
    {
        /* reads a text file in form:
            --ARRAY_NAME--
            var = val
            var = val
            --NEXT_ARRAY_NAME--
            ...
            puts results into an array of arrays
            $ar[$array_name][$var] => $val
        */
        
        if ((! file_exists($text_file) )
          or
          (($fh = fopen($text_file, 'r'))  === false)
          ) {
             throw new Exception("Cannot open $text_file");
        }
        $arr = array ();
        $last_r = '';
        $this_r = '';
        
        while (! feof($fh)) {
            $line = fgets($fh);
            if (!preg_match('/\w/', $line)) {
                continue;
            } #no words on line
            if (preg_match('#^\s*(\#|//)#', $line)) {
                continue;
            } #comment
           #else
           # set array name to load
            if (preg_match('/--(\w+)--/', $line, $m)) {
                $this_r = $m[1];
                $arr[$this_r] = array();
                continue;
            }
           
            list ($var,$val) = explode('=', $line, 2);
            $arr[$this_r][trim($var)] = trim($val);
        }
        fclose($fh);
        return $arr;
    }

    public function getDbTable($name = '')
    {
        if (empty($name)) {
        	throw new Exception ("Request DB Table with no name" );
        }
        elseif ($name == 'All'){
            return $this->db_tables;
        } else {
            return $this->db_tables[$name];
        }
    }
    
    
    // for slim app
    public function getConfig(){
       
        return $this->config;
    }
   
} #end class
