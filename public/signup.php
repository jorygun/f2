<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;



if ($login->checkLogin(0)){
   $page_title = 'AMD Flames Signup';
	$page_options=['ajax']; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START



if ($_SERVER['REQUEST_METHOD'] == 'GET'): ?>
	<p >
		Membership is open to former and current AMD employees, contractors,
	   and representatives.<br>
	<br>
	  The information you enter here is sent to the site administrator for approval. <em>(This could take a day or two.)</em> Until then the information is not accessible to anybody.</p>
	<p>
	After your membership is validated, you will be able to update your profile and communicate with other FLAMEsite members. </p><br>




	<form  method="post"  >

		<p > <span class="input required">Yellow fields</span> are required information</p><br />
		<table>
		<tr><td colspan="2" class="instr">Enter your name (Firstname Lastname)</td></tr>
		<tr><td>Name</td><td><input  class="required" name="name" id="name" type="text" size="40" maxsize='50' /></td></tr>

		<tr><td colspan="2" class="instr">Enter your email address
		</td></tr>

		<tr><td>Email</td><td><input id='email' name="email" type="email" class="required" size="40"></td></tr>

	<tr><td colspan="2" class="instr">Enter your current location (city, state or country)
		</p></td></tr>

		<tr><td>Location</td><td><input name="location" type="text" class="required" size="40"></td></tr>


		<tr><td colspan="2" class="instr">Enter the most recent position(s) you held at AMD. Include the division, location and period: what, where, when. <br>
		e.g. <span class="example">Marketing Eng., PLD, Sunnyvale,
		1982-1988; Field Sales; Denver, 1988-1991<br />

		If you were with an AMD rep or had some other affiliation with AMD, please describe what, where, when. <br />
			</td></tr>

		<tr><td>AMD Affiliation </td>
		<td><input  class="required" size="90" id='affiliation' name="affiliation" type="text" ></td></tr>


		<tr><td colspan="2"><p class="instr">
			Finally, if you'd like to send a note to the admin, enter it here.
			</td></tr>
		<tr><td>Note to Admin</td><td>
			<textarea rows="8" cols="60" name='comment' ></textarea></td></tr>
		</table>

		<input name="Submit" value="Submit"  type="submit">
	</form>


<?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST') :

   // check data


		if (! $email =  filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		   echo "Email not valid.  <a href='/signup.php'>Try again</a>.";
		   exit;
		}

   // duplicat email
	$q = "SELECT username, joined from `members_f2` where user_email like '$email' ";

	 if ($result = $pdo->query($q)->fetchAll()) {
      $send_button = f\actionButton('Send Login','sendLogin',$email,'','resp');

	   	echo	"
<p>The email you entered &lt;$email&gt;
is already in the member database for one or more users: <br>
<table>
<tr><th>Name</th><th>Join Date</th></tr>
";
			foreach ($result as $row) {
			   $jd = u\make_date($row['joined']);
				echo "<tr><td>${row['username']}</td><td>$jd</td></tr>\n";
			}

			echo "</table>
<p>  If you see your self on this list, then click here to have the login
associated with this email sent to you. $send_button </p>
";
   }


//check for duplicate name

	$q = "SELECT username,user_id,joined,user_from,status from `members_f2` where username like '${_POST['name']}' ;"; # looking for exact match, not similar

	if ( $result = $pdo->query($q)->fetchAll() ){

      echo <<<EOT
      <p>I found one or more members with similar names.  If one
      of these is you, click the 'Send Login' button, to have the login
      emailed to that user.  If you aren't sure, just send a note to
      the admin saying you may already be in the database.  </p>
      <table>
      <tr><th>Name</th><th>Joined</th><th>From</th><th>Status</th><th>Send Login</th></tr>
EOT;
      foreach ($result as $row) {
         $status = Defs::getMemberDescription($row['status']);
         $joined = u\make_date($row['joined']);
         $logbutton = f\actionButton('Send Login','sendLogin',$row['user_id'],'','resp');

         echo "<tr><td>${row['username']}</td><td>$joined</td><td>${row['user_from']}
         </td><td>$status</td><td>$logbutton</td></tr>
         ";
      }
      echo "</table>\n";

   }


echo "<p>Your new signup data has been entered.  If
all else fails, <a href='mailto:admin@amdflames.org'>contact the admin</a>.</p>
";


	 // SQLify the insert

// u\echor($_POST,'post before prep');
// exit;

	$source_ip = $_SERVER['REMOTE_ADDR'];
	$upd['source_message'] = sprintf("From %s at %s\n",$source_ip,date('Y-m-d H:i'));
   $upd['username'] = $_POST['name'];
   $upd['user_email'] = $email;
   $upd['user_from'] =  $_POST['location'];
   $upd['user_amd'] = $_POST['affiliation'];
   $upd['IP'] = $source_ip;
   $upd['comment'] = $_POST['comment'];
   $upd['status'] = 'U';

   $allowed_list = ['username','user_email','user_from','user_amd','IP','comment'];
   	$prep = u\pdoPrep($upd,$allowed_list,'');
 /**
 	$prep = u\pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
	  $sql = "INSERT INTO `signups`
	 ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
	   $stmt = $pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

      $message = file_get_contents(REPO_PATH . "/templates/N1.txt");
      $message = str_replace('::signup::',SITE_URL . "/action.php?S$new_id",$message);
       $message = str_replace('::name::',$upd['username'],$message);
      $header = "From: admin@amdflames.org\r\n";
      $header .= "CC: admin@amdflames.org\n\n";
      $to = $upd['user_email'];
      mail ($to,'Verify your AMD Flames Signup',$message,$header);



echo <<<EOT

	 <h3>Signup Submitted - Thank You</h3>
	 <p>You will receive an email in a few minutes confirming your registration.</p> <p>If you discovered you were already registered, ignore that email.  Otherwise, <b>you need to click the Verify Email link in that email within the next
	 3 days</b>, so we know the email got through to you.</p>

	 <p>A few days after you've verified your email, you will get a welcome message with your permanent login.</p>

</body></html>
EOT;
endif;
