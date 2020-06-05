<h4>Edit Gallery</h4>
<form  method="POST"  style="border:1px solid black;padding:6px;" name="gallery_form" >
<input type='hidden' name = 'id' value='<?= $id ?>'>
<table>
<tr><td>ID</td><td><?= $id ?></td></tr>
<tr><td>Gallery Title</td><td><input type='text' size='60' name='title' id='title' value="<?= $title ?>"></td></tr>
<tr><td>Caption</td><td><input type='text' size='60' name='caption'  value="<?= $caption ?>"></td></tr>


<tr><td>Thumb File </td><td><input type='text' name='thumb_file' value='<?= $thumb_file ?>' size='40'>
	thumb asset id <input type='text' name='thumb_id' value = '<?=$thumb_id ?>' >
	</td></tr>

<tr><td>Origin</td><td>Vintage: <input type='text' name='vintage' value = "<?= $vintage ?>" size="6">  </td></tr>
<tr><td>Assets </td><td>list by id, range, or 'search: criteria'<br><textarea rows='4' cols='40' name='gallery_items'><?= $gallery_items ?></textarea></td></tr>


<tr><td>Contributor</td><td><input type='text' name='contributor' value='<?=$contributor?>' onfocus="form.contributor_id.value='';"
    style = '$cont_style'> id: <input type='text' name='contributor_id' id='contributor_id' value='<?=$contributor_id?>' ><br><?=$alias_list?></td></tr>
<tr><td>

<input type="submit" value='Submit'>

</td><td >
</td></tr>

</table>
</form>

