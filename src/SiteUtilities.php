<?php
namespace digitalmx\flames;
// site-specific utlities

use Digitalmx\Lib as u;
use \Exception;
use Digitalmx\Flames\Definitions;
use Digitalmx\Flames\Member;
#use Digitalmx\Flames\Configuration;




function actionButton($label,$action,$uid,$affects='',$message='') {
   	// script to buld button for ajax
   	// label is the text on the button
   	// action is the action defined in action.php script
   	// uid is the uid which all scripts using this function require
   	// affects is the #id of the object that should get new text returned,
   	$button = '<button type="button" onClick="takeAction('
   		. $uid
   		. ",'$action','$affects','$message')\">"
   		. $label
   		. "</button>";
   	return $button;
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
        




