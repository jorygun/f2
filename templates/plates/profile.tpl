

<div style="width:100%;margin-left:30px;">
 <h1> <?=$username ?></h1>

<p> -- Last Updated: <?= $profile_date ?>. (verified <span id='profver'><?= $profile_valid_date ?></span>)</p>

 <?php if ($credential):
   if (!empty($warning) && empty($_SESSION['warning_seen'] )): ?>
    <div class='warning' id='warning'><?=$warning?></div>
	<?php endif ?>
	Click to edit profile, change email address, change subscribe options:
    <button onClick = 'window.location.href ="/profile.php/?edit=<?=$user_id?>"'>
            Edit Profile </button>

<?php endif ?>

<hr>

<div style="border:1px solid green;padding:4px;width:350px;margin-right:12px; float:left;">


<p>
		<b>Joined FLAME site: </b> <?=$join_date ?> <br>
		Current status: <?=$status_name?></p>

	<p><b>Email:</b> <?=$email_public?> <em><?=$email_status_name?></em> <?=$hidden_emailer?>
		</p>

	<p><b>Now</b> <br><?=$this->nl2br($user_current )?></p>

	<p><b>At AMD: </b> <?= $at_amd ?><br>
		<?=$this->e($user_amd )?><br>
		Badge No: <?=$badge_no?></p>

	<p><b>Interests:</b><br><?=$this->e($user_interests )?></p>
	<p><?=$weblink ?></p>
	<p><?= $linkedinlink ?></p>

</div>

<?php if (strlen($user_about) > 0 ): ?>

     <div style="width:350px;margin-right:12px;float:left;">
		<p><b>About Me</b></p>
         <?=$user_about_linked?>

	</div>
<?php endif ?>


<?php if(!empty($photos)) :?>
	<div class='user-photos left'>
	<p><b>Photos</b></p>
	<?php foreach ($photos as $aid=>$pdata) :
			if(isset($pdata['random'] ) ):
				echo "(Random Photo)<br>";
			endif;
			echo $pdata['block'];


	endforeach; ?>
	</div><div class='clear'></div>
<?php endif; ?>


 <?php if (strlen($user_memories) > 0 ): ?>
     <div style="width:350px;margin-right:12px;float:left;">
		<p><b>Working at AMD:</b></p>
		 <?= $user_memories ?>
    </div>
<?php endif ?>

</div>

 <div class='float-clear'>
 </div>








