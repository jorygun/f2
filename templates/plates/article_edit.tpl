
<h3>Create/Edit News Article <button type='button' name='SearchHelp' class='help-button' id ='help-button' value='article' >Help</button></h3>

<form  method="POST"  onsubmit="return check_form(['title','topic']);">

<div style='background-color:#ccc;border:1px solid black;'>
(Data below cannot be changed)<br>
ID: <input type='text' name = 'id' value='<?=$id?>' READONLY><br>
Entered: <?=$date_entered?><br>
Status: <?=$status_name?> <?=$date_published?><br>
<input type='hidden' name= 'status' value='<?=$status?>'>

FLAME contributor: <?=$contributor?>

</div>

<hr>
<h4>Article Title and Content</h4>



<table>

<tr><td width='160'>Topic: (required)</td><td><select name='topic' id='topic' class='input required'> <?=$topic_options?></select></td></tr>


<tr><td >Item Title (required)</td><td><input type='text' size='60' name='title' class='input required' id='title' value="<?=$this->e($title)?>"></td></tr>


<tr><td >News Source</td><td><input type='text' name='source' value="<?=$source?>" size="30"> date: <input type='text' name='source_date' value = "<?=$source_date?>" size="15"></td></tr>

<tr><td>URL for more info</td><td><input type='text' name='link' value = "<?=$link?>" size="60"></td></tr>
<tr><td >Title for above url</td><td><input type='text' size='60' name='link_title' value="<?=$link_title?>"></td></tr>


<tr><td style="vertical-align:top;">Text of the Article</td><td><textarea cols=60 rows=10 name='content' class='useredit' ><?=$content?></textarea></td><tr>

<tr><td colspan='2'>Check here to allow other users to add comments or up-votes</td></tr>
<tr><td colspan='2'>Allow Comments? <input type='checkbox' value='1' name='take_comments' <?= $comments_checked ?>> &bull;
Allow Votes? <input type='checkbox' value='1' name='take_votes' <?= $votes_checked ?>></td></tr>



<tr><td><b>Assets</b></td>
	<td>Assign asset ids to surround the article content, and/or assign 1 asset to occupy the full width of the article.  You can find existing assets or create a new one here.<br>
	<button type = 'button' onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >Create a new asset</button>

	<button type='button' onclick="window.open('/asset_search.php?mode=j' ,'assets','width=1100,left=160');">Search Assets</button>
</td></tr>

<tr><td>Surrounding asset ids (left/top of content)</td>
   <td>Separate multiple asset ids with spaces.</br><input type=text name='asset_list' id='asset_list' size = 40 value='<?=$asset_list?>'>
   </td></tr>

<tr><td>One central asset id (will fill width of article body):</td>
	<td><input type=text name='asset_main' id=size=8 value='<?=$asset_main?>'>
	</td></tr>



<?php if ( $_SESSION['level'] >= 7): ?>
	<tr><td colspan='2'><hr></td></tr>
   <tr> <td >
    Priority (3 highest) </td><td> <select name='queue'><?=$queue_options?></select>

    </td></tr>
<tr><td>Editor's comment on the article</td><td><textarea cols=60 rows=3 name='ed_comment'><?=$ed_comment?></textarea></td><tr>

<tr><td>FLAME contributor:</td><td><input type='text' name='contributor' value='<?=$contributor?>' onfocus="form.contributor_id.value='';"
    style = '$cont_style'> id: <input type='text' name='contributor_id' id='contributor_id' value='<?=$contributor_id?>' ><br><?=$Aliastext?></td></tr>

<?php else: ?>

<input type='hidden' name='contributor' value='<?=$contributor?>'>
<input type='hidden' name='contributor_id' id='contributor_id' value='<?=$contributor_id?>' >
<input type='hidden' name='queue' value='<?=$queue_select?>' >
<?php endif; ?>

</table>
<input type='submit' value='Submit Article' style='background:#CFC;'>
<?php if ($id > 0): ?>
<button type='button' style='background:#CFC;' onClick ='window.open("/get-article.php?<?=$id?>")'>View Article</button>
<?php endif; ?>

</form>
</div>

