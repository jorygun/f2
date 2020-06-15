<?php
/* this is to transition from old login system to new
   all old files call this to create a login and check security
   This causes the new login to be triggered instead.

   Note this thing is kind of backward.  Question is level below,
   so true means forbid access.

 */

function f2_security_below ($min) {
	   return security_below($min);

}

function security_below($min) {
	   if ($_SESSION['level'] < $min) {
			echo "Permission Denied: (${_SESSION['level']} / $min )" . BRNL;
			exit;
		}
		return false;
}

class Voting extends \DigitalMx\Flames\Voting {};


