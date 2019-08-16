<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(2)){exit;}
	use digitalmx\flames\Definitions as Defs;
	use digitalmx as u;
	use digitalmx\flames as f;


//END START



/* *********************************************************


for this script id = user_id


********************************************************* */

   $login->checkLogin(3);
      #or checkLevel(min) if already logged in.

	$page_title = "Edit Profile";
	$page_options = []; # ['ajax','tiny','votes']

   echo $page->startHead($page_title,$page_options);
   echo <<<EOT;
   	<script type="text/javascript" >
    	function validate_profile(theForm) {
		 var s = '';
		 var v = '';
			//alert ('in script');
			v = document.getElementById('email').value;
			//s=v;

 		  if (! validateEmail(v)){
 		  	s += " Email address is not valid. "; }


			  v = document.getElementById('affiliation').value;
			if (v.length <5) {s += 'Affilation not filled in. ';}

 			  v = document.getElementById('location').value;
 			 if (v.length < 5){s += "Location field incomplete.";}

			  if (s != ''){alert (s); return false;}
 			 return true;

		}
   </script>
EOT;
 	echo $page ->startBody($page_title);



	$user_id = $_SESSION['login']['user_id']; #local logged in user
   $edit_id = $_GET['id'];
   if ($edit_id != $user_id and $_SESSION['level'] < 8){
	   die ("Cannot update profile if not logged in");
	}


	//get the requested record id from get or post


   $mrow = $member->getMemberData($edit_id);
   $row = $mrow['data'];



	$username = $row['username'];
	$id = $row['id'];
	$uid = $row['user_id'];

	#extract($row,EXTR_PREFIX_ALL,'D');

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {




	$hide_checked =  ($row['email_hide'] == 1)? "checked check='checked' ":'';
	$no_bulk_checked = ($row['no_bulk'] == 1)? "checked check='checked' ":'';
	$user_web = $row['user_web'] ?? '';


	list ($email_age,$email_date) = age($row['email_last_validated']);
	list ($profile_age,$profile_date) = age ($row['profile_updated']);
	list ($profile_validated_age,$profile_validated_date) = age ($row['profile_validated']);
	$verify_email = verify_click_email($id,$row['user_email']);
	$verify_profile = verify_click_profile($id);

	// compute decade choices from amd_when field
	$decade_choices = buildCheckBoxSet('amd_when',Defs::$decades,$row['amd_when'],6);

	$location_choices = buildCheckBoxSet('amd_where',Defs::$locations,$row['amd_where'],4);

	$department_choices = buildCheckBoxSet('amd_dept',Defs::$departments,$row['amd_dept'],6);

	$member_type = Defs::getMemberDescription($row['status']);

	$update_msg = $profile_update_msg = '';
	if ($email_age > Defs::$inactivity_limit && $row['email_status'] == 'Y'){
	    $email_update_msg = "<tr><td colspan='2' style='color:red'>Your email has not been validated since $email_date.
	If it's wrong, you can change it or submitting this form will validate it.</td></tr>";}

	if ($profile_age> Defs::$old_profile_limit ){

	    $profile_update_msg =  "
	<tr><td colspan='2' style='color:red'>
	Your profile  has not been updated since $profile_date. Please review it.  <br>
	If everything's still current, just submit this form to
	to mark it current.</td></tr>";}

	#echo "db: $row['email_hide']; hide:$hide_checked<br>\n";



// convert greeting to html kind of (\n to <br>)  or .\n. to </p><p> ??
	$html_greeting = $row['user_greet'];
		# $html_greeting = nl2br($html_greeting);

	$email_status = Defs::getEmsName($row['email_status']);
?>

<h1>Profile and Email Updater</h1>
<h3 ><?=$username?></h3>
<p>Your user name can be changed.  Please <a href='mailto:admin@amdflames.org'>email the admin</a>.</p>

<p>Profile last updated: <?=$row['profile_updated']?><br>
Validated <?=$row['profile_validated']?><br>
Joined FLAME site:  <?=$row['join_date']?><br>
Flame <?=$member_type?> since <?= $row['status_updated'] ?><br>
Last Login: <?=$row['last_login'] ?>
<br></p>

<p>Submitting this form will verify your profile and email address. You won't get reminder notices for at least a year, even if there are no changes. </p>



<form method="post" name='profile' id='profile' onsubmit='return validate_profile(this);'>
		<input type='hidden' name="uid" value="<?=$uid?>">
		<input type='hidden' name='username' value="<?=$username?>">


		<p > <span class="input required">Yellow fields</span> are required information</p>
		<table style="table-layout:fixed;">
		<tbody>
		<tr><td style="width:25%">&nbsp;</td><td style="width:75%">&nbsp;</td></tr>

		<tr><td colspan="2" ><h4>Contact Information</h4></td></tr>


		<tr><td >Say Hi.</td>
		<td><span class="instr">
			Something short. Will be displayed in your profile.
			</span><br />
			<input type='text' name="user_greet" class="input" value="<?=$html_greeting?>" size='90'>
			</td></tr>

		<?=$email_update_msg?>

		<tr><td>Your Email:</td><td><em>Current email status:<?=$email_status?></em><br />
		<input id='email' name="user_email" type="text" class="required" size="60" value='<?=$row['user_email']?>'>
		<br />
			<b>If you change your email, you will receive a confirming email within a few minutes.  You MUST respond to confirm your new email. Otherwise you will be marked as 'lost'.</b>
			</td></tr>

		<tr><td >Email Hidden</td>
		<td>
		 <input type='checkbox' id='vis' name='emailvis' <?=$hide_checked ?> >Hide Email <span class='instr'>
		 Check to prevent other Flames members from seeing your email address. </span>
		 </td></tr>

		 <tr><td>Opt out </td><td>
		  <input type='checkbox' id='nobulk' name='no_bulk' <?=$no_bulk_checked?> >Opt Out of Weekly Email. <span class='instr'>But Please Don't</span> 😥 </td></tr>
<tr><td>Web Site</td>
	<td><span class="instr">Your band? Anything you want.</span><br />
<input type='url' name='user_web' value='<?=$user_web?>' placeholder='http://...'>
</td></tr>

<tr><td >LinkedIn
          <img src="https://static.licdn.com/scds/common/u/img/webpromo/btn_liprofile_blue_80x15.png" width="80" height="15" border="0" alt="profile on LinkedIn" /> </td>
        <td ><span class='instr' >like 'https://linkedin/in/linkedinname'</span><br />

			<input  size="60" type='url' name='linkedin' value=
			'<?=$row['linkedin']?>' pattern='https://linkedin/*' placeholder='https://linkedin/'>
			</td></tr>


<tr><td colspan="2" ><h4>AMD Affiliation</h4></td></tr>
		<tr><td colspan='2' class='instr'>
		The checkboxes are to make it easier for people to find their co-workers. Check all applicable boxes. </td></tr>
		<tr><td>Decades At AMD </td><td><?=$decade_choices?><br></td></tr>
		<tr><td>Locations </td><td><?=$location_choices?><br></td></tr>
		<tr><td>Departments </td><td><?=$department_choices?><br></td></tr>

		<tr><td >Briefly what you did:

		<td><span class="instr">Will be shown along with info from the checkboxes above.</span><br />
		<input type='text' class="required" name="user_amd" size='80' max-size='140' value="<?=$row['user_amd']?>">
		</td></tr>

<?=$profile_update_msg?>
		<tr><td colspan="2" ><h4>Your Profile</h4></td></tr>

		<tr><td >What are you're doing now? </td>
		<td><input size="60"  name="user_current" type="text" class='required' value='<?=$row['user_current']?>'>
		</td></tr>

		<tr><td >Location </td>
		<td><span class="instr">Enter
		your current City, State/Province/Region/Country</span><br />
		<input class="required" size="60" id='location' name="user_from" type="text" value='<?=$row['user_from']?>'>

		</td></tr>


<tr><td >About <?=$username?> </td>
	<td  class="instr">
			Enter anything you'd like to say about yourself. </td></tr>
	<tr><td></td><td>
			<textarea rows="15" cols="60" name="user_about" class="input"  ><?=$row['user_about']?></textarea></td></tr>


<tr><td >Working at AMD</td><td class='instr'>Share some memories about working at AMD. What made it the best place you ever worked?</td></tr>

	<tr><td></td><td>
			<textarea rows="10" cols="60" name="user_memories" class="input "  ><?=$row['user_memories']?></textarea></td></tr>

<tr><td>Interests</td>
	<td class='instr'>Enter any special interests you have.</td>
</tr>
		<tr><td >
		</td><td><textarea name="user_interests" rows="3" cols="60" class="input "><?=$row['user_interests']?></textarea>
		</td></tr>

	<tr><td colspan="2" class="h3"><input name="Submit" value="Submit" style="background:#9F9;" type="submit">
				<input type="button" name="Cancel" value="Cancel" onclick="window.location.href='profile_view.php?id=$id'; "></td></tr>
		</table>



	</form>


<?php
	}

elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

	echo "Updating Profile Information<br>";
		// Gather info (strip any slashes added by the POST function)

	$upd_fields = array(
	'user_from','user_email','email_hide','alt_contact','amd_when', 'amd_where','amd_dept','user_amd','user_current','user_interests','linkedin','user_greet','user_memories,profile_updated');


	/* check for errors
	*/
		$er_msg = array();
		if (!$_POST['username']){
			array_push($er_msg,'User Name must be filled in');
		}
		if (!$_POST['user_from']){
			array_push($er_msg,'Location must be filled in');
		}
		if (! u\is_valid_email($_POST['user_email'])){
			array_push ($er_msg,'Email address not valid');
		}

		if (! $_POST['user_current']){
			array_push ($er_msg,'Please fill in what you\'re doing now');
		}

		if ($er_msg){
			echo "<p>There were some problems with your data. Please go back
			and re-enter</p>";
			echo join("<br",$er_msg);
			exit;
		}


	// check for profile update

	//use the safe value of these fields in the update without modification
	$direct_fields = array ('user_from','user_email','email_hide','no_bulk','alt_contact','user_amd','user_greet','user_memories','user_about','uid');
	$update = array();
	foreach ($direct_fields as $fld){
		$update[$fld] = $_POST[$fld];
	}
	#decode the entities in the text fields.
	foreach (['user_memories','user_interests','user_about'] as $fld ){
		$update[$fld] = spchard($_POST[$fld]);
	}
	#fix up the multiple choice fields
		if ($set = $_POST['amd_where'] ){
			$update['amd_where']  = charListToString($set);
		}

		if ($set = $_POST['amd_when'] ){
			$update['amd_when'] = charListToString($set);
		}
		if ($set = $_POST['amd_dept'] ){
			$update['amd_dept']  = charListToString($set);
		}


		$update['email_hide']= ($_POST[emailvis] == 'on') ? 1:0;
		$update['no_bulk']= ($_POST[no_bulk] == 'on') ? 1:0;

		if ($linkedin = $_POST['linkedin'] and $linkedin <> $row['linkedin']){
			if (substr($linkedin,0,4) != 'http'){
				$update['linkedin'] = "http://$linkedin";
			}
			else {$update['linkedin'] = $linkedin;}
		}

/*     IF (new.user_about != old.user_about
         OR
         new.user_interests != old.user_interests
         OR
         new.user_current != old.user_current
         OR
         new.user_from != old.user_from
         OR
         new.user_memories != old.user_memories
        ) THEN
         set new.profile_updated = now();
      END IF;
 */

		#assume user also checked email
		$update['email_last_validated'] = sql_now();
		$update['email_status'] = 'Y';
		$update['profile_validated'] = sql_now();
		if ($profile_changed) {
			$update['profile_updated'] = sql_now();
		}
    	notify_admin($username,$id);


// recho($update,'update array');
// 	exit;

	$prep = pdoPrep($update,[],'uid');

 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/

  	$sql = "UPDATE `members_f2` SET ${prep['update']} WHERE user_id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

	#update session info if user changed their own profile
		 if ($user_id == $_POST[uid]){
			$_SESSION['DB'] = array_merge($_SESSION['DB'],$update);
		}

	#check if email changed.

	 	$existing_email = trim($row['user_email']);
	 	$form_email = trim($_POST['user_email']);
	 	$use_email = $existing_email;
		 if ($existing_email <> $form_email){ #email changed
			 $use_email = $form_email;
			send_verify($_POST[uid],'E1');

			echo "<P>Your email was changed from ",
		 		h($existing_email) ,
		 		" to ",
		 		h($form_email) ,
		 		".  <br>A verification email has been sent.",

		 		"</p><p>Please respond to confirm the new email.</p>\n";

		 }






	$reloc = "/scripts/profile_view.php?id=$uid"; #works for either id or user_id

	echo "<script>window.location.href='$reloc';</script>\n";




}

function notify_admin($name,$id){
             $adm_sub = "Profile Update: $user";
	        $adm_msg = "User $name has updated their profile and verified email.";
            send_admin($adm_sub,$adm_msg);
        }

?>


</body></html>

