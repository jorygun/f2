<hr>
      <?= $id ?>  <b> <?= $title ?></b>
       <table class='indent' width="100%">
       <tr><td width='50%'>
       <i>Caption:</i> <?= $caption ?><br>
        <i>Source url:</i> <?= $asset_url ?> <span class='red'><?=$source_warning?></span> <br>
        <i>Type:</i> <?= $type ?>  <i>Vintage</i> <?= $vintage ?>  <i>Size</i> <?= $sizekb ?> KB <br>
        <i>Status:</i> <?= $status_label ?> <i>Tags</i> <?= $tags ?><br>
        <i>Thumbs</i> <?= $show_thumbs ?> <br>
        <br>

      <?php if ($editable) : ?>
		
	<button type='button'
        onclick="ewin = window.open('/asset_editor.php?id=<?=$id?>','asset_edit');">
        Edit Asset</button>
	<?php endif; ?>

	</td><td>
	 <a href='imagelink' target='image'><?= $image ?></a>
	 </td></tr>
	</table>       
  

