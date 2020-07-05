<?php

	if (stripos($comment,'<p>') === FALSE) $comment = nl2br($comment);
?>
<div class='comment_box' >

	<div class='presource'>
		<?=$username?> - From <?=$user_from?>.  Posted <?=$pdate?>
	</div>
	<?php if (!empty($asset)):
	\DigitalMx\echor ($asset);

			echo $asset['adiv'] ;
				 foreach ($asset['asset_blocks'] as $a) :
 				echo $a;
 			 endforeach;
			echo "</div>" ;

	?>


		</div>
	<?php endif; ?>
	<div class='comment left'><?=$comment?></div>

</div>
