<style>
table {border:1px solid black; }
.thumbtable td {text-align:center;width:10em;size:1em;}
.assettable td {size:1em;}
</style>

<h2 style="border-top:1px solid black;">Asset Editor</h2>

<?php if ($id>0): ?>
	<h4>Existing ID</h4>
	(automatic data below)
	<ul>
	<li><b>ID:</b> <?=$id?>  <b>Status:</b> <span style='<?=$status_style?>'><?=$status?> <?=$status_name?> </span><b>Entered:</b> <?=$date_entered?> 
<li><b>Mime type:</b> <?=$mime?> <b>Size: </b><?=$sizekb?> KB. 
<li><b>First Use:</b> <?=$first_use?>
</ul>
<?php else:?>

	<h4>Create New Asset</h4>
	<p><b>ID:</b> <?=$id?>  <b>Status:</b> <?=$status?> <?=$status_name?> <b>Entered:</b> <?=$date_entered?> </p>
<?php endif;?>

<form  method="POST" enctype="multipart/form-data"  name="asset_form" id="asset_form">
	
<input type='hidden' name ='id' value ='<?=$id?>'>

<hr>

<table class='assettable' style='width:750px;'>

<tr><td>Item Title</td><td><input type='text' size='60' name='title' id='title' value="<?=$title?>"></td></tr>
<tr><td>Caption (not reqd)</td><td><textarea  name='caption' rows=5 cols=60><?=$caption?></textarea></td></tr>
<tr><td>Other Keywords (comma sep)</td><td><input type='text' name='keywords' value='<?=$keywords?>' size='60'/></td></tr>

<tr><td>Origin</td><td>Vintage: <input type='text' name='vintage' value = "<?=$vintage?>" size="6"> Attribute to <input type='text' name='source' value="<?=$source?>" size="40"> </td></tr>
<tr><td>FLAME contributor:</td><td><input type='text' name='contributor' value='<?=$contributor?>' onfocus="form.contributor_id.value='';"
    style = '$cont_style'> id: <input type='text' name='contributor_id' id='contributor_id' value='<?=$contributor_id?>' ><br><?=$Aliastext?></td></tr>
    
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



<tr><td style="vertical-align:text-top;">Notes (not published)</td><td><textarea rows=2 cols=40 name='notes'><?=$notes?></textarea></td></tr>
<tr><td>Thumb Images</td>
	<td><table class='thumbtable'>
	<tr><th>Form</th><th>Exists</th><th>Create/Recreate</th></tr>
	<tr ><td>Thumb </td><td ><?=$thumb_tics['thumbs']?></td>
	<td><input type='checkbox' name='thumbs' <?=$thumb_checked?>></td></tr>
	<tr><td>Gallery </td><td><?=$thumb_tics['galleries']?></td>
	<td><input type='checkbox' name='galleries'></td></tr>
	<tr><td>Toon </td><td><?=$thumb_tics['toons']?></td>
	<td><input type='checkbox' name='toons'></td></tr>
	</td></tr>
</table>

</table>

<hr>
<input type='submit' name='submit'>
</form>
<?=$link?>
<hr>
