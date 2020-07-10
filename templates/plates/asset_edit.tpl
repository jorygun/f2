<style>
table {border:1px solid black; }
.thumbtable td {text-align:center;width:10em;size:1em;}
.assettable td {size:1em;}
</style>

<form  method="POST" enctype="multipart/form-data"  name="asset_form" id="asset_form">

<div style='background:#FFC; border=1px solid black;'>
<p>Current search result list: <?=$current_count?> assets.
Next To Edit: <input type='text' name='next_edit' size=6 value=<?=$next_edit?>> </p>
<button name='submit' value='save'>Save and Review</button>
<button name='submit' value='next'> Save and Go Next</button>
<button name='submit' value='skip'>Skip and Go Next</button>
<button name='submit' value = 'new'>New Asset</button>
<br>
<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='assets' >Help</button>
</div>

<?php if ($id>0): ?>
	<h2>Existing ID <?=$id?></h2>
	<?=$list_note?>
	<table>

	<tr><td> Status:</td>
		<td> <span style='<?=$status_style?>'><?=$astatus?>
		<?=$status_name?> </span></td></tr>
<tr><td> Entered: </td><td><?=$date_entered?> </td></tr>
<tr><td>Mime &amp; Asset Type: </td><td><?=$mime?> : <?=$type?> </td></tr>
<tr><td>Size: </td><td><?=$sizekb?> KB. </td></tr>
<tr><td>First Use:</td><td> <?=$first_use?></td></tr>
<tr><td>Errors</td>
	<td><textarea rows=2 cols=40 name='errors' READONLY><?=$errors?></textarea>
</td></tr>

</table>
<br>
<?php else:?>

	<h4>Create New Asset</h4>
	<p><b>ID:</b> <?=$id?>  <b>Status:</b> <?=$astatus?> <?=$status_name?> <b>Entered:</b> <?=$date_entered?> </p>
<?php endif;?>
<hr>
<input type='hidden' name ='id' value ='<?=$id?>'>
<input type='hidden' name ='old_status' value = '<?=$astatus?>'>
<input type='hidden' name='old_aurl' value = '<?=$asset_url?>'>
<input type='hidden' name='old_turl' value = '<?=$thumb_url?>'>
<hr>

<table class='assettable' style='width:750px;'>

<tr><td>Item Title (<=64 chars)</td><td><input type='text' size='60' maxlength ='64' name='title' id='title' value="<?=$title?>"> </td></tr>
<tr><td>Caption (not reqd)</td><td><textarea  name='caption' rows=5 cols=60><?=$caption?></textarea></td></tr>
<tr><td>Other Keywords (comma sep)</td><td><input type='text' name='keywords' value='<?=$keywords?>' size='60'/></td></tr>
<?php if ($_SESSION['level'] >=7) : ?>
<tr><td>Status (admin only)</td><td><select name='astatus'><?=$status_options?></select></td></tr>
<?php else: ?>
	<input type='hidden name='astatus' value = '<?=$astatus?>'?
<?php endif; ?>
<tr><td>Origin</td><td>Vintage: <input type='text' name='vintage' value = "<?=$vintage?>" size="6"> Attribute to <input type='text' name='source' value="<?=$source?>" size="40"> </td></tr>
<tr><td>FLAME contributor:</td><td><input type='text' name='contributor' value='<?=$contributor?>' onfocus="form.contributor_id.value='';"
    style = '$cont_style'> id: <input type='text' name='contributor_id' id='contributor_id' value='<?=$contributor_id?>' ><br>
    Contributor aliases: <?=$Aliastext?><br>
    Use 0 for non-member contributor id.</td></tr>

    <tr><td>Tags</td><td><?=$tag_options?></td></tr>
    <tr><td colspan='2'>&nbsp;</td></tr>

<tr><td colspan='2'>Enter EITHER a url to the asset  OR select a file to upload</td></tr>

<tr><td colspan='2'>Link asset to this file or url </td></tr>
<tr><td></td><td>
Upload file <input type="file" name="uasset" > <br>
    or URL: <input type='text' name='asset_url' value='<?=$asset_url?>' size=60>
    <br>
     <span class='red'><?=$source_warning?></span>
    Use '/ftp/xxx' for files in ftp dir; 'uploads/xxx' for files in uploads dir.

</td></tr>


<tr><td colspan='2'>Upload Thumb Source file if not the asset itself.<br>
	<small>(Thumbs for pdf files and youtube videos generated from asset automatically.)</small></td></tr>
<tr><td></td><td>-- Upload thumb/source file:
    <input type="file" name="uthumb" id="photo"><br>
--- or source URL: <input type='text' name='thumb_url' value='<?=$thumb_url?>' size='60'>
     </td></tr>



<tr><td style="vertical-align:text-top;">Notes (not published)</td>
	<td><textarea rows=2 cols=40 name='notes'><?=$notes?></textarea></td></tr>



<tr><td>Thumb Images</td>
	<td>
	Local Source: <?=$local_src?><br>
	<table class='thumbtable'>

	<tr><th>Form</th><th>Exists</th></tr>

	<tr ><td>Small </td><td ><?=$thumb_tics['small']?></td></tr>
	<tr><td>Medium </td><td><?=$thumb_tics['medium']?></td><tr>

	<tr><td>Large </td><td><?=$thumb_tics['large']?></td></tr>

	</table>
</td></tr>


</table>


[Submit is at top of screen]
</form>
<br>
<?=$link?>
<hr>
