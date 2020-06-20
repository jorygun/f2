
<div class='calendar'>
<h3>Calendar Items</h3>
<table id = 'calendar'>

<?php foreach ($citems as $row) :
 ?>
<tr class='first'>
	<td colspan=3 class='event'><?= $row['event'] ?></td>

</tr>
<tr>
	<td class='date'> <?= $row['edate'] ?> <?= $row['etime'] ?></td>
	<td><?= $row['city'] ?></td>
	<td class='location'><?= $row['location'] ?></td>
</tr>
<tr>
	<td colspan=3 class='info'><?= $row['info'] ?></td>

</tr>
<tr><td colspan=3><?= $row['contact'] ?></td></tr>
<?php if ($credential) : ?>
<tr><td><?=$row['edit_link']?></td></tr>
<?php endif; ?>
<?php endforeach; ?>

</table>
<button type='button' onClick=window.location='/calendar.php?edit=new'>New Item</button>

</div>
