<?
namespace digitalmx\flames;

/* *** SECURITY FUNCTIONS *** */
//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);


require_once 'init.php'; #don't think I need this, because this is called from init

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
		$this->pdo = $pdo;
		$this->member = new Member($pdo);
		
		$log_info = $this->member->getMemberFromLogin($user,$pass);
		
		if (! $this->checkLogin ($log_info)){
			throw new Exception ("Login Failed");
		}
		
		
		
	
	}
	
	private function checkLogin ($log_info) 
	{
		// is this the same as current logged in user?
		if (empty($login_id = $_SESSION['login']['user_id'] )){
			#no current user
			$this->setLogin($log_info);
		} elseif ($login_id = $log_info['user_id']) {
				#same user, go on.
				return true;
		} else {
			#different user to log in
			u\echoAlert("Changing logged in user to " . $log_info['username']);
			$this->setLogin($log_info);
				
		}
			
	}
	
	
	private function setLogin ($log_info) {
		// sets vars in session
		$nav = new Menu($log_info);
		$navbar = $nav -> getMenuBar();
		
		
		$_SESSION['login'] = $log_info;
		$_SESSION['menu'] = $navbar;
	}

	private function logout(){
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
		 $params = session_get_cookie_params();
		 setcookie(session_name(), '', time() - 42000,
			  $params["path"], $params["domain"],
			  $params["secure"], $params["httponly"]
		 );
		}
		session_destroy();
		header ("Location: http://amdflames.org\n\n");
		exit;

	
	}




private function not_logged_in() {#not logged in
	$alert_message = "You are not logged in.  (Your login may have expired.)";

	#$stext = addslashes($alert_message);
	$stext = $alert_message;
   header('HTTP/1.0 401 Restricted');
   echo <<<EOT
<html>
<head>
<title>Not Logged In</title>
<link rel='stylesheet' href='/css/flames2.css'>

</head>


<body>


<h1>You are not logged in.</h1>
<p>You have attempted to access a page or function which is only
accessible to members.</p>
<p>If you are a member, please log in again using your personal login code</p>

<p><b>If you believe
this is an error, please <a href='mailto:admin@amdflames.org'>contact the admin</a></b>.  Sometimes I break stuff.</p>
<hr>


</body></html>
EOT;

	exit;

  }






 private function randPW() {
 //Generate a 5 digit password from 20 randomly selected characters
	global $GV;
	$pdo = MyPDO::instance();
	 static $tb1 = array (0,1,2,3,4,5,6,7,8,9,'P','Q','W','X','V','b','r','z','k','n');
	 static $iterations = 0;
	 if ($iterations > 5){die ("Too many iterations of random password");}
	 $pass = "";
	 $q = "SELECT * from $GLOBALS[members_table] WHERE upw = ?;";
	 $stmt = $pdo -> prepare($q);
	 while (!$pass){
	 	
	 	 ++$iterations;
		 for ($i=0; $i<5; $i++) {
			$pass = $pass . $tb1[rand(0,19)];
		  }
		 
		  #make sure it's unique
		  
		  $stmt->execute([$pass]);
		  if ($stmt -> rowCount() >0 ){$pass='';}
  	}
	 return $pass;
 }



	private function echoAlert($text) {
		$stext = addslashes($text);
		 echo "<script type='text/javascript'>alert('{$stext}');</script>";
	}




	private function splitLogin($login){
			$pw = substr($login,0,5); // Split user_id from upw (user password)
			$user_id = substr($login,5);
			return array($user_id,$pw);
	}


}
