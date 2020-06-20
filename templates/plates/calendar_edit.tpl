<?php $row = array_shift($citems); ?>

	<form method='post'>
	<h3>Edit/Create calendar item</h3>
	<input type='text' name = 'id' READONLY value='<?= $row['id'] ?>'>
	<table>
	<tr><td>Date and Time</td><td><input type=text name='datetime' value = '<?=$row['datetime'] ?>'> (Local Time at Event)</td></tr>
	<tr><td>Event Name</td><td><input type=text name='event' size=40 value = '<?= $row['event'] ?>'></td></tr>
	<tr><td>Region/City</td><td><input type=text name='city' value = '<?= $row['city'] ?>'></td></tr>
	<tr><td>Specific Location (url will be linked)</td><td><textarea name='location' rows=3 cols=40><?= $row['location'] ?></textarea></td></tr>
	<tr><td>Contact (url will be linked)</td><td><input type=text name='contact' size=60 value = '<?= $row['contact'] ?>'></td></tr>
	<tr><td>More Info (url will be linked)</td><td><textarea name='info' rows=4 cols=60 class = 'useredit'><?= $row['info'] ?></textarea></td></tr>
	<tr><td><input type=submit name="Submit" value='Enter'></td></tr>
	</table>
	</form>
