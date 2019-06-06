<?php
// ini_set('display_errors', 1);
 ini_set('error_reporting', E_ALL);
/**
    * Comment class manages comments on assets or articles.
    *
    * retrieve the comments for a particular asset or article,
    * or all the comments from an individual.
    *
    * Comment object is created for the logged in user and for a specific
    * database (news_items or assets or some non-db tag).
    *
    * @category
   * @package  FLames
   * @author   john springer <john@digitalmx.com>
   * @license  <>
   * @link
*/


 /**
    * Comment class needs to be able to add a comment,
    * retrieve all the comments for a particular asset or article,
    * or all the comments from an individual.

    * Comment object is created for the logged in user


    * @package  FLames
   * @author   john springer <john@digitalmx.com>
    */

class Comment
{
    public  $user_id;
    public  $user_email;
    public  $username;

    private $_on_db;
    public $dbn;
    public $item_id;
    public $ucomment;
    private static $_dbcon;

    public $clist;
    public $stmt, $sql,$item_title;


/* this definition is here so that the calls to
	this function aren't dependent on the table names
	in the db.  Also to prevent letting public POST command
	contain the db table name.
*/

   private static $db_names = array(
        'asset' => 'assets',
        'article' => 'news_items',
        'spec'=>'spec',
        'assets' => 'assets',
        'news_items' => 'news_items'
    );

/**
 * Sets up object for a specific user
 *
 * @param $user the user_id from the member database.  $_SESSION['user_id'];
 * @param PDO $dbcon  A valid PDO object
 * @return none
 */
    public function __construct($user_id)
    {
        $ucom = null;
        $this->user_id = $user_id;
        self::$_dbconn = MyPDO::instance();
        $sql = "SELECT username,user_email
        from members_f2
        where user_id = '$this->user_id';";
        $row = self::$_dbcon->query($sql)->fetch();
        $this->username = $row['username'];
        $this->user_email = $row['user_email'];

      //   echo "Class ",__CLASS__," instantiated for user
//             $this->username,
//             $this->user_email
//             <br>
//             \n";
    }

/**
    * addComment
        dbn is name of database (defined in db_names array)
        item_id is the id of the entry in the database
        ucomment is the comment text

		single is true/false to control if user can add multiple
		comments or if a new comment replaces an old one.

        dbn is only used to make an entry in comments database;
        it does not actually access the database. Therefore,
        it can be a special name like 'spec' instead of a db table.

        (dbn is needed in comments db though so the source db
        like news_items can also
        retrieve comments attached to an entry)

        item_id must be numeric so if dbn is spec, item id needs to be
        a number and unique to spec.

        mailto is either an empty array, or an array containing "all" or
        an array containg a list of  email addresses.  The comment will be
        sent to either none, all previous commentors, or specific emails.

        adds comment entry to comments database for this user
*/

    public function addComment($dbn,$item_id,$ucomment,$single,$mailto=array())
    {
         $on_db = self::nameToTable($dbn);


           //  $sql = "INSERT INTO comments SET
        //      _on_db = ?,
        //         item_id = ?,
        //         user_id = ?,
        //         comment = ?,
        //         date = NOW()
        //         ;
        //         ";

    // use this sql to gwt thw story title
        if($on_db != 'spec'){$item_title = $this->getTitle($on_db,$item_id);}
        else {$item_title = 'Special Item';}

      # echo "Item title: $sql_title";

    // use this sql to insert a new comment into the db
         $sql_insert = "INSERT INTO comments SET
            on_db = '$on_db',
            item_id = '$item_id',
            user_id = '$this->user_id',
            comment = ?,
            cdate = NOW()
            ;
            ";
    // use this sql to determine if there is already a comment in the db

        $sql_count = "SELECT count(*) from comments
            where user_id = '$this->user_id'
            AND on_db = '$on_db'
            AND item_id = '$item_id'

            ";

        // use this sql to update a user's existing comment (where they
        // only get one comment.

        $sql_update = "UPDATE comments SET
            comment=?,
            cdate = NOW()
            WHERE on_db = '$on_db' and
            item_id = '$item_id' and
            user_id = '$this->user_id'
            ;";

    // if called as single, first see if there is already a comment by this user
        if ($single){
            $stmt_count = self::$_dbcon->query($sql_count);
            $daycount = $stmt_count->fetchColumn();
        }

        if ($single && $daycount > 0){ #already commented

            echo "<script>alert('Your previous comment has been replaced.');</script>";

            $stmt_update = self::$_dbcon->prepare($sql_update);
            $stmt_update->execute([$ucomment]);
            $comment_action = 'Update';
        }
        else {
            // insert new comment

           $stmt_insert = self::$_dbcon->prepare($sql_insert);
            $stmt_insert -> execute([$ucomment]);
            $comment_action = 'Insert';
            $inserted_rows = $stmt_insert->rowCount();

    // increemnt the comment count for this item
        $sql = "UPDATE `$on_db` set comment_count = comment_count +1 where id = $item_id";
        $cntq = self::$_dbcon->prepare($sql) -> execute();

        }

       if (!empty($mailto)){
        self::_send_emails($dbn, $item_id,$ucomment,$mailto);
        // Sending emails to whoever, including all commenters is most of the code.
        }

        // rerun the recent.php to generate new comment count display
        include_once(SITEPATH . "/scripts/recent.php");
    }

private function _send_emails ($dbn, $item_id,$ucomment,$mailto)
    {
        // Sending emails to whoever, including all commenters is most of the code.
        $sendlist = array(); #email => [full address,name,login]
        $sendnames = array(); #list of names of recipients
         $on_db = self::nameToTable($dbn);
        if($on_db != 'spec'){$item_title = $this->getTitle($on_db,$item_id);}

               /* the mailto is an array of email addreess in any legal
               email format.  The keyword 'all' means to add all the
               people who've commented to date.

               The technique is to build a deduped list of commenters
               by userid with name, email, and login code.
               Then add in the other
               entries in the mailto using the requested email itself
               as the key.
               So you can step through the array, generate a login code if its a
               user_id key, and just use the email otherwise.

               The message contains the article title, the link to the
               discussion page (including login if its a commentor),
               the comment itself, and a list of the names of the commentors
               notified.

               */


        foreach ($mailto as $em){
            if (empty($em)){continue;} #oh wel
            if ($em == 'all'){continue;} #wait and do after the others.
            else{
                $emonly = self::_extract_email ($em);
                 $sendlist[$emonly] = [$em,'',''];
            }
        }

        if (in_array('all',$mailto))
        { #flag to include all commentors.
            #echo "start all";
            //  buld array of previous comment emails, removing dups
            //  and removing current user

          // returns array of all the comment data.
                $clist = $this->getCommentsForItem($dbn,$item_id);
                #print_r ($clist);

                #build a deduped array by keying of user_id.
                $uid_list = array();
                foreach($clist as $row){
                    $uid_list[$row['user_id']] = 1;
                }

                // get the current user's user_id (the one making the comment)
                // and remove it from the list
                $current_user_id = $_SESSION['DB']['user_id'];
                unset($uid_list [$current_user_id]); #ddon't send the commenter


                // now go through the list, getting the user
                // info from the members table
                 $liq = self::$_dbcon->prepare("SELECT username,upw,user_email FROM `members_f2` WHERE user_id = ? AND email_status not like 'L%' ");


                foreach (array_keys($uid_list) as $uid) {
                #echo "getting loginfo for uid $uid ";
					$liq -> execute ([$uid]);
					$cominfo = $liq-> fetch (PDO::FETCH_ASSOC);
					#echo "Rows: " . $liq->rowCount() . '<br>';
					#echo "<pre>", print_r($loginfo), "</pre>";
					  if ($cominfo){
					   #add the current name from the member file

					   $comuser = $cominfo['username'];
					   $comemail = $cominfo['user_email'];
					   $comlogin = $cominfo['upw'] . $uid;
					   $comaddr =  $cominfo['username']
                                . ' <'
                                . $cominfo['user_email']
                                .'>';

					   $sendlist[$comemail] = array (
					        $comaddr,
					        $comuser,
					        $comlogin
					    );

					    $sendnames[] = $comuser;

                        }
                }
                # echo '<pre>' . print_r ($sendlist) . '</pre>';
            }



#Now build the main message...

    $commenter_email = $_SESSION['user_email'];
    $commenter_name = $_SESSION['username'];
    $mailfrom = "AMD Flames Editor <editor@amdflames.org>";
    switch ($on_db){
        case 'news_items':
        $item_link = "http://amdflames.org/scripts/news_article_c.php?id=$item_id";
            break;
        case 'assets':
        $item_link = "http://amdflames.org/scripts/asset_c.php?id=$item_id";
            break;
        default:
        $item_link = '';
    }


    $cclist = "Article Contributor and these commentors: "
        . implode(', ',$sendnames);

    $mailfrom = "AMD Flames Editor <editor@amdflames.org>";

#Now go though the sendlist
    foreach (array_keys($sendlist) as $em){
        list($sendto,$xname,$xlogin) = $sendlist[$em];

        if(!empty($xlogin)){

            $link_add =  "&s=$xlogin
   (This link includes your personal login).
";
        }
        else {$link_add = "
   (You will need to log in before using this link.)
";      }

    $elink = $item_link . $link_add;
 $emsg = "A comment has been added by $commenter_name to this item
    you suggested or previously commented on:

$item_title
	$elink

-------------------------
$ucomment
--------------------------

This email was sent to $cclist.

" ;

             mail($sendto,"$commenter_name has commented on a FLAMES story",$emsg,"From:$mailfrom");
        }
     }

  public function getCommentsForItem($dbn,$item_id)
    {
        $this->_on_db = self::nameToTable($dbn);
        $sql="SELECT * FROM comments where on_db = '$this->_on_db'
            AND item_id = '$item_id'
            AND status is null
            ORDER BY cdate;";


        return self::_build_comment_array($sql);
    }

 public function getCommentsByItem($dbn,$item_id)
    {

        return self::_get_comment_array('',$dbn,$item_id);
    }

 public function getCommentsByUser($user_id,$dbn='')
    {
        /*make array of db names - either the one requested or all of them
        */


             $carray = self::_get_comment_array($user_id,$dbn);
            #$carray = array_merge($carray , self::_build_comment_array($sql));


        return $carray;

    }
    public function getTitle($dbtable,$item_id)
    {
        #echo "Getting title $dbtable, $item_id<br>";

        if ($dbtable == 'spec'){return "Special web page";}
        #$_on_db = self::nameToTable($dbn);
        $sql = "Select title from `$dbtable` where id = '$item_id'";
        $stmt = self::$_dbcon->query($sql);
        $title = stripslashes($stmt -> fetchColumn());
        return $title;

    }
    public function getCommentsByAge($age)
    {
        return self::_get_comment_array('','','',$age);
    }
    public function getContactFromUserid($user_id)
    {
        $st2 = self::$_dbcon->query(
            "Select username,user_email from members_f2 where user_id = '$user_id';"
        );
            $row = $st2->fetch();
            $cusername = $row['username'];
            $cuser_email = $row['user_email'];
            $user_contact
                = "<a href='mailto:$cuser_email'>$cusername</a>";
            return array($cusername,$cuser_email);

            #return $user_contact;
    }

    public static function nameToTable($dbn)
    {
        if ($on_db = self::$db_names[$dbn]) {
              return $on_db;
        }
        return '';
    }

    private function _extract_email ($text)
    {
        $text = trim($text);
        preg_match('/^(.\s+)?.*?([\w\.\-]+@[\w\.\-]+)/',$text,$m);
        $email = $m[2];
        $name = $m[1]; #anything in front of the email address
        return $email;
    }


     private function _get_comment_array($uid='',$dbn='',$item='',$age='')
    {
        $cset = array();
        $whereconditions = array();
        if (!empty($uid)) {$whereconditions[] ="c.user_id = '$uid'";}
        if (!empty($item)) {$whereconditions[] =  "c.item_id = '$item'";}
        if (!empty($age)) {$whereconditions[] = "c.cdate > now() - interval $age";}
        if ($dbn != '') {$whereconditions[] = "c.on_db = '" .
            self::nameToTable($dbn) . "'" ;}
        $whereclause = implode(' AND ',$whereconditions);

        $jsql = "
            SELECT c.id as cid, c.user_id,c.comment,c.cdate,c.on_db,c.item_id,
            u.username,u.user_email
            FROM `comments` c
            LEFT JOIN `members_f2` u  on c.user_id = u.user_id
            WHERE $whereclause
            ORDER BY c.cdate;
            ";
        #echo "sql: $jsql<br>";
        $stmt = self::$_dbcon->query($jsql);
        foreach ($stmt as $row) {

            $cdata = array(
                'dbn' => $dbn,
                'comment' => $row['comment'],
                'pdate' => gmdate('M d, Y H:i T', strtotime($row['cdate'])),
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'user_email' => $row['user_email'],
                'user_contact' => "<a href='mailto:${row['user_email']}'>${row['username']}</a>",
                'dbtable' => $row['on_db'],
                'item' => $row['item_id'],
                'title' => self::getTitle($row['on_db'],$row['item_id']),
                'cid' => $row['cid'],
                 'user_profile' => "<a href='/scripts/profile_view.php?uid=${row['user_id']}' target='profile'>${row['username']}</a>"
            );
            #echo $row['comment'],' ';
            $cset[] = $cdata;
        }

        return $cset;
    }

    private function _build_comment_array($sql)
    {
        $cset = array();
        $stmt = self::$_dbcon->query($sql);
        foreach ($stmt as $row) {
            list($username,$user_email) = $this->getContactFromUserid($row['user_id']);
            $cdata = array(
                'cid' => $row['id'],
                'db' => '',
                'comment' => $row['comment'],
                'pdate' => gmdate('M d, Y H:i T', strtotime($row['cdate'])),
                'user_id' => $row['user_id'],
                'username' => $username,
                'user_email' => $user_email,
                'user_contact' => "<a href='mailto:$user_email'>$username</a>",
                'user_profile' => "<a href='/scripts/profile_view.php?uid=${row['user_id']} target='profile'>$username}</a>"

            );
            #echo $row['comment'],' ';
            $cset[] = $cdata;
        }

        return $cset;
    }

}
