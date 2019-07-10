<?php
namespace digitalmx\flames;

#ini_set('display_errors', 1);


//BEGIN START
	require_once "init.php";

	#require others

	use digitalmx\flames\DocPage;
	use digitalmx as u;
	use digitalmx\flames\Definitions as Defs;

	

	$page = new DocPage;
	$title = "User Admin"; 
	echo $page->startHead($title, 8);
	echo $page->startBody($title ,2);

//END START

	$status_options = "<option value=''>Choose...</option>";
	foreach (array('M','G','MC','MU','MN','N','T','I') as $v){
		$desc = Defs::getMemberDescription($v);
		$status_options .= "<option value='$v'>$v ($desc)</option>";
	}

    $ems_options = "<option value=''>Choose...</option>";
    foreach (Defs::getEmsNameArray() as $v=>$desc){
		$ems_options .= "<option value='$v'>$v ($desc)</option>";
	}


if (!empty ($_POST['search']) ){
    search($_POST,$pdo);

}
elseif (!empty ($_GET['xout'])) {
    $r = xout($_GET['xout'],$pdo);
    echo $r , BRNL;
    search ($_SESSION['last_search'],$pdo);
}
elseif (isset($_POST['submit']) && $_POST['submit'] == 'Run Sweep'){
    echo  "<script type='text/javascript'>var win = window.open('/scripts/sweep.php?mode=$_POST[mode]&onlyid=$_POST[onlyid]','Sweep') ; </script>";
}


echo showFinder();

function echo_user_row ($row,$post=''){
       $fields = array('status','email_status', 'email_last_validated','record_updated','last_login','no_bulk');

    echo "<tr><td style='border-top:3px solid green' colspan='8'></td></tr>";
        $username = dmx\entity_spec($row['username']);
        echo "<tr><td colspan='2'><b>$username</b></td>";

        $v = $row['user_email'];
        $v = "<a href='mailto:$v'>$v</a>";
        echo "<td colspan='2'>$v</td>";
        $user_login_link = "https://amdflames.org/?s=$row[upw]$row[user_id]";
       echo "<tr>";
        foreach ($fields as $k){
            $v = $row[$k];
            switch ($k) {

                case 'email_status_time':
                case 'email_last_validated':
                case 'profile_validated':
                case 'record_updated':
                case 'last_login':
                    $v = substr($row[$k],0,10); break;

            }


            echo " <td  style='border-top:1px solid green'>$v</td>";
        }
        echo "</tr>\n";
        echo "<tr> <td colspan='5'>User Login: $user_login_link <a href='$user_login_link' target='user_login'>Log in as</a></td></tr>";

        $login = $row['upw'] . $row['user_id'];
        $id = $row['id'];

        $urlemail = rawurlencode($row['user_email']);

        echo "<tr><td align='center'><a href='#' onclick=\"window.open('/scripts/send_lost_link.php?email=$urlemail','lostlink','height=200,width=400,x=200,y=200');return false;\">Send Login</a></td>";
        echo "<td align='center'><a href='/scripts/profile_view.php?id=$id' target='profile'>Profile</a></td>";
        echo "<td align='center'><a href='/scripts/update_member.php?id=$id&email_status=LB' target='$username'>Bounces</a></td>";
        echo "<td align='center'><a href='/scripts/verify_email.php?r=$id' target='verify'>Verify Email</a></td>";
        echo "<td align='center'><a href='/scripts/update_member.php?id=$id' target='$username'>Update</a></td>";
        echo "<td align='center'><a href='/scripts/edit_member.php?id=$id' target='$username'>Edit</a></td>";
        echo "<td align='center'><a href='/scripts/mark_contributor.php?id=$id' target='_blank'> Donor</a></td>";
       // echo "<td align='center'><a href='/scripts/xout.php?xid=$id&post=$post' target='_blank'>X out</a></td>";
        echo "<td align='center'><button name='xout' value='$id' type='submit'>Xout</button></td>";
        echo "</tr>\n";

}

function xout($id,$pdo){
  

    $sql = "UPDATE `members_f2` SET status = 'X' WHERE id = $id;";
    echo $sql;
    if (! $result = $pdo -> query($sql)) {return ("x out failed");}
    else {return "OK";}


}

function search($post,$pdo){
    

    // encode the search params so they can be put into a get command
    //$_POST['get'] = true; #sets flag for future xout script
    // $post_array=urlencode(json_encode($_POST));
     $_SESSION['last_search'] = $post;


    $fields = array('status','email_status', 'email_last_validated','record_updated','last_login','no_bulk');
		$q = array ();

		if (!empty($name = $post['name'])){
		  # $name = str_replace('\\','\\',$name);
			$q[] = " username like ? ";
			$namelike="%${name}%";
			$use_name=1;
		}
		
		

		if ($email = $post['email']){
			#if (!is_valid_email($email = trim($email))){die ("Invalid email $email");}

			$q[] = " user_email LIKE '%$email%' ";
		}
		if ($status = $post['status']){
			$q[] = " status LIKE '$status' ";
		}
		if ($ems = $post['ems']){
			$q[] = " email_status LIKE '$ems' ";
		}
		if ($admin_status = $post['admin_status']){
			$q[] = " admin_status LIKE '$admin_status' ";
		}
		$sql = "SELECT * FROM `members_f2` WHERE " . implode (' AND ',$q) . " ORDER BY status " . " LIMIT 100;";
#echo $sql .  BRNL;

		$stmt = $pdo -> prepare($sql);
		if (!empty($name)){
      	$stmt -> execute([$namelike]) ;
      	#echo " using username = $namelike" . BRNL;
      } else {
      	$stmt -> execute();
      }
		$rc = $stmt->rowCount();
      if ($rc == 0){echo "Nothing Found.";}
		else{
        		echo "Found " . $rc . BRNL;
        		
            echo "<form><table style='border-collapse:collapse;font-size:small;'>";

            echo "<tr>";
            foreach ($fields as $k){
                echo "<th>$k</th>";
            }
            echo "<th>-</th><th> - </th></tr>";
            echo "</tr>\n";

            while ($row = $stmt -> fetch()){
           # dmx\echor ($row);
            
                echo_user_row($row);
            }
            echo "</table></form>\n";

        }

}



?>
function showFinder(){
return <<<EOT
<hr>
<p><b>Locate a Member to update</b></p>
To modify a member's record (including accepting new signups) find the member
here. Name and email can be partials.
<form  method = 'POST'>
<table style = 'font-size:small;'>
<tr><th>Find by name: </th><th>Find by email:</th><th>Find by status:</th>
    <th>Email Status</th></tr>
<tr>

    <td> <input type='text' name = 'name' ></td>
    <td><input type='text' name='email'></td>
    <td><select type='text' name='status'><?=$status_options?></select></td>
   <td><select type='text' name='ems'><?=$ems_options?></select></td></tr>
     <tr><td>Admin Status:<input type="text" name="admin_status" size='4'></td></tr>
</table>
<input type=submit name='search' value='Search'>
</form>
EOT;
}

