<?php

$mydir = dirname(__DIR__); #~/Sites/flames/<repo>

	defined ('HOMEPATH') or
		define ('HOMEPATH', '/usr/home/digitalm');
	defined ('REPO') or
		define ('REPO',$mydir);
	defined ('SITE_PATH') or
		define ('SITE_PATH', REPO . "/public");
	defined ('SITE') or
		define ('SITE','amdflames.org');
	defined ('SITE_URL') or
		define ('SITE_URL','http://amdflames.org');
	defined ('NAVBAR') or
		define ('NAVBAR', SITE_PATH . '/navbar_div.php');

  if (!defined ('DB')) {
 		define('DB','digitalm_db1');
	   define('DB_SERVER', "db151d.pair.com");   
      define('DB_USER', "digitalm_w");        
      define('DB_PASSWORD', "iavLEuKz");     
      define('DB_DB', "digitalm_db1");
      define('DB_NAME',"digitalm_db1");
  		define ('DB_CHAR','utf8mb4'); 
  	}



// url  not immediately preceeded by a ' or " or >)
	defined ('URL_REGEX') or
		define ('URL_REGEX', '/(?<!["\'>])https?\:\/\/[\/\w\.\-\(\)\%\#\:\+]+([\?]+[\w\.\=\&\-\(\)\:\%\#\+]+)?/' );
		/* Must start with not a quote or > (end of a link)
			followed by http or https://
			forllowed by any number of \w . - ( ) % #
			possibly followed by a ? and then any number of \w.=&-():% or #
		*/

defined ('BRNL') or
		define ('BRNL',"<br>\n");
defined ('CRLF') or
		define ('CRLF',"\r\n");
defined ('BR') or
		define ('BR',"<br>\n");

defined ('CONSTANTS') or
	define ('CONSTANTS','set');

defined ('MAX_UPLOAD_MB') or
    define ('MAX_UPLOAD_MB',40);
    


