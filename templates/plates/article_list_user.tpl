<?php
	$laststatus = '';
	$statushead = array(
		'P' => "Published",
		'Q' => "Queued for Next Issue",
		'N' => "Entered, But Not Queued",
	);
	$admincred = ($_SESSION['level'] > 7) ? true:false;


?>


<h3>Create New Article or Edit An Existing One
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article_list' >Help</button></h3>

Click here to start a new article: <button onClick="window.open('/article_editor.php?id=0','article_edit');" type='button'><b>New Article</b></button>
<br> OR <br>
Edit one of the existing articles below.
<p>You can View any articles.  You can edit articles that you created that are not queued for publication.  If one of your articles is queued, you can remove it from the queue, and then edit it.</p>



<form id='alist_form' method='post'>
<table class = 'row-lines'>
		<tr><th>Section/Topic</th><th>ID</th>
		<th>Title/Contributor/Source</th>
		<th>Assets (only 1 shown)</th><th>View</th><th>Manage</th></tr>



	<?php foreach ($list as $row) :
		$status = $row['status'];
		?>

		<?php if ($status != $laststatus):
			$laststatus = $status;
		?>

		<tr><td colspan='5' style='background-color:#CFC;'><b><?=$statushead[$status]?></b></td>

				<td></td></tr>
		<?php endif; ?>

		<tr>

		<td><?=$row['section_name']?>/<br>
			&nbsp;&nbsp;<?=$row['topic_name']?>

			</td>
		<td ><?=$row['id']?></td>
			<td><b><?=$row['title']?></b><br>
			 From: <?=$row['contributor']?>
			  <i><?=$row['source']?></i><br>


			 </td>

			<td s><?=$row['asset_count']?> Assets <?=$row['image']?></td>
			<td>
				<?=$row['view-button']?></td>
				<td>
				<?php if ($row['contributor_id'] == $_SESSION['login']['user_id'] ) : ?>
					<?php if ($status == 'N'): ?>
						<?=$row['edit-button']?><br>
					<?php endif; ?>
					<?php if ($status == 'Q'): ?>
					<?=$row['use-button']?><br>
					<?php endif; ?>
				<?php endif; ?>

			</td>
		</tr>


	<?php endforeach; ?>
	</table>

</form>
