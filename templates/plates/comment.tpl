<?php

	if (stripos($comment,'<p>') === FALSE) $comment = nl2br($comment);
?>
<div class='comment_box' >

	<div class='presource'>
		<?=$username?> - From <?=$user_from?>.  Posted <?=$pdate?>
	</div>
	<?php if (!empty($asset)):
	u\echor ($asset);
		 // foreach ($asset as $aset):
// 			echo $aset['adiv'] ;
// 				 foreach ($aset['asset_blocks'] as $a) :
// 				echo $a;
// 			 endforeach;
// 			echo "</div>" ;
// 		 endforeach;
	?>


		</div>
	<?php endif; ?>
	<div class='comment left'><?=$comment?></div>

</div>
