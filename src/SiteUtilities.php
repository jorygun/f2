<?php
namespace digitalmx\flames;
// site-specific utlities

use Digitalmx\Lib as u;
use \Exception;
use Digitalmx\Flames\Definitions;
use Digitalmx\Flames\Member;
#use Digitalmx\Flames\Configuration;


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
        




