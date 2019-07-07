<?php
namespace digitalmx\flames;
// update to old level8 screen, plus incude function of update_member.php
// utilize members and messaging.
// all io through members

//the member admin search and the member admin update both use 
// the routines in this class.



ini_set('display_errors', 1);
ini_set('error_reporting', -1);
//BEGIN START

	require_once 'init.php';
	use digitalmx\flames\Definitions as Defs;

	use digitalmx as u;
	use digitalmx\flames\Member;
	use digitalmx\flames\Messenger;
	use digitalmx\flames\DocPage;

	
//END START

    
		
class MemberAdmin {
	private static  $members_db = 'members_f2';
	
	private $member;
	private $page;
	private $messenger;
	private $pdo;
	
	
	
	public function __construct(){
		$this->pdo = \MyPDO::instance();
		$this->member = new Member ($this->pdo);
		$this->page = new DocPage();
		$this->messenger = new Messenger($this->pdo);
	}
	
	// this function just echos out the data in a list of found members.
	private function echo_user_row ($row,$post=''){
       #$fields = array('status','email_status', 'email_last_validated','record_updated','last_login','no_bulk');
		 $uid = $row['user_id'];
		  $urlemail = rawurlencode($row['user_email']);
		   $username = u\entity_spec($row['username']);
		  $last_login = date('d M, Y',strtotime($row['last_login']));
		  $email_last_validated = date('d M, Y', strtotime($row['email_last_validated']));
    $o = "<tr><td style='border-top:3px solid green' colspan='8'></td></tr>";
       
      $o .=  "<tr>
        <td colspan='2'><b>$username</b></td>
			<td colspan='2' >" . u\linkHref($row['user_email']) . "</td>";
         $login = $row['upw'] . $row['user_id'];
        $user_login_link = "https://amdflames.org/?s=$login";
      
        $o .=  "<td colspan='4'><a href='$user_login_link' target='_blank'>$user_login_link</a> 
        <button type='button' onClick='sendLogin($uid)'>Send Login</button></td></tr>";

       $o .= "<tr style='text-align:center'>
       <td>${row['status']}</td>
       <td>${row['email_status']}</td>
        <td>$email_last_validated</td>
        <td>$last_login</td>

          <td>${row['no_bulk']}</td>
          <td></td>
          <td></td>

       </tr>";

       

        $o .=   "<tr>";
        $o .=   "<td align='center'><a href='/scripts/profile_view.php?id=$uid' target='profile'>Profile</a></td>";
        $o .=   "<td align='center'><a href='/scripts/update_member.php?id=$uid&email_status=LB' target='$username'>Bounces</a></td>";
        $o .=   "<td align='center'><a href='/scripts/verify_email.php?r=$uid' target='verify'>Verify Email</a></td>";
        $o .=   "<td align='center'><a href='/scripts/update_member.php?id=$uid' target='$username'>Update</a></td>";
        $o .=   "<td align='center'><a href='/scripts/edit_member.php?id=$uid' target='$username'>Edit</a></td>";
        $o .=   "<td align='center'><a href='/scripts/mark_contributor.php?id=$uid' target='_blank'> Donor</a></td>";
       // echo "<td align='center'><a href='/scripts/xout.php?xid=$uid&post=$post' target='_blank'>X out</a></td>";
        $o .=   "<td align='center'><button name='xout' value='$uid' type='submit'>Xout</button></td>";
        $o .=   "</tr>\n";
		return $o;
	}

	private function xout($uid){
		$this->members->setStatus($uid,'X');
	
	}

	public function search($post){
   //save search so can be repeated
     $_SESSION['last_member_search'] = $post;
	
	$result = $this->member->getMemberListFromAdmin($post);
  # u\echor ($result,'from Member');
   
   if ($result['count'] == 0){echo "Nothing Found.";}
	else{
        		echo $result['info'] . BRNL;
        		
            echo "
            <table style='border-collapse:collapse;font-size:small;'>";

            echo "<tr>
            	
            	<th>Status</th>
            	
            	<th>Email Status</th>
            	<th>Email Validated</th>
            	<th>Last Login</th>
            	<th>No Bulk</th>

            	<th></th>
            	<th></th>

            	</tr>";
            	

            foreach ($result['data'] as $row){
            
                echo $this->echo_user_row($row);
            }
           
           echo "</table>\n";

        }

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
	
	

/* this script is used to make all  updates to members records.
	You can change email status or user status.  Changing a user from New
	to Member automatically sends the welcome message.

	Updating email status will send a verify email if appropriate.

*/

// convert Get data yo Post data
 public function member_edit(){
	if (isset($_GET['id'])) {$_POST['uid'] = $_GET['id'];}
	$uid = $_POST['uid'];
	
	if (isset($_GET['email_status'])){
		$_POST['ems'] = $_GET['email_status'];
	}
	
	// start by getting users record.  Needed for both get and put
	$md = $member->getMemberData($uid);
	$mdd = $md['data'];
	$username = $mdd ['username'];
	

// echo $page->getHead('Member Update');
// echo $page ->startBody("Act On Member : $username");

// process any data in the post array
	extract ($_POST,EXTR_PREFIX_ALL,'P');
	if (empty ($P_uid)){ #?? think there should always be something here 
		exit;
	}
	//go over data and find updates and perform as encountered
	
	if (!empty($P_new_email)){ #new email address; update and send verify
		echo "<p>New Email: $P_new_email</p>";
		if (! u\isValidEmail($P_new_email)){
			echo "Invalid Email address $P_new_email<br>\n";
			exit;
		}
		// put new email in place for messenger
		$member->setEmail ($uid,$P_new_email);
		if (substr($mdd['status'],0,1) == 'L'){ #member was lost
			$informant = 'you';
			if (!empty ($P_informant)){
				$informant = $P_informant;
			} elseif (isset($P_suggested_email )){
				$informant = 'another member';
			}
			$extra = array(
				'informant' => $informant,
				'prior_email' => $mdd['user_email'],
				);
			$messenger->sendMessages($uid,'em_found');
			
			
		}
		else {
			$messenger->sendMessages($uid,'em_change');
		}
		$P_email_status = 'E1';
		$use_email = $P_new_email;


	}

	if (!empty ($P_email_status)){
		echo "<p>New Email Status: $P_email_status</p>";
	
		$member->setEmailStatus($uid,$P_new_status);
		$messenger->sendMessages($uid,'ems-' . $P_email_status);
	
	}


	if (!empty($P_new_status)){
		echo "<p>Status Change: $P_new_status</p>";
		$member->setStatus($uid,$P_new_status);
		if ($P_new_status == 'D'){ #deceased
			$member->setEmailStatus($uid,'LD');
		}
		if (
			(empty($mdd['status']) or $mdd['status'] == 'N') 
			&& in_array($P_new_status,Defs::getMemberInList())
			){
				$extra = array(
##FIX THIS###
				'login' => 'login',
				);
				$messenger->sendMessages($uid,'welcome',$extra);
		}
	}
	
	if (!empty($P_admin_status)){
		echo "<p>Change Admin Status: $P_admin_status</p>";
		$member->setAdminStatus($uid,$P_admin_status);
	}

	if (!empty($P_new_name)){
		echo "<p>change user name</p>";
		$member->setUserName($uid,$P_new_name);
		
	}

	
	$nobulkclear = ($mdd['no_bulk'] && ! isset($P_nobulk))?1:0;
	$nobulkset = (! $mdd['no_bulk'] && isset($P_nobulk))?1:0;
	if ($nobulkclear or $nobulkset){
		echo "p>Bulk Mail Changed</p>";
		if ($nobulkclear){
			$member->setNoBulk($uid,0);
		}
		elseif ($nobulkset){
			$member->setNoBulk($uid,1);
			$messenger->sendMessages($uid,'nobulk');
		}
	}

	if (!empty($P_current) && ($P_current <> $mdd['user_current'])) {
		echo "Updating user's current information.<br>";
		$member->setCurrent($uid, $P_current);
	}


	if (!empty($P_admin_note) && ($P_admin_note <> $mdd['admin_note']) ){
		echo "Updating admin note<br>";
		$member->setAdminNote($uid, $P_admin_note);
	}
	

	
	
	// reset my row with updated data
	$md = $member->getMemberData($uid);
	$mdd = $md['data'];
}
## end of update

public function sendLogin($id) {
        // can receive a user_id or an email as input
        if (is_numeric($id)){
            $where =  "user_id = '$id'";
        }
        elseif (filter_var($id,FILTER_VALIDATE_EMAIL)){
            $where = "user_email = '$id'";
        }
        else {throw new Exception ("Request $id not valid.");}
        
        $sql = "SELECT username,user_id,upw,user_email FROM `members_f2`
            where $where";
        if (!$result = $this->pdo->query($sql) ){
            throw new Exception ("get user row in sendlogin failed");
        }
        if ($result->rowCount() == 0) {return "No Members Found";}
        $format = "   %-25s %-50s\n";
        $msg = sprintf($format,"Member Name",  "login url");
        foreach ($result as $row){ #there may be more than one
            $login = 'https://' . SITE_NAME . "/?s=" . $row['upw']  . $row['user_id'];
            //echo $login;
            // send message with login
            $msg .=  sprintf($format,$row['username'], $login );
        }
        
        $data = array(
                'username' => $row['username'],
                'email'=>$row['user_email'],
                'msg'=>$msg,
            );
       // u\echoR($data,'to sendit');
        
       # $messenger = $this->messenger->sendit('sendlogin',$data);
        return "Login Sent";
    
    }
// GEt PAGE
public function show_update() {

     // Start a display table
    $login_string = "https://amdflames.org/?s=${mdd['upw']}${mdd['user_id']}";

  echo <<<EOT
  <h3 class='y_row'>${mdd['username']}</h3>
  (user_id = ${mdd['user_id']})


  <form action="$_SERVER[PHP_SELF]" method="POST">
  <input type='hidden' name='uid' value='$uid'>
  
	
  <table border='1' cellpadding='2' cellspacing='0'>

EOT;
// Set headings
	$cn_fields = array(
		'status','status_updated','admin_status', 'last_login','profile_updated','profile_validated','no_bulk');

	$en_fields = array('user_email','email_status','email_status_time','email_last_validated','email_chg_date','prior_email');



    // build option fields
		$target_status = $mdd['status'];
		$nm = ($target_status == 'N')?'(Send Welcome)':'';

		$status_options = array(
		'--'	=> '',
		"Member $nm" => 'M',
		"Guest $nm" => 'G',
		"Reader $nm" =>'R',
		'Test' => 'T',
		'X'	=> 'X',
		'Lost'	=> 'L',
		'Inactive' => 'I',
		'Deceased'	=> 'D'
		);

		$status_contribute =array (
			'--News--' => '',
         'Contributor' => 'MC'
		);
		$status_admin =array (
			'--Admins--' => '',
			'Publisher' => 'MN',
			'User Admin' => 'MU'
		);


		$email_status_options = '';
		#only allow certain changes.  x-bad, a-start validation y-verified q-unknown
		foreach (array('A1','Y','Q','LO','LB','XX','A2','A3','B1') as $k){
			if ($k <> $mdd['email_status']){$email_status_options .= "<option value='$k'>$k " . Defs::getEmsName($k) . "</option>";}
		}

		if ($_SESSION['level'] > 6) {  #news admin
			$status_options = array_merge($status_options,$status_contribute );
		}

		if ($_SESSION['level'] > 8) { #admin admin
			$status_options = array_merge($status_options,$status_admin );
		}

		  $target_email = $mdd['user_email'];
		  if ($target_email){$show_email = "<a href='mailto:$target_email'>$target_email</a>";}
		  else {$show_email = '';}


			// now show target data

			//print heading row
                echo '<tr>';
                foreach ($cn_fields as  $v){echo "<th>$v</th>";}
                echo "</tr>\n";
		// print data for cn fields
				echo "<tr class = 'y_row'>";
				foreach ($cn_fields as $k){
					echo "<td>$mdd[$k]</td>";
				}
				echo "</tr>

				</table><table><tr>\n";
				foreach ($en_fields as $k){
					echo "<th>$k</th>";
				}
				echo "</tr><tr class = 'y_row'>";
				foreach ($en_fields as $k){
					echo "<td>$mdd[$k]</td>";
				}
				echo "</tr></table><table><tr>\n";

				echo "<tr><td><b>User Current</b></td><td class = 'y_row' > ${mdd['user_current']}</td></tr>";
				echo "<tr><td><b>At AMD</b></td><td class = 'y_row' > ${mdd['user_amd']}</td></tr>";
				echo "<tr><td>Admin Note</td><td class = 'y_row'> ${mdd['admin_note']}</td></tr>";
				echo "</table>\n";

	  #now show action fields

	  	$new_warning = ($target_status == 'N')?"<p>THIS IS A NEW SIGNUP.  Changing status to M or G will assign
	  	this person a user_id and send out a welcome message. </p>":'';

	  	$nobulkchecked = $mdd['no_bulk'] ? 'checked':'';

	  	$validate_email_click = verify_click_email($mdd['id'],'');

    echo <<<EOT

	  <h3 style="border-top:1px solid black;">Actions on this record</h3>


	 	<table>
	 		<columns>
	 		<col width="50%">
	 		<col width="50%">
	 		</columns>

	 	<tr><td><p><b>Change email address</b><br>This will change email_status to E1 and send out a verification email. This change will occur before any of the other actions listed below. If suggested by
	 	someone else is checked, then an explanatory email also goes to the new
	 	address.</p> </td><td>New email: <input type='text' name = 'new_email' size=60>
	 	<br><input type=checkbox name='suggested_email' id='suggested_email' >New Email suggested by someone else. <input type=text id='informant' name='informant' placeholder='Another FLAME member'oninput="check_the_box('suggested_email',true);"></td></tr>

	 	<tr style="background-color:#F90; "><td><p><b>Update user status</b>$new_warning</td><td><select name='new_status'>
EOT;
		foreach ($status_options as $k => $v){echo "<option value='$v'>$k</option>\n";}

		echo <<<EOT
	  	</select></td></tr>

	 	<tr><td><p><b>Change User Name</b><br></p></td><td>
	 	New User Name: <input type='text' name='new_name' size=40></td></tr>



	  	<tr><td><b>No Bulk</b> Set/Clear the No Bulk tag for this users.</td><td>No Bulk <input type="checkbox" name=nobulk $nobulkchecked >
	  	<input type='hidden' name='nobulkchecked' value='$nobulkchecked' >
	  	</td></tr>

	  	<tr><td><b>Change Email Status</b> </td><td>email_status (currently ${mdd['email_status']} ):
	  	<select name='email_status'><option value=''>Leave as ${mdd['email_status']}</option>
	  		$email_status_options</select><br>
	  		(Note: changing to A1 will send a validation email.)

	  	</td></tr>
	  	<tr><td><b>Admin Status</b></td><td>(currently ${mdd['admin_status']}):
	  	<input type="text" size="4" name="admin_status">
	  	</td></tr>

	  	<tr><td><b>Mark Current Email Valid</b></td><td>$validate_email_click</td></tr>

	  	<tr><td><p><b>Update user's current information.</b> For deceased members, indicate date and other info.</td><td>
	  	<textarea  name='current' cols = '40' rows = '8'>${mdd['user_current']}</textarea></td></tr>

	  	<tr><td><p><b>Update the Admin Note.</b>  </td><td>
	  	<textarea  name='admin_note' cols = '40' rows = '8'>${mdd['admin_note']}</textarea></td></tr>

	  	<tr><td><b>Send Lost Link to this user.</b>  This will happen AFTER all the changes above.</td><td>Send lost link: <input type='checkbox' name='sendlost'></td></tr>


	  	<tr><td ><input type='submit' name='submit' value='Do It' style='background:#6F6; width:12em;'></td><td></td></tr>

	  	</table>

		</form>
<hr>
    <form action='../level8.php' method='post'>
    Search for another name <input type='text' name='name'>
    <input type='hidden' name='submit' value='Search'>
    <input type='submit' >
</form>
</body></html>

EOT;

}

#####  FUNCTIONS ############


function update_email ($row,$new_email){ #$id,$name,$old_email,$new_email){

    $ems = new EmsMessaging( MyPDO::instance());
    
    $pdo = MyPDO::instance();
	//  if email is changed, send message to old email and verify to new email
	//
	$id = $row['id'];
	$old_email = $row['user_email'];
	$name = $row['username'];
	$login="s=${row['upw']}${row['user_id']}";
	$login_string = "https://amdflames.org/?$login";
	$verify_string = "https://amdflames.org/scripts/verify_email.php?$login";
    if ($old_email == $new_email){
        echo "Email not changed."; return 1;
    }

	echo "Updating Email for $name from $old_email to $new_email<br>";

	$old_msg = <<< EOT
	The email on the AMD Alumni site for $name has been changed
	to $new_email.

	If this is not correct, please contact the site administrator
		at admin@amdflames.org, or just reply to this message.

EOT;
    $join_date_text = date('j F Y', strtotime($row['join_date']));
    $informant = (empty($_POST['informant']))?'another member':$_POST['informant'];
    $found_msg = <<<EOT
    Your email address on the AMD Alumni site amdflames.org has just
been updated to $new_email.

    You have been a member of amdflames.org along with about 2000 other
ex-AMDers since $join_date_text, but somewhere along the line we lost
a working email for you.

Each week we publish newsletter with a list of a few “lost members” -
lost because we have no email, or the one we have bounces, or they did not
respond to several requests to confirm their email.

    Your name showed up recently, and $informant suggested that
this is the correct email for you.  I have updated your email address with
this one, and also set your account to receive the weekly update email.

  PLEASE CLICK THE LINK BELOW TO CONFIRM THIS EMAIL ADDRESS.
  $verify_string

    Your login to the site is shown below.  You can use this to enter the site
and provide a different email, confirm your email, update your current
information, and block weekly emails from the site. Just
log in and Edit Profile.

Your personal login to the amdflames site:
    $login_string

Thanks
Flames Admin
EOT;

	// record new email and set email status to Q; will be reset immediately by the verify process
	$q = "UPDATE `members_f2` SET user_email = '$new_email',email_status='E1'  WHERE id = $id;";
			 $result = $pdo->query($q);
			if ($result){echo "<br>Database Updated<br>";} #prior_email and date setr by trigger}
			else {die ("Database update in update_email failed.");}


	mail($old_email,"AMD Alumni Site: Email for $name has been changed",$old_msg, "From: Flames Administrator <admin@amdflames.org>\n\r");
	if (isset($_POST['suggested_email'])){
	    mail ($new_email,
	"AMD Alumni Site: Email for $name has been updated",
	$found_msg, 'admin@amdflames.org');

	}

	else {$ems->update_ems ($id,'E1');
	#will immediately set the status to E1 and send out verify email
	}
	return 1;
}



}	



