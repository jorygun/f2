<?php
namespace digitalmx\flames;
// site-specific utlities

use digitalmx\Lib as u;
use \Exception;
use digitalmx\Flames\Definitions as Defs;
use digitalmx\Flames\Member;
use digitalmx\flames\FileDefs;
#use Digitalmx\Flames\Configuration;


function getLastPub() {
	$lts_file = FileDefs::last_pubdate;
	if (file_exists($lts_file)){
		return trim( file_get_contents($lts_file) );
	}
	else {
		echo "<p class='red'>$lts_file not found; setting to -7 days.</p>";
		return strtotime('-7 days');
	}
	return false;
	
	
}

function actionButton($label,$action,$uid=0,$affects='',$message='') {
   	// script to buld button for ajax
   	// label is the text on the button
   	// action is the action defined in action.php script
   	// uid is the uid which all scripts using this function require
   	// affects is the #id of the object that should get new text returned,
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
        

function getWarning () {
	//retrurns warning message if set and not seen
	
	if (empty($_SESSION['login']['seclevel'])){return '';}
	if ($_SESSION['login']['seclevel'] < 1 ){return '';}
	if (!empty($_SESSION['warning_seen'])){return '';}
			$warning = [];
		$msgs = [];
		
		
		if ($_SESSION['login']['email_status'] == 'E1'){
			$msgs[] = "Your email has been changed. Please be sure to respond to the verification email.";
			
		}
		elseif ($_SESSION['login']['email_status'] != 'Y'){
			$msgs[] = "There is a problem with your email.";
			
		}
		
		if ($_SESSION['login']['profile_valid_age']> Defs::$profile_warning){
			$msgs [] = "Your profile is getting a bit long in the tooth. ";
			
		}
		if (!empty($msgs)){
			$msgs[] = "Please choose go to your profile (under your name in the menu bar)  and choose 'Edit Profile'.";
			$warning = join (" ",$msgs);
			
		}
			
		return $warning;
	
} 
	

