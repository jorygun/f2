<style>
table{
	width:100%;
	border-collapse:collapse;
}
table tr {
	border-top:1px solid gray;
}
table tr td {
	padding-bottom: 1em;
}
</style>
<?php $lastmsg=''; $lastestat='';?>

<form id='alist_form' method='post'>
Edit Any Article id: <input type='text' size=6 id='editme' name='editme' value='0'>
<button  name='edit_this' onClick="open_article_edit('editme')">
Edit</button> (Use id 0 for new article)<br>
Lists:
<button  name='style' value='unpub'>List Unpub</button>
<button  name='style' value='recent'>List Recent</button>
<br>


</form>
<h3><?=$emsg['selected']?></h3>
<?php foreach (['editable','noneditable'] as $estat): ?>
<h4 class='blueback'><?=$emsg[$estat]?></h4>
	<?php if (empty($$estat)) :?>
		None
	<?php else: ?>
	<table >
	<tr><th>Section(priority)</th><th>ID</th>
		<th>Title/Contributor/Source</th>
		<th>Assets (only 1 shown)</th><th>Manage</th></tr>
	<?php foreach ($$estat as $row) : ?>
		<?php if ($row['smsg'] != $lastmsg): ?>
			<tr><td colspan='5' style='background-color:#CFC;'><b><?=$row['smsg']?></b></td></tr>
		<?php $lastmsg = $row['smsg']; endif; ?>

		<tr>

		<td><?=$row['section_name']?>/<br>
			&nbsp;&nbsp;<?=$row['topic_name']?>
			 (<?=$row['use_me']?>)
			</td>
		<td ><?=$row['id']?></td>
			<td><b><?=$row['title']?></b><br>
			 From: <?=$row['username']?><br>
			  <i><?=$row['source']?></i><br>

			 </td>

			<td s><?=$row['asset_count']?> Assets <?=$row['image']?></td>
			<td>
				<?=$row['view-button']?>
				<?=$row['edit-button']?><br>
				<?=$row['use-button']?>

			</td>
		</tr>


	<?php endforeach; ?>
	</table>
	<?php endif; ?>
<?php endforeach; ?>
</table>
