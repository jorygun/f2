<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(-1)){exit;}
//END START

use \digitalmx\flames\Definitions as Defs;
$pdo = MyPDO::instance();

$member_status_set = Defs::getMemberInSet();

echo <<<EOT


<!DOCTYPE html>
<html lang="en">
<head>
	
	 <link rel="stylesheet" href="/css/flames2.css">

	<script src="/js/f2js.js"></script>
    <script type = 'text/javascript'>
         function validate_signup(theForm) {
             var s = '';
             var v = '';
			v = document.getElementById('email').value;
 		  if (v.length < 5){s += " Email field incomplete. "; }
 	//		if (!v.match(/\@/)){s += "Email invalid. ");}
 	       else {if (!validateEmail(v)){s += "Email invalid. ";}
 	       }

			  v = document.getElementById('affiliation').value;
			if (v.length <5) {s += 'Affliation not filled in. ';}

 			  v = document.getElementById('name').value;
 			 if (v.length < 5){s += "Name field incomplete.";}

			  if (s != ''){alert (s); return false;}
 			 return true;
		}
	</script>
	<style type="text/css">


		</style>
		<title>FLAMESite Signup</title>
		<meta name="generator" content="BBEdit 11.0" />


</head>

<body >
EOT;

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
echo <<<EOT
	<h1><strong><span style="font-size: 36pt; color: green">FLAME<em>site</em></span></strong><span style="font-size: 26pt;color:black;">
			&nbsp; Membership Signup</span></h1>

	<p >
		Membership is open to former and current AMD employees, contractors,
	   and representatives.<br>
	<br>
	  The information you enter here is sent to the site administrator for approval. <em>(This could take a day or two.)</em> Until then the information is not accessible to anybody.</p>
	<p>
	After your membership is validated, you will be able to update your profile and communicate with other FLAMEsite members. </p><br>



	<!-- ~~~~~~~~~~~~- FORM ~~~~~~~~~~~~~~- -->

	<form  style="margin-left: 30pt" method="post" onsubmit='return validate_signup()'  >

		<p > <span class="input required">Yellow fields</span> are required information</p><br />
		<table>
		<tr><td colspan="2"><p class="instr"><hr>Enter your name (Firstname Lastname)</td></tr>
		<tr><td>Name</td><td><input  class="required" name="name" id="name" type="text" size="40" /></td></tr>



		<tr><td colspan="2"><p class="instr"><hr>Enter the email address you want to use to correspond with other FLAMEs
		</p></td></tr>

		<tr><td>Email</td><td><input id='email' name="email" type="text" class="required" size="40"></td></tr>

	<tr><td colspan="2"><p class="instr"><hr>Enter your current location (city, state or country)
		</p></td></tr>

		<tr><td>Location</td><td><input id='email' name="location" type="text" class="required" size="40"></td></tr>


		<tr><td colspan="2"><p class="instr">	<hr>Enter the most recent position(s) you held at AMD. Include the division, location and period.<br>
		e.g. <span class="example">Marketing Eng., PLD, Sunnyvale,
		1982-1988; Field Sales; Denver, 1988-1991<br />
		VP Sales, 1998-Present</span><br />

		If you were with an AMD rep indicate the Rep's name. <br />
		e.g. <span class="example">FSE; ElectraRep, Tampa, 1988-2002</span><br />
		If you have some other affiliation with AMD please describe. <br>
		e.g. <span class="example">Recuiter 2003 - Present</span><br></p>	</td></tr>
		<tr><td><p class="inpt">AMD Affiliation </td>
		<td><input  class="required" size="90" id='affiliation' name="affiliation" type="text" ></td></tr>


		<tr><td colspan="2"><p class="instr"><hr>
			Finally, if you'd like to send a note to the admin, enter it here.  Especially note
			if you think you're already a member but couldn't log in successfully.
			</p></td></tr>
		<tr><td>Note to Admin</td><td>
			<textarea rows="8" cols="60" name='admin_note' ></textarea></td></tr>
		</table>
		<hr>



		<p class="inpt">
		<input name="Submit" value="Submit"  type="submit">
		</p>
	</form>
EOT;

	
	} else  { # ($_SERVER['REQUEST_METHOD'] == 'POST')
		

		if (! filter_var($_POST ['email'], FILTER_VALIDATE_EMAIL)) {
		
  			echo "<p>Bad Address - ${_POST['email']} is not a valid email address.</p>";
 			 echo "<p><a href='${_SERVER['PHP_SELF']}'>Try again.</a></p>";
 			exit;
		}




	$q = "SELECT username,join_date,id,user_id,email_status,status from `members_f2` where user_email like '$_POST[email]' ";

	 $result = $pdo->query($q);

		$row_count = $result->rowCount();
		$obscure_names = array ();
		if ($row_count>0){
			foreach ($result as $row){
				$obscure_names[] = obscure_name($row['username']);
			}
			$urlemail = rawurlencode($_POST['email']);

			echo	"
<h3>Already here?</h3>
<p>The email you entered {_POST['email']}
is already in the member database for one or more users named: <br>
";

			foreach($obscure_names as $v){echo "$v, ";}
			echo "
</p>
<p>  Should I send the login link for those members to ${_POST['email']}?
Note: the same email can be used by more than one member, but each member
has their own profile and login.  If you are one of the people listed
above, then click to have the member logins associated with this email
address sent to you.
<a href='#' onclick=\"window.open('send_lost_link.php?email=$urlemail','lostlink','height=200,width=400,x=200,y=200');return false;\">Send existing logins to {_POST['email']}</a>
</p>
<p>If you are NOT one of the people named above, then you can
get your own member account at the same address.
Just look for your confirmation email, and click to verify that
it's you. </p>

<p>If you entered the wrong email, then you won't receive the
confirmation sent to that address.  Just sign up again, using your
correct current email address.</p>

<p>If all else fails, <a href='mailto:admin@amdflames.org' >contact the admin</a>.</p>
";

	}

//check for duplicate name
$name = $_POST['name'];
	$q = "SELECT username,join_date,id,user_id,email_status,status,user_email from 'members_f2' where username like '%$name%'  "; #basically looking for exact match, not similar

	 $result = $pdo->query($q);

		$row_count = $result->rowCount();
		foreach ($result as $row ){
			if ($row_count>0){
				$dup_found = TRUE;
				echo "<h3>Already signed up?</h3>
				<p>I found $row_count members named $SAFE[name]</p>\n";
				$this_email = $row[user_email];
				$urlemail=rawurlencode($this_email);
				$obscure_mail = obscure_email($this_email);

				if ($this_email){
					echo "
<p>The name ${_POST['name']} is already in the member database with what
appears to be a valid email
address like this: <code>$obscure_mail</code>.</p>
<p>If that is you and your email, should I send a login link to that
email address?
<a href='#' onclick=\"window.open('send_lost_link.php?email=$urlemail','lostlink','height=200,width=400,x=200,y=200');return false;\">Yes, send login link.</a>
</p>

<p>Your new signup data is still being sent along as well.  If
all else fails, <a href='mailto:admin@amdflames.org'>contact the admin</a>.</p>
";
				 break; #only do one record
				  }
			}
		}
		



	 // SQLify the insert
echo "Inserting new user" . BRNL;

	$upw = randPW(); #temporary password until data is confirmed
	$user_id = 0;
	$login = $upw . $user_id;
	$user_email	=	$_POST['email'];
	$uemenc = rawurlencode($user_email);

	$source_ip = $_SERVER['REMOTE_ADDR'];
	$source_message = sprintf("From %s at %s\n",$source_ip,date('Y-m-d H:i'));

	  $sql = <<< EOT
	INSERT INTO `members_f2` SET
	   user_id = $user_id,
	   upw = '$upw',
	   status = 'N',
	   status_updated = NOW(),
	   username = "$_POST[name]",
	   user_email = "$_POSt[email]",
	   user_from = "$_POST[location]",
	   email_status = 'N1',
	   user_amd = "$_POST[affiliation]",
	 
	   admin_note = "$source_message  $_POST[admin_note]"

	   ;
EOT;
echo "$sql" . BRNL;

  	$user_name = $_POST['username'];

	 $result = $pdo->query($sql);

	 // Get the ID for this Insert
	  
	 $id = $pdo->getLastInsertId();
	
echo "New id $id " . BRNL;


	$dup_notice = '';
	if ($dup_found){$dup_notice = "
	One or more possible duplicate entries were found during your signup.
	If you chose to have the login for one of them sent to you,
	and want to keep that instead of signing up new,
	just don't click the link on this email, and your new signup will be ignored.\n
	";
	}

	// Let user know
	 echo "<h3>Signup Submitted - Thank You</h3>
	 <p>You will receive an email in a few minutes confirming your registration.</p> <p><b>You need to click the Verify Email link in that email within the next
	 3 days, so we know the email got through to you.</b></p>


	 <p>$dup_notice</p>
	 <p>A few days after you've verified your email, you will get a welcome message with your permanent login.</p><br>";

	  print "<p><a href='$GLOBALS[siteurl]'>Return to main page</a></p>";




echo "Sending verify to $id";

	#send_verify($id,'N1');


}


