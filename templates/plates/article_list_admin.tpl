<script>
 $('#alist_form').on('change','#editme', function(){
        let str = $("#editme").val();

        if (str == 0) {
        	$('#editlabel').text('New');
        } else {
        	$('#editlabel').text('Edit');
        }
    });

    // to cause page to refresh after doing something else.
    var blurred = false;
	window.onblur = function() { blurred = true; };
	window.onfocus = function() { blurred && (location.reload()); };

</script>
<?php
	$laststatus = '';
	$statushead = array(
		'P' => "Published",
		'Q' => "Queued for Next Issue",
		'N' => "Entered, But Not Queued",
	);
	use DigitalMx\Flames\Definitions as Defs;
	$admincred = ($_SESSION['level'] > 7) ? true:false;


?>

<h3>Create New Article or Edit An Existing One
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article_list' >Help</button></h3>


<p class='bold'>Edit Any Article or Create a New One</p>
<div class='indent'>Edit this Article Id (Use id 0 to create new article): <input type='text' size=6 id='editme' name='editme' value='0'  >
<button  name='edit_this' onClick="open_article_edit('editme')">
<span id="editlabel"><b>Go</b></span></button><br>

</div>
<br> OR <br>
<form id='alist_form' method='post'>
<p class='bold'>Choose an article from one of these lists:</p>
<div class='indent'>
<button  name='cat' value='unpub'>All Unpublished</button>
<button  name='cat' value='recent'>Recently Published</button>
A single newsletter:
	<select name='issue'><?=$ioptions?></select>
	<button name='cat' value='issue'> Load Issue </button>
</div>


<h3><?=$selected?></h3>

<table class = 'row-lines'>
		<tr><th>Section/Topic</th><th>ID</th>
		<th>Title/Contributor/Source</th>
		<th>Assets (only 1 shown)</th><th>View</th><th>Manage</th></tr>

<?php if (empty($list)):
	echo "Nothing Found";
else: ?>

<?php foreach ($list as $row) :
		$status = $row['status'];
		//$status_name = $row['use_msg'];

		?>

		<?php if ($status != $laststatus):
			$laststatus = $status;
		?>

		<tr><td colspan='5' style='background-color:#CFC;'><b><?=Defs::$article_status[$row['status']]?></b></td>
			<?php if ( $status == 'Q') :?>
					<td><?=$preview_button?></td></tr>
			<?php	endif; ?>
			</tr>
		<?php endif; ?>

		<tr>

		<td><?=$row['section_name']?>/<br>
			&nbsp;&nbsp;<?=$row['topic_name']?>
			<br>
			  <?=$row['delete-button']?>
			</td>
		<td ><?=$row['id']?></td>
			<td><b><?=$row['title']?></b><br>
			 From: <?=$row['contributor']?><br>
			  <i><?=$row['source']?></i><br>
			Votes: <?=$row['take_v']?> Comments: <?=$row['take_c']?>


			 </td>

			<td ><?=$row['asset_count']?> Assets <?=$row['image']?></td>
			<td>
				<?=$row['view-button']?> </td>
			<td>

				<?=$row['edit-button']?><br>
				<?php if ($status != 'P'): ?>
					<?=$row['use-button']?> <br>
				<?php endif; ?>



			</td>
		</tr>


	<?php endforeach; ?>
	</table>

</form>
<?php endif;?>
