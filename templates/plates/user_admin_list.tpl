 <?php if ($info == 0): ?>
   <p>No Matching Members</p>
<?php else: ?>
    <p>Matches: <?= $info ?></p>


    <table style='border-collapse:collapse;'>

       <tr>
            <th>Status</th>
            <th>Email Status</th>
            <th>Email Validated</th>
            <th>Last Login</th>
            <th>Profile Validated</th>
            <th>Contributed</th>
            <th>No Bulk</th>
            </tr>

     <?php foreach ($mdata as $row) : ?>

        <tr><td style='border-top:3px solid green' colspan='8'></td></tr>
		<tr>
		<td colspan='2'><b><?= $row['username'] ?></b</td>
		<td  ><?= $row['email_linked'] ?> </td>


		<td>

			<input type='text' READONLY name='login' id='login-<?=$row['user_id']?>' value = '<?= $row['user_login_link'] ?>'>
			</td><td>
			<?= $row['send_login_button'] ?>
			</td><td>
			<button type='button' onClick = copyField('login-<?=$row['user_id']?>')>Copy Login</button>
		    </td></tr>


		<tr style='text-align:center'>
			<td id = '<?= $row['status_id'] ?>' ><?= $row['status'] ?></td>
			<td id = '<?= $row['emstat_id'] ?>' ><?= $row['email_status'] ?></td>
			<td id = '<?= $row['emver_id'] ?>' ><?= $row['email_last_validated_date'] ?></td>
			<td> <?= $row['last_login_date'] ?></td>
			<td><?= $row['profile_date'] ?></td>
			<td id='<?= $row['cdate_id'] ?> '> <?= $row['cdate'] ?> </td>
			<td> <?= $row['no_bulk'] ?> </td>
		</tr>



		<tr>
		<td align='center'>
			<?= $row['x-out-button'] ?>
			</td>
		<td align='center'><?= $row['bounceEmailButton'] ?></td>
		<td align='center'><?= $row['validateEmailButton'] ?> </td>
		<td align='center'><a href='/member_admin.php?uid=<?= $row['uid'] ?> ' target='<?= $row['username'] ?> '>Update</a></td>

		<td align='center'><a href='/profile.php?uid=<?=$row['uid'] ?> ' target='profile'>Profile</a></td>
		<td align='center'><?= $row['markContributeButton'] ?> </td>
		</tr>

        <?php endforeach; ?>

          </table>
<?php endif; ?>
<hr>


