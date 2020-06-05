

<div class='float-left box' >
	<button type='button' onClick = "window.open('/asset_editor.php')">New Asset</button><br>
	<button type='button' onclick="clearForm(this.form);">Clear Form</button><br>
	<button type='button' name='SearchHelp' id ='help-button' value='assets' >Help</button>
</div>


<div class='float-left'>

<form method='post' name='select_assets' id='select_assets'>
	<b>Choose any of the parameters below to find photos, videos, and audio files.</b>
	<hr>
	<table>

		<tr>
			<td>Search Terms</td>
			<td>
			<input type='text' name='searchon' value='<?=$searchon_hte?>'></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><small>Search is not case sensitive. Search terms can include spaces (like 'John East'). Separate terms with commas. </small></td>
		</tr>
		<tr>
			<td>Vintage (year)</td>
			<td>
			<input type='text' name='vintage' size="8" value='<?=$vintage?>'> +/- years:
			<input type="text" name='plusminus' size="3" value='<?=$plusminus?>'></td>
		</tr>
		<tr>
			<td>Asset Type</td>
			<td>
			<select name='type'>

<?=$type_options?>
			</select>
			</td>
		</tr>
		<tr>
			<td>Tags (* tags are 'archival')</td>
			<td>
<?=$tag_options?>
			</td>
		</tr>
		<tr>
			<td>Asset id or range</td>
			<td>
			<input type="text" name='id_range' value='<?=$id_range?>'></td>
		</tr>
<?php if ($_SESSION['level'] > 4 ) : ?>
		<tr>
			<td>Status</td>
			<td> <input type='checkbox' name='all_active' id='all_active' value=1
<?=$all_active_checked?>
			onchange = 'asset_status_search(this)' > All Active or ... <input type='checkbox' name='unreviewed' id='unreviewed' value=1
<?=$unreviewed_checked?>
			onchange = 'asset_status_search(this)' > Unreviewed or ...
			<select name='status' id='status_options' onchange='asset_status_search(this)'>

<?=$status_options?>
			</select>
			</td>
		</tr>
<?php else : ?>
		<tr>
			<td>
			<input type='hidden' name='all_active' value="1">
			<input type='hidden' name='status' value=''></td>
		</tr>
<?php endif; ?>
		<tr $hideme>
			<td>Contributor<br> </td>
			<td><input type='text' name='contributor' id='contributor' value='<?=$contributor?>' </td>
		</tr>
		<tr>
			<td>First Use Date</td>
			<td>
			<select name='relative'>
<?=$use_options?>
			</select>
			<input type='text' name='searchuse' value='<?=$searchuse?>'></td>
		</tr>
		<tr>
			<td><button type='submit' name='submit' id='submit' value="true">Submit</button></td>
			<td></td>
		</tr>
		<tr>
			<td colspan='3'>
			<hr></td>
		</tr>
	</table>
</form>

</div>
<div id='helpdiv' style='display:none'>
<?php $this->insert('help::assets'); ?>
</div>
</body></html>
