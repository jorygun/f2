<?php

namespace DigitalMx\Flames;

use DigitalMx as u;
use DigitalMx\Flames as f;

class Login
{

   private $member;
	private $loginfo;
	private $menubar;

    public function __construct ($container)
    {
        foreach (['member'] as $dclass) {
			$this->$dclass = $container[$dclass];
		}
		$this->logger = $container['logger-dbug'];


    }


    /*
        receives login/security request checks s= for login, logout, no change
        check for get[s].
        if empty, is there a current login
            if yes, go on
            if no, log in as non-member.
        else
            is login same as existing login
                if yes, go on
                if not, logout old user; log in new uyser
        check min level



    */


    public function checkLogin ($min=0)
    {
            if (isset ($_GET['s']) ){
                $login_code = $_GET['s'] ;

                #uid 0 for non-member
                if ($uid = $this->member->checkPass($login_code) ) {
                	$this->logger->info("Login user $uid");
                } else {
                	$this->logger->info("Login failed with $login_code");
                }
                #u\echoAlert ("new login user $uid");

            } else {
                $login_code = '';
                $uid = 0;
            }


            if (isset ($_SESSION['login'])){ #already logged in, as member or non-member
                $login_user = $_SESSION['login']['user_id'];
                #u\echoAlert ("Reading session " . session_id() . " logged in uid: " . $login_user);
            }
            else {
                $login_user = -1; #flag for no login at all
            }


            if ($login_code){
                #echo " s-code: $login_code" . BRNL;
                if ($login_code == 'logout' ){
                    if ($login_user > 0) {
                        $this->logout();
                    }
                    else {
                        u\echoAlert ( "Not logged in; cannot log out." );
                        exit;
                    }
                }

                elseif ($login_code == 'relogin'  ) {
                    #relogin current user
                    if ($login_user > 0) {
                        #u\echoAlert ( " Re-login as $login_user." );
                        $log_info = $this->member->getLoginInfo($login_user);
                        $this->setSession($log_info);
                    }
                    else {
                        u\echoAlert ("Not logged in; cannot re-login.");
                        exit;
                    }

                }

                else { #any other login code

                    if ($uid == $login_user) {
                        #if no login, uid = 0 but login = -1; it won't match
                        #same user; do nothing
                    }
                    else  {
                        #u\echoAlert ("new login " );
                        #$this->logOut($_SERVER['REQUEST_URI']); #relog in with same uri
                        session_unset();
                        $log_info = $this->member->getLoginInfo($uid);
                        $this->setSession($log_info);
                    }


                }
        #no login code
        } elseif ($login_user >= 0) {
                #u\echoAlert ( "No s-code; already logged in. Done.");

        } else {
            #login as non mmeber
                #u\echoAlert ( "no login; no current.  Non-member login");
                $log_info = $this->member->getLoginInfo(0);
                $this->setSession($log_info);
        }
        return $this->checkLevel($min); // for compabiliyy with oldeer scripts
    }



    //checks security level and issues 403
    public function checkLevel($min)  {
        $user_level = $_SESSION['level'] ?? 0;
        if ($user_level < $min) {
            #failed security
            	echo "<h3>403 Denied</h3>
            		<p>Permission denied.<br>

            		";
               # u\echor ($_SESSION['login'], 'login'); exit;

                header ( "location:/403.html");

        }
        return true;
}

    private function setSession ($log_info)  {
        // sets vars in session
       // u\echor($log_info, 'Saving session ' . session_id() ) ;

        $this->loginfo = $log_info;
        $_SESSION['login'] = $log_info;
        $_SESSION['level'] = $log_info['seclevel'];
		$this->member->setLastLogin($log_info['user_id']);

        return true;
    }


	public function getLoginfo() {
		return $this->loginfo;
	}
    private function logOut($next ='/'){
// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
        #echo "Logging out now.";
        $_SESSION = array();
        if (ini_get('session.use_cookies'))
        {
             $p = session_get_cookie_params();
             setcookie(session_name(), '', time() - 31536000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
         }
        session_unset();
        session_destroy();
        $location = $next;

        header ("Location: $location");

        #"<script>window.location.href='/';</script>\n";


    }

}
