<script>
 $('#alist_form').on('change','#editme', function(){
        let str = $("#editme").val();

        if (str == 0) {
        	$('#editlabel').text('New');
        } else {
        	$('#editlabel').text('Edit');
        }
    });
</script>
<?php $lastmsg=''; $lastestat='';?>
<h3>Create New Article or Edit An Existing One
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article_list' >Help</button></h3>

<form id='alist_form' method='post'>
<p class='bold'>Edit Any Article or Create a New One</p>
<div class='indent'>Edit this Article Id (Use id 0 to create new article): <input type='text' size=6 id='editme' name='editme' value='0'  >
<button  name='edit_this' onClick="open_article_edit('editme')">
<span id="editlabel"><b>Go</b></span></button><br>

</div>
<br> OR <br>

<p class='bold'>Choose an article from one of these lists:</p>
<div class='indent'>
<button  name='cat' value='unpub'>All Unpublished</button>
<button  name='cat' value='recent'>Recently Published</button>
A single newsletter:
	<select name='issue'><?=$ioptions?></select>
	<button name='cat' value='issue'> Load Issue </button>
</div>
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
				<?=$row['use-button']?><?=$row['status']?><br>
				<?=$row['delete-button']?>


			</td>
		</tr>


	<?php endforeach; ?>
	</table>
	<?php endif; ?>
<?php endforeach; ?>
</table>