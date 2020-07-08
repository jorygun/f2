<script>
 $("#editme").change(function(){
        var str = $("#editme").val();
        alert(str);
    });
</script>
<?php $lastmsg=''; $lastestat='';?>
<h3>Create New Article or Search for Existing
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article_list' >Help</button></h3>

<form id='alist_form' method='post'>
<p class='bold'>Create New Article or Edit Any Article</p>

Enter Article Id (use 0 for new article): <input type='text' size=6 id='editme' name='editme' value='0'  >
<button  name='edit_this' onClick="open_article_edit('editme')">
Go</button>

<br> OR <br>

<p class='bold'>Select a List:</p>
<button  name='cat' value='unpub'>All Unpublished</button>
<button  name='cat' value='recent'>Recently Published</button>
One Issue:
	<select name='issue'><?=$ioptions?></select>
	<button name='cat' value='issue'> Go: </button>

</form>

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
		<?php if ($row['use_msg'] != $lastmsg): ?>
			<tr><td colspan='4' style='background-color:#CFC;'><b><?=$row['use_msg']?></b></td>
			<td><?=$preview_button?></td></tr>
		<?php $lastmsg = $row['use_msg']; endif; ?>

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
				<?=$row['use-button']?>

			</td>
		</tr>


	<?php endforeach; ?>
	</table>
	<?php endif; ?>
<?php endforeach; ?>
</table>
