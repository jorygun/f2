<?php $row = array_shift($citems); ?>

	<form method='post'>
	<h3>Edit/Create calendar item</h3>
	Calendar ID: <?= $row['id'] ?>
	<input type='hidden' name = 'id'  value='<?= $row['id'] ?>'>
	<input type='hidden' name='contributor_id'
		value = '<?=$_SESSION['login']['user_id']?>'>
	<table>
	<tr><td>Date and Time (eg., July 1, 2020 1:30pm)</td><td><input type=text name='datetime' value = '<?=$row['datetime'] ?>'> (Local Time at Event)</td></tr>
	<tr><td>Event Name</td><td><input type=text name='event' size=40 value = '<?= $row['event'] ?>'></td></tr>
	<tr><td>Region/City</td><td><input type=text name='city' value = '<?= $row['city'] ?>'></td></tr>
	<tr><td>Specific Location </td><td><textarea name='location' rows=3 cols=40><?= $row['location'] ?></textarea><br>
	Map link: <input type='text' size=60 name='map_link' value='<?=$row['map_link']?>'></td></tr>
	<tr><td>Contact (email or url will be linked)</td><td><input type=text name='contact' size=60 value = '<?= $row['contact'] ?>'></td></tr>
	<tr><td>Web Link to Event</td><td><input type=url size=60 name='link' value='<?= $row['link'] ?>'></td></tr>
	<tr><td>More Info <br>
	(You can paste from <br>MS Word into here.)</td><td><textarea name='info' rows=4 cols=60 class = 'useredit'><?= $row['info'] ?></textarea></td></tr>

	<tr><td><input type=submit name="Submit" value='Enter'></td></tr>
	</table>
	</form>
