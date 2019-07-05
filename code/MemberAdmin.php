<?php
namespace digitalmx\flames;
// update to old level8 screen, plus incude function of update_member.php
// utilize members and messaging.
// all io through members


ini_set('display_errors', 1);
ini_set('error_reporting', -1);
//BEGIN START

	require_once 'init.php';
	require 'Member.php';
	use digitalmx\flames\Definitions as Defs;
	use digitalmx as u;
	use digitalmx\flames\Member;
	

	
//END START

    
		
class MemberAdmin {
	private static  $members_db = 'members_f2';
	private $pdo;
	private $member;
	
	
	public function __construct($pdo){
		$this->pdo = $pdo;
		$this->member = new Member ($pdo);
		
	}
	
	
	private function echo_user_row ($row,$post=''){
       #$fields = array('status','email_status', 'email_last_validated','record_updated','last_login','no_bulk');
		 $uid = $row['user_id'];
		  $urlemail = rawurlencode($row['user_email']);
		  
    $o = "<tr><td style='border-top:3px solid green' colspan='8'></td></tr>";
        $username = u\entity_spec($row['username']);
      $o .=  "<tr>
        <td colspan='2'><b>$username</b></td>
			<td colspan='2' >" . u\linkHref($row['user_email']) . "</td>";
         $login = $row['upw'] . $row['user_id'];
        $user_login_link = "https://amdflames.org/?s=$login";
      
        $o .=  "<td colspan='4'>User Login: $user_login_link <a href='$user_login_link' target='user_login'>Log in as</a></td></tr>";

       

       

        $o .=   "<tr><td align='center'><a href='#' onclick=\"window.open('/scripts/send_lost_link.php?email=$urlemail','lostlink','height=200,width=400,x=200,y=200');return false;\">Send Login</a></td>";
        $o .=   "<td align='center'><a href='/scripts/profile_view.php?id=$id' target='profile'>Profile</a></td>";
        $o .=   "<td align='center'><a href='/scripts/update_member.php?id=$id&email_status=LB' target='$username'>Bounces</a></td>";
        $o .=   "<td align='center'><a href='/scripts/verify_email.php?r=$id' target='verify'>Verify Email</a></td>";
        $o .=   "<td align='center'><a href='/scripts/update_member.php?id=$id' target='$username'>Update</a></td>";
        $o .=   "<td align='center'><a href='/scripts/edit_member.php?id=$id' target='$username'>Edit</a></td>";
        $o .=   "<td align='center'><a href='/scripts/mark_contributor.php?id=$id' target='_blank'> Donor</a></td>";
       // echo "<td align='center'><a href='/scripts/xout.php?xid=$id&post=$post' target='_blank'>X out</a></td>";
        $o .=   "<td align='center'><button name='xout' value='$id' type='submit'>Xout</button></td>";
        $o .=   "</tr>\n";
		return $o;
}

function xout($id,$members_db){
    $pdo = MyPDO::instance();

    $sql = "UPDATE `$members_db` SET status = 'X' WHERE id = $id;";
    echo $sql;
    if (! $result = $pdo -> query($sql)) {return ("x out failed");}
    else {return "OK";}


}

	public function search($post){
   
     $_SESSION['last_member_search'] = $post;
	
	$result = $this->member->getMembersForAdmin($post);
   u\echor ($result,'from Member');
   
      if ($result['count'] == 0){echo "Nothing Found.";}
		else{
        		echo $result['info'] . BRNL;
        		
            echo "<form>
            <table style='border-collapse:collapse;font-size:small;'>";

            echo "<tr>
            	<th>Name</th>
            	<th>Status</th>
            	<th>Email</th>
            	<th>Email Status</th>
            	<th>Email Validated</th>
            	<th>Last Login</th>
            	<th>No Bulk</th>
            	</tr>";
            	

            foreach ($result['data'] as $row){
            
                echo echo_user_row($row);
            }
           
           echo "</table></form>\n";

        }

}

	
	
	public function pagehead($navbar){
	$nav = new navBar(1);
	$navbar = $nav -> build_menu();
	return <<<EOT
<html>
<head>
<title>User Admin Page</title>

<link rel='stylesheet' href='/css/flames2.css'>
</head>

<body>
$navbar
<h1>User Admin</h1>

EOT;
	}
	

 public function showSearch(){
 	$status_options = "<option value=''>Choose...</option>";
	foreach (array('M','G','MC','MU','MN','N','T','I') as $v){
		$desc = Defs::getMemberDescription($v);
		$status_options .= "<option value='$v'>$v ($desc)</option>";
	}

    $ems_options = "<option value=''>Choose...</option>";
    foreach (Defs::getEmsNameArray() as $v=>$desc){
		$ems_options .= "<option value='$v'>$v ($desc)</option>";
	}



 	$o = <<<EOT
<p><b>Locate a Member to update</b></p>
To modify a member's record (including accepting new signups) find the member
here. Name and email can be partials.
<form  method = 'POST'>
<table style = 'font-size:small;'>
<tr><th>Find by name: </th><th>Find by email:</th><th>Find by status:</th>
    <th>Find by Email Status</th><th>Admin Status</th></tr>
<tr>

    <td> <input type='text' name = 'name' ></td>
    <td><input type='text' name='email'></td>
    <td><select type='text' name='status'>$status_options</select></td>
   <td><select type='text' name='ems'>$ems_options</select></td>
     <td>Admin Status:<input type="text" name="admin_status" size='4'></td></tr>
</table>
<input type=submit name='search' value='Search'>
</form>
EOT;
	return $o;
	
	}
	
}	



