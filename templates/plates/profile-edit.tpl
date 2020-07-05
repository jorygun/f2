
<div style="width:100%;margin-left:30px;">

 <?php if (! $credential):
 	echo "Permission Denied";
 	exit;
	endif;
?>


<h1> AMD Flames Profile and Email Editor</h1>
<p>Saving this form marks your email address and profile as confirmed. <br>
If you make changes to fields marked with an asterisk *, your profile will be listed as an update on the next newsletter. If you change your email address,
that will listed on the next newsletter as well.</p>
<p>Fields in yellow are <span class='required'>required</span>.</p>

	<h3><?= $username ?></h3>
	<p>Profile last updated: <?= $profile_date ?>,
	last verified <?= $profile_valid_date?><br>
		Flame member since  <?= $join_date ?> (<?= $status_name ?>) <br>
		</p>

<?php if (!empty($warning) && ($profile_valid_age > $profile_warning) ): ?>

   <div class='warning'>Your profile has not been updated since
   <?=$profile_date?>.  Please look it over and make edits as needed.  If everything
   is OK <i>including your email</i>, just click here to verify everything.  If you make any edits, click Update at the bottom of the page.<br>
   <?= $profile_verify_button ?>
   </div>

<?php endif; ?>


<form method='post' name='profile' id='profile' enctype="multipart/form-data" action='/profile.php'>
	<input type='hidden' name='user_id' value='<?= $user_id ?>' >


<table class='profile'>

 <tr><td colspan='2' ><h5>Quick Update</h5></td></tr>

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

	<?php if ($email_status != 'Y'): ?>
<tr><td colspan=2><div class='warning'>There is a problem with your current email
address. You may change it here, or, if it is correct, just submit this page to confirm it. If you change it, be sure to respond to the confirmation email. Your current email status is <?= $email_status_name ?>.
	</td></tr>
<?php endif; ?>

		<tr><td>My current Email:</td><td><input id='email' name='user_email' type='email' class='required' size='60' value='<?= $user_email ?>'><br>

			</td></tr>

<tr><td>Submit and Confirm email</td><td><input type='submit' name='submit' id='submit'></td></tr>

<tr><td colspan='2' ><h5>Basic Information</h5></td></tr>

		<tr><td colspan='2' class='instr' >
		 Check here to prevent other Flames members from seeing your email address.  </td> </tr>
		 <tr><td>Hide Email</td><td><input type='checkbox' id='vis' value = '1' name='email_hide'  <?= $hide_checked ?> >Hide Email</td>
		 </tr>


		<tr><td colspan='2' class='instr'>
		 <u>Weekly Email</u> We send out an email whenever a new newsletter is published, typically weekly.
		 Check here to OPT OUT of the weekly email.  (Please don't, because we can lose contact with you, and you will not hear about updates posted by your co-workers.
		 <b>You will still receive occasional emails.</b> If you don't want to hear from this site ever, then you should go inactive.  Contact the admin to be set to inactive. </td></tr>
		 <tr><td>No Weekly Email</td><td><input type='checkbox' id='nobulk' name='no_bulk' value=1 <?= $no_bulk_checked ?> > Do Not Send Weekly Email.</td></tr>
         <tr><td > Your LinkedIn address. </td><td>	<input type='url' size='60' name='linkedin' value="<?= $linkedin ?>" placeholder='https://linkedin.com'  " </td></tr>
      <tr><td>Your personal very favorite web site</td><td><input type='url' size='60' name='user_web' value="<?= $user_web ?>" </td></tr>


	<tr><td colspan='2'><h5>Photos</h5></td></tr>
	<tr><td>Assets to show on my profile</td>
		<td><input type='text' name='asset_list' id='asset_list' value='<?=$asset_list?>'><br>
	To remove a photo from your profile, remove the asset id here.<br>
	To find and add an existing asset, click here.
	<button type='button' onclick="window.open('/asset_search.php?mode=j' ,'assets','width=1100,left=160');">Search Assets</button> <br>
	To create and add a new asset, click here.
	<button type = 'button' onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >Create a new asset</button>
	</td></tr>
	<div class='user-photos'>
	<?php foreach ($photos as $aid=>$pdata) : ?>
	<tr><td>Asset ID: <?=$aid?></td><td>
		<?=$pdata['block']?>

			</td><tr>
		<?php endforeach ?>

	</div>


    <tr><td colspan='2' ><h5>AMD Affiliation</h5></td></tr>
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

		<tr><td colspan='2'><h5>Narratives (optional)</h5></td></tr>
   	<tr><td><u>My interests</u></td></td></tr>
		<tr><td colspan='2'><input size='96'  name='user_interests' type='text' value="<?= $this->e($user_interests) ?>">
		</td></tr>

        <tr><td ><u>About Me</u></td><td  class='instr'>
			Enter anything you'd like to say about yourself.  What was your career path? What keeps
			you busy?
			</td></tr>
			<tr><td colspan='2'>
			<textarea rows='15' cols='120' name='user_about' class='input useredit'> <?= $this->e($user_about) ?></textarea></td></tr>


        <tr><td ><u>What is/was great about working at AMD</u></td><td  class='instr'>
			Share some memories.
			</td></tr>
			<tr><td colspan='2'><textarea rows='15' cols='96' name='user_memories' class='input useredit'  ><?= $this->e($user_memories) ?></textarea></td></tr>



			<tr><td colspan='2' class='h3'><input name='Submit' value='Update' style="background:#9F9;" type='submit'>
				<input type='button' name='Cancel' value='Cancel' onclick="window.location.href='/profile.php/uid<?= $user_id ?>'; "></td></tr>
		</table>
	</form>


</div>

 <div class='float-clear'>
 </div>





