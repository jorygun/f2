
<div style="width:100%;margin-left:30px;">

 <?php if (! $credential):
 	echo "Permission Denied";
 	exit;
	endif;
?>


<h1> AMD Flames Profile and Email Editor</h1>
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='profile' >Help</button>
<p>Saving this form marks your email address as confirmed. <br>
Making changes to fields marked with an asterisk *, "updates" your profile.<br>
If you change your email address or update your profile, the changes will be
listed in the next newsletter.</p>

<?php if (!empty($warning) ):
	echo $warning;
	endif;
?>

<p>Fields in yellow are <span class='required'>required</span>. Changes to fields with * 'update' your profile.</p>

	<h3><?= $username ?></h3>
	<p>Profile last updated: <?= $profile_date ?>,
	last verified <?= $profile_valid_date?><br>
		Flame member since  <?= $join_date ?> (<?= $status_name ?>) <br>
		</p>



<form method='post' name='profile' id='profile' enctype="multipart/form-data" action='/profile.php'>
	<input type='hidden' name='user_id' value='<?= $user_id ?>' >


<table class='profile'>

 <tr><td colspan='2' ><h3>Quick Update</h3></td></tr>

	<!--
<tr><td>* What's New?<br><span class='instr'>(Tweet-sized update)</span></td><td>
			<textarea name='user_greet' class='input' rows=2 cols=80 ><?= $this->e($user_greet) ?></textarea></td></tr>
 -->

	<tr><td >* Briefly, what's new with you? </td>
		<td><textarea  name='user_current' type='text' rows=3 cols=80 class='required'><?= $user_current ?></textarea>
		</td></tr>


    <tr><td>* Your current location <br>City, State/Province/Region, Country</td>
		<td><input class='required' size='96' id='location' name='user_from' type='text' value='<?= $this->e($user_from) ?>'>
		</td></tr>


		<tr><td>My current Email:</td><td><input id='email' name='user_email' type='email' class='required' size='60' value='<?= $user_email ?>'><br>

			</td></tr>

<tr><td>Submit and Confirm email</td><td><input type='submit' name='submit' id='submit'> This button submits the whole form, same as the one at the bottom. <br>Submitting now will verify the email above, and if you changed the *'ed fields, will also mark your profile as updated.</td></tr>

<tr><td colspan='2' ><h3>Basic Information</h3></td></tr>
<tr><td colspan='2' class='instr' >
		 This is your name as it appears in many places on the site.  You can change it here.  It does not affect your login.  </td> </tr>
		<tr><td> Your user name </td>
			<td> <input type='text' name='username' value='<?=$username?>'>
			</td></tr>
		<tr><td colspan='2' class='instr' >
		 Check here to prevent other Flames members from seeing your email address.  </td> </tr>
		 <tr><td>Hide Email</td><td><input type='checkbox' id='vis' value = '1' name='email_hide'  <?= $hide_checked ?> >Hide Email</td>
		 </tr>


		<tr><td colspan='2' class='instr'>
		 We send out an email whenever a new newsletter is published, typically weekly.
		 Check here to OPT OUT of the weekly email.  (Please don't, because we can lose contact with you, and you will not hear about updates posted by your co-workers.
		 <b>You will still receive occasional emails.</b> If you don't want to hear from this site ever, then you should go inactive.  Contact the admin to be set to inactive. </td></tr>
		 <tr><td>No Weekly Email</td><td><input type='checkbox' id='nobulk' name='no_bulk' value=1 <?= $no_bulk_checked ?> > Do Not Send Weekly Email.</td></tr>
         <tr><td > Your LinkedIn address. </td><td>	<input type='url' size='60' name='linkedin' value="<?= $linkedin ?>" placeholder='https://linkedin.com'  " </td></tr>
      <tr><td>Your personal very favorite web site</td><td><input type='url' size='60' name='user_web' value="<?= $user_web ?>" </td></tr>


	<tr><td colspan='2' ><h3>Photos</h3></td>
	<tr><td class='instr' colspan='2'>
	This is a list of integers, separated by spaces, representing the
		asset ids you want to show on your profile.
		To remove a photo from your profile, just remove its id from the list.
		To add one, just type in the id, or use one of the tools below.<br>
		DON'T FORGET TO SAVE THIS PAGE IF YOU MAKE ANY CHANGES TO THE LIST.
		</td></tr>
	<tr><td>Show these assets on my profile:</td>
		<td><input type='text' name='asset_list' id='asset_list' value='<?=$asset_list?>'><br>

		<br>
	<button type='button' onclick="window.open('/asset_search.php?mode=j' ,'assets','width=1100,left=160');">Search Assets</button> Click to find an existing asset. Search, then choose one from the search results.
	<br>
	<button type = 'button' onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >Create new asset</button> Click to create a new asset. Its id will be added to your list when you save it.

	</td></tr>
	<div class='user-photos'>
	<?php foreach ($photos as $aid=>$pdata) : ?>
	<tr><td>Asset ID: <?=$aid?></td><td>
		<?=$pdata['block']?>

			</td><tr>
		<?php endforeach ?>

	</div>


    <tr><td colspan='2' ><h3>AMD Affiliation</h3></td></tr>
        <tr><td >Enter what you did at AMD, briefly:</td>
            <td><textarea class='required' name='user_amd' rows='3' cols='60'><?= $this->e($user_amd) ?></textarea></td></tr>
        <tr><td colspan='2' class='instr'> The checkboxes below are so
        other members can search for co-workers a bit more easily</td></tr>

		<tr><td>Decades At AMD (check all applicable)</td><td><?= $decade_boxes ?><br></td></tr>
		<tr><td>Locations (check all applicable)</td><td><?= $location_boxes ?><br></td></tr>
		<tr><td>Departments (check all applicable)</td><td><?= $department_boxes ?><br></td></tr>
		<tr><td>Remember your badge number?</td>
			<td><input type='text' name='badge_no' value = '<?=$badge_no?>'>
			</td></tr>

		<tr><td colspan='2'><h3>Narratives (optional)</h3></td></tr>
   	<tr><td><u>* My interests</u></td></td></tr>
		<tr><td colspan='2'><input size='96'  name='user_interests' type='text' value="<?= $this->e($user_interests) ?>">
		</td></tr>

        <tr><td >* <u>About Me</u></td><td  class='instr'>
			Enter anything you'd like to say about yourself.  What was your career path? What keeps
			you busy?
			</td></tr>
			<tr><td colspan='2'>
			<textarea rows='15' cols='120' name='user_about' class='input useredit'> <?= $this->e($user_about) ?></textarea></td></tr>


        <tr><td colspan = 2 ><u>What is/was great about working at AMD</u><br>
        	<span  class='instr'>Share some memories.</span>
			</td></tr>
			<tr><td colspan='2'><textarea rows='15' cols='96' name='user_memories' class='input useredit'  ><?= $this->e($user_memories) ?></textarea></td></tr>



			<tr><td colspan='2' class='h3'><input name='Submit' value='Save Update' style="background:#9F9;" type='submit'>
				<input type='button' name='Cancel' value='Cancel' onclick="window.location.href='/profile.php/uid<?= $user_id ?>'; "></td></tr>
		</table>
	</form>


</div>

 <div class='float-clear'>
 </div>





