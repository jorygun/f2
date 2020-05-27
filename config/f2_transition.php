<?php
/* this is to transition from old login system to new
   all old files call this to create a login and check security
   This causes the new login to be triggered instead.

 */

function f2_security_below ($min) {
	   return security_below($min);

}

function security_below($min) {
	   if ($_SESSION['level'] >= $min) {return true;}
		echo "Not Logged In: " . $_SESSION['level'] . $min . BRNL;
		exit;
}
