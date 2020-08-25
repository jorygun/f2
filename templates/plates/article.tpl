<div class='article'>
	<p class='topic'><u><?= $topic_name ?></u> in <?=$section_name?></p>
	<p class='headline'> <?= $title ?></p>

	<?php if (!empty($asset_blocks) ) : ?>
		<div class='<?=$adiv?>'>
		<?php foreach ($asset_blocks as $ablock): ?>
			<?=$ablock ?>
		<?php endforeach; ?>
		<div class='clear'></div>
		</div>
	<?php endif; ?>

	<div class='story '>

		<?=$content?>
		<?=$more?>

		<p class='source'>  <?=$sfrom?>
			<span class='contributor'> -- Contributed by <?=$contributor?> on <?=$date_entered_human?></span></p>

		<?php if (!empty($ed_comment)) : ?>
			<div class='ed_comment'> <?=$ed_comment?><br />--/<?=$contributor?></div>
		<?php endif; ?>

	</div>
<div class='clear'></div>

	<?= $pblock?>
	<?= $dblock?>

</div>


