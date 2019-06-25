<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

//BEGIN START
	require_once "init.php";
	if (f2_security_below(0)){exit;}
	
	use digitalmx\flames\Definitions as Defs;
	echo "From Defs: " . Defs::$stale_data_limit . BRNL;
	exit;
	
	
//END START
if (isset($_SESSION['pwid'])){ #user is logged in
    $my_id = $_SESSION['recid'];
	$sl = $_SESSION['level'];

	$username = $_SESSION['DB']['username'];
	$join_date = age( $_SESSION['DB']['join_date'])[1];

	$user_status = $_SESSION['DB']['status'];
	list ($profile_age,$last_profile) = age($_SESSION['DB']['profile_updated']);
    list ($profile_validated_age,$profile_validated_date) = age($_SESSION['DB']['profile_validated']);

}
else {
    $my_id=$sl=0;
    $username = "Nobody";
    $user_status = '';
}

 $news_latest = SITE_PATH . "/news/news_latest";

$nav = new navBar(false);
$navbar = $nav -> build_menu();


?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">

 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta http-equiv="X-UA-Compatible" content="IE=edge">

 <link rel="stylesheet" href="/css/flames2.css">

<script type='text/javascript' src="js/f2js.js"></script>
<script type='text/javascript'>
    function logmein (){
        var stext = document.getElementById('litext').value;
        var re = /(\w{5}\d{1,6})/;
        var reArray = re.exec(stext);
        if (! reArray){alert ("The password you entered is not valid");}
        var logincode = reArray[1];
        //alert ("Login code: "+logincode);
        if (logincode){
            var loginurl='/?s=' + logincode;
            alert ("Loggin in to "+loginurl);
            window.location.href = loginurl;
        }
        else{alert ("The password you entered is not valid");}
    }
</script>
<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "Organization",
  "url": "https://amdflames.org",
  "logo": "https://amdflames.org/graphics/logo-FLAMEs.gif"
}
</script>
<meta name="google-site-verification" content="VIIA7KGTqXjwzC6nZip4pvYXtFVLx7Th7VpWNGpWzpo" />
<title>Welcome to AMD FLAMES</title>
</head>
<body>


 <div style="color: #009900; font-family: helvetica,arial,sans-serif; font-size: 24pt; font-weight:bold; ">
	<div style="position:relative;float:left;vertical-align:bottom;margin-left:100px;">
		<div style=" float:left;"><img alt="" src="graphics/logo-FLAMEs.gif"></div>

		<div style= 'position:absolute; bottom:0;margin-left:100px;width:750px;'>FLAMES - The Official AMD Alumni Site
		</div>
	</div>
		<p style="font-size:14pt;clear:both;text-align:center;width:750px;margin-left:100px;">
		Keeping thousands of ex-AMDers connected since 1997<br>
	<span style="font-size:12pt;color:#030;font-style:italic;">AMD was probably the best place any of us ever worked.</span></p>

</div>


<?=$navbar ?>;
<!-- start of content -->





<?php

#get latest newsletter date

 $pub_date = 'Published '
    . get_latest_pub_date('conventional')
    .'. ';
 if (file_exists("$news_latest/breaking_at.txt")){
            $update_time = strtotime(file_get_contents("$news_latest/breaking_at.txt"));
                #file_get_contents("$news_latest/breaking_at.txt");

		    $pub_date .=  "(Breaking update at "
		        . date ('M d H:i T', $update_time)
		      # . $update_time
		        . '.)' ;
		        }

if (isset($_SESSION['pwid'])){
    if(in_array($user_status,$G_member_status_array) or $user_status == 'GA'){
		echo <<< EOT
		<div id='block1' style='border:1px solid #360;padding:5px;background-color:#efe;'>

		<h3>Welcome Back, $username</h3>

		<p>Flames Member since $join_date.  Profile last updated on $last_profile.</p>

		<p style="text-align:center"> <a href="/news/" target="_blank"><b>The latest FLAMEs Newsletter is HERE</b></a>.
		    <br> $pub_date
		</p>

		<p>Use the menus above to:</p>
		<ul>
		<li>Your Name: Update your Profile, so others can see what you're doing now, Log out, Get back to this page.
		<li>Site: Signup a new member, About this site, Help
		<li>Library: Current newsletter, index to old newsletters, photo galleries, and special one-off pages.
		<li>Search: Search in newsletters, members, or graphic library
		<li>Opportunties: Look for opportunities posted by members
		<li>Other menu items may appear for special purposes.
		</ul>

EOT;

		echo age_warnings($my_id);

    } #end if logged in

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

elseif ($user_status=='N'){
		echo <<< EOT
		<div id='block1' style='border:1px solid #360;padding:5px;background-color:#cfc;'>
		<h3>Welcome Back, $username</h3>
		<p>Thanks for signing up for FLAMEs.  You should received your permanent login soon.</p>
		<p>Until then, you can still <a href="/news/">view the latest newsletter</a>.</p>
		</div>
EOT;
		}

elseif ($user_status=='I'){
		echo <<< EOT
		<div id='block1' style='border:1px solid #360;padding:5px;background-color:#cfc;'>
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
	<div id='block1' style='border:1px solid #360;padding:5px;background-color:#cfc;'>
	<h3>Welcome AMD Alumni and Friends</h3>
	<p>This site is for former employees and associates of Advanced Micro Devices.</p>
	<p>You must access the site with your FLAMES-supplied link to enter the site. <br>
	If you are already a member and have lost your login link, retrieve it <a href="#logininfo">below</a>.<br>
	If you are not a member but would like to be, <a href="/scripts/signup.php"><b>Sign Up</a> here.</b></p>
	</div>
EOT;
}



?>
<div id='block5'><a name='logininfo'></a>
<hr>


<?php
$siteurl = SITE_URL;
if (!array_key_exists('level',$_SESSION) || $_SESSION['level']<1) {echo <<< EOT
<p>You must access the site with your FLAMES-supplied link to view the rest of the site.</p>
<form action="/scripts/send_lost_link.php" method="post">
<p>If you are member and need to retrieve your password, enter your email below: <br>Email: <input type="text" name="email" size="40"> <input type="submit" value="Send Login"><br>
(If your email has changed, please contact the admin using the link below.)</p>
<p>If you know your password (it's the 8-11 characters following 's=' in the link we frequently send out), you can enter it here to log in: <br>Login: s=<input type="password" name="stext" id="litext" > <input type=button value="Go" onclick="logmein();"></p>
<p>If all else fails, contact the admin: <a href="mailto:admin@amdflames.org" target="_blank">admin@amdflames.org</a>.</p>
</form>


EOT;
}

?>

</div>

<p style="text-align:center;clear:both"></p>


<?php
if (isset($_SESSION['pwid'])){echo "<p><small>user: $username S:$sl </small></p>";
//echo "<p>Current login: $_SESSION[username]; status: $_SESSION[status] ($_SESSION[type] on $_SESSION[status_updated]) seclev $_SESSION[level]</p>\n";
}


?>
</div>

<?php print file_get_contents ('includes/footer_scripts.js'); ?>

</body>
</html>

<?
function age_warnings ($id){
	global $G_ems_defs;
	if ($_SESSION['status'] == 'GA'){return;} #anonymous guest

	// set up all varioables
	$my_id = $id;
	//refresh the local datanbase
	#$row = get_member_by_id($my_id);

	$email_status = $_SESSION['DB']['email_status'];
	$email_status_time = $_SESSION['DB']['email_status_time'];
	$email_status_description = $G_ems_defs[$email_status];

	list ($profile_age,$last_profile) = age( $_SESSION['DB']['profile_updated']);
    list ($email_age,$last_verify) = age ( $_SESSION['DB']['email_last_validated']);
    list ($profile_validated_age,$profile_last_validated) = age ($_SESSION['DB']['profile_validated']);


	$G_stale_data_limit = Defs::$stale_date_limit;

	$G_member_status_array = Defs::getMemberInList();


	$user_status = $_SESSION['DB']['status'];
	$user_email = 	 $_SESSION['DB']['user_email'];
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
			&& (in_array($user_status,$G_member_status_array))
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

		if ( ($profile_validated_age>$G_stale_data_limit) ){ $update_scratch .= <<< EOT

		<p>Your profile has not been validated since $profile_last_validated.  Please look it over at <a href="/scripts/profile_update.php">edit profile</a>.  You can update it or just verify that it's current.  </p>
EOT;
		}
	// check email age
		if (0 or ($email_age>$G_stale_data_limit)){ $update_scratch .= <<< EOT

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

// check to see if came from old site
function from_old_site(){
return '';
}


?>
