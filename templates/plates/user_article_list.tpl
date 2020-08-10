
<?php $lastmsg=''; $lastestat='';?>
<h3>Create New Article or Edit An Existing One
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article_list' >Help</button></h3>

Click here to start a new article: <button onClick="window.open('/article_editor.php?id=0','article_edit');" type='button'><b>New Article</b></button>
<br> OR <br>
Edit one of the existing articles below.  You can "manage" articles that you created.
Articles "Queued for Next" are set to appear in the next newsletter.

<h3><?=$emsg['selected']?></h3>
<?php foreach (['editable','noneditable'] as $estat): ?>
<h4 class='blueback'><?=$emsg[$estat]?></h4>
	<?php if (empty($$estat)) :?>
		None
	<?php else: ?>
	<table class = 'row-lines'>
	<tr><th>Section(priority)</th><th>ID</th>
		<th>Title/Contributor/Source</th>
		<th>Assets (only 1 shown)</th><th>Manage</th></tr>
	<?php foreach ($$estat as $row) : ?>
		<?php
			if ($row['use_msg'] != $lastmsg):
				echo "
				<tr><td colspan='5' style='background-color:#CFC;'><b>{$row['use_msg']}</b></td>";
				if ($row['use_msg'] == 'Queued For Next') :
					echo "<td>$preview_button</td></tr>";
				endif;
				$lastmsg = $row['use_msg'];
			endif;
		?>

		<tr>

		<td><?=$row['section_name']?>/<br>
			&nbsp;&nbsp;<?=$row['topic_name']?>
			 (<?=$row['use_me']?>)
			</td>
		<td ><?=$row['id']?></td>
			<td><b><?=$row['title']?></b><br>
			 From: <?=$row['contributor']?><br>
			  <i><?=$row['source']?></i><br>

			 </td>

			<td s><?=$row['asset_count']?> Assets <?=$row['image']?></td>
			<td>
				<?=$row['view-button']?>
				<?=$row['edit-button']?><br>



			</td>
		</tr>


	<?php endforeach; ?>
	</table>
	<?php endif; ?>
<?php endforeach; ?>
</table>
