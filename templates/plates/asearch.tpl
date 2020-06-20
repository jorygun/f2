

<div class='box'>
	<button type='button' onClick = "window.open('/asset_editor.php?id=0')">New Asset</button>
	<button type='button' onclick="clearForm('select_assets');">Clear Form</button>
	<button type='button' name='SearchHelp' class='help-button' id ='help-button' value='asearch' >Help</button><br>
	<b>Choose any of the parameters below to find photos, videos, and audio files.</b>
</div>


<hr>
<form method='post' name='select_assets' id='select_assets'>


	<table>
		<tr>
			<td>Asset id or range</td>
			<td>
			<input type="text" name='id_range' value='<?=$id_range?>'>
			<br><small>(id <i>1234</i>, range <i>1234 - 1239</i>, or list <i>1234, 1239, 1247 ...</i>)</small></td>
		</tr>

		<tr>
			<td>Phrases</td>
			<td>
			<input type='text' size='40' name='searchon' value='<?=$searchon_hte?>'>
			<br><small>Search is not case sensitive. Search terms can include spaces (like 'John East'). Separate terms with commas. </small></td>
		</tr>

		<tr>
			<td>Vintage (year)</td>
			<td>
			<input type='text' name='vintage' size="8" value='<?=$vintage?>'> +/- years:
			<input type="text" name='plusminus' size="3" value='<?=$plusminus?>'></td>
		</tr>
		<tr>
			<td>Type</td>
			<td>
			<select name='type'>
				<?=$type_options?>
			</select>
			</td>
		</tr>
		<tr>
			<td>Tags</td>
			<td>
				<?=$tag_options?>
			</td>
		</tr>

<?php if ($_SESSION['level'] > 4 ) : ?>
		<tr>
			<td>Status</td>
			<td>
			<select name='status' id = 'status_select'>
				<?=$status_options?>
			</select>
			</td>
		</tr>
<?php else : ?>
		<tr>
			<td>
			<input type='hidden' name='status' value='active'>
			</td>
		</tr>
<?php endif; ?>
		<tr $hideme>
			<td>Contributor Name </td>
			<td><input type='text' name='contributor' id='contributor' value='<?=$contributor?>'>
			<br><small>(must match Flames user name)</small></td>
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

</body></html>
