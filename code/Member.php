<?php
namespace digitalmx\flames;
$proj_path = dirname(__DIR__);

#require_once '../config/init.php';
#require_once $proj_path . '/mx-libs/phpmx' . '/MxPDO.php';
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
 	$member = new Member($pdo);
 	
    getMemberList($tag, $limit = 100, $method = '') //list
    getMemberData($tag,method) // all data for one member, limit 1
    	tag is either email, uid, or username 
    	email may return error if more than one person found
    	
    getMembers($_POST) // returns list based on post vars
    getMemberLogin(loginstring) returns login data for one user
  
   return array: returnResult($data)
    'data' => member data array (one or more)
    
    
    'info' => messages from this->infoh

    'error' => $this->error message
     'count' => no of records returned.
     

    public function getMemberDisplayEmail($tag) // email or message
    public function getMemberName($tag) /
    public function getMemberEmail($tag)
    public function getMemberEmailLinked($tag)
    public function getMemberId($tag)
    public function getLastLogin($tag)
    public function getDonors() // list of liinked emails
    public function getAuthors() // list of liinked emails
    public function updateEms($uid,$ems)


*/

/* member fields:
'admin_status'
'aka' --
'alt_contact'
'amd_dept'
'amd_when'
'amd_where'
'contributed' @
'email_chg_date' @
'email_hide'
'email_last_validated' @
'email_status_time' @ ++
'email_status'
'id' --
'image_url'
'join_date' @
'last_login' @
'linkedin'
'no_bulk'
'previous_ems' ++
'prior_email' ++ 
'prior_username' ++ 
'profile_updated' @ * ++ 
'profile_validated' @
'record_updated' @ ++ 
'status_updated' @ ++
'status'
'test_status'
'upw'
'upwards'
'user_about'
'user_amd'
'user_current'
'user_email'
'user_from'
'user_greet'
'user_id'
'user_interests'
'user_memories'
'user_web'
'username'


#-- deprecated, ++ trigger @ timestamp
 (* on user_about,user_interests,user_current,user_from,user_memories)
 
Generated fields:
	'decades',
	'departments',
	'email_age',
	'email_public',
	'email_status_name',
	'is_member',
	'join_date',
	'login_string',
	'profile_age',
	'profile_date',
	'seclevel',
	'status_name',         
	'subscriber',


Long profile fields
	'user_memories',
	'user_about',
	'user_interests',
	
*/ 


use \Exception as Exception;
use \digitalmx\flames\Definitions as Defs;
use digitalmx as u;
use digitalmx\flames as f;


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
   #all database fiels
   private static $member_fields = array (
   'admin_status',
'alt_contact',
'amd_dept',
'amd_when',
'amd_where',
'contributed',
'email_chg_date',
'email_hide',
'email_last_validated',
'email_status_time',
'email_status',
'id' ,
'image_url',
'join_date' ,
'last_login',
'linkedin',
'no_bulk',
'previous_ems',
'prior_email' ,
'prior_username' ,
'profile_updated' ,
'profile_validated',
'record_updated' ,
'status_updated',
'status',
'test_status',
'upw',
'upwards',
'user_about',
'user_amd',
'user_current',
'user_email',
'user_from',
'user_greet',
'user_id',
'user_interests',
'user_memories',
'user_web',
'username'
);

#db fields not including ones controlled by trigger
private static $update_fields = array(
'admin_status',
'alt_contact',
'amd_dept',
'amd_when',
'amd_where',
'contributed' ,
'email_hide',
'email_status',
'image_url',
'linkedin',
'no_bulk',
'status',
'test_status',
'upw',
'upwards',
'user_about',
'user_amd',
'user_current',
'user_email',
'user_from',
'user_greet',
'user_interests',
'user_memories',
'user_web',
'username',

);
#long text fields only needed for profile
private static $long_profile_fields = array (
	'user_interests',
	'user_memories',
	'user_about',
);

#additional fields genereated from main data
 private static $added_fields = array (
 	'decades',
	'departments',
	'locations',
	'email_age',
	'email_public',
	'email_status_name',
	'is_member',
	'join_date',
	'login_string',
	'profile_age',
	'profile_date',
	'seclevel',
	'status_name',         
	'subscriber',
	'linkedinlink',
	'at_amd',
	'needs_update',

 );
 
 //fields returned for log-in
 private static $member_info = array (
 	'username',
 	'user_id',
 	'user_email',
 	'email_public',
 	'seclevel',
 	'user_current',
 	'user_from',
 	'at_amd',
 	'profile_age',
 	'join_date',
 	'email_status',
 	'last_login',
 	'status_name',
 	'linkedin',
 
 );
 #limited fields returned from member listss
 private static $list_fields = array (
 'username', 'user_id', 'user_email', 'email_status',
 'status', 'upw'
 );
 
 	private static $no_member = array(
 	'username' => 'Not a Member',
 	'user_id' => 0,
 	'user_email' => '',
 	'seclevel' => 0,
 	'status_name' => "Not a Member",
 	'linkedin' => '',
 	
 	);
 	
 	private static $login_regex =  '/^(\w{5})(\d{5})$/';
    private  $memberTable = 'members_f2';
    private $pdo;
 #   private $messenger;

 
 /* fields normally delivered.
 	db fields plus added fields less long profile fields
 	*/

    private  $data_fields = array();   

    // data for return
    private $info='';
    private $error='';
    private $credential; #not sure what this is for
  
    # plus record count and data
    
 public function __construct($pdo)
    {
       
	$this->pdo = $pdo;
	$this->data_fields = array_diff(array_merge(self::$member_fields,self::$added_fields),self::$long_profile_fields);
	
	
	

    }

   /* searches are returned an array containing these fields */
 private function returnResult ($data=[] ) {
    #mnenomic rdc  
    $r['data'] = $data;
    $r['info'] = $this->info;
    $r['error'] = $this->error;
    $r['count'] = count($data);

    $r['credential'] = $this->credential;
    return $r;
 }

  public function getMemberData($tag,$method='')
    {
         /*returns all the member data for one member,
         // enhanced with computed fields, except profile text

        // Methods: email, login, name_exact, uid, 
        // method generally selected automtically from the tag format
        
        */
       if (empty($tag)){throw new Exception ("Attempt to getMemberData on empty tag");}
       
       #get searchfield for to prepare sql, then searchfor to execute
        if (! list ($searchfield,$searchfor) = $this->setSearchCriteria($tag,$method)){
        		$this->error = 'Search method not understood';
            return returnResult();
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
        	$this->error = "Got $idcnt results; only $limit allowed (searching on '$tag')";
            return $this->returnResult();
        }
        if ($idcnt == 0) {
        		$this->info = "No Members Found" ;
            return $this->returnResult();
        }
        
        $mdata = $stmt->fetch();
       # u\echor($mdata,'Mdata');
        
     
        $user_array = $this->enhanceData($mdata);
       # u\echor ($user_array ,'after merge');
        #u\echor ($this->data_fields, 'Data fields');
        $user_array = array_intersect_key($user_array, array_flip($this->data_fields ) );
       # u\echor ($user_array,'post-filter');
     
        return $this->returnResult($user_array);
            
    }

	private function isLogin($login) {
    #returns true or false
    // regex for user login string 5 char pw, 5 digit user_id
      
      return preg_match(self::$login_regex,$login) ; 
	}
	
	private function parseLogin ($login) {
		// paarse old style login string into user and pw
		if (preg_match(self::$login_regex,$login,$match) ){
			return array_slice($match,1,2);
		}
		return false;
	}
	
	
     private function enhanceData($row,$fieldlist=[])
    {
    	// takes row from select *, adds computed fields, and
    	// returns the fields requested in fieldlist
    	
        $id = $row['user_id'];
        // creates array of other fields to be added to the db fields
        $login_string = $row['upw'] . $id ;
        
        $is_member = in_array($row['status'], Defs::getMemberInList());
        
        $image_url = SITE_PATH . "/assets/users/{$id}.jpg";
        if (!file_exists($image_url)){$image_url = '';}
    
       $profile_date = (empty($row['profile_validated']))? "(Never)" :
            u\make_date($row['profile_validated']);
        
        $addons= array(
        
        'seclevel' => Defs::getSecLevel($row['status']) ,
        'status_name' => Defs::getMemberDescription($row['status']) ,
            
        'login_string' =>  $login_string ,
        'subscriber' => $row['no_bulk']?false:true ,
        'is_member' => $is_member ,     
        'email_age' => u\days_ago($row['email_last_validated']),
        'profile_age' => u\days_ago ($row['profile_validated']),
        
        'email_public' => $this->buildDisplayEmail($row['user_email'], $row['email_status'], $row['email_hide']),
        'join_date' => u\make_date($row['join_date']),
        'linkedinlink' => u\linkHref($row['linkedin'],'Me on Linked In'),
        'email_status_name' => Defs::getEmsName($row['email_status']),
        'image_url' => $image_url,
        'decades' => u\decompress (
            $row ['amd_when'], Defs::$decades ),
       'departments' => u\decompress (
            $row['amd_dept'], Defs::$departments),
         'locations' => u\decompress (
         	$row['amd_where'], Defs::$locations),
        'profile_date' => $profile_date,
         'at_amd' => $row['user_amd'] 
         	. "("
         	. u\decompress( $row ['amd_when'], Defs::$decades )
         	. ', '
         	. u\decompress ( $row['amd_where'], Defs::$locations)
         	. ')',
     );
       
       $addons ['needs_update'] = (
            $addons['profile_age'] > 365
            or
            $addons['email_age'] > 365

            )?
            true:false;
            
      # u\echoR($addons,'addons');
       
        $enhanced = array_merge($row, $addons);
        
        if (! empty($fieldlist) ){
        		if (is_array($fieldlist)){
       	 		$enhanced = array_intersect_key($enhanced,array_flip($fieldlist) );
       	 	} else {
       	 		throw new Exception ("Field list to enhance data is not a list"); 
       	 	}
        }
        
        return $enhanced;
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
	public function setEmail ($uid,$email){
		$sql = "UPDATE `members_f2` SET user_email = '$email'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	public function setAdminStatus ($uid,$status) {
	$sql = "UPDATE `members_f2` SET admin_status = '$status'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	
	public function setStatus ($uid,$status){
		$sql = "UPDATE `members_f2` SET status = '$status'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	public function setUserName ($uid,$name){
	$sql = "UPDATE `members_f2` SET username = '$name'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	public function setNoBulk($uid,$nobulk){
		// nobulk must be 0 or 1
	$sql = "UPDATE `members_f2` SET no_bulk = $nobulk";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	public function setCurrent ($uid,$current) {
		$sql = "UPDATE `members_f2` SET user_current = ?";
		 $stmt = $this->pdo->prepare ($sql);
		 $stmt->execute ([$current]);
			
		return true;
		
	}
	public function setAdminNote ($uid,$note) {
		$sql = "UPDATE `members_f2` SET user_current = ?";
		 $stmt = $this->pdo->prepare ($sql);
		 $stmt->execute ([$note]);
			
		return true;
		
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
        } elseif ($field == 'login' or $this->isLogin($tag)) { #looks like a login code
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


    private function randPW() {
 //Generate a 5 digit password from 20 randomly selected characters

	
	 static $tb1 = array (0,1,2,3,4,5,6,7,8,9,'P','Q','W','X','V','b','r','z','k','n');
	 static $iterations = 0;
	 if ($iterations > 5){die ("Too many iterations of random password");}
	 $pass = "";
	 $q = "SELECT * from `members_f2` WHERE upw = ?;";
	 $stmt = $this-> pdo -> prepare($q);
	 while (!$pass){
	 	
	 	 ++$iterations;
		 for ($i=0; $i<5; $i++) {
			$pass = $pass . $tb1[rand(0,19)];
		  }
		 
		  #make sure it's unique
		  
		  $stmt->execute([$pass]);
		  if ($stmt -> rowCount() >0 ){$pass='';}
  	}
	 return $pass;
 }
 
  public function updateMember($post){
    if (empty ($post['user_id'])){
        throw new Exception ("update Member called with no user_id.");
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
          
    $sql = "UPDATE `members_f2` set $fields where user_id = $key;";

   if (! $stmt = $this->pdo->prepare($sql)){
     throw new Exception ("pdo prepare failed. ");
    }
    if (! $stmt->execute($data) ){
        throw new Exception ("pdo execute failed. ");
    }
    return true;
  }


	public function getMembersForAdmin($post) {
		// gets members by email, name, ems, status, or admin 
		// from MemberAdmin
		$q = array ();
		$fields = "status, user_email, email_status,  email_last_validated,
			record_updated, 
			last_login, no_bulk,upw,user_id,username";
		
		if (!empty($name = $post['name'])){
			$q[] = " username LIKE '%" . addslashes($post['name']) . "%' "; 
		}
		
		if ($email = $post['email']){
			$q[] = " user_email LIKE '%$email%' ";
		}
		if ($status = $post['status']){
			$q[] = " status LIKE '$status' ";
		}
		if ($ems = $post['ems']){
			$q[] = " email_status LIKE '$ems' ";
		}
		if ($admin_status = $post['admin_status']){
			$q[] = " admin_status LIKE '$admin_status' ";
		}
		$sql = "SELECT $fields FROM `members_f2` WHERE " . implode (' AND ',$q) . " ORDER BY status " . " LIMIT 100;";
#echo $sql .  BRNL;
		echo "Ready to search: $sql" . BRNL;
		
		$result = $this->pdo -> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

		
		return $this->returnResult($result);
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
           $this->info = "No Members Found";
            return $this->returnResult();
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
                $short_list[] = enhanceData($row,self::short_data_fields);
            }
         }   
                
        
        return $this->returnResult($short_list);
    }
    
    
    public function getMemberList($tag, $limit = 100)
    {
    #echo "Starting memberlist with tag $tag" . BRNL;
        $messages = [];
        #get limited information at this point; enough for returning
        #some simple functions.
        $limitplusone = $limit + 1;
        list ($searchfield,$searchfor) = $this->setSearchCriteria($tag);
   		$list_fields = implode (',',self::$list_fields);
        $sql = "SELECT $list_fields from `$this->memberTable` WHERE $searchfield LIMIT $limitplusone";
        #echo $sql . BRNL;
        $stmt = $this->pdo-> prepare($sql);
        $ids = $stmt->execute($searchfor);
        $idcnt = $stmt->rowCount();
        if ($idcnt > $limit) {
            $this->error .= "Got $idcnt results; only $limit allowed (searching on '$tag').";
        }
        
        #return array of all reesults 
        $mb = $stmt->fetchAll();
       
       return $this->returnResult($mb);
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
        
    public function getMemberFromLogin($user,$pass='')
    {
    	if (empty($user)) {
    		return self::$no_member;
    	}
    	if (empty($pass) and $m = $this->parseLogin($user) ){
    		list($pass,$uid) = $m;
    	}
    	else {
    		return self::$no_member;
    	}
    	$fields = implode (',',self::$member_info);
    	
    	$sql = "SELECT * from `members_f2` where user_id = $uid and upw = '$pass';";
    	#echo "$sql" . BRNL;
    	
    	$result = $this->pdo->query($sql)->fetch();
    	
    	if (! $result){return $this-no_member;}
    	//add fields and filter result
    	$result = $this->enhanceData($result,self::$member_info);
    	return $result;
    }
    	
    

	public function getMemberAll ($uid){
		$sql = "SELECT * from `members_f2` WHERE uid = $uid";
		$row = $this->pdo->query($sql) -> fetch();
		$user_data = $this->enhanceData($row);
		return $user_data;
	}
		
   

   
  
   
    public function getMemberDisplayEmail($tag)
    {
         $md = $this->getMemberData($tag);
        if ($md['records'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return $md['data']['display_email'];
    }
    
   
    public function verifyEmail ($id) {
    $newstat = $this->member->setEmailStatus($id,'Y');
    return $newstat;
	}

	public function verifyProfile ($id) {
    $newstat = $this->member->setProfileVerified($id);
    return $newstat;
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
        if ($md['count'] == 0 or !empty($mb['error'])) {
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
        return u\make_date( $last);
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
        $sql = "Update `$this->memberTable` set profile_validated = NOW(),email_status='Y'
            WHERE user_id = $id;";
            
        $stmt = $this->pdo->query($sql);
        $md = $this->getMemberData($id);
        // u\echoR($md);
//         exit;
        
        $newstat = $md['data']['profile_validated'];
        
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
	public function  setEmailStatus($uid,$ems) {
		$sql = "UPDATE `members_f2` SET email_status = '$ems' 
			WHERE user_id = '$uid';";
			echo "from member->updateEms: $sql" . BRNL;
			
		if (! $result = $this->pdo->query($sql) ){
			throw new Exception ("Failed setEmailStatus on $uid, $ems");
		}
		
	}
	
} #end class

