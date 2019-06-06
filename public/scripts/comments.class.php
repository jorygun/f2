<?php
// ini_set('display_errors', 1);
error_reporting ( E_ALL & ~E_NOTICE);
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
    private  $user_id;
    private  $user_email;
    private  $username;
    private $user_level;

    private $_on_db;
    private $dbn;
    private $item_id;
    private $ucomment;
    private static $_dbcon;

    private $clist;
    private $stmt, $sql,$item_title;


/* this definition is here so that the calls to
	this function aren't dependent on the table names
	in the db.  Also to prevent letting public POST command
	contain the db table name.
*/

   private static $db_names = array(
        'asset' => 'assets',
        'article' => 'news_items',
        'spec'=>'spec_items',
        'assets' => 'assets',
        'news_items' => 'news_items',
        'spec_items' => 'spec_items'
    );

/**
 * Sets up object for a specific user
 *
 * @param $user the user_id from the member database.  $_SESSION['user_id'];
 * @param PDO $dbcon  A valid PDO object
 * @return none
 */
    public function __construct($user_id=0)
    {
        $ucom = null;
        $this->user_id = $user_id;
        self::$_dbcon  = MyPDO::instance();
        /* moved the setting of username and email
            into an option so the class can be
            instantiated with being logged in.
            To retrieve comments, not to post
        */
        if ($user_id > 0){
        $sql = "SELECT username,user_email
        from members_f2
        where user_id = '$this->user_id';";
        $row = self::$_dbcon->query($sql)->fetch();
        $this->username = $row['username'];
        $this->user_email = $row['user_email'];
        $this->user_level = $_SESSION['level'];
        }
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
    public function getUserEmail(){
        return $this->user_email;
    }

    public function getUsername(){
        return $this->username;
    }
    public function addComment($dbn,$item_id,$ucomment,$single,$mailto=array(),$no_email=0)
    {
        if ($this->user_id == 0){echo "error: cannot post if not logged in";} #not logged in; can't post
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
        $articleRow = $this->_getArticle($on_db,$item_id);
        $item_title = $articleRow['title'];


      # echo "Item title: $sql_title";

    // use this sql to insert a new comment into the db
         $sql_insert = "INSERT INTO comments SET
            on_db = '$on_db',
            item_id = '$item_id',
            user_id = '$this->user_id',
            comment = ?,
            no_email= $no_email,
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
            user_id = '$this->user_id' and
            no_email = $no_email
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
        // Sending emails to whoever, including all commentors is most of the code.
        }

        // rerun the recent.php to generate new comment count display
        include_once(SITEPATH . "/scripts/recent.php");
    }

private function _send_emails ($dbn, $item_id,$ucomment,$mailto)
    {
        // Sending emails to whoever, including all commentors is most of the code.
        $sendlist = array(); #email => [full address,name,login]
        $sendnames = array(); #list of names of recipients
        $uid_list = array(); #list of uids to look up for sendlist
        $no_email_list=array(); #list of uids to exclude from emails

         $on_db = self::nameToTable($dbn);
         /* on_db = spec if special page in /spec_items
         These pages do not send out email notices to other commenters.
         */

        if(true ) { #$on_db != 'spec'){
           $articleRow =  $this->_getArticle($on_db,$item_id);
            $item_title = $articleRow['title'];
            $contributor_id = $articleRow['contributor_id'];
            $contributor_name = $articleRow['contributor_name'];
            #echo "Got articleRow $item_title, $contributor_id<br>";
        }

               /* the mailto is an array of email addreess in any legal
               email format.  The keyword 'all' means to add all the
               people who've commented to date.

               The technique is to build a deduped list of commentors
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

        if (empty($mailto)){return;}
        foreach ($mailto as $em){
            if (empty($em)){continue;}
            #find raw emails first; they may be replaced with more info
            #later if they are a contributor or commentor.

            if (substr($em,0,1) == '-' ){#put on exclusion list
             if ($emonly = self::_extract_email (substr($em,1))) {
                $excludelist[$emonly] = 1;
            }
            }

            elseif ($emonly = self::_extract_email ($em))
            {
                #echo "extracted $emonly<br>";
                $sendlist[$emonly] = [$em,'',''];
            }
        }

        if (in_array('all',$mailto) or in_array('commentors',$mailto))
        { #flag to include all commentors.
            //  buld array of previous comment emails, removing dups
            //  and removing current user

          // returns array of all the comment data.
            $clist = $this->getCommentsForItem($dbn,$item_id);
  #  mail ('admin@amdflames.org','Script debug 276', print_r($clist,true) );
        #remove user from the email copy list.  (Or add back in)
            foreach($clist as $row){
                $uid = $row['user_id'];
                $uid_list[] = $uid;
               if ($row['no_email'] == 1){ $no_email_list[]=$uid;}
               elseif (in_array($uid,$no_email_list)) {$no_email_list = array_diff($no_email_list, array($uid) );}

            }
                #echo "Commentor ${row['user_id']}<br>";

        }
#mail ('admin@amdflames.org','Script debug 283', print_r($uid_list,true) );
         if (in_array('all',$mailto) or in_array('contributor',$mailto))
        {
            if (!empty($contributor_id)){
            $uid_list[] = $contributor_id;
           # echo "Contriutor $contributor_id<br>";
            }
        }

        // dedup the list

        $uid_list = array_unique($uid_list);



         // get the current user's user_id (the one making the comment)
        // and add it to the  no-email list
        $current_user_id = $_SESSION['DB']['user_id'];
        $no_email_list[] = $current_user_id;

        $uid_list = array_diff($uid_list,$no_email_list);


        if (!empty($uid_list)){
 #       mail ('admin@amdflames.org','Script debug', print_r($uid_list,true) );

                // now go through the list, getting the user
                // info from the members table
                 $liq = self::$_dbcon->prepare("SELECT username,upw,user_email FROM `members_f2` WHERE user_id = ? AND email_status not like 'L%' ");


                foreach ($uid_list as $uid) {
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



                        }
                        if ($uid == $contributor_id){
                            $contributor_name = $comuser;
                        }
                        else {$sendnames[] = $comuser;} #don't include contributor in list of commentor names.
                }
                 #echo "Sendlist:\n<pre>" . print_r ($sendlist) . '</pre>';
            }



#Now build the main message...

    $commenter_email = $_SESSION['user_email'];
    $commenter_name = $_SESSION['username'];

    $mailfrom = "AMD Flames Editor <editor@amdflames.org>";
    switch ($on_db){
        case 'news_items':
        $item_link = "https://amdflames.org/scripts/news_article_c.php?id=$item_id";
            break;
        case 'assets':
        $item_link = "https://amdflames.org/scripts/asset_c.php?id=$item_id";
            break;
        case 'spec_items':
        $spec_url = $articleRow['url'];
        $item_link = "https://amdflames.org/spec/$spec_url";
            break;

        default:
        $item_link = '';
    }


    $cclist = "Article Contributor ($contributor_name) ";
    if (! empty($sendnames)){
        $cclist .= "and these commentors: ". implode(', ',$sendnames);
    }

// set the reply to address to etierh the commenter or to the
// responder email where it can be processed by a script.

    //$replyto = 'AMD Flames Responder <respond@amdflames.org>';
    $replyto = "$commenter_name <$commenter_email>";

#Now go though the sendlist
#mail ('admin@amdflames.org','Script debug', print_r($sendlist,true) );
    foreach (array_keys($sendlist) as $em){
        if ($excludelist[$em]){continue;} #skip this email
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
-------------------------
$ucomment
--------------------------

* To POST a reply on the website and email other commenters, click this link:
    $elink

* To email a PRIVATE reply only to $commenter_name, just reply to this email.
  (You should remove the personal login above from the reply.)

This email was sent to $cclist.

" ;

    mail($sendto,"$commenter_name has commented on a FLAMES story",$emsg,"From: $mailfrom\r\nReply-To: $replyto");
        }
     }

  public function getCommentsForItem($dbn,$item_id)
    {
        $this->_on_db = self::nameToTable($dbn);
        $sql="SELECT * FROM comments where on_db = '$this->_on_db'
            AND item_id = '$item_id' AND status is null

            ORDER BY cdate;";


        return self::_build_comment_array($sql);
    }

public function getCommentcount($dbn,$item_id)
    {
    $this->_on_db = self::nameToTable($dbn);
        $sql="SELECT count(*) FROM comments where on_db = '$this->_on_db'
            AND item_id = '$item_id' AND status is null
            ;";
        $count = self::$_dbcon->$query($sql)->fetchColumn();
        return $count;

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

private function _getArticle($dbtable,$item_id)
    {
        #echo "Getting article data $dbtable, $item_id<br>";

        #if ($dbtable == 'spec_items'){return "Special web page $item_id";}
        #$_on_db = self::nameToTable($dbn);
        $sql = "Select title,contributor,contributor_id,url from `$dbtable` where id = '$item_id'";
        // url is used for spec items, where it is the page name in /spec_items
        $stmt = self::$_dbcon->query($sql);
        if (!$stmt){die ("Failed to ftch on $sql");}
        $row = $stmt -> fetch();
        $row['title'] = stripslashes($row['title']);
        return $row;

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
        $whereconditions[] = "c.status is null";
        $whereclause = implode(' AND ',$whereconditions);

        $jsql = "
            SELECT c.id as cid, c.user_id,c.comment,c.cdate,c.on_db,c.item_id,
            u.username,u.user_email,u.user_from,u.user_amd
            FROM `comments` c
            LEFT JOIN `members_f2` u  on c.user_id = u.user_id
            WHERE $whereclause
            ORDER BY c.cdate;
            ";
    //    echo "sql: $jsql<br>";
        $stmt = self::$_dbcon->query($jsql);
        foreach ($stmt as $row) {
            $articleRow = self::_getArticle($row['on_db'],$row['item_id']);
            $cdata = array(
                'dbn' => $dbn,
                'comment' => $row['comment'],
                'pdate' => gmdate('M d, Y H:i T', strtotime($row['cdate'])),
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'user_email' => $row['user_email'],
                'user_contact' => "<a href='mailto:${row['user_email']}'>${row['username']}</a>",
                'user_about' => 'In ' . $row['user_from'] . '. ' . $row['user_amd'],
                'dbtable' => $row['on_db'],
                'item' => $row['item_id'],
                'title' => stripslashes($articleRow['title']),
                'contributor_id' => $articleRow['contributor_id'],
                'contributor_name' => $articleRow['contributor'],
                'cid' => $row['cid'],
                 'user_profile' => "<a href='/scripts/profile_view.php?uid=${row['user_id']}' target='profile'>${row['username']}</a>",
                 'user_amd' => $row['user_amd'],
                 'user_from' => $row['user_from']
            );
     //       echo "{$cdata['username']} ${cdata['user_id']} ${cdata['cid']} ";
           //  recho($cdata);
//             echo "getting cdata for ${row['username']} ";
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
                'user_contact' => "$username <a href='mailto:$user_email'>email</a>",
                'user_profile' => "<a href='/scripts/profile_view.php?uid=${row['user_id']} target='profile'>$username profile}</a>",
                'no_email' => $row['no_email']

            );
            #echo $row['comment'],' ';
            $cset[] = $cdata;
        }

        return $cset;
    }

    public function display_comments ($carray,$show_title,$limit=99) {
        if (empty($carray)){return '(no comments)';}

        #echo "carray:\n<pre>" . print_r($carray,true) . "</pre>\n";
        $clist =  "<div style='width:100%;background-color:#eee;padding:1em;border:1px solid #393;'>";
        $j=0;
        $num_comments = count($carray);
        $first_to_show = max(1,$num_comments - $limit + 1);

        foreach ($carray as $cdata){
            ++$j;
            if ($j<$first_to_show){continue;}

            $ucomment = nl2br($cdata['comment']);
            $ucomment = make_links($ucomment);

            $cid = $cdata['cid'];
            $pdate = $cdata['pdate'];
            $cuser_id = $cdata['user_id'];
            $dbtable = $cdata['dbtable'];
            $dbitem = $cdata['item'];
            $title = htmlentities($cdata['title']);
            if ($dbtable == 'news_items'){
                $dblink = "/scripts/news_article_c.php?id=$dbitem";
            }
            elseif ($dbtable == 'assets'){
                $dblink = "/scripts/asset_c.php?id=$dbitem";
            }
            elseif ($dbtable == 'spec_items'){
                $dblink = "/spec/";
            }
            else {$dblink = "#";}

            $itemlink = $show_title ?
            "<p style='margin-top:4px;'><i>On $dbtable <a href='$dblink'> $title</a> </i></p>"
            : '';
            $user_contact = $cdata['user_contact'];
            $user_profile = $cdata['user_profile'];


            $user_about = $cdata['user_about'];
             $clist .= "
	<div class='comment_box' style='width:600px;background-color:#FFF;
                border:1px solid #999;'>
        <div class='presource'>
            $itemlink
             <p style='float:left;margin-top:4px;'> $user_contact  - $pdate<br>
             <span style='<font-size:0.8em;font-style:italic;margin-left:1em;'> $user_about </span> </p>
        </div> 
        <p class='comment' style='clear:both'>$ucomment</p>
		<p style='text-align:right;font-size:small;margin-top:4px; clear:both'>(cid # $cid)</p>
            
    </div>
    ";
        }
        $clist .= "</div>\n";
        return $clist;
    }
}
