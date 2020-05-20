<?php
/* this is to transition from old login system to new
   all old files call this to create a login and check security
   This causes the new login to be triggered instead.

 */

use DigitalMx\Flames\Login;

function f2_security_below ($min) {

	   $login->checkLevel($min);

   return false;
}

function security_below($min) {

	   $login->checkLevel($min);
	return false;
}
