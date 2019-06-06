<?php
namespace digitalmx\flames;
// site-specific utlities

use Digitalmx\Lib as u;
use \Exception;
use Digitalmx\Flames\Definitions;
use Digitalmx\Flames\Member;
use Digitalmx\Flames\Configuration;




function getDBTable (\Slim\Container $ci ,$name='') {
	/* call this to set the value of eah of the
		table names below, so they can be used as
		variables in program.  Returns whole table
		if no name requested.
 	*/
 	$settings = $ci->get('settings');
 	$db_table = $settings['appvars']['DB_TABLES'];
 	
 	if (empty($name)){
 	    return $db_table;
 	 }
 	return $db_table[$name];
 	  
}

function replaceAlias ($maybe){
    // looks for maybe in alias list and replaces with alias name
    if (preg_match('/^\w+$/',$maybe)){ # match alias format
        if (in_array($maybe,array_keys(Definitions::$user_aliases))){
            $lookup = Definitions::$user_aliases[$maybe];
            return $lookup;
        }
    }
    return $maybe;
 }

function getAliasList (){
    $t = 'Aliases: ' . implode(', ',array_keys(Definitions::$user_aliases));
    return $t;
}

// not sure this is needed.
function assignNameId ($name) {
  // assigns user name and id for a name or alias
    # try alias translation first
    die ("Replace this with member->getMemberList(tag,1)");
    
   
    $name = replaceAlias($name);
    $member = new Member();
    if (! ($member_name = $member->getMemberName($name))){
        $member_name = $name;
    }
    if (! ($member_id = $member -> getMemberId($name))) {
        $member_id = 0;
    }
    return array($member_name,$member_id);
    
}
   
   
function splitLogin($login){
		$user_id=$pw='';
		$pw = substr($login,0,5); // Split user_id from upw (user password)
		if (!empty ($pw)){
			$user_id = substr($login,5);
			if (is_numeric($user_id)){
				return array($user_id,$pw);
			}
		}
		else  {return ['','']; }
}

    
function isLogin($login) {
    #returns true or false
    // regex for user login string 5 char pw, 5 digit user_id
       $login_regex =  '/^(\w{5})(\d{5})$/';
       return preg_match($login_regex,$login);
       
}
        

function decompress($data,$defs){
  	//to turn a string of character codes into a descriptive string.
        $choices = [];
		// step through the codes and values in the defining array
		foreach ($defs as $k=>$v){  # D => '60s'
			if (strchr($data,$k)){$choices[]  = $v;}
		}
        if (empty($choices)){
            $my_choices = 'Not specified';
        }
        else {
		    $my_choices = implode (',',$choices);
		}

		return $my_choices;
}


function getIncluded ($file){
		$gfile = INCLUDES . "/$file";
		#echo "testing for $gfile" . BRNL;
		if (file_exists($gfile)){
			
		}
		else { throw new Exception ("requested include file $file not in Includes.");}
		// if (substr($file,-3) == 'php'){
// 		    include $gfile;
// 		}
// 		else {
// 		    return file_get_contents($gfile);
// 		}
		 include $gfile;   
	}
	

// 
// function logIt($type,$message,$context,) {
//         $name='f3.log';
//         $logger = new Logger('f3');
//         $logger->pushHandler(new StreamHandler(LOGPATH . "/$name", Logger::DEBUG));
//         $logger->pushProcessor(new IntrospectionProcessor());
//         #$logger->info('f3 logger ready');
//         $logger->$type($message,$code,$context);
//         
// }

// function  startHtmL ($title='(Title)',$more=[]) {
// 		// starts html, includes title and any lines in a
// 		// list supplied as a list in more
// 		
// 		// header('Content-Type: text/html; charset=UTF-8')
// 		$base = SITE_URL;
// 		$f = <<<EOF
// 	<!DOCTYPE html>
// 	<html><head>
// 	<meta content="text/html" charset="utf-8">
// 	<base='$base'>
// 	<title>$title</title>
// 	<script type="text/javascript" src="js/f3.js"></script>
// 	<link rel='stylesheet' href='css/f3.css'>
// EOF;
// 		if (!empty($more)){
// 			$f .= explode("\n",$more);
// 		}
// 		//$f .= "</head>\n";
// 		// end head in the start body tag, so additional head
// 		// tags can be included on the page itself.
// 		return $f;
// 	}
// 
// function startBody($type='') {
// 		$f = "</head>\n";
// 		if ($type == 'collapsible'){
// 			$f .= <<<EOT
// 			<script type="text/javascript" src="/lib/js/collapsibleLists.js"></script>
// 			<body onload="CollapsibleLists.apply();" >
// EOT;
// 		}
// 		else {
// 			$f  .= "<body>\n";
// 		}
// 		return $f;
// 	}
// 	
// 
// function quoteBox ($text) {
// 	
//        if (
//        	($quote_count = preg_match_all ( '/\sQ\[(.*?)\]\s*(\(.*?\))?/s' ,$text,$m)) !== false){
//       	$c = $quote_count + 1;
//        	echo	 "$c quotes." . BRNL;
//         	echoR ($m);
//         $t = $text;
//         	for ($i=0;$i<$quote_count;++$i){
//         		$sblock='';
//         		$qphrase = $m[0][$i];
//         		$qtext = $m[1][$i];
//         		$qsrce	= $m[2][$i];
//         		#remove parens around source
//         		$qsrce = substr($qsrce,1,strlen($qsrce)-1);
//         		if (! empty ($qsrce) ){
//         			$sblock = "<p class='qsource'>--source: $qsrce</p>";
//         		}
//         		$qblock = "<p>$qtext</p>\n";
//         		
//         		
//         		$t = str_replace(
//         			$qphrase, 
//         			"<div class='quotebox'>$qblock  $sblock </div>" ,
//         			$t);
//         		
//         			
//         		
//         	}
//         }
//        
//         return $t;
//     }
 
    
  
  
