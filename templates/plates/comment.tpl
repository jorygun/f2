<?php

	if (stripos($comment,'<p>') === FALSE) $comment = nl2br($comment);
?>
<div class='comment_box' >

	<div class='presource'>
		<?=$username?> - From <?=$user_from?>.  Posted <?=$pdate?>
	</div>
	<?php if (!empty($asset)): ?>
		<?php foreach ($asset as $aset): ?>
			<?= $aset['adiv'] ?>
			<?php foreach ($aset['asset_blocks'] as $a) : ?>
				<?= $a ?>
			<?php endforeach; ?>
			</div>
		<?php endforeach; ?>


		</div>
	<?php endif; ?>
	<div class='comment left'><?=$comment?></div>

</div>
