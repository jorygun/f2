<?php
namespace DigitalMx\Flames;
// site-specific utlities

use DigitalMx as u;
use \Exception;
use DigitalMx\Flames\Definitions as Defs;
use DigitalMx\Flames\Member;
use DigitalMx\Flames\FileDefs;
#use Digitalmx\Flames\Configuration;

// replace with Publish->getLastPub() #defaults to pubdate
// function getLastPub() {
// 	$lts_file = FileDefs::last_pubdate;
// 	if (file_exists($lts_file)){
// 		return trim( file_get_contents($lts_file) );
// 	}
// 	else {
// 		echo "<p class='red'>$lts_file not found; setting to -7 days.</p>";
// 		return strtotime('-7 days');
// 	}
// 	return false;
//
//
// }

function actionButton($label,$action,$uid=0,$affects='',$message='') {
	/* script to buld button for ajax
		label is the text on the button
		action is the action defined in action.php script
		uid is the uid which all scripts using this function require
		affects is the #id of the object that should be replaced with new text returned,
	if message = 'resp', the response will be reported as an alert
	*/
   	$button = '<button type="button" onClick="takeAction('

   		. "'$action','$uid','$affects','$message')\">"
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




