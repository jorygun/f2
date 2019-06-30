<?php
namespace digitalmx\flames;

 /**
  *
  *  member class  ... get info about members from the database              *
  *  Called with either an email or a user id or a login code.               *
  *  Routine will retrieve the user (if only one), and create                *
  *  a user_row from the database.  Various getters are used                 *
  *  to retrieve data items or derivatives.                                  *
  *
  **/
                                                                 

/* Methods and Properties
    $memberTable // current db table for members
    
    
    getMemberList($tag, $limit = 100, $method = '') //list
    getMemberData($tag,method) // all data, limit 1
    getMembers($_POST) // returns list based on post vars
    getMemberLogin(loginstring) returns login data for one user
      
   getMemberData(tag,method) returns enhanced data for one member
  

    
   return array:
    'data' => member data array (one or more)
    'info' => messages from the search
    public function getMemberDisplayEmail($tag)
    public function getMemberName($tag)
    public function getMemberEmail($tag)
    public function getMemberEmailLinked($tag)
    public function getMemberId($tag)
    public function getLastLogin($tag)
    public function getDonors() // list of liinked emails
    public function getAuthors() // list of liinked emails


*/


#use Digitalmx\Lib as u;
use \Exception as Exception;
use \digitalmx\flames\Definitions as Defs;
use digitalmx as u;


class Member
{
   

  /*
   *  Model class for member data.                                            *
   *  Only class that access the member db.                                   *
   *  Returns data from db, or various fields computed from it.               *
   *  Also updates member info, records login date, contributor, etc.         *
   *  Searches for members by any tag including uid, login, email, name,      *
   *  exact name.             
   
 
    
    
   *
   */

    private  $memberTable = 'members_f2';
    private $pdo;
    private $messenger;

 /**
  *  base fields retrieved in initial pull                                   *
  *  set these fields so some applications get all the data they need.       *
  *  e.g., list of members and email meeting some criteria,                  *
  *  list of users from a search.                                            *
  **/

    private static $short_data_fields = array(
    'user_id','status','user_email','username',
    'email_status','email_status_time', 'last_login', 'email_hide',
    'email_verified','email_public','user_from','decades','departments','aka'
    
    );
    # these fields retained in _SESSION['login_user']
    private static $login_fields = array(
    'user_id', 
    'username', 
    'status', 
    'status_name', 
    'is_member', 
    'seclevel', 
    'user_email', 
    'email_status', 
    'email_public',
    'email_hide', 
    'last_login', 
    'image_url', 
    'subscriber', 
    'email_verified', 
    'profile_verified', 
    'profile_age', 
    'email_age', 
    'needs_update',
    'join_date',
    'profile_date',
    
    
    
    );
    
    public static $member_update_fields = array (
	'username', 'upw', 'joined', 'user_email', 'prior_email', 'email_chg_date', 'email_status', 'email_status_time', 'email_verified', 'previous_ems', 'no_bulk', 'email_hide', 'status_updated', 'status', 'admin_status',  'profile_verified', 'user_from', 'user_amd', 'amd_where', 'amd_when', 'amd_dept', 'user_current', 'user_interests', 'user_greet', 'user_about', 'user_memories', 'image_url', 'linkedin', 'admin_note', 'contributed','user_web','upward'
	);
    // data for return
    private $info;
    private $error;
    private $credential = false;
    # plus record count and data
    
 public function __construct($pdo)
    {
       
	$this->pdo = $pdo;

    }
    
   
 private function getReturn ($records,$data) {
    #mnenomic rdc  
    $r['data'] = $data;
    $r['info'] = $this->info;
    $r['error'] = $this->error;
    $r['records'] = $records;
    $f['credential'] = $this->credential;
    return $r;
 }

  public function getMemberData($tag,$method='')
    {
         /*returns all the member data for one member,
         // enhanced with computed fields
        // Methods: email, login, name_exact, uid
        
        */
       if (empty($tag)){throw new Exception ("Attempt to getMemberData on empty tag");}
       
       #get searchfield for to prepare sql, then searchfor to execute
        if (! list ($searchfield,$searchfor) = $this->setSearchCriteria($tag,$method)){
            return getReturn(0,[]);
        }
        
        # only want 1 result.  
        #Set limit for 2 so can detect where more than one returned.
        $limit = 1;
        $limitplusone = $limit + 1;
        $sql = "SELECT * from `$this->memberTable` WHERE $searchfield LIMIT $limitplusone";
       # echo $sql . BRLF . print_r($searchfor,true) . BRLF ;
        $stmt = $this->pdo-> prepare($sql);
        $ids = $stmt ->execute($searchfor);
        $idcnt = $stmt->rowCount();
       # echo $idcnt . BRLF; 
        $messages = [];
        if ($idcnt > $limit) {
            $this->error .= "Got $idcnt results; only $limit allowed (searching on '$tag').";
            return $this->getReturn(0,[]);
        }
        if ($idcnt == 0) {
            $this->info = "No Members Found";
            return $this->getReturn(0,[]);
        }
        
        $mdata = $stmt->fetch();
        $addon_array = $this->buildAddons($mdata);
        $user_array = array_merge($mdata,$addon_array);
      // u\echoR($user_array,"Get data user array");
  
        return $this->getReturn($idcnt,$user_array);
            
    }

     private function buildAddons($row)
    {
        $id = $row['user_id'];
        // creates array of other fields to be added to the db fields
        $login_string = $row['upw'] . $id ;
        
        $is_member = in_array($row['status'], Definitions::get_member_array());
        
        $image_url = SITE_PATH . "/assets/users/{$id}.jpg";
        if (!file_exists($image_url)){$image_url = '';}
    
       $profile_date = (empty($row['profile_verified']))? "(Never)" :
            u\make_date($row['profile_verified']);
        
        $addons= array(
        
        'seclevel' => Defs::get_seclevel($row['status']) ,
        'status_name' => Defs::getMemberDescription($row['status']) ,
            
        'login_string' =>  $login_string ,
        'subscriber' => $row['no_bulk']?false:true ,
        'is_member' => $is_member ,     
        'email_age' => u\days_ago($row['email_verified']),
        'profile_age' => u\days_ago ($row['profile_verified']),
        
        'email_public' => $this->buildDisplayEmail($row['user_email'], $row['email_status'], $row['email_hide']),
        'join_date' => u\make_date($row['joined']),
        'email_status_name' => Defs::$getEmsName($row['email_status']),
        'image_url' => $image_url,
        'decades' => $this->decompress (
            $row ['amd_when'], Defs::$decades ),
       'departments' => $this->decompress (
            $row['amd_dept'], Defs::$departments),
        'profile_date' => $profile_date,
            
        

       
                
        );
       
       $addons ['needs_update'] = (
            $addons['profile_age'] > 365
            or
            $addons['email_age'] > 365

            )?
            true:false;
            
      # u\echoR($addons,'addons');
       
       
       
        $addon_array = array_merge($row, $addons);
        return $addon_array;
    }
  
  public function addMember ($post){
    // adds new member to the db
    // gets new id,upw
     $post['upw'] = u\random_strings(5);
   
    
    $valid_vars = array('upw','username','user_email','status',
    'user_from','user_amd','admin_note','email_status');
// switch to pdo_prep
    $prepared =  u\prepareVars($post,$valid_vars);
    $fieldlist = $prepared['field_list'];
    $data = $prepared['data'];
 //      echo "Fields: $fields <br>Prepared data: "; u\echoR($data); 

    
    $a=[];
  foreach ($fieldlist as $f){
    $a[] = ":$f";
  }
  $fields = implode(', ',$fieldlist);
  $values = implode (', ',$a);
      
    $table = $this->memberTable;
    
   $sql = "INSERT into `$table` ($fields) VALUES ($values);";
   echo "sql: $sql" . BRNL;
   #u\echoR($data,'data array');
  
   
   if (! $stmt = $this->pdo->prepare($sql) ){
     throw new Exception ("pdo prepare failed. ");
    }
    if (! $stmt->execute($data) ){
        throw new Exception ("pdo execute failed. ");
    }
    $id = $this->pdo->lastInsertId();
    $login_string = SITE_URL . '/?s=' . $post['upw'] . $id;
    
    echo "New id $id" . BRNL;
    $data=array(
        'email'=>$post['user_email'],
        'loginstring' => $login_string,
    );
    
    #$this->messenger->sendit('welcome', $data);
  
  }

 private function setSearchCriteria($tag, $method = '')
    {
        // sets where clause and data based on the tag
        //(using only method if specd)
        // search fields for each method:
        $search_fields = array(
           'email' => 'user_email = ?',
           'login' => 'user_id = ? and upw = ?',
           'name_exact' => 'username = ?',
           'name_loose' => 'CONCAT_WS (" ", username, aka) like ? ' ,
           'uid' => 'user_id = ?',
        );
        
        $field = false;
       
        if (!empty($method) and isset($search_fields[$method])) {
            $field = $method;
        }
        
        if ($field == 'email' or strpos($tag, '@') > 0) { #is email
            $searchfield = $search_fields['email'];
            $searchfor = [$tag];
        } elseif ($field == 'login' or isLogin($tag)) { #looks like a login code
            $searchfield = $search_fields['login'];
            $searchfor = splitLogin($tag);
        } elseif ($field == 'uid' or is_numeric($tag)) { #is a userid
            $searchfield = $search_fields['uid'];
            $searchfor = [$tag];
        } elseif ($field == 'name_exact' or (in_array($tag[0], ["'",'"']))) {
            $searchfield = $search_fields['name_exact'];
            $searchfor = [substr($tag, 1, strlen($tag)-2)];
        }
        
        #probably matches anything ??
        elseif ($field == 'name_loose'
           or preg_match('/^[\w \'\.\-]+$/u', $tag)) {
            $searchfield = $search_fields['name_loose'];
            $searchfor = ["%$tag%"];
        } else {
            $this->error .= "Cannot understand tag $tag for member lookup";
            return false;
        }
        
        return array ($searchfield,$searchfor);
    }
 
    // same as implode??
     private function array_to_string($arr){
        if (is_string($arr)){return $arr;}
        if (is_array($arr)){
            $vstring = '';
            foreach ($arr as $v){
                    $vstring .= $arr;
            }
            return $vstring;
        }
    }  


    
 
  public function updateMember($post){
    if (empty ($post['user_id'])){
        throw new Exception ("update Member called with no valid array.");
    }
    
   
    // build array to execute with prepared post
    // If logged in user has modified their own profile, update
    //  the session loginuser so they don't get false warnings.
    
    if (isset($post['updated']) 
        and $post['user_id'] == $_SESSION['login_user']['user_id'] ){
            $_SESSION['login_user']['needs_update'] = false;
            $_SESSION['login_user']['email_status'] = 'Y';
    }
    
    if (!$prepared =  u\prepareVars($post,self::$member_update_fields, 'user_id') ) {throw new Exception ("failed to prepare vars: " . print_r($post,true) ) ;}
    
       $fields = $prepared['fields'];
       $data = $prepared['data'];
    #   echo "Prepared data: "; u\echoR($data); 
       $key = $prepared['key'];
          
    $sql = "UPDATE `$this->memberTable` set $fields where user_id = $key;";

   if (! $stmt = $this->pdo->prepare($sql)){
     throw new Exception ("pdo prepare failed. ");
    }
    if (! $stmt->execute($data) ){
        throw new Exception ("pdo execute failed. ");
    }
    return true;
  }



    
    public function getMembers($post){
         $q=[]; $messages=[];
       // returns list of members based on either a generic
       // 'searchon' tag, or an ANDED combinaation of 
       // all valid search keys 
        $valid_keys = ['email_status','status'];
       
       // if there's a searchon, use that as a tag
        if (!empty($post['searchon'])){
             list ($searchfield,$searchfor) = $this->setSearchCriteria($post['searchon']);
        }
        else  {
           //  'email' => 'user_email = ?',
//            'login' => 'user_id = ? and upw = ?',
//            'name_exact' => 'username = ?',
//            'name_loose' => 'username like ? ' ,
//            'uid' => 'user_id = ?',
            $q=[];$v=[];
            foreach ($valid_keys as $key){
                if (!empty($post[$key])){
                    $q[] = "$key  = :$key";
                    $searchfor["$key" ] = $post[$key];
                }
            } 
            $searchfield = implode(' AND ' ,$q);
            
        }   
        if (empty($searchfield)){
            $this->info = 'No Members Found';
            return $this->getReturn(0,[]);
        }
        
        $sql = "SELECT * from `$this->memberTable` WHERE $searchfield ";
  //       echo $sql . BRNL; u\echoR($searchfor,'search data');
        
        $stmt = $this->pdo-> prepare($sql);
        $stmt->execute($searchfor);
    
        $idcnt = $stmt->rowCount();
//         echo "Count: $idcnt" .BRNL;
        $mb = array();
         if ($idcnt == 0) {
            $this->info = "No Members Found";
    
        }
        else {
            $this->info = "$idcnt Members Found";
             foreach ($stmt as $row){
                $row = array_merge ($row,$this->buildAddons($row));
               
                foreach (self::$short_data_fields as $field){
                    $short_row[$field] = $row[$field];
                }
            $mb[] = $short_row;
            }
         }   
                
        
        return $this->getReturn($idcnt,$mb);
    }
    
    
    public function getMemberList($tag, $limit = 100)
    {
    #echo "Starting memberlist with tag $tag" . BRNL;
        $messages = [];
        #get limited information at this point; enough for returning
        #some simple functions.
        $limitplusone = $limit + 1;
        list ($searchfield,$searchfor) = $this->setSearchCriteria($tag);
         $short_data_fields = implode(',', self::$short_data_fields);
        $sql = "SELECT $short_data_fields from `$this->memberTable` WHERE $searchfield LIMIT $limitplusone";
        #echo $sql . BRNL;
        $stmt = $this->pdo-> prepare($sql);
        $ids = $stmt->execute($searchfor);
        $idcnt = $stmt->rowCount();
        if ($idcnt > $limit) {
            $this->error .= "Got $idcnt results; only $limit allowed (searching on '$tag').";
        }
        
        #return array of all reesults 
        $mb = $stmt->fetchAll();
       
       return $this->getReturn($idcnt,$mb);
       ;
    }
    

    public function getContributors () {
        // retreives donors and authors
        $sql = "SELECT user_id,username,user_email from `$this->memberTable`
        where status in ('MC','MU');";
        $authors = $this->pdo->query($sql)->fetchAll();
        
         $sql = "SELECT user_id,username,user_email from `$this->memberTable`
         where contributed is not null";
         
        $donors = $this->pdo->query($sql)->fetchAll();
        
        $data['authors'] = $authors;
        $data['donors'] = $donors;
        return $data;
    }
        
    public function getMemberLogin($login_string)
    {
        if (! isLogin($login_string)){
            throw new Exception ("Invalid login tag $tag");
        }
    
    
       $md = $this->getMemberData($login_string,'login');
       if (!empty($md['error'] )){
        throw new Exception ("Could not get login data: {$md['error']}");
        }
        
       
        foreach (self::$login_fields as $f){
            $li_data[$f] = $md['data'][$f];
        }
       # u\echoR($li_data,'li_data');
        return $li_data;
    }

   

   
  
   
    public function getMemberDisplayEmail($tag)
    {
         $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return $md['data']['display_email'];
    }
    
   
    
    
    public function getMemberName($tag)
    {
        $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return $md['data']['username'];
    }
    public function getMemberEmail($tag)
    {
        $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return $md['data']['user_email'];
    }
    
    
    public function getMemberEmailLinked($tag)
    {
        $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return u\linkEmail($md['data'][0]['user_email'], $md['data']['username']);
    }
    public function getMemberId($tag)
    {
        $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return $md['data']['user_id'];
    }
    // returns fully filled in member data on one member.
   
    public function getLastLogin($tag)
    {
        $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        $last = $md ['data']['last_login'];
        return u\formatDate('CDL', $last);
    }
    
    public function setEmailStatus ($id,$status) {
        $sql = "Update `$this->memberTable` set email_status = '$status'
            WHERE user_id = $id;";
            
        if (!$stmt = $this->pdo->query($sql)){
            return false;
        }
        return "OK";
    }
        

    public function sendLogin($id) {
        // can receive a user_id or an email as input
        if (is_numeric($id)){
            $where =  "user_id = '$id'";
        }
        elseif (filter_var($id,FILTER_VALIDATE_EMAIL)){
            $where = "user_email = '$id'";
        }
        else {return "Request not valid.";}
        
        $sql = "SELECT username,user_id,upw,user_email FROM $this->memberTable
            where $where";
        if (!$result = $this->pdo->query($sql) ){
            throw new Exception ("get user row in sendlogin failed");
        }
        if ($result->rowCount() == 0) {return "No Members Found";}
        $format = "   %-25s %-50s\n";
        $msg = sprintf($format,"Member Name",  "login url");
        foreach ($result as $row){ #there may be more than one
            $login = 'https://' . SITE_NAME . "/?s=" . $row['upw']  . $row['user_id'];
            //echo $login;
            // send message with login
            $msg .=  sprintf($format,$row['username'], $login );
        }
        
        $data = array(
                'username' => $row['username'],
                'email'=>$row['user_email'],
                'msg'=>$msg,
            );
       // u\echoR($data,'to sendit');
        
       # $messenger = $this->messenger->sendit('sendlogin',$data);
        return "Login Sent";
    
    }
    
     public function setProfileVerified ($id) {
        $sql = "Update `$this->memberTable` set profile_verified = NOW(),email_status='Y'
            WHERE user_id = $id;";
            
        $stmt = $this->pdo->query($sql);
        $md = $this->getMemberData($id);
        // u\echoR($md);
//         exit;
        
        $newstat = $md['data']['profile_verified'];
        
        return  $newstat;

    }
    
    public function getDonors()
    {
        $sql = "SELECT username from `$this->membersTable` where 
        contributed IS NOT NULL and contributed > CURDATE() - INTERVAL 24 month;";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetchColumn();
        
        return $result;
    }
    public function getAuthors()
    {
        $sql = "SELECT username from `$this->membersTable` where 
        status = 'MC' ";
        
         $stmt = $this->pdo->query($sql);
        $result = $stmt->fetchColumn();
        
        return $result;
        
    }
    
    private  function decompress($data,$defs)
    {
	//to turn a string of character codes into a descriptive string.
        $choices = [];
		// step through the codes and values in the defining array
		foreach ($defs as $k=>$v){  # D => '60s'
			if (strchr($data,$k)){$choices[]  = $v;}
		}
        if (empty($choices)){
            $my_choices = 'Not specified';
        }
        else {
		    $my_choices = implode (',',$choices);
		}

		return $my_choices;
    }

  private function buildDisplayEmail($email, $ems, $hide)
    {
        /* Returns either a linked email address or a message that the email is hidden, or a
        message that it is an invalid address.
        */
        
    
        if (!$email) {
            return "No email on file";
        }
        if (strpos($email, '*') !== false) {
            #echo "Invalid email address ($addr) in display_email.";
            return "**Invalid email address**";
        }

        if ($hide) {
            $v = '(Email hidden)';
        } elseif ($ems == 'LB') {
            $v = "$email (but it bounces)";
        } elseif ($ems == 'LD') {
            $v = "--";
        } elseif (substr($ems, 0, 1) == 'L') {
            $v = "$email (but we can't get a response.)";
        } else {
            $v = "<a href='mailto:$email'>$email</a>";
        }
        return $v;
    }


} #end class

