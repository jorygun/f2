
    <p><?= $info ?></p>


    <table >


     <?php foreach ($mdata as $row) :

        ?>


		<tr>
		<td ><b><?= $row['username'] ?></b>
			<?php if ($row['status'] == 'D') : ?>
			 *
			 <?php endif; ?>
			 </td>
		<td ><?= $row['email_public'] ?> </td>
		<td><?= $row['user_from'] ?> </td>
		<td align='center'><a href='/profile.php?uid=<?=$row['uid'] ?> ' target='profile'>Profile</a></td>


		</tr>





        <?php endforeach; ?>

          </table>
          <p> * = Deceased</p>



