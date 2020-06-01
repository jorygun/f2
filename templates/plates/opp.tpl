<h3><?=$title?></h3>
		Location: <?=$location?><br>
		Contact: <?=$owner?> <a href=mailto:<?=$owner_email?>><?=$owner_email?></a><br>
		 Posted: <?=$created?> Expires: <?=$expired?><br>
		 <hr><?=$description?><hr>
		 <?php if (!empty($link)) : ?>
		 More Info: <a href='<?=$link?>' target='_blank'>Link</a><br>
		 <?php endif; ?>
		 <i>Status: <?=$status?> </i>
		 <br>
		 <?=$edit_button?>
