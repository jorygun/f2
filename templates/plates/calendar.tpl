
<div class='calendar'>
<h2>Calendar Items</h2>
To place an item on the calendar, click here. <button type='button' onClick=window.location='/calendar.php?edit=new'>New Item</button>
<?php if (empty($citems)) : ?>
	<p>No Item on the Calendar</p>
<?php else: ?>

<table id = 'calendar'>

<?php foreach ($citems as $row) :
 ?>
<tr class='top'>
	<td colspan=2 class='event'><b><?= $row['event'] ?></b></td>
	<td class='date'> <?= $row['edate'] ?> <?= $row['etime'] ?></td>


</tr>
<tr>
	<td class='city'><?= $row['city'] ?></td>
	<td class='location'><?= $row['location'] ?></td>
	<td><?=$row['loc_link']?></td>

</tr>
<tr>
	<td>More Info</td>
	<td  class='info'><?= $row['linked_info'] ?> </td>
	<td><a href='<?=$row['link']?>'><?=$row['link']?></a></td>
</tr>
<tr><td >Contact:</td><td> <?= $row['linked_contact'] ?></td>

<td>Entered by <a href='mailto:<?=$row['user_email']?>' ><?=$row['username']?></a>
<?php if ($row['contributor_id'] == $_SESSION['login']['user_id']
	|| $_SESSION['level'] > 6) : ?>
	<?=$row['edit_link']?>
<?php endif; ?>

</td></tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</div>
