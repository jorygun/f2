<?php

$mydir = dirname(__DIR__); #~/Sites/flames/<repo>

	defined ('HOMEPATH') or
		define ('HOMEPATH', '/usr/home/digitalm');
	defined ('REPO') or
		define ('REPO',$mydir);
	defined ('SITEPATH') or
		define ('SITEPATH', REPO . "/public");
	defined ('SITE') or
		define ('SITE','amdflames.org');
	defined ('SITEURL') or
		define ('SITEURL','http://amdflames.org');
	defined ('NAVBAR') or
		define ('NAVBAR', SITEPATH . '/navbar_div.php');

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
    
$DBT = setTables();
/* in scripts, add 
	global $DBT
// or 
	$dbt = setTables();
// then SELECT * from `$DBT[memberTable]` ...
*/	

function setTables () {
	/* call this to set the value of eah of the
		table names below, so they can be used as
		variables in program, as in
		SELECT * from `$memberTable` ...
	*/
	
	static $tables = array(
		'memberTable' => 'members_f2',
  		'commentTable' => 'comments',
  		'assetTable' => 'assets',
  		'eventTable' => 'events',
  		'linkTable' => 'links',
  		'newsTable' => 'news_items',
  		'oppTable' => 'opportunities',
  		'readTable' => 'read_table',
  		'specTable' => 'spec_items',
  		'voteTable' => 'votes'
		);
 	
	return $tables;
}


