<style>
table {border:1px solid black; }
.assettable td {size:1em;}
</style>

<h4>Create New Asset</h4>
	
<form  method="POST" enctype="multipart/form-data"  name="asset_form" id="asset_form">
	
<input type='hidden' name ='id' value = '0' >
<input type='hidden' name ='astatus' value = 'N' >
<input type='hidden' name='contributor' value = <?=$contributor?>' >

<table class='assettable' style='width:750px;'>

<tr><td>Item Title</td><td><input type='text' size='60' name='title' id='title' value="<?=$title?>"></td></tr>
<tr><td>Caption (not reqd)</td><td><textarea  name='caption' rows=5 cols=60><?=$caption?></textarea></td></tr>
<tr><td>Origin</td><td>Vintage: <input type='text' name='vintage' value = "<?=$vintage?>" size="6"> Attribute to <input type='text' name='source' value="<?=$source?>" size="40"> </td></tr>

 
<tr><td colspan='2'>Enter EITHER a url to the asset  OR select a file to upload</td></tr>

<tr><td colspan='2'>Link asset to this file or url </td></tr>
<tr><td></td><td>
Upload file <input type="file" name="uasset" > <br>
    or URL: <input type='text' name='asset_url' value='<?=$asset_url?>' size=60>
   
</td></tr>
</table>
<input type='submit' name='submit'>
</form>
