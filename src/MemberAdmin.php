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
	use  League\Plates\Engine;
	
	
	
//END START

    
		
class MemberAdmin {
	//classes
	private $member;
	private $messenger;
	private $pdo;
	private $templates;
	
	
	public function __construct(){
		$this->pdo = u\MyPDO::instance();
		$this->member = new Member ();
		$this->messenger = new Messenger();
	
		
	}
	
	
//	this function just returns an html table row for a member row.
// used for member admin page
	private function echo_user_row ($row){
		#$fields = array('status','email_status', 'email_last_validated','record_updated','last_login','no_bulk');
		$uid = $row['user_id'];

		$status_id = "stat-$uid";
		$emstat_id = "emstat-$uid";
		$emver_id = "emver-$uid";	
		$cdate_id = "cdate-$uid";

		$bounceEmailButton = f\actionButton('Bouncer','bounceEmail',$uid,$emstat_id,'bounced');
		$urlemail = rawurlencode($row['user_email']);
		$username = u\special($row['username']);
		$last_login = u\make_date($row['last_login']);
		$email_last_validated = u\make_date($row['email_last_validated']);
		#$validateEmailButton = f\actionButton('Verify Email','verifyEmail',$uid,'emver-id');
		$validateEmailButton = "<button type='button' onClick='verifyEmail($uid)'>Verify</button>";


		$markContributeButton = f\actionButton('Contributed','markContribute',$uid,"$cdate_id");
		$contribute_time = strtotime($row['contributed']);
		if ($contribute_time == 0){
			$cdate = 'Never';
		} else {
			$cdate = date('d M Y', $contribute_time);
		}
		$login = $row['upw'] . $row['user_id'];
		$user_login_link = "https://amdflames.org/?s=$login";


		$o = "<tr><td style='border-top:3px solid green' colspan='8'></td></tr>";

		$o .=  "<tr>
		<td colspan='2'><b>$username</b</td>
		<td colspan='2' >" . u\linkHref($row['user_email']) . "</td>";
		
		
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



		$o .=  "<tr>";
		$o .=  "<td align='center'> " 
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
		$this->member->setStatus($uid,'X');
	
	}
	
	 
	public function listMembers($post){
   
		$result = $this->member->getMemberListFromAdmin($post);
	  # u\echor ($result,'from Member');
	

		$data = [];
		// add additional fields to each row
		foreach ($result['data'] as $row){
		  
			$uid = $row['user_id'];
			$status_id = "stat-$uid";
			$emstat_id = "emstat-$uid";
			$emver_id = "emver-$uid";	
			$cdate_id = "cdate-$uid";
			$row['uid'] = $uid;
			$row['cdate_id'] = $cdate_id;
			$row['emver_id'] = $emver_id;
			$row['emstat_id'] = $emstat_id;
			
			
			$row['status_id'] = $status_id;
			$row['bounceEmailButton'] = f\actionButton('Bouncer','bounceEmail',$uid,$emstat_id,'bounced');
			$urlemail = rawurlencode($row['user_email']);
		
			$row['last_login_date'] = u\make_date($row['last_login']);
			$row['email_last_validated_date'] = u\make_date($row['email_last_validated']);
			#$validateEmailButton = f\actionButton('Verify Email','verifyEmail',$uid,'emver-id');
			$row['validateEmailButton'] = "<button type='button' onClick='verifyEmail($uid)'>Verify</button>";

			$row['email_linked'] = u\linkHref($row['user_email']);
			$row['markContributeButton'] = f\actionButton('Contributed','markContribute',$uid,"$cdate_id");
			$row['cdate'] = u\make_date($row['contributed']);
		
			$login = $row['upw'] . $row['user_id'];
			$row['user_login_link'] = "https://amdflames.org/?s=$login";
			$row['send_login_button'] =  f\actionButton('Send Login','sendLogin',$uid,'','resp');
			$row['x-out-button'] = f\actionButton('X-out','xout',$uid,$status_id) ;
			$data[] = $row;

		}

		return $data;

      }

	public function processSignups($post) {
		# u\echor($post,'Incoming to process');
		
	
		foreach ($post as $key=>$val){
			if (substr($key,0,1) != 'D'){continue;} #find Dnn vars
			if (empty($val)){continue;} #no change
			$id = substr($key,1);
			# echo "Now do $key:$val" . BRNL;
			$xids = []; #array of items to be deleted
			switch ($val) {
				case 'X': 
				case 'R':	
					 $sql = "UPDATE `signups` SET status = '$val' WHERE id='$id'";
// 					echo $sql . BRNL;
					$this->pdo->query($sql);
					break;
				case 'M': #add as member or guest
				case 'G':
					$now = u\make_date('now','sql','datetime');
					$sql = "SELECT * FROM `signups` WHERE id = $id;";
					$row = $this->pdo->query($sql)->fetch();
					$srow = array(
						
						'status' => $val,
						'username' => $row['username'],
						'user_email' => $row['user_email'],
						'admin_note' => "Entered from : " . $row['IP'] . " on " . $row['entered'] . "\n"
								. $row['comment'] . "\n",
						'user_from' => $row['user_from'],
						'user_amd' => $row['user_amd'],
						'email_status' => 'Y',
						'email_last_validated' => $now,
					'profile_validated' => $now,
					'joined' => $now,
						
						);
					$new_id = $this->member->addSignup($srow);
					echo "New user_id: $new_id: ${srow['username']}" . BRNL;
					
					// send welcome message
					$this->messenger->sendMessages($new_id,'welcome');
					
					#now remove processed drow from the signup list
					$sql = "DELETE from `signups` WHERE id = $id";
					$this->pdo->query($sql);
					
					break;
				default:
					echo "Unknown code on signup id $key:$val" . BRNL;
			}
			
			
		}
		// now delete all the x'd out records
		
		$sql = "DELETE from `signups` WHERE status = 'X'";
		$stmt = $this->pdo->query($sql);
		 $stmt->rowCount() . " entries deleted". BRNL;
		
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
 // receives array of member values to update
 // data reviewed for consequential changes
//  u\echor($post, 'incoming to update');
//  exit;
	$uid = $post['uid'] ?? '';
	if (empty($uid)){
		throw new Exception ("No uid suppied in updateMember");
	}
	$now_sql = u\make_date('now','sql','datetime');
	//start by getting users existing record. 
	$md = $this->member->getMemberRecord($uid,true);
	if (empty($md)){
		throw new Exception ("User $uid does not exists.");
	}
	
	
	// review all the data inthe update post for validity and ancillary changes
	
	foreach ($post as $var=>$val){
		#ignore fields that haven't changed
		if (empty($val)){continue;}
		switch ($var) {
			case 'uid':
			case 'suggested_email':
			case 'informant':
			case 'Update':
			case 'nobulk':
			case 'nobulkchecked':
	
				#do nothing
				break;
			case 'user_email':
				$extra = [];
				if (isset($post['suggested_email'])){
					if (empty($informant = $post['informant'])){
						$informant = 'another flames member';
					}
					$extra['informant'] = $informant;
				}
				
				echo "Changing User Email and sending E1" . BRNL;
				$this->member->setEmail($uid,$val);
				$this->member->setEmailStatus($uid,'E1');
				$this->messenger->sendMessages($uid,'E1',$extra);
			
				break;
			case 'username':
				echo "Changing user's user name." . BRNL;
				$this->member->setUserName($uid,$val);
				break;
			case 'status':
				echo "Updating user's status" . BRNL;
				
				$this->member->setStatus($uid,$val);
				if ($val == 'D'){
					$this->member->setEmailStatus($uid,'LD');
				}
				break;
			case 'email_status':
				echo "Updating users Email Status and sending messages." . BRNL;
				$this->member->setEmailStatus($uid,$val);
				$this->messenger->sendMessages($uid,$val);
	
				break;
			case 'admin_status':
				echo "Updating users Admin Status. " . BRNL;
				$this->member->setAdminStatus($uid,$val);
				break;
			case 'test_status':
				echo "Updating user's test-status" . BRNL;
				$this->member->setTestStatus($uid,$val);
				break;
			case 'current':
				$val = u\despecial($val);
				if (strcmp($md['user_current'],$val) !== 0){
				echo "Updating user's current info" . BRNL;
				$this->member->setCurrent($uid,$val);
				}
				break;
			case 'admin_note':
				if (strcmp($md['admin_note'],$val) !== 0){
				echo "Updating users' admin note". BRNL;
				$this->member->setAdminNote($uid,$val);
				}
				break;	
			default:
				echo "Unknown var $var" . BRNL;
			
		} 
			
	}

	$bulkflag = isset($post['nobulk']);
	if ($bulkflag != $md['no_bulk']){
		$this->member->setNoBulk($uid,$bulkflag);
		if ($bulkflag == 1){
			$this->messenger->sendMessages($uid,'nobulk');
		}
			
	}
	



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
	$mdd = $this->member->getMemberRecord($uid,true);

	if (empty($mdd)){
		throw new Exception ("No data for user id $uid");
	}
	$td = $mdd;
	$username = $mdd ['username'];
	$uid = $mdd['user_id'];
    // Start a display table
   

    //build option fields
		$target_status = $mdd['status'];
		$td['nm']= ($target_status == 'N')?'(Send Welcome)':'';
		
		$td['user_status_options']= u\buildOptions(Defs::getStatusOptions());

		// $status_contribute =array (
// 			'--News--' => '',
//          'Contributor' => 'MC'
// 		);
// 		$status_admin =array (
// 			'--Admins--' => '',
// 			'Publisher' => 'MN',
// 			'User Admin' => 'MU'
// 		);


		$email_status_options = '';
		
		#only allow certain changes.  x-bad, a-start validation y-verified q-unknown
		foreach (array('A1','Y','Q','LO','LB','XX','A2','A3','B1') as $k){
			if ($k <> $mdd['email_status']){$email_status_options .= "<option value='$k'>$k " . Defs::getEmsName($k) . "</option>";}
		}
		$td['email_status_options'] = $email_status_options;
		
		  $target_email = $mdd['user_email'];
		  if ($target_email){$show_email = "<a href='mailto:$target_email'>$target_email</a>";}
		  else {$show_email = '';}
		$td['target_email'] = $target_email;
		$td['show_email'] = $show_email;

		


	  #now show action fields

	  	$td['new_warning']= ($target_status == 'N')?"<p>THIS IS A NEW SIGNUP.  Changing status to M or G will assign
	  	this person a user_id and send out a welcome message. </p>":'';

	  	$td['nobulkchecked']= $mdd['no_bulk'] ? 'checked':'';

	  	$td['validateEmailButton']= f\actionButton('Verify Email','verifyEmail',$uid,'resp');
	  	$td['bounceEmailButton']= f\actionButton('Bouncer','bounceEmail',$uid);
		$td['sendLoginButton']= f\actionButton('Send Login','sendLogin',$uid);
		
	echo $this->showMemberSummary($mdd);
	
	
	return $td;
	
 
}

	public function email_member($uid) {
	
	}

 public function getProfileData($uid) {
 	$row = $this->member->getMemberRecord($uid,true);
 	$tdata = $row; // put all retrieved data into template row
 	#u\echoAlert ("MA Site: " . SITE);
# u\echor($row,'From Member');
 	
 
	
// 	$linkedinlink=  ($D_linkedin)?
//          " <p><a href='$D_linkedin' target='_blank'><img src='https://static.licdn.com/scds/common/u/img/webpromo/btn_liprofile_blue_80x15.png' width='80' height='15' border='0' alt='profile on LinkedIn' /><br />$D_linkedin </a></p>":'';
//   
// 	

	
	
	$tdata['hide_checked'] =  ($row['email_hide'])? "checked check='checked' ":'';
	$tdata['no_bulk_checked'] = ($row['no_bulk'])? "checked check='checked' ":'';
	
	
	$user_today = $row['user_current'] ;
	if (!empty($row['user_from'])){$user_today .= " ... in ${row['user_from']}";}
	$tdata['user_today'] = $user_today;
	
   $tdata['weblink'] = (!empty($row['user_web']))? 
   	"<p><a href='${row['user_web']}' target='_blank' >Favorite Web Site</a></p>"
   	:
   	'';
   // see if user is editing their own or its an admin that  can edit it
  $credential = ($uid == $_SESSION['login']['user_id']  || $_SESSION ['level'] > 7)
  			&& $row['status'] != 'D';
   
    $button_text = <<<EOT
		<button onClick = "window.open('/scripts/profile_update.php?id=$uid');">
		Edit My Profile</button>
EOT;
    $tdata['edit_button'] = ($credential )? $button_text : '';
	
	$tdata['decade_boxes'] = buildCheckBoxSet('amd_when',Defs::$decades,$row['amd_when'],6);

	$tdata['location_boxes'] = buildCheckBoxSet('amd_where',Defs::$locations,$row['amd_where'],4);

	$tdata['department_boxes'] = buildCheckBoxSet('amd_dept',Defs::$departments,$row['amd_dept'],6);

           
   $tdata['profile_warning'] = Defs::$profile_warning;
	$tdata['profile_verify_button'] = 
	#f\actionButton('Profile is Good','verifyProfile',$uid,'profver','Verified') ;
	"<button type='button' onClick=location.assign('/profile.php?confirmed=$uid')>It's All Good</button>";
	
	$tdata['email_verify_button'] = 
	f\actionButton('Confirm Email','verifyEmail',$uid,'em-stat','Verified') ;
	
	#f\actionButton('Bouncer','bounceEmail',$uid,$emstat_id,'bounced');
	$tdata['info_text'] = <<<EOT
	This is what you need to know.
EOT;


	$tdata['credential'] = $credential;
	$tdata['warning'] = f\getWarning(); 
	
 	return $tdata;
 
 }
 
 public function confirmProfile($uid) {
 	return $this->member->setProfileVerified($uid);
 	$_SESSION['warning_seen'] = true;
 	

 }
 
 public function saveProfileData($post) {
 	
 		echo "Updating Profile Information<br>";
 		
# 		u\echoAlert ("MA Site: " . SITE);

	if (empty($uid = $post['user_id'])){
		throw new Exception ("No uid supplied for save profile data");
	}
	
/* check for errors
	*/
		$er_msg = [];

		if ($er_msg){
			echo "<p>There were some problems with your data. Please go back
			and re-enter</p>";
			echo join("<br",$er_msg);
			exit;
		}

// check for profile changes

	// get current data
    $md = $this->member->getMemberRecord($uid,true);
    // review incoming data for errors, changes, etc.
    $post['email_hide'] = (isset($post['email_hide']))?1:0;
    $post['no_bulk'] = (isset($post['no_bulk']))?1:0;
    if (!empty( $post['amd_where'])){
   	 $post['amd_where'] = u\charListToString( $post['amd_where'] ) ;
   }
    if (!empty( $post['amd_when'])){
    	$post['amd_when'] = u\charListToString( $post['amd_when'] );
    }
     if (!empty( $post['amd_dept'])){
    $post['amd_dept'] = u\charListToString( $post['amd_dept'] );
    }
 #   u\echor($post,'Incoming post');  
  #	u\echor($md,'MD'); exit;
	$profile_changed = false;
	$new_email = false;
	$update=[];
	// go through each key, see if it's changed, build update array
    foreach ($post as $key=>$val){
    	if (in_array($key,['Submit'])){continue;}
    	
    	// these items are shown escaped
    	if ( in_array($key,['user_greet','user_from','user_current',
    	'user_amd','user_interests'] ) ){
    			$val=u\despecial($val);
    	}
    	
    	// echo "testing $key: new = $val<br>existing = ${md[$key]} ... ";
    	if ( strcmp($md[$key],$val) == 0){continue;} #no change
   
#    	echo "Changed data in $key -> $val" . BRNL; 
   	
   	
    	switch ($key){
   	
    		case 'user_email':
				if (! u\is_valid_email($val)){
					echo 'New Email address not valid' . BRNL; exit;
				}
				#new email
				$update[$key] = $val;
				#$this->messenger->setTestMode(true);
				$new_email = true;				
				// change email status only AFTER updating to the new email
				break;
			case 'user_interests':
			case 'user_current':
			case 'user_memories':
			case 'user_about':
			case 'user_from':
			case 'user_greet':
			
			 	$update[$key] = $val;
			 	$profile_changed = true;
				break;
			
			case 'user_amd':
				$update[$key] = $val;
				#$profile_changed = true;
				break;
				
			case 'user_web':
			case 'linkedin':
			case 'email_hide':
			case 'no_bulk':
				$update[$key] = $val;
				break;
			case 'amd_where':
			case 'amd_when':
			case 'amd_dept':
				$update[$key] = $val;
				break;
				
			case 'user_id' :
			case 'profile_photo':
				#do nothing
				break;
			default:
				die ("Unrecognized field in profile update: $key");
		}
	
	}
	
// won't happen until upload turned on ini form
	if (isset($_FILES['linkfile'])){
			// photo uploaded
			// is there already an id for this
			$asset_id = $md['photo_asset'] ?? 0;
			$update['photo_asset'] = $this->save_profile_photo($asset_id,$md['username']);
			
	}
			
 // if change to profile or profile has never been updated
	if ($profile_changed || $md['profile_updated'] == null){  
	  	 $update['profile_updated'] = sql_now();
    
	    $subj = "Profile Update " . $md['username'];
	    $msg = $md['username'] . " has updated their profile";
	    mail ('admin@amdflames.org',$subj,$msg);
    }



 //   #assume user also checked email
 	 
 	 $update['profile_validated'] = sql_now();
	$update ['user_id'] = $uid;
	
	
		
#	 u\echor($update,'update array');

	 
	$prep = u\pdoPrep($update,[],'user_id');

 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
  


  	$sql = "UPDATE `members_f2` SET ${prep['update']} WHERE user_id = ${prep['key']} ;";
  	//echo $sql . BRNL;
  	//u\echor ($prep['data'],' prep data');
 $stmt = $this->pdo->prepare($sql);
 $stmt -> execute($prep['data']);
  
  if ($new_email){
  	$this->messenger->sendMessages($uid,'E1',['informant'=>'profile update']);
				u\echoAlert("You have changed your email.  Be sure to watch for an 
				email asking you to confirm the change.");
	}
	else { #validate email
		$this->member->verifyEmail($uid);

  	}
 	
 }
 	
 	
 	private function save_profile_photo($asset_id,$username){

			$post_array = array (
				'id' => $asset_id,
				'title' => $username,
				'type' => 'Member Photo',
				'status' => 'N'
			);
			
			include REPO_PATH . '/public/scripts/asset_functions.php';
			$id = post_asset($post_array);
			return $id;
			
	}
}	



