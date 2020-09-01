
<?php $lastmsg=''; $lastestat='';?>
<h3>Create or Edit a News Article</h3>
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article_list' >Help</button></h3>


<p class='bold'>Edit Any Article or Create a New One</p>
<button onClick = "window.open('article_editor.php?id=0','article_edit')"
	type='button'>New Article</button>


<br> OR <br>

<p class='bold'>Choose an article from the list below:</p>



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
				<tr><td colspan='4' style='background-color:#CFC;'><b>{$row['use_msg']}</b></td>";
				if (stristr($row['use_msg'],'Queued') !== false) :
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
				<?=$row['use-button']?><br>


			</td>
		</tr>


	<?php endforeach; ?>
	</table>
	<?php endif; ?>
<?php endforeach; ?>
</table>
