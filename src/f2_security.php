<?php
/* *** SECURITY FUNCTIONS *** */
//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);



use digitalmx\flames\Definitions as Defs;


function security_below($min) {
	return f2_security_below($min);
}


function login_security_below($min){
	return f2_security_below($min);
}

function f2_security_below($min)
// Check the security level for this user
// If it's less than $min, show user a rejection message and return True (user's level is below minimum)
//  if >=, return False
// Must precede any HTML in calling document
// Call: if (security_below(5)) exit;
{
	#echoAlert ("running security");
		 #see if there is a login code present
	 if (array_key_exists ('s',$_GET)){
	 	$loginpw = $_GET['s'];
	 #	echoAlert ("checking in with $loginpw");
		 #is user alrready logged in with same code?
		 if (isset($_SESSION['pwid'])){
			$sessionpw = $_SESSION['pwid'];
			if ($sessionpw == $loginpw){
				$seclevel = $_SESSION['level'];
				#echoAlert ("same user; sl $seclevel");
			}
			else { #user is logged in under a different name; re-login
				#echoAlert ("different user"); 
				$seclevel = login($loginpw);
			}
		 }
		 else {
			#echoAlert ("new user");
			$seclevel = login($loginpw);
		 }
	}
	elseif (isset ($_SESSION['level'])){
		$seclevel = $_SESSION['level'];
	}
	else {$seclevel = 0;}
	
	#echoAlert("Seclevel $seclevel");

	 if ($seclevel >= $min){ return false;} #ok to go 
	 else {nogo();}
	 exit;
 }

function login($pw){
	#echoAlert ("logging in with $pw"); 
	$pdo = MyPDO::instance();
	
	if ($pw == '0'){logout();}
	
	if (empty($pw)
		|| ! $row = get_row_for_login($pw)  
		){echoAlert ("No user for that login. ($msg)");}
		
	$rstatus = $row['status'];
	$seclevel = Defs::getSecLevel($rstatus );
	
	#echoAlert("Loggin you in as $showname");
	if (isset($_SESSION['pwid']) && $pw != $_SESSION['pwid']){
		echoAlert("Changing your login to ${row['username']}");
	}
	//speial case for beta testers
	

	$new_type = Defs::getMemberDescription($rstatus);

		$_SESSION['pwid'] = $pw; // Add to SESSION collection 
		
		$_SESSION['level'] = $seclevel;// Member's Security Level
		$_SESSION['recid'] = $row['id'] ; // Member's current record
		$_SESSION['username'] = $row['username'];
		$_SESSION['user_id'] = $row['user_id'];
		$_SESSION['status'] = $rstatus;
		$_SESSION['type'] = $new_type;
		$_SESSION['status_updated'] = $row['status_updated'];
		$_SESSION['linkedin'] = $row['linkedin'];
		$_SESSION['profile_updated'] = $row['profile_updated'];
		$_SESSION['profile_validated'] = $row['profile_validated'];
		$_SESSION['email_last_validated'] = $row['email_last_validated'];
		$_SESSION['user_email'] = $row['user_email'];
		$_SESSION['typename'] = Defs::getMemberDescription ($rstatus);
		$_SESSION['DB'] = $row;
		$_SESSION['user'] = $row; #eventually switch everything to this

 		$q = "UPDATE `members_f2` SET last_login = NOW() where id = ?";
		$stmt = $pdo -> prepare($q);
		$stmt -> execute ([$row['id']]);
		
		#echoAlert ("returning $seclevel from login");
  return $seclevel;
}




function login_error($msg = ''){
	
	echoAlert ("No user for that login. ($msg)");
	
}

function logout(){
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


function nogo($alert_message = 'Not logged in correctly') {

	$member_type = $member_type_name = $member_status = $member_name = 'none';
  if (isset($_SESSION['pwid'])){
	$member_type = $_SESSION['type'];
	$member_type_name = $_SESSION['typename'];
	$member_status = $_SESSION['status'];
	$member_name = $_SESSION['username'];

	
	// $next_page = "<script type='text/javascript'>window.location.assign('/forbidden.php');</script>";
	
	$alert_message = "You are logged in, but your member type does not have access to this page.";
	
	
	#$stext = addslashes($alert_message);
	$stext = $alert_message;
	
	
	
   header('HTTP/1.0 401 Restricted');
   echo <<<EOT
<html>
<head>
<title>Forbidden</title>
<link rel='stylesheet' href='/css/flames2.css'>

</head>


<body>
EOT;
include SITE_PATH . '/navbar_div.php';

echo <<<EOT

<h1>Sorry, You Can't Do That.</h1>
<p>You have attempted to access a page or function which is not
accessible to your member type ($member_type_name).
</p>
<p>Please use the menu to continue. </p>
<p><b>If you believe
this is an error, please <a href='mailto:admin@amdflames.org'>contact the admin</a></b>.  Sometimes I break stuff.</p>
<hr>


<script type='text/javascript'>alert("$stext");</script>
</body></html>
EOT;

	exit;
	
  }

  else {#not logged in
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
}
/* *** DATABASE FUNCTIONS *** */





 function randPW() {
 //Generate a 5 digit password from 20 randomly selected characters
	global $GV;
	$pdo =  MyPDO::instance();
	 static $tb1 = array (0,1,2,3,4,5,6,7,8,9,'P','Q','W','X','V','b','r','z','k','n');
	 static $iterations = 0;
	 if ($iterations > 5){die ("Too many iterations of random password");}
	 $pass = "";
	 $q = "SELECT * from `members_f2`] WHERE upw = ?;";
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



function echoAlert($text) {
	$stext = addslashes($text);
    echo "<script type='text/javascript'>alert('{$stext}');</script>";
}


function get_row_for_login ($login){
	// returns record id for valid record for this login
	$pdo =  MyPDO::instance();

	if (strlen($login) < 6){return false;} #illegal
	
	$loginarray = split_login($login);
	
	$q = "SELECT * FROM `members_f2` WHERE user_id = ? and upw = ? and status NOT LIKE '%x%' ORDER BY id DESC;";
	#echo "$q<br>";
	$stmt = $pdo -> prepare ($q);
	$stmt -> execute ($loginarray);
	$row = $stmt-> fetch(PDO::FETCH_ASSOC);
		if (!$row){return false;}

	return $row	;	
}

function split_login($login){
		$pw = substr($login,0,5); // Split user_id from upw (user password)
		$user_id = substr($login,5);
		return array($user_id,$pw);
}


function get_member_by_id($id){
	$pdo =  MyPDO::instance();
	if (!$id){die ("get member by id called with no id");}
	
	if ($id){
		$stmt = $pdo -> prepare("SELECT * FROM members_f2 WHERE id = ?;");
		$stmt -> execute([$id]);
		$num_rows = $stmt -> rowCount() ;
		if ($num_rows == 1){
			$row = $stmt -> fetch(PDO::FETCH_ASSOC);
		}
		else {echo "No record found for id $id";return false;}
	}
	return $row;	
}
function get_member_by_uid($uid){
	$pdo = MyPDO::instance();
	
	if (!$uid){die ("get member by uid called with no uid");}
	$stmt = $pdo -> prepare("SELECT * FROM members_f2 WHERE user_id = ? ORDER BY id DESC;");
	$stmt -> execute([$uid]);
	$num_rows = $stmt -> rowCount() ;
	if ($num_rows == 1){
		$row = $stmt -> fetch(PDO::FETCH_ASSOC);
	}
	else {echo "No record found for user id $uid";return false;}
		
	return $row;	
}


?>
