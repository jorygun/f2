<div class='story left clearafter'>

	<?=$content?>
	<?=$more?>

	<p class='source'> From <?=$source?>
		<span class='contributor'> -- Contributed by <?=$contributor?></span></p>

	<?php if (!empty($ed_comment)) : ?>
		<div class='ed_comment'> <?=$ed_comment?></div>
	<?php endif; ?>

</div>
