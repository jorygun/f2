<?php

	if (stripos($comment,'<p>') === FALSE) $comment = nl2br($comment);
?>
<div class='comment_box' >

	<div class='presource'>
		<?=$username?> - From <?=$user_from?>.  Posted <?=$pdate?>
	</div>
	<?php if (!empty($asset)): ?>
		<div class='asset-column'>
			<?= $asset ?>
		</div>
	<?php endif; ?>
	<div class='comment left'><?=$comment?></div>

</div>