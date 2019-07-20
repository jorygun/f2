<?php
 ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

//BEGIN START
	require_once "init.php";
	if (f2_security_below(0)){exit;}
	
use digitalmx\MyPDO;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;
use digitalmx\flames as f;

// 	$page = new DocPage();
	$title = 'AMD Flames';
	echo $page -> startHead($title,['ajax']);
	echo "<meta name='google-site-verification' content='VIIA7KGTqXjwzC6nZip4pvYXtFVLx7Th7VpWNGpWzpo' />";
	
	echo $page -> startBody($title,3);
#u\echor($_SESSION, 'Session'); 
//END START


 //  $my_id = $_SESSION['login']['user_id'];
	$news_latest = SITE_PATH . "/news/news_latest";

	$username = $_SESSION['login']['username'];
	$user_level = $_SESSION['level'];
	$user_status = $_SESSION['login']['status'];
	$breaking = '';
	$notice = '';

#set breaking news
 
 if (file_exists("$news_latest/breaking_at.txt")){
            $update_time = strtotime(file_get_contents("$news_latest/breaking_at.txt"));
               
		    $pub_date .=  "(Breaking update at "
		        . date ('M d H:i T', $update_time)
		      # . $update_time
		        . '.)' ;
		        $breaking = 
			"<div id='block2' style='border:0px solid black; padding:5px;' >"
			. file_get_contents("$news_latest/breaking_at.txt")
			.	"</div>";

}
#set notice 
 if (file_exists("index_notice.html")){
		        $notice = 
			"<div id='block2' style='border:0px solid black; padding:5px;' >"
			. file_get_contents('index_notice.html')
			.	"</div>";

}


# for logged in users
if ($_SESSION['level'] > 0){
    	$last_profile = $_SESSION['login']['profile_date'];
		$profile_age = $_SESSION['login']['profile_age'];
   	$join_date =  $_SESSION['login']['join_date'];
		$user_current = $_SESSION['login']['user_current'];
		$email_status = $_SESSION['login']['email_status'];
		
 		$news_latest = SITE_PATH . "/news/news_latest";
		$pub_date =  get_latest_pub_date('conventional');
		
	
		echo <<< EOT
		<div style='border:1px solid #360;padding:5px;background-color:#efe;'>

		<h3>Welcome Back, $username</h3>

		<p style="text-align:center"> <a href="/news/" target="_blank"><b>The latest FLAMEs Newsletter is HERE</b></a>.
		    <br> $pub_date
		</p>

		<p>Use the menus above to update your profile, view old newsletters and photo galleries, search for members.
		</p>
		<h3>Your current information:</h3>
		<ul>
		<li>Flames Member since $join_date.  
		<li>You are currently located in $user_current
		<li>Your profile was last updated on $last_profile.
		
EOT;
	if  (1 or $email_status<>'Y' and $email_status <>'Q') {
			echo email_warning($_SESSION['login']);
		}
		echo "</ul></div>
		";
		
		echo this_newsletter($news_latest);
		
}

elseif ($user_status=='N'){
		echo <<< EOT
		<div  style='border:1px solid #360;padding:5px;background-color:#cfc;'>
		<h3>Welcome, $username</h3>
		<p>Thanks for signing up for FLAMEs.  You should received your permanent login soon.</p>
		<p>Until then, you can still <a href="/news/">view the latest newsletter</a>.</p>
		</div>
EOT;
		}

elseif ($user_status=='I'){
		echo <<< EOT
		<div style='border:1px solid #360;padding:5px;background-color:#cfc;'>
		<h3>Welcome Back, $username</h3>
		<p>You have requested an "Inactive" status, which limits the
		information you can retrieve from the site.</p>
		<p>If you would like to restore your membership,
		please <a href="mailto:admin@amdflames.org">contact the admin</a> and have your status reset.  You can still opt
		out of any regular emails from the site.
		</p>
		</div>
EOT;
		}

else {
	echo <<< EOT
	<div style='border:1px solid #360;padding:5px;background-color:#cfc;'>
	<h3>Welcome AMD Alumni and Friends</h3>
	<p>This site is for former employees and associates of Advanced Micro Devices.</p>
	<p>You must access the site with your FLAMES-supplied link to enter the site. <br>
	If you are already a member and have lost your login link, retrieve it <a href="#logininfo">below</a>.<br>
	If you are not a member but would like to be,choose the sign-up option under the menu above.</p>
	</div>
EOT;
}



$siteurl = SITE_URL;
if (!array_key_exists('level',$_SESSION) || $_SESSION['level']<1) {
	echo <<< EOT
<p>You must access the site with your FLAMES-supplied link to view the rest of the site.</p>

EOT;
}

echo <<<EOT

</div>
<p style='text-align:center;clear:both'></p>
</div>
</body></html>
EOT;

exit;

###########################
function this_newsletter($news_latest){
		$t =  "<div><h3>In This Week's Newsletter:</h3>";

		if (file_exists("$news_latest/updates.txt")){
		    $t .= thtml(file_get_contents ("$news_latest/updates.txt"));
		    }
		if (file_exists("$news_latest/calendar.txt")){
		   $t .= thtml(file_get_contents("$news_latest/calendar.txt"));
		    }
		if (file_exists("$news_latest/headlines.txt")){
		    $t .= nl2br (file_get_contents("$news_latest/headlines.txt"));
		}
		$t .= "</div>";
	return $t;
}

	function email_warning ($data) {
		$validateEmailButton = f\actionButton('Validate Email','verifyEmail',$data['user_id']);
			$t = "<li><span class='red'>There is a problem with your email: " . $data['user_email'] . "</span>";
			$t .= "<br>Current status is: " 
				. Defs::getEmsName($data['email_status'])
			   . ", set on " . date('M d, Y',strtotime($data['email_status_time']))
			   . ", and we've sent emails to you
				that have not been responded to yet.";

			$t .= "<br>If your email has changed, please update it in your profile.  If it's right, just "
			. $validateEmailButton
			. "to validate it or respond to one of the emails we've sent you.
			";

	return $t;
	}
	
	
function age_warnings (){
	
	

	// set up all varioables
	
	//refresh the local datanbase
	#$row = get_member_by_id($my_id);

	$email_status = $_SESSION['login']['email_status'];
	$email_status_time = $_SESSION['login']['email_status_time'];
	$email_status_description = Defs::getEmsName($email_status);

	list ($profile_age,$last_profile) = age( $_SESSION['login']['profile_updated']);
    list ($email_age,$last_verify) = age ( $_SESSION['login']['email_last_validated']);
    list ($profile_validated_age,$profile_last_validated) = age ($_SESSION['login']['profile_validated']);




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

