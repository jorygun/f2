<?php
namespace digitalmx\flames;

//BEGIN START
	require_once "init.php";

	
	use digitalmx\flames\DocPage;
	use digitalmx\flames\Definitions as Defs;
	
	use digitalmx as u;
	

	$page = new DocPage;
	echo $page->startHead("AMD Flames", 0);
	echo "<meta name='google-site-verification' content='VIIA7KGTqXjwzC6nZip4pvYXtFVLx7Th7VpWNGpWzpo' />\n";
	echo $page->startBody("AMD Flames",0);

// END START

	
	
	
	
// END START
// if (isset($_SESSION['pwid'])){ #user is logged in
//     $my_id = $_SESSION['recid'];
// 	$sl = $_SESSION['level'];
// 
// 	$username = $_SESSION['DB']['username'];
// 	$join_date = age( $_SESSION['DB']['join_date'])[1];
// 
// 	$user_status = $_SESSION['DB']['status'];
// 	list ($profile_age,$last_profile) = age($_SESSION['DB']['profile_updated']);
//     list ($profile_validated_age,$profile_validated_date) = age($_SESSION['DB']['profile_validated']);
// 
// }
// else {
//     $my_id=$sl=0;
//     $username = "Nobody";
//     $user_status = '';
// }

 $news_latest = SITE_PATH . "/news/news_latest";


#get latest newsletter date

 $pub_date = 'Published '
    . get_latest_pub_date()
    .'. ';
 if (file_exists("$news_latest/breaking_at.txt")){
            $update_time = strtotime(file_get_contents("$news_latest/breaking_at.txt"));
                #file_get_contents("$news_latest/breaking_at.txt");

		    $pub_date .=  "(Breaking update at "
		        . date ('M d H:i T', $update_time)
		      # . $update_time
		        . '.)' ;
}

#u\echor ($_SESSION['login'], 'login');

 $username = $_SESSION['login']['username'];
if ($_SESSION['login']['user_id'] > 0){ #user is logged in
  
	$join_date = $_SESSION['login']['join_date'];
	 $user_status = $_SESSION['login']['status'];
	$last_profile = $_SESSION['login']['profile_date'];

		echo <<< EOT
		<div id='block1' style='border:1px solid #360;padding:5px;background-color:#efe;'>

		<h3>Welcome Back, $username</h3>

		<p>Flames Member since $join_date.  Profile last updated on $last_profile.</p>

		<p style="text-align:center"> <a href="/news/" target="_blank"><b>The latest FLAMEs Newsletter is HERE</b></a>.
		    <br> $pub_date
		</p>

		<p>Use the menus above to:</p>
		<ul>
		<li>Your Name: Update your Profile, so others can see what you're doing now, Help, About this Site, Log out.
		
		<li>Dig In: Current newsletter, index to old newsletters, photo galleries, and special one-off pages.
		<li>Search: Search in newsletters, search for a member, or search assets (pics and a/v).
		<li>Opportunties: Look for opportunities posted by members
		<li>Other menu items may appear for special purposes.
		</ul>

EOT;

#		echo age_warnings($my_id);

  

		echo
			"<div id='block2' style='border:0px solid black; padding:5px;' >",
			file_get_contents('index_notice.html'),
			"</div>";


		echo  "<div><h3>In This Week's Newsletter:</h3>";

		if (file_exists("$news_latest/updates.txt")){
		    echo thtml(file_get_contents ("$news_latest/updates.txt"));
		    }
		if (file_exists("$news_latest/calendar.txt")){
		    echo thtml(file_get_contents("$news_latest/calendar.txt"));
		    }
		if (file_exists("$news_latest/headlines.txt")){
		    echo nl2br (file_get_contents("$news_latest/headlines.txt"));
		}
		echo "</div>";

		echo "</div>";
}

// elseif ($user_status=='N'){
// 		echo <<< EOT
// 		<div id='block1' style='border:1px solid #360;padding:5px;background-color:#cfc;'>
// 		<h3>Welcome Back, $username</h3>
// 		<p>Thanks for signing up for FLAMEs.  You should received your permanent login soon.</p>
// 		<p>Until then, you can still <a href="/news/">view the latest newsletter</a>.</p>
// 		</div>
// EOT;
// 		}
// 
// elseif ($user_status=='I'){
// 		echo <<< EOT
// 		<div id='block1' style='border:1px solid #360;padding:5px;background-color:#cfc;'>
// 		<h3>Welcome Back, $username</h3>
// 		<p>You have requested an "Inactive" status, which limits the
// 		information you can retrieve from the site.</p>
// 		<p>If you would like to restore your membership,
// 		please <a href="mailto:admin@amdflames.org">contact the admin</a> and have your status reset.  You can still opt
// 		out of any regular emails from the site.
// 		</p>
// 		</div>
// EOT;
// 		}

else {
	echo <<< EOT
	<div id='block1' style='border:1px solid #360;padding:5px;background-color:#cfc;'>
	<h3>Welcome AMD Alumni and Friends</h3>
	<p>This site is for former employees and associates of Advanced Micro Devices.</p>
	<p>You must access the site with your FLAMES-supplied link to enter the site. <br>
	If you are already a member and have lost your login link, retrieve it <a href="#logininfo">below</a>.<br>
	If you are not a member but would like to be, <a href="/scripts/signup.php"><b>Sign Up</a> here.</b></p>
	</div>
EOT;
}






$siteurl = SITE_URL;
// if (!array_key_exists('level',$_SESSION) || $_SESSION['level']<1) {echo <<< EOT
// <p>You must access the site with your FLAMES-supplied link to view the rest of the site.</p>
// <form action="/scripts/send_lost_link.php" method="post">
// <p>If you are member and need to retrieve your password, enter your email below: <br>Email: <input type="text" name="email" size="40"> <input type="submit" value="Send Login"><br>
// (If your email has changed, please contact the admin using the link below.)</p>
// <p>If you know your password (it's the 8-11 characters following 's=' in the link we frequently send out), you can enter it here to log in: <br>Login: s=<input type="password" name="stext" id="litext" > <input type=button value="Go" onclick="logmein();"></p>
// <p>If all else fails, contact the admin: <a href="mailto:admin@amdflames.org" target="_blank">admin@amdflames.org</a>.</p>
// </form>
// 
// 
// EOT;
// }

echo "</div></body></html>\n";


############################
function age_warnings ($id){
	
	if ($_SESSION['login']['status'] == 'GA'){return;} #anonymous guest

	// set up all varioables
	$my_id = $id;
	//refresh the local datanbase
	#$row = get_member_by_id($my_id);

	$email_status = $_SESSION['DB']['email_status'];
	$email_status_time = $_SESSION['DB']['email_status_time'];
	$email_status_description = Defs::getEmsName($email_status);

	list ($profile_age,$last_profile) = age( $_SESSION['DB']['profile_updated']);
    list ($email_age,$last_verify) = age ( $_SESSION['DB']['email_last_validated']);
    list ($profile_validated_age,$profile_last_validated) = age ($_SESSION['DB']['profile_validated']);




	$user_status = $_SESSION['login']['status'];
	$user_email = 	 $_SESSION['login']['user_email'];
	$H_user_email = h("<$user_email>");
	$enc_user_email = rawurlencode($user_email);


	#echo print_r (array($email_val_age,$last_val,$profile_age,$last_profile));
	$verify_click = verify_click_email($my_id,$user_email);
	$verify_profile_click = verify_click_profile ($my_id);

	#build scratch file to put results in.  Build update message to display resutls if there's anything in the scratch
		$update_scratch = $update_msg ="";

	// check email status

		if (1
			&& ($email_status<>'Y' and $email_status <>'Q')
			&& (in_array($user_status,Defs::getMemberInList()))
		){
			$update_scratch .= <<< EOT
			<p>There is a problem with your email $H_user_email.
				Current status is: $email_status_description set on $email_status_time, and we've sent emails to you
				that have not been responded to yet.

				If your email has changed, please update it in <a href="/scripts/profile_update.php"> your profile</a>.  If it's right, just $verify_click to validate it or respond to one
				of the emails we've sent you.</p>

EOT;

	}
	// check profile

		if ( ($profile_validated_age>Defs::$old_profile_limit) ){ $update_scratch .= <<< EOT

		<p>Your profile has not been validated since $profile_last_validated.  Please look it over at <a href="/scripts/profile_update.php">edit profile</a>.  You can update it or just verify that it's current.  </p>
EOT;
		}
	// check email age
		if (0 or ($email_age>Defs::$inactivity_limit)){ $update_scratch .= <<< EOT

		<p>Your email has not been verified since $last_verify.  Please look it over in your profile at <a href="/scripts/profile_update.php">edit profile</a>.  You can update it or just verify that it's current.  </p>
EOT;
		}

		if ($update_scratch){
		$update_msg = <<< EOT
		<div style="border:2px solid red;padding:5px;background-color:#fcc;">
		<p style='color:red'>Are Your Email and Profile Current?</p>
		$update_scratch
		</div>
EOT;
	}

		return $update_msg;
}



?>
