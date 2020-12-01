<div class='article'>
	<?php if ($topic != 'toon'): ?>
	<p class='topic'><u><?= $topic_name ?></u> </p>
	<p class='headline'> <?= $title ?></p>
	<?php endif; ?>
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



	</div>
<div class='clear'></div>
<?php if (!empty($ed_comment)) : ?>
			<div class='ed_comment'> <?=$ed_comment?></div>
<?php endif; ?>

	<?= $pblock?>
	<?= $dblock?>

</div>


