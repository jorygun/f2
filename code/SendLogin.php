<?php
namespace digitalmx\flames;

use digitalmx\flames\members;
use digitalmx\flames\Definitions as Defs;
use digitalmx as u;

class SendLogin {

   private $pdo;
   private $member;
   
   private static $message = <<<EOT
   Someone has requested the logins for users assocated with
   this email address at amdflames.org.
   
   Below are the names and logins for these users.
   There may be more than one user at the same email.
   
EOT;

   public function __construct($pdo) {
         $this->pdo = $pdo;
         $this->member = new Member($pdo);
   
   }
   public function sendLink($email) {
      $memberslist = $this->member->getMemberList($email);   
      #u\echor ($memberslist, 'Member List');
     if ($memberslist['count'] == 0 ){
     	echo "No members found at that email.";
     	exit;
     }
     
     $message = $this->$message;
     foreach ($memberlist['data'] as $row){
     	 $login = SITE_URL . "/?s=" . $row['upw'] . $row['user_id'];
     	$message .= "   " . $row['username'] . '    ' . $login . "\n";
     	}
     	
     	$message .= "
     	If you have any difficulties, contact the admin at
     	admin@amdflames.org
     	";
     	
     	$mail($email,'AMD Flames Logins',$message, "From: admin@amdflames.org\r\n");
      echo "Logins Sent."
   
   
   }
// function send_lost_link($this_email){
// 
// 
// 
// 	$output = '';
// 	$msg = "Below is the link for the access to the FLAMEsite attached to $this_email .\n
// 	There may be more than one.\n\n";
// 
// 	if (!$this_email){return "No email provided for send_lost_link";}
// 
// 	$this_email = trim($this_email);
// 	if (! filter_var($this_email, FILTER_VALIDATE_EMAIL)) {
// 	  $output .=  "<br/>Bad Address - $this_email - is not a valid email address.</span>";
// 	  $output .=  "<p><a href='" . SITE_URL . "'>Return to main page</a></p>";
// 	  return $output;
// 
// 	}
// 
// 
// 
// 	 // Look up this address in DB
// 		echo "Looking for $this_email<br>\n";
// 		$q = "SELECT upw, user_id, username, user_email from `members_f2` WHERE user_email LIKE '$this_email'
// 		AND status NOT in('x','d','n');";
// 
// 	   if (!$result = $pdo->query($q) ){
// 	  
// 		$output .= "<p>$this_email was not not found in the member file.";
// 		$output .=  "<p>Please <a href='mailto:admin@amdflames.org'>contact the administrator</a>.</p>";
// 		$output .=  "<p><a href='" . SITE_URL . "'>Return to main page</a></p>";
// 		return $output;
// 
// 	   }
// 
// 	 // List each match (some addresses are shared by two or more members)
// 		 foreach ($result as $row){
// 		  
// 			$login = $row['upw'] . $row['user_id'];
// 		   $msg .= "${row['username']}:" . SITE_URL . "/?s=$login\n";
// 
// 		  }
// 
// 
// 	 // mail("admin@amdflames.org", $subj, $msg, $hdrs);
// 	 $hdrs =	"From: admin@amdflames.org";
// 	 
// 
// 	 mail($this_email,"Your FLAMEsite Login",$msg,$hdrs);
// 
// 	 	 $output .=  "Your login link has been emailed to &lt;${this_email}&gt;.";
// 
// 	 return $output;
//  }
// 
// 
// 
// 


}
