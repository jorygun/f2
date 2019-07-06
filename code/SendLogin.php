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
   	if (! u\isValidEmail($email)){
   		echo "Invalid Email Requested";
   		exit;
   	}
      $members = $this->member->getMemberList($email);   
      #u\echor ($memberslist, 'Member List');
     if ($members['count'] == 0 ){
     	echo "No members found at that email.";
     	exit;
     }
     
     $message = self::$message;
     foreach ($members['data'] as $row){
     	 $login = SITE_URL . "/?s=" . $row['upw'] . $row['user_id'];
     	$message .= sprintf ("\t%30s    %s\n", $row['username'], $login);
     	}
     	
     	$message .= "
     	If you have any difficulties, contact the admin at
     	admin@amdflames.org
     	";
     	$message = u\email_std($message); 
     if (mail($email,'AMD Flames Logins',$message, "From: Flames admin <admin@amdflames.org>\r\n") ){
      		echo "Logins Sent.";
      }
      else {echo "Error";}
   
   
   }
}
