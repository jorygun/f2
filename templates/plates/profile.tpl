

<div style="width:100%;margin-left:30px;">
 <h1> <?=$this->e($username) ?></h1>
 <p ><i><?=$this->e($user_greet) ?></i></p>
<p> -- Last Updated: <?= $profile_date ?>. (verified <span id='profver'><?= $profile_valid_date ?></span>)</p>

 <?php if ($credential): ?>
    <?php if ($needs_update) : ?>
    <div class='warning' id='warning'>
    <p>Your profile has not been verified since <?= $profile_valid_date ?>.<br>

	If everything here is right, just to confirm it (and your email address. <?= $confirm_button ?> <br>
	If anything needs to be changed, click the Edit button.</p>
	</div>
	<?php endif ?>
	Click to edit profile, change email address, change subscribe options:
    <button onClick = 'window.location.href ="/profile.php/?edit=<?=$user_id?>"'>
            Edit My Profile </button>
    
<?php endif ?>
</p>
 
<hr>

<div style="border:1px solid green;padding:4px;width:350px;margin-left:30px; float:left;">
<?php if  ($member_photo) :  
   echo $member_photo;
endif ?>

<p>
		<b>Joined FLAME site: </b> <?=$join_date ?> <br>
		Current status: <?=$status_name?></p>

	<p><b>Email:</b> <?=$email_public?> <em><?=$email_status_name?></em>
		</p>

	<p><b>At AMD: </b><?=$this->e($user_amd )?><br><?= $at_amd ?></p>
	
	<p><b>Now</b> <br><?=$this->e($user_today )?></p>
	<p><b>Interests:</b><br><?=$this->e($user_interests )?></p>
	<p><?=$weblink ?></p>
	<p><?= $linkedinlink ?></p>
	
</div>

<?php if (strlen($user_about) > 0 ): ?>

     <div style="width:350px;margin-left:30px;float:left;">
		<p><b>About Me</b></p>
         <?= $user_about ?>
	</div>
<?php endif ?>
  

 <?php if (strlen($user_memories) > 0 ): ?>
     <div style="width:350px;margin-left:30px;float:left;">
		<p><b>Working at AMD:</b></p>
		 <?= $user_memories ?>
    </div>
<?php endif ?>

</div>

 <div class='float-clear'>
 </div>





 


