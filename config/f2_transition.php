<?php
/* this is to transition from old login system to new
   all old files call this to create a login and check security
   This causes the new login to be triggered instead.
   
 */
 
use digitalmx\flames\Login;

function f2_security_below ($min) {
	   $login = new Login();
	   $login->checkLogin($min);
   
   return false;
}

function security_below($min) {
   $login = new Login();
	   $login->checkLogin($min);
	return false;
}
