<?php 
	
	if (stripos($comment,'<p>') === FALSE) $comment = nl2br($comment);
?>
<div class='comment_box' >
               
        <div class='presource'>
            <?=$username?> - From <?=$user_from?>.  Posted <?=$pdate?> 
        </div> 
        <div class='comment' style='clear:both'><?=$comment?></div>
            
</div>
