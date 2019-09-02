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

#	require_once 'init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	
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
		$this->pdo = u\MyPDO::instance();
		$this->member = new Member ();
		$this->page = new DocPage();
		$this->messenger = new Messenger();
	}
	
//	this function just echos out the data in a list of found members.
	private function echo_user_row ($row,$post=''){
       #$fields = array('status','email_status', 'email_last_validated','record_updated','last_login','no_bulk');
		 $uid = $row['user_id'];
		 
		$status_id = "stat-$uid";
		 $emstat_id = "emstat-$uid";
		 $emver_id = "emver-$uid";	
		$cdate_id = "cdate-$uid";
		   
		$bounceEmailButton = f\actionButton('Bouncer','bounceEmail',$uid,$emstat_id,'bounced');
		  $urlemail = rawurlencode($row['user_email']);
		   $username = u\entity($row['username']);
		  $last_login = date('d M Y',strtotime($row['last_login']));
		  $email_last_validated = date('d M Y', strtotime($row['email_last_validated']));
		  	#$validateEmailButton = f\actionButton('Verify Email','verifyEmail',$uid,'emver-id');
		  	$validateEmailButton = "<button type='button' onClick='verifyEmail($uid)'>Verify</button>";
		  
		 
		  	 $markContributeButton = f\actionButton('Contributed','markContribute',$uid,"$cdate_id");
		  	$contribute_time = strtotime($row['contributed']);
		  	if ($contribute_time == 0){
		  		$cdate = 'Never';
		  	} else {
		  		$cdate = date('d M Y', $contribute_time);
		  	}
		 
		  	
		  
		  	
    $o = "<tr><td style='border-top:3px solid green' colspan='8'></td></tr>";
       
      $o .=  "<tr>
        <td colspan='2'><b>$username</b</td>
			<td colspan='2' >" . u\linkHref($row['user_email']) . "</td>";
         $login = $row['upw'] . $row['user_id'];
        $user_login_link = "https://amdflames.org/?s=$login";
     
      $o .=  "<td colspan='4'><a href='$user_login_link' target='_blank'>$user_login_link</a> ";
		$o .= f\actionButton('Send Login','sendLogin',$uid,'','Login Sent');
		$o .= "</td></tr>\n";
		
       $o .= "<tr style='text-align:center'>
       <td id = '$status_id'>${row['status']}</td>
       <td id='$emstat_id'>${row['email_status']}</td>
        <td id='$emver_id'>$email_last_validated</td>
        <td>$last_login</td>
 <td>${row['profile_date']}</td>
          
          <td id='$cdate_id'>$cdate</td>
         <td>${row['no_bulk']}</td>

       </tr>";

  

        $o .=   "<tr>";
       $o .=   "<td align='center'> " 
        		. f\actionButton('X-out','xout',$uid,$status_id) 
        		. "</td>";
        $o .=  "<td align='center'>$bounceEmailButton</td>";
        $o .=   "<td align='center'>$validateEmailButton</td>";
        $o .=   "<td align='center'><a href='/member_admin.php?id=$uid' target='$username'>Update</a></td>";
       
         $o .=  "<td align='center'><a href='/scripts/profile_view.php?id=$uid' target='profile'>Profile</a></td>";
        $o .=   "<td align='center'>$markContributeButton</td>";
     
      $o .= "</tr>\n";
       
        		
		return $o;
	}

	private function xout($uid){
		$this->members->setStatus($uid,'X');
	
	}
	
	 
   
	public function listMembers($post){
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
            	
            	<th>Profile Validated</th>
            	<th>Contributed</th>
            	<th>No Bulk</th>

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

//convert Get data yo Post data

 public function updateMember ($post){
	$uid = $post['uid'];
	//start by getting users record.  Needed for both get and put
	$md = $this->member->getMemberData($uid);
	$mdd = $md['data'];
	$username = $mdd ['username'];
	
//process any data in the post array
	extract ($post,EXTR_PREFIX_ALL,'P');
	if (empty ($P_uid)){ #?? think there should always be something here 
		exit;
	}
	//go over data and find updates and perform as encountered
	#u/echor($post); exit;
	
	if (!empty($P_new_email)){ #new email address; update and send verify
		$new_email = trim($P_new_email);
		echo "<p>New Email: $new_email</p>";

		if (filter_var($new_email, FILTER_VALIDATE_EMAIL) === false){

			echo "Invalid Email address $new_email<br>\n";
		}
		

		//put new email in place for messenger
		$this->member->setEmail ($uid,$new_email);
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
			$this->messenger->sendMessages($uid,'em-found',$extra);
			
		}
		else {
			$this->messenger->sendMessages($uid,'em-change');
		}
		$P_email_status = 'E1';
		$use_email = $new_email;


	}

	if (!empty ($P_email_status)){
		echo "<p>New Email Status: $P_email_status</p>";
	
		$this->member->setEmailStatus($uid,$P_email_status);
		$this->messenger->sendMessages($uid,$P_email_status);
	
	}


	if (!empty($P_new_status)){
		echo "<p>Status Change: $P_new_status</p>";
		$this->member->setStatus($uid,$P_new_status);
		if ($P_new_status == 'D'){ #deceased
			$this->member->setEmailStatus($uid,'LD');
		}
		/* is this a new member?
		// no do this in a signup function, not here.
		- move from signup table
		to mmember table, sned welcome.
		
		
		if (
			(empty($mdd['status']) or $mdd['status'] == 'N') 
			&& in_array($P_new_status,Defs::getMemberInList())
			){
				// send welcome message
				$extra = array(
				
				'login' => 'login',
				);
				$this->messenger->sendMessages($uid,'welcome',$extra);
		}
		*/
	}
	
	if (!empty($P_admin_status)){
		echo "<p>Change Admin Status: $P_admin_status</p>";
		$this->member->setAdminStatus($uid,$P_admin_status);
	}
	if (!empty($P_test_status)){
		echo "<p>Change Test Status: $P_test_status</p>";
		$this->member->setTestStatus($uid,$P_test_status);
	}
	if (!empty($P_new_name)){
		echo "<p>change user name</p>";
		$this->member->setUserName($uid,$P_new_name);
		
	}

	
	$nobulkclear = ($mdd['no_bulk'] && ! isset($P_nobulk))?1:0;
	$nobulkset = (! $mdd['no_bulk'] && isset($P_nobulk))?1:0;
	if ($nobulkclear or $nobulkset){
		echo "p>Bulk Mail Changed</p>";
		if ($nobulkclear){
			$this->member->setNoBulk($uid,0);
		}
		elseif ($nobulkset){
			$this->member->setNoBulk($uid,1);
			$this->messenger->sendMessages($uid,'nobulk');
		}
	}

	if (!empty($P_current) && ($P_current <> $mdd['user_current'])) {
		echo "Updating user's current information.<br>";
		$this->member->setCurrent($uid, $P_current);
	}


	if (!empty($P_admin_note) && ($P_admin_note <> $mdd['admin_note']) ){
		echo "Updating admin note<br>";
		$this->member->setAdminNote($uid, $P_admin_note);
	}
	

	
	
	//reset my row with updated data
	$md = $this->member->getMemberData($uid);
	$mdd = $md['data'];
}
## end of update


//GEt PAGE
public function showMemberSummary($mdd) {
	
	
	$username = $mdd ['username'];
	$uid = $mdd['user_id'];
    $login_string = "https://amdflames.org/?s=${mdd['upw']}${mdd['user_id']}";
	$sendLoginButton = f\actionButton('Send Login','sendLogin',$uid);
 $summary = "
 	<div id='memberSummary'>
  	 <table border='1' cellpadding='2' cellspacing='0'>
  	 ";
  	$summary .= "<tr>
  	<th>Name</th><td>$username</td><th>Login</th><td colspan='2'>$login_string</td><td>$sendLoginButton</td></tr>
";
  	
//Set headings
	$fields = array(
		'status','status_updated','admin_status', 'last_login','profile_updated','profile_validated');
	$summary .= "<tr>";
	foreach ($fields as $field){
		$summary .= "<th>$field</th>";
	}
	$summary .= "</tr><tr>";
	foreach ($fields as $field){
		$summary .= "<td>${mdd[$field]}</td>";
	}
	$summary .= "</tr><tr>";
	
	$fields = array('user_email','email_status','email_status_time','email_last_validated','email_chg_date','prior_email');
	$summary .= "<tr>";
	foreach ($fields as $field){
		$summary .= "<th>$field</th>";
	}
	$summary .= "</tr><tr>";
	foreach ($fields as $field){
		$summary .= "<td>" . nl2br($mdd[$field]) . "</td>";
	}
	$summary .= "</tr>
	</table>\n";
	$summary .= "<p><b>Subscriber</b>: " ;
	$summary .= ($mdd['no_bulk'])? 'No' : 'Yes' . "</p>\n";
	$summary .=  "<p><b>At AMD: </b>${mdd['user_amd']}</p>\n";
	$summary .= "</div>\n";
	
	return $summary;
}

public function change_report($since) {
	// get changed emails
	$email_changed = $this->Member->getUpdatedEmails($since);
	u\echor ($email_changed);
	exit;


}
public function showUpdate($uid) {
	$md = $this->member->getMemberData($uid);
	
	if (empty($mdd = $md['data'])){
		throw new Exception ("No data for user id $uid: ${md['error']} ");
	}
	
	$username = $mdd ['username'];
	$uid = $mdd['user_id'];
    // Start a display table
   

    //build option fields
		$target_status = $mdd['status'];
		$nm = ($target_status == 'N')?'(Send Welcome)':'';
		
		$user_status_options = u\buildOptions(Defs::getStatusOptions());

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

		
		  $target_email = $mdd['user_email'];
		  if ($target_email){$show_email = "<a href='mailto:$target_email'>$target_email</a>";}
		  else {$show_email = '';}


		


	  #now show action fields

	  	$new_warning = ($target_status == 'N')?"<p>THIS IS A NEW SIGNUP.  Changing status to M or G will assign
	  	this person a user_id and send out a welcome message. </p>":'';

	  	$nobulkchecked = $mdd['no_bulk'] ? 'checked':'';

	  	$validateEmailButton = f\actionButton('Verify Email','verifyEmail',$uid);
	  	$bounceEmailButton = f\actionButton('Bouncer','bounceEmail',$uid);
		$sendLoginButton = f\actionButton('Send Login','sendLogin',$uid);
		
	echo $this->showMemberSummary($mdd);
	
    echo <<<EOT
	
	  <h3 style="border-top:1px solid black;">Actions on this record</h3>
Fields left blank will not be changed.
	<form method="post">
		<input type='hidden' name ='uid' value ='$uid'>
	 	<table>
	 		<columns>
	 		<col width="50%">
	 		<col width="50%">
	 		</columns>

	 	<tr><td><p><b>Change email address</b><br>This will change email_status to E1 and send out a verification email. This change will occur before any of the other actions listed below. If suggested by
	 	someone else is checked, then an explanatory email also goes to the new
	 	address.</p> </td><td><input type='text' name = 'new_email' size=60>
	 	<br><input type=checkbox name='suggested_email' id='suggested_email' >New Email suggested by someone else. <input type=text id='informant' name='informant' placeholder='Another FLAME member'oninput="check_the_box('suggested_email',true);"></td></tr>

	 	<tr style="background-color:#F90; ">
	 		<td><p><b>Update user status</b>$new_warning</td>
	 		<td><select name='new_status'>$user_status_options</select></td>
	 	</tr>

	 	<tr><td><p><b>Change User Name</b><br></p></td><td>
	 	New User Name: <input type='text' name='new_name' size=40></td></tr>



	  	<tr><td><b>No Bulk</b> Set/Clear the No Bulk tag for this users.</td><td>No Bulk <input type="checkbox" name=nobulk $nobulkchecked >
	  	<input type='hidden' name='nobulkchecked' value='$nobulkchecked' >
	  	</td></tr>

	  	<tr><td><b>Change Email Status</b> $validateEmailButton</td> <td>email_status (currently ${mdd['email_status']} ):
	  	<select name='email_status'><option value=''>Leave as ${mdd['email_status']}</option>
	  		$email_status_options</select><br>
	  		(Note: changing to A1 will send a validation email.)

	  	</td></tr>
	  		
	  	<tr><td><b>Admin Status</b></td><td>(currently ${mdd['admin_status']}):
	  	<input type="text" size="4" name="admin_status">
	  	</td></tr>
	
		<tr><td><b>Test Status</b></td><td>(currently ${mdd['test_status']}):
	  	<input type="text" size="4" name="test_status">
	  	</td></tr>
	  	
	  

	  	<tr><td><p><b>Update user's current information.</b> For deceased members, indicate date and other info.</td><td>
	  	<textarea  name='current' cols = '40' rows = '8'>${mdd['user_current']}</textarea></td></tr>

	  	<tr><td><p><b>Update the Admin Note.</b>  </td><td>
	  	<textarea  name='admin_note' cols = '40' rows = '8'>${mdd['admin_note']}</textarea></td></tr>

	  


	  	<tr><td ><input type='submit' name='Update' value='Update' style='background:#6F6; width:12em;'></td><td></td></tr>

	  	</table>

		</form>

<hr>

EOT;

}



}	



