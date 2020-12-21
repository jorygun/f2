

    <h3>Current Entries in Signup Table</h3>
    <p>Disposition: <br>
    M,G Add to members as Member or Guest list<br>
    R Tag for Review<br>
    X Remove from list<br>
    - Do nothing
    </p>
    <?php
        $last_status = ''; #dummy state
        $heads = array(
        'N' => 'New Unvalidated',
        'R' => 'Hold for Review',
        'X' => 'Remove',
        'A' => 'New, Validated',
    );

    ?>
    <form method="post">

    <table style='border-collapse:collapse;'>

       <tr>
            <th>Name</th>
            <th>Email</th>
            <th>IP</th>
             <th>location</th>
             <th>Entered</th>
            <th>Disposition</th>

            </tr>

     <?php foreach ($mdata as $row) :
        $status = $row['status'];
        $label='D'.$row['id'];
        if ($status != $last_status) :
            $heading = $heads[$status];
            $last_status = $status;
            echo "<tr><td colspan='5' style='background:#030;color:#FFF;font-weight:bold;'>
            $heading</td></tr>
            ";
        endif;

        ?>

        <tr><td style='border-top:3px solid green' colspan='8'></td></tr>
		<tr>
		<td ><?= $this->e($row['username']) ?></td>
		<td  ><?= $row['user_email'] ?> </td>
		<td ><?php
			echo $row['IP'] . '<br>' . $ipdig[$row['IP']]
			?> </td>
		<td> <?= $this->e($row['user_from'] )?></td>
		<td> <?= $row['entered'] ?> </td>
		 <td rowspan='4'>
		    <?php if (in_array($row['status'],[ 'A','R'])): ?>
            <input type='radio' name='<?=$label?>'  value='M' > M
               <input type='radio' name='<?=$label?>'  value='G' > G<br>
            <?php endif; ?>
            <?php if ($status != 'R'): ?>
             <input type='radio'  name='<?=$label?>'  value='R' > R
            <?php endif; if ($status != 'X'): ?>
            <input type='radio' name='<?=$label?>'  value='X' > X
            <?php endif;?>
            <br>
            <input type='radio' name='<?=$label?>'  value='' checked >  -

         </td></tr>

         <tr><td>At AMD: </td><td colspan='3'><?= $this->e($row['user_amd']) ?></td>

         <tr><td>Comment: </td><td colspan='3'><?= $this->e($row['comment'] )?></td></tr>
        <tr><td colspan='4'>&nbsp;</td></tr>
        <?php endforeach; ?>

          </table>
        <input type='submit' name='Process' value='Process'>
        </form>


