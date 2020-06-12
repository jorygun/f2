<hr>
      <?= $id ?>  <b> <?= $title ?></b>
       <table class='indent' width="100%" id='tbl<?=$id?>' >
       <tr><td width='50%'>
       <i>Caption:</i> <?= $caption ?><br>
        <i>Source url:</i> <?= $asset_url ?>  <br>
        <i>Type:</i> <?= $type ?>  <i>Vintage</i> <?= $vintage ?>  <i>Size</i> <?= $sizekb ?> KB <br>
        <i>Status:</i> <?= $status_label ?>  <?=$warning?><br>
        <i>Tags</i> <?= $tags ?><br>

        <i>Forms:</i> <?= $show_thumbs ?> <br>
        <br>
			<?php if ($mode == 'j') : ?>
				<button type='button' style='background:orange;' onclick='send_id(<?=$id?>)'>
				Send ID</button> << Click to send this ID back to article or comment editor.
			<?php endif; ?>
      <?php if ($editable) : ?>
		<button type='button'
        onclick="ewin = window.open('/asset_editor.php?id=<?=$id?>','asset_edit');">
        Edit Asset</button>
      <?php endif; ?>

      <?php if ($_SESSION['level'] > 7 ): ?>
   		<button type='button' style='background:#FCC'
        onclick="takeAction('deleteAsset',<?=$id?>,'tbl<?=$id?>','resp')">
        Delete</button>

			<?php if ($status != 'R') : ?>
			<button type='button' id='rb<?=$id?>' onclick="takeAction('markReviewed',<?=$id?>,'rb<?=$id?>','')">
			Mark OK </button>
			<?php endif; ?>

	<?php endif; ?>



	</td><td>
	 <a href='imagelink' target='image'><?= $image ?></a>
	 </td></tr>
	</table>


