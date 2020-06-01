<div style='margin-left:2em;float:left'>
<style>
	table.article_list td{text-align:center;}
</style>
   <h4>Archival Assets Added Since <?=$assets_from?></h4>
<p style='font-size:0.9em;'>(This list shows archival assets only, not all assets.  Find any asset on the site by using Search &gt; Search Graphics/Video. 'Multimedia' means streaming audio/video. )</p>
<?php if (!empty ($assets)) : ?>
   <table class='alternate article_list'>

         <tr style='background:#cfc;'><th>Title (click to view)</th><th>Contributor</th><th>Tags</th><th>Type</th><th>Vintage</th><th>MB</th></tr>

    <?php foreach ($assets as $row) : ?>
			<tr><td style='text-align:left;'>
				<?=$row['link'] ?></a> </td>
				<td><?=$row['contributor']?> </td>
			<td><?= $row['tagtext']?> </td>
			<td><?= $row['type'] ?> </td>
			<td><?= $row['vintage'] ?> </td>
			<td><?= $row['sizemb'] ?> </td>
			</tr>
      <?php endforeach; ?>
		</table>
<?php else: ?>
	Nothing to Report
<?php endif; ?>
		<p><small>Run <?= $run_date?></small></p>

</div>


