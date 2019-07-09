<?php
namespace digitalmx\flames;

//ini_set('display_errors', 1);

use digitalmx\flames\Definitions as Defs;
use digitalmx\flames\Member;
use digitalmx as u;


class Login
{
	private $pdo;
	private $member;
	
	// mays supply old style login string in $user with empty pass
	public function __construct ($pdo,$user,$pass='') 
	{
		if ($user == -1){$this->logout();}
		
		$this->pdo = $pdo;
		$this->member = new Member($pdo);
		
		//returns info for whatever id was passed, including 0
		$log_info = $this->member->getInfoFromLogin($user,$pass);
		
		
		if (! $this->checkLogin ($log_info)){
			throw new Exception ("Login Failed");
		}
	
	}

	private function checkLogin ($log_info) 
	{
		// is this the same as current logged in user?
		if (!isset ($_SESSION['login']['user_id'] )){
			#echo "no current user, logging in." . BRNL;
			return $this->setLogin($log_info);
			
		} else {
			$current_userid = $_SESSION['login']['user_id'];
			if ($log_info['user_id'] == 0 
				|| $log_info['user_id'] == $_SESSION['login']['user_id']) {
				#echo "same user, go on." . BRNL;
				return true;
			} else {
				#echo "different user to log in" . BRNL;
			
				#u\echoAlert("Changing logged in user to " . $log_info['username']);
				return $this->setLogin($log_info);
			}
		}
		return false;
			
	}
	
	
	private function setLogin ($log_info) {
		// sets vars in session
		$nav = new Menu($log_info);
		$navbar = $nav -> getMenuBar();
		
		
		$_SESSION['login'] = $log_info;
		$_SESSION['menu'] = $navbar;
		$_SESSION['level'] = $log_info['seclevel'];
		return true;
	}

	private function logout(){
// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			 $params = session_get_cookie_params();
			 setcookie(session_name(), '', time() - 42000,
				  $params["path"], $params["domain"],
				  $params["secure"], $params["httponly"]
			 );
		}

		// Finally, destroy the session.
		session_destroy();
		#header ("Location: /\n\n");
		echo "<script>window.location.href='/';</script>\n";
	
	
	}

}
