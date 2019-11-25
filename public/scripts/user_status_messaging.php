<?php
#ini_set('display_errors', 1);
#ini_set('error_reporting', -1);


/*
	provides text for messages when user status is changed.
	$code is new status code
	modelled on email_status_messaging


	Everything is done inside the function to keep namespace
	separate
*/

require_once 'init.php';
	use digitalmx\MyPDO;
function add_user($id,$new_status){
        $pdo = MyPDO::instance();

  		//create next sequentioal user id
 		$sql = "SELECT MAX( user_id ) AS user_id FROM `members_f2`";
		 $max = $pdo -> query($sql)->fetchColumn();
		 $user_id = $max + 1; // New user_id = last used plus 1

	   $join_date = date("Y-m-d");

		$upw = randPW();


 		$q = "UPDATE `members_f2` SET
		status = '$new_status',
 		user_id = '$user_id',
 		join_date = '$join_date',
 		upw = '$upw',
 		no_bulk = 0
		where id = $id;";

#echo $q,"\n";
   		$result =$pdo -> prepare($q) -> execute();
    echo "Member at record $id created with user id $user_id and status $new_status.\n";

}


function update_user_status($id,$mstatus,$mode='sending'){
    // update silently if mode = Silent

    $pdo = MyPDO::instance();


    #updates status, sends emails
    if(empty($mstatus)){
        echo 'bad update status call',"empty status with id $id";
        return false;
    }

    if (!$row = get_member_by_id($id)){
	echo 'Get member_by_id called for non-existent user.',"Called with id $id code $code.";
	    return false;
	}


     if ($row['user_id'] == 0){
        add_user($id,$mstatus);
        $row =get_member_by_id ($id);
     }



        $sqla = array();
        #update the email status in the db.
            // also sets user status if second char on status
        $sqla[] = "status = '$mstatus'";

        $sqla[] = "email_last_validated = NOW()";

        if (!empty($sqla)){
            $sqlj = implode(',',$sqla);
            $sql = "UPDATE `members_f2`
            SET $sqlj
            WHERE id = '$id';";
            $result = $pdo -> query($sql);
        }

        $msg =  get_user_status_msg($mstatus,$row);
        $em_subj = $msg['subj'];
        $em_msg = $msg['msg'];
        if (!empty($em_subj) && ($mode == 'sending')){
                send_user($row['user_email'],$em_subj,$em_msg);
        }



	return $em_subj;
}



function get_user_status_msg($new_status,$row){

	#preset these variables

	$login = get_login_from_row($row);
	$name = $row['username'];
    $email = $row['user_email'];


	$profile_text = get_profile_message($row,'text');
#echo "<br>retreiving profile message: $profile_text<br><br>";

$bulk_warn = $row['no_bulk'] ?	"
	The FLAMEsite sends out an email whenever a new newsletter is
    published, typically once a week.  YOU ARE NOT CURRENTLY RECEIVING
    THIS.  If you'd like to keep informed about AMD alumni, go to your
    profile using the link below, and UNcheck the box 'No Email Updates'.
	"
	:
	'You are subscribed to the weekly news announcement, which will
	also contain your unique login to the site.';


$closing =  "

--
	Regards,
	AMD FLAME site administrator
	admin@amdflames.org

";


############################################

switch ($new_status){
    case 'M':
        $subj = "Welcome to AMD Flames...";
        $msg = <<<EOT
Welcome to the club $name!

    You are now registered as a Member on the
    AMD Alumni site AMDflames,org.

    This email contains your Personal Link (below)
    to access the FLAMEsite.  Please save it.

Your personal link to the FLAMEsite:
    https://amdflames.org/?s=$login

    Instead of a user id and password, this site uses
    a unique login for each user.  (Your password is the
    part after "s=", but you don't generally need that).

$bulk_warn

    We hope you will enjoy seeing what other current and
    former AMDers are up to, and hope you will contribute
    your own news and opinions.



$closing

EOT;
    break;

 case 'I':
        $subj = "Your AMD Flames membership is now Inactive";
        $msg = <<<EOT
 Dear $name:

    Per your request, your status on the AMD Flames Site
    has been set to "Inactive".

    This means that while you can still log in, you can
    only view the most recent newsletter.  You cannot
    search archives, graphics, or members.  You will not
    receive any further emails from the site.

    You information still remains on the site, and members can
    find it by searching, but you are marked as
    an inactive member.

    If you change your mind, contact the admin and your
    account can be restored to active.  As an active member,
    you can still set your profile to opt out of email updates.

    We are sorry to see you go.

$closing
EOT;
    break;

 case 'MC':
        $subj = "You are now and AMD Flames Contributor";
        $msg = <<<EOT
$name, your member status on the AMD Flames site has
	been upgraded to "Contributor".

	This means now have the ability to create news articles for the
	newsletter, as well as see and comment on articles that others have
	created that have not yet been published.

	There will be a new menu item in the left menu stack labeled
	"New/Pending Articles".  You can create a new article, including
	a link to some url and a graphic. You can also see unpublished articles
	others have created.  You can edit your own article as long as it hasn't
	been published yet.

	Your article will be reviewed by the admin before it is published,
	so don't worry about making a mistake.  Hopefully, the instructions
	on the page are clear; if not please let me know.

	You can also see what links people have clicked on in past newsletters
	and how many views each newsletter issue has received.

$bulk_warn

	As always, feel free to contact me with questions or issues.

	Your personal link to the FLAMEsite:
    https://amdflames.org/?s=$login

$closing

EOT;
    break;

 case 'G':
        $subj = "You are now a registered Guest on the AMD FLames site";
        $msg = <<<EOT

$name, you have been registered as a guest on the AMD Flames site.

	This means you can view newsletters and some other things, but do
	not have full capability of a member, such as searching for contact
	information for members.

	I hope you enjoy your access to the AMD Flames newsletters.

	Please feel free to contact me if there are any questions.

	Your personal link to the FLAMEsite:
    https://amdflames.org/?s=$login

$closing
EOT;
    break;

 case 'MA':
        $subj = "You are now an administer for AMD Flames Site";
        $msg = <<<EOT
$name, your AMD Flames Membership has been upgraded to Administrator.

This gives you all privileges on the site, including user management and
publishing newsletters.

In general, all activities can be conducted by using the web pages and forms
you have access to.  Some additional information is needed to
ftp to the site (graphics, for example), to edit scripts, and to
directly access the database using phpMyAdmin.


	Your personal link to the FLAMEsite:
    https://amdflames.org/?s=$login

$closing
EOT;
    break;


 default:
    $subj= '';
    $msg = '';

 }

return array(
    'subj'=> $subj,
    'msg' => $msg
    );

###################################


}
function get_profile_message($row,$type='html'){

        list($profile_days,$profile_date) = u\age_and_date ($row['profile_updated']);
        $login = get_login_from_row($row,'code'); #just the code
        $profile_url = SITE_URL . "/scripts/profile_update.php?s=$login";


	    $html =
			"<p>Your profile was lasted updated on $profile_date.
			If you'd like to update it, here's the link:<br>
			<span class='url'><a href='$profile_url'>Update Your Profile</a></span></p>"
			;
		$text =
			"
    Your profile was lasted updated on $profile_date.
    If you'd like to update it.  Here's the link:
        $profile_url
        "
			;
		return ($type=='html')?$html:$text;
 }

