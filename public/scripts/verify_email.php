<?
/*   verify script receives email and get session data from user login  from link on verify email:
		 $GV[siteurl]/scripts/verify_email.php?s=$login&m=$uemenc [ = rawurlencode($user_email) ]

*/


//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	#if (f2_security_below(0)){exit;}
//END START
$error_msg = "No record found. Please contact administrator at admin@digitalmx.com";


 ?>
 <!DOCTYPE html>
<html lang="en">
<head>

<title>FlameSite Email Verification</title>
</head>
<body onblur='self.close();'>

<?php
	
	if ($ident = $_GET['s']){
  		if (is_valid_login($ident)){$row = get_row_for_login($ident);}
  	}
	elseif ($ident = $_GET['r']) {
		if (is_valid_id($ident)){$row = get_member_by_id($ident);}
	}
	elseif ($ident = $_GET['u'] ){
		if (is_valid_uid($ident)){$row = get_member_by_uid($ident);}
	}
	else {die ("Cannot determine user id.");}
	
	if (empty($row)){ die ("User not found.");}
	
	$sql_today = sql_now('date');
	
	$member_type = get_member_description($row['status']);

    list ($join_days,$join_date) = age ($row['join_date']);

    if ($row['status'] == 'N'){send_new_user($row);}
    if ($row['status'] == 'D'){send_lost_user($row);}

    if (in_array($row['email_status'],array('A4','LB','LA','LE','E2'))){
        send_lost_user($row);
    }

    
        #quickly exit
        echo "<p>Thanks for verifying your email.</p>";
   




	#always
    set_email_status($row['id'],'Y');
    if ($_SESSION['DB']['id'] == $row['id']){
        $_SESSION['DB'] = get_member_by_id($row['id']);
    }



echo "</body></html>";
exit;

###############################


function send_new_user($row){
    $login = get_login_from_row($row,'link');
    echo "<p>Thanks $row[username], for confirming your email address.</p>
    <p>Your signup will now be forwarded to an admin for approval and assignment of a permanent login.</p>
    ";

    $adm_sub = "New signup verified: $row[username]";
    $adm_msg = "New user $row[username] has verified their email and is awaiting your approval.
        At AMD: $row[user_amd]
        Admin Note: $row[admin_note]

        https://amdflames.org/scripts/update_member.php?id=${row['id']}
        ";
    send_admin($adm_sub,$adm_msg);
    return ;
}


function profile_message($row){
        list($profile_days,$profile_date) = age ($row[profile_updated]);
        $login = get_login_from_row($row);
	    $message =
			"<p>Your profile was lasted updated on $profile_date. If you'd like to update it.  Here's the link:<br>
			<span class='url'><a href='$GV[siteurl]/scripts/profile_update.php?s=$login' >Update Your Profile</a></span></p>"
			;
 }

 function send_lost_user($row){

    echo  "<p>Ooooh.  We thought you were lost! <br>
			Happy you've been found!</p>";

    $user = $row['username'];
    $adm_sub = "Happy verification: Lost user $user";
	 $adm_msg = "User $user has verified their email, but we thought they were lost (status: ${row['status']}, was ems: ${row['email_status']}).";
    send_admin($adm_sub,$adm_msg);
    $email = $row['user_email'];
    send_user($email,'Happy Days.  Welcome Back','
    	Thanks for verifying your email at AMD Flames.
    	We thought you were lost.');
    	
    


}
?>



