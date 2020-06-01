<?php
namespace DigitalMx\Flames;

// ini_set('display_errors', 1);

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\MyPDO;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception ;


/**
    * Comment class manages comments on assets or articles.
    *
    * retrieve the comments for a particular asset or article,
    * or all the comments from an individual.
    *
    * Comment object is created for the logged in user and for a specific
    * database (news_items or assets or some non-db tag).
    *

*/


 /**
    * Comment class needs to be able to add a comment,
    * retrieve all the comments for a particular asset or article,

    * Comment object is created for the logged in user
    * @package  FLames
   * @author   john springer <john@DigitalMx.com>
    */

class Comment
{
    private  $user_id;
    private  $user_email;
    private  $username;
    private $user_level;

    private $on_db;
    private $on_id;

    private $commenter_list=[]; #list of info on each commenter
	private $no_email_list = []; #array of uids excluded from email

	private  $pdo;
	private $member;
    private $clist;



/* this definition is here so that the calls to
	this function aren't dependent on the table names
	in the db.
*/

   private static $db_names = array(
        'article' => 'news_items',
        'spec'=>'spec_items',

    );

	private $mailer;
/**
 * Sets up object for a specific user and article
  on_db is name of database (defined in db_names array)
        on_id is the item id of the entry in the database
        ucomment is the comment text

		single is true/false to control if user can add multiple
		comments or if a new comment replaces an old one.

        on_db is only used to make an entry in comments database;
        it does not actually access the database. Therefore,
        it can be a special name like 'spec' instead of a db table.

        item_id must be numeric so if on_db is spec, item id needs to be
        a number and unique to spec.  This is done with the spec_items
        db table, that translates the id into a url for the page.

        mailto is array listing who gets copied on an email when someone
        posts a comment:
        'commenters' all previous commenters
        'contributor' the person identified as contributor on the article
        'admin' admin@amdflames.org
        'editor' editor@amdflames.org
        any number of user_ids for specific members
        It can be empty, which means no-one gets and email.


 */
    public function __construct($container)
    {
        $ucom = null;

        $this->pdo  = $container['pdo'];
        $this->member = $container['member'];
			$this->mailer = new PHPMailer();



    }


    public function addComment($post, $params)
    {
    /* the post array must contain on_id and on_db and user_id */
    //u\echor($post); exit;
		$this->params = $params;

        if ($params['user_id'] == 0){echo "error: cannot post if not logged in";} #not logged in; can't post
        $on_db = self::$db_names[$params['on_db']];
        $on_id = $params['on_id'];
			$user_id = $params['user_id'];

        $ucomment = $post['comment'];
        $asset_list = $post['asset_list'] ?: '0';
        $aid = $asset_list;

        if (! empty($aid)){
        		if (! u\isInteger($aid)  ) {
        				u\echoAlert ("Invalid Asset Requested");
        				u\goBack();
        	 	}

        		$sql = "SELECT count(*) FROM `assets2` WHERE id ='$aid' ";
        		if (! $this->pdo->query($sql)->fetchColumn()) {
        			u\echoAlert ("No such asset");
        			u\goBack();
        		}
	}
        $no_email = (isset($post['no_email'])) ? 1:0;

    // use this sql to insert a new comment into the db
         $sql_insert = "INSERT INTO comments SET
            on_db = '$on_db',
            item_id = '$on_id',
            user_id = '$user_id',
            comment = ?,
            no_email= $no_email,
            cdate = NOW(),
            asset_list = '$asset_list'
            ;
            ";

        // use this sql to update a user's existing comment (where they
        // only get one comment.

        $sql_update = "UPDATE comments SET
            comment=?,
            cdate = NOW(),
            asset_list='$asset_list',
            WHERE on_db = '$on_db' and
            item_id = '$on_id' and
            user_id = '$user_id'
            ;";

    // if called as single, first see if there is already a comment by this user
    	$stmt_count = 0;
		if ($params['single']){
			$sql_count = "SELECT count(*) from comments
				WHERE user_id = '$user_id'
				AND on_db = '$on_db' AND item_id = '$on_id' ";
			$stmt_count = $this->pdo->query($sql_count)->fetchColumn();
		}
		if ($stmt_count > 0){ #single and already commented
			echo "<script>alert('Your previous comment has been replaced.');</script>";
			$stmt_update = $this->pdo->prepare($sql_update);
			$stmt_update->execute([$ucomment]);
			$comment_action = 'Update';
		} else {
			// insert new comment
	//echo $sql_insert . BRNL;
		  $stmt_insert = $this->pdo->prepare($sql_insert);
			$stmt_insert -> execute([$ucomment]);
			$comment_action = 'Insert';
			$inserted_rows = $stmt_insert->rowCount();
		}
	// prepare to mail all the involved parties
		$carray = $this->getComments($params); #to buildd the list of comments

		if (!empty($params['mailto'])) {$this->sendEmails($ucomment, $params); }
		else {echo "No recipeints" . BRNL;}
        // rerun the recent.php to generate new comment count display
        //include_once(SITE_PATH . "/scripts/recent.php");
    }

private function sendEmails ($ucomment, $params)
    {

    list($title,$cid) = $this->getArticleInfo($params['on_db'],$params['on_id']);
    $mailto = $params['mailto'];
    if (in_array('commenters',$mailto)){
		 $recipients = $this->commenter_list;
		 $recipient_names = $this->commenter_names;
		 // name, userid, email, login,
		 $mailto = u\array_filter_remove($mailto,'commenters');
	 }
	 if (in_array('contributor',$mailto)){
		 // add in the article contributor
		 if (!empty($ma = $this->member->getActiveBasic($cid) )){
		 	$recipients[$cid] = $ma;
		 	$recipient_names[] = $recipients[$cid][0];

		 }
    	 $mailto = u\array_filter_remove($mailto,'contributor');
    }
    if (in_array('admin',$mailto)){
		 // add in the article contributor
		 $recipients[Defs::$admin_id] = $this->member->getMemberBasic(Defs::$admin_id);
		 $recipient_names[] = 'Flames admin';
    	 $mailto = u\array_filter_remove($mailto,'admin');
    }
    if (in_array('editor',$mailto)){
		 // add in the article contributor
		 $recipients[Defs::$editor_id] = $this->member->getActiveBasic(Defs::$editor_id);
		 $recipient_names[] = 'Flames Editor';
    	 $mailto = u\array_filter_remove($mailto,'editor');
    }
    // add in specials
    if (!empty($mailto)){
    	foreach ($mailto as $id){
    		if (!is_numeric($id)){
    			throw new Exception ("Undefined entry in mailto list: $id");
    		}
    		if (!$recipients[$id] = $this->member->getActiveBasic($id) ) {
    			throw new Exception ("Unknown recipient in mailto list: $id");
    		}
    		 $recipient_names[] = $recipients[$id][0];
    	}
    }

   $recipient_names = array_unique($recipient_names);


    // remove anone on no_email list
    if (!empty($this->no_email_list)){
    	foreach ($this->no_email_list as $uid){
    		$cname = $recipients[$uid][0];
    		unset ($recipients[$uid]);
			$recipient_names = u\array_filter_remove($recipient_names,$cname);

    	 }
    }
    	//u\echor($this->commenter_names,'names');
    //	u\echor($recipients,'recips');

    	 $cclist = implode(', ',$recipient_names);
    	  $commenter_email = $_SESSION['login']['user_email'];
    		$commenter_name = $_SESSION['login']['username'];

		$this->mailer->Subject = "$commenter_name has commented on a FLAMES story";
		$this->mailer->setFrom ($commenter_email,$commenter_name);
		$this->mailer->addCustomHeader('Errors-to','postmaster@amdflames.org');
		$this->mailer->CharSet = 'UTF-8';
		$this->mailer->isSendmail();
		$this->mailer->isHTML(TRUE);

    	foreach (array_values($recipients) as  $r){
    		list ($name,$uid,$email,$login,$level) = $r;
    		if (empty($email)) continue;
    		if ( SITE == Defs::$local_site ) {
    			if ( ! in_array($email,Defs::$safe_emails) ) {
    				continue;
    			} else {
    				echo "Mailing from local site to $name ($uid) at $email" . BRNL;
    			}
    		}
    		#echo "mailer: $name at $email" . BRNL;

    		// set up the message

   		$elink =  SITE_URL
   			. "/get-article.php?id=${params['on_id']}&m=d&s=$login";
    		// different for spec items!

    		$emsg = $this->formMessage($name,$title,$commenter_name, $elink,$cclist,$ucomment);
    		// block sending of emails from the local dev site, unless on safe list


				$this->mailer->addAddress($email);
				$this->mailer->Body = $emsg;
				$this->mailer->send();
				$this->mailer->clearAddresses();

   }

 }

 private function formMessage($name,$title,$commenter_name, $elink,$cclist,$ucomment) {
	$msg = <<<EOT
<p>Greetings $name,<br><br>
A comment has been added by $commenter_name to this
    Flames item you suggested or previously commented on:</p>

<h4>$title</h4>
$ucomment

<hr>
<ul>
<li> To POST a reply on the website that emails other commenters, click this link:
    $elink<br>

<li> To email a PRIVATE reply only to $commenter_name, just reply to this email.
  (You should remove the personal login above from the reply.)
</ul>
<p>This email was sent to $cclist.</p>

EOT;
	return $msg;
}


 private function buildCommenters($carray){
 	// creates list of contact info for commenters and also
 	// list of people who have excluded from email.

 	$clist = []; $cnames = []; $no_mail=array();;
 	foreach ($carray as $row){
 		$uid = $row['user_id'];
 		if (empty($clist[$uid])){
 			$ma = $this->member->getActiveBasic($uid);
 			if (!empty($ma)){
 				$clist[$uid] = $ma;
 				$cnames[] = $clist[$uid][0];
 			}
 			// username,uid,useremail,login,seclevel

 		}
 		if ($row['no_email'] == 1){$no_mail[$uid]  = 1;}
 	}
 	$this->no_email_list = array_keys($no_mail) ;
 	$this->commenter_list = $clist;
 	$this->commenter_names = $cnames;

 	// u\echor($this->commenter_list,'com list');
 	// u\echor($this->no_email_list,'nomail list');
 	//	u\echor($this->commenter_names,'name list');
 }
	public function getComments($params){
	// retrieves all comments into a list.
	$this->params = $params;
		$on_db =  self::$db_names[$params['on_db']] ?? $params['on_db'];
		$on_id = $params['on_id'];

		 $sql = "
            SELECT c.id, c.user_id,c.comment,c.on_db,c.item_id,c.no_email,c.asset_list,
            DATE_FORMAT(c.cdate,'%e %b %Y' ) as pdate,
            u.username,u.user_email,u.user_from
            FROM `comments` c
            JOIN `members_f2` u  on c.user_id = u.user_id
            WHERE c.on_db = '$on_db' AND c.item_id = '$on_id'
            ORDER BY c.cdate;
            ";

         $carray = $this->pdo->query($sql)->fetchAll();
        # u\echor ($carray, $sql); exit;

			$this->buildCommenters($carray); // builds commenters, emails, no-email list


        return $carray;

	}

private function getArticleInfo($on_db,$on_id)
    {
        #echo "Getting article data $dbtable, $item_id<br>";

        #if ($dbtable == 'spec_items'){return "Special web page $item_id";}
        $on_db = self::$db_names[$on_db] ?? $on_db;
        $sql = "Select title,contributor_id from `$on_db` where id = '$on_id'";
        // url is used for spec items, where it is the page name in /spec_items
        if (! $ainfo  = $this->pdo->query($sql)->fetch() ) {
        	die ("Failed to ftch on $sql");
        }
        return [$ainfo['title'],$ainfo['contributor_id']]; # [title,cid]

    }




}
