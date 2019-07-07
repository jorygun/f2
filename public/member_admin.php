<?php
namespace digitalmx\flames;

ini_set('display_errors', 1);
ini_set('error_reporting', -1);
//BEGIN START
	require_once 'init.php';
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\MemberAdmin;
	
	$page = new DocPage();
	$admin = new MemberAdmin();
	
	
//END START

     echo $page->getHead('Member Admin');
 	echo $page ->startBody("Search for Member");


//END START

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



