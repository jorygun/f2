<?php
/*   verify script receives email and get session data from user login  from link on verify email:
		 $GV[siteurl]/scripts/verify_email.php?s=$login&m=$uemenc [ = rawurlencode($user_email) ]

*/

if ($ident = $_GET['s']){
  		$uid = substr($ident,-5); #last 5
  		if (!is_numeric($uid)){
  			throw new Exception ("Could not determine correct action.  
  			Please contact admin at admin@amdflames.org .");
  		}
	header ("location: /action.php/?V" . $uid);

} else {throw new Exception ("Could not determine correct action.  
  			Please contact admin at admin@amdflames.org .");
  		}
  		
  	
