<?php
namespace digitalmx\flames;
$proj_path = dirname(__DIR__);

use \Exception as Exception;
use \digitalmx\flames\Definitions as Defs;
use digitalmx as u;
use digitalmx\flames as f;
use digitalmx\MyPDO;


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
    
*/

class Member
{
    
#all database fiels
   private static $member_fields = array (    
'admin_note',
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
'photo_asset',
'joined' ,
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
public function getUpdateFields(){
	return self::$update_fields;
}
private static $update_fields = array(
'admin_status',

'amd_dept',
'amd_when',
'amd_where',
'contributed' ,
'email_hide',
'email_status',
'photo_asset',
'linkedin',
'no_bulk',
'status',
'test_status',
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
'email_last_validated',
'profile_updated',
'profile_validated',
'admin_status',
'upwards',

);

 private static $profile_fields = "
 'username','user_current','user_about','user_from','user_interests'
 ";

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
	'profile_valid_date',
	'profile_valid_age',
	'seclevel',
	'status_name',         
	'subscriber',
	'linkedinlink',
	'at_amd',

	'email_valid_date',
	'login_age',

 );
 // fields filled in for any get, but not 
 // actually in the db
  private static $virtual_fields = array (

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
 private static $info_fields = array (
 	'username',
 	'user_id',
 	'status',
 	'email_last_validated',
 	'email_status_time',
 	'email_status_name',
 	'status_name',
 	
 	'user_email',
 	'email_public',
 	'seclevel',
 	'user_current',
 	'user_greet',
 	'user_from',
 	'at_amd',
 	'profile_date',
 	'profile_age',
 	'profile_valid_age',
 	'join_date',
 	'email_status',
 	'last_login',
 	'status_name',
 	'no_bulk',
 	'upw',
 	'linkedin',
 	'contributed',
 	'login_age',
 
 );
 

 #limited fields returned from member listss
 private static $min_fields = array (
 'username', 
 'user_id', 
 'user_email', 
 'seclevel',
 'status_name',
 'email_status',
 'status', 
 'upw'
 );
 
 	private static $no_member = array(
 	'username' => 'Not a Member',
 	'user_id' => 0,
 	'user_email' => '',
 	'seclevel' => 0,
 	'status_name' => "Not a Member",
 	'linkedin' => '',
 	'status' => 'Y'
 	
 	);
 	
 private static $bad_member = array(
 	'username' => 'Not a Member',
 	'user_id' => 0,
 	'user_email' => '',
 	'seclevel' => -1,
 	'status_name' => "Invalid Login",
 	'linkedin' => '',
 	'status' => 'Y'
 	);
 
 
 	private static $login_regex =  '/^(\w{5})(\d{5})$/';
    private  $memberTable = 'members_f2';
    private $pdo;
 #   private $messenger;

 
 /* fields normally delivered.
 	db fields plus added fields 
 	*/
    private  $std_fields = array();   

    // data for return
    private $info='';
    private $error='';
   
    private $test;
  	private $member_status_set;
    # plus record count and data
    
 public function __construct($test=false)
    {
    $this->test = $test;
       
	$this->pdo = MyPDO::instance();
	$this->member_status_set = Defs::getMemberInSet();
	$this->std_fields = array_merge(self::$member_fields,self::$added_fields);
    }

   /* searches are returned an array containing these fields */
 private function returnResult ($data=[] ) { 
    $r['data'] = $data;
    $r['info'] = $this->info;
    $r['error'] = $this->error;
    $r['count'] = count($data);

    return $r;
 }

  public function getMemberData($tag,$method='')
    {
         /*returns all the member data for one member,
         // enhanced with computed fields

        // Methods: email, login, name_exact, uid, 
        // method generally selected automtically from the tag format
         //
        
        */
       if (empty($tag)){
       	$this->info = "New Member"; 
       	return $this->returnResult(self::$no_member);
       	}
       $tag = trim($tag); #remove extraneious white space.
       
       #get searchfield for to prepare sql, then searchfor to execute
        if (! list ($searchfield,$searchfor) = $this->setSearchCriteria($tag,$method)){
        		$this->error = 'Search method not understood';
            return $this->returnResult();
        }
        
        # only want 1 result.  
        #Set limit for 2 so can detect where more than one returned.
        $limit = 1;
        $limitplusone = $limit + 1;
        $fields = implode(',',$this->std_fields);
        $sql = "SELECT * from `$this->memberTable` WHERE $searchfield LIMIT $limitplusone";
    echo $sql . BRNL . print_r($searchfor,true) . BRNL ;
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
        
        #u\echor($mdata,'Mdata');
        $user_array = $this->enhanceData($mdata,$this->std_fields);
			
     	
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
    	#u\echor($row,'input to enhancer');
    	
        $id = $row['user_id'];
        // creates array of other fields to be added to the db fields
        $login_string = $row['upw'] . $id ;
        
        $is_member = in_array($row['status'], Defs::getMemberInList());
        
      $profile_date =  u\make_date($row['profile_updated']);
      
      $profile_age = u\days_ago($row['profile_validated']);
      
         
      $decade_choices = u\decompress($row['amd_when'],Defs::$decades);
		$location_choices = u\decompress($row['amd_where'],Defs::$locations);
			$department_choices = u\decompress($row['amd_dept'],Defs::$departments);

		$amd_box_data = '';
		if (!empty($department_choices) || !empty($location_choices) ||  !empty($decade_choices) ) {
		$amd_box_data .= "I worked at AMD ";
		}
		$amd_box_data .= (!empty($department_choices)) ? "in " . $department_choices :'';
		$amd_box_data .= (!empty($location_choices)) ? " in " . $location_choices : '';
		$amd_box_data .= (!empty($decade_choices)) ? " during the " . $decade_choices: '' ;
		$member_photo = '';
		// $member_photo = (isset($row['photo_asset'])) ? 
//    		 get_asset_by_id($row['photo_asset'],'photo') : '' ;
   	$linkedinlink = ($row['linkedin'])? "<a href='${row['linkedin']}'>
   		<img src='https://static.licdn.com/scds/common/u/img/webpromo/btn_liprofile_blue_80x15.png' width='80' height='15' border='0' alt='profile on LinkedIn' /> </a>" : '' ;
        $addons= array(
        
        'seclevel' => Defs::getSecLevel($row['status']) ,
        'status_name' => Defs::getMemberDescription($row['status']) ,
            
        'login_string' =>  $login_string ,
        'subscriber' => $row['no_bulk']?false:true ,
        'is_member' => $is_member ,     
        'email_age' => u\days_ago($row['email_last_validated']),
        'email_valid_date' => u\make_date($row['email_last_validated']),
        
        'email_public' => $this->buildDisplayEmail($row['user_email'], $row['email_status'], $row['email_hide']),
        'join_date' => u\make_date($row['joined']),
        'linkedinlink' => $linkedinlink,
        'email_status_name' => Defs::getEmsName($row['email_status']),
        'member_photo' => $member_photo,
      	'profile_age' => $profile_age,
        'profile_date' => $profile_date, #updated
        'profile_valid_date' => u\make_date($row['profile_validated']),
        'profile_valid_age' => u\days_ago($row['profile_validated']),
         'at_amd' => $amd_box_data,
         
         'login_age' => u\days_ago($row['last_login']),
     );
       

            
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
  
  public function setLastLogin($uid) {
  	$sql = "UPDATE members_f2 SET last_login = now() 
  			WHERE user_id = $uid";
  	$this->pdo->query($sql);
  	return true;
  }
  	
  public function addMember ($post){
    // adds new member to the db
    // gets new id,upw
     $post['upw'] = u\random_strings(5);
   
    
    $valid_vars = array('upw','username','user_email','status',
    'user_from','user_amd','admin_note','email_status');
// switch to pdo_prep
    $prepared =  u\pdoPrep($post,$valid_vars);
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
  # echo "sql: $sql" . BRNL;
   #u\echoR($data,'data array');
  
   
   if (! $stmt = $this->pdo->prepare($sql) ){
     throw new Exception ("pdo prepare failed. ");
    }
    if (! $stmt->execute($data) ){
        throw new Exception ("pdo execute failed. ");
    }
    $id = $this->pdo->lastInsertId();
    $login_string = SITE_URL . '/?s=' . $post['upw'] . $id;
    
   # echo "New id $id" . BRNL;
    $data=array(
        'email'=>$post['user_email'],
        'loginstring' => $login_string,
    );
    
    #$this->messenger->sendit('welcome', $data);
  
  }
	public function setEmail ($uid,$email){
		
		$sql = "UPDATE `members_f2` SET user_email = '$email' where user_id = '$uid'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	public function setAdminStatus ($uid,$status) {
	$sql = "UPDATE `members_f2` SET admin_status = '$status' where user_id = '$uid'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	
	public function setStatus ($uid,$status){
		$sql = "UPDATE `members_f2` SET status = '$status' where user_id = '$uid'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	public function setTestStatus ($uid,$status){
		$sql = "UPDATE `members_f2` SET test_status = '$status' where user_id = '$uid'";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	public function setUserName ($uid,$name){
	$sql = "UPDATE `members_f2` SET username = '$name' where user_id = '$uid' ";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	public function setNoBulk($uid,$nobulk){
		// nobulk must be 0 or 1
	$sql = "UPDATE `members_f2` SET no_bulk = $nobulk where user_id = '$uid' ";
		if (! $this->pdo->query($sql) ){
			return false;
		} else {
		return true;
		}
	}
	
	public function setCurrent ($uid,$current) {
		$sql = "UPDATE `members_f2` SET user_current = ? where user_id = '$uid' ";
		 $stmt = $this->pdo->prepare ($sql);
		 $stmt->execute ([$current]);
			
		return true;
		
	}
	public function setAdminNote ($uid,$note) {
		$sql = "UPDATE `members_f2` SET admin_note = ? where user_id = '$uid' ";
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
           'name_loose' => ' username like ? ' ,
           'uid' => 'user_id = ?',
           'id' => 'id = ? ',
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
            $searchfor = f\splitLogin($tag);
        } elseif ($field == 'uid' or 
        		(is_numeric($tag) && (int)$tag >= 10000)) { #is a userid
            $searchfield = $search_fields['uid'];
            $searchfor = [$tag];
         } elseif ($field == 'uid' or 
        		(is_numeric($tag) && (int)$tag < 10000)) { #is a id
            $searchfield = $search_fields['id'];
            $searchfor = [$tag];
            // if name has quotes around it, or is a single word of text
        } elseif ($field == 'name_exact' ){
        		$searchfield = $search_fields['name_exact'];
            $searchfor = [$tag];
         } elseif (  preg_match ('/^[\'"](.+)[\'"]$/',$tag,$match)
        		) {
        			$searchfield = $search_fields['name_exact'];
            	$searchfor = [$match[1]];
         } elseif ( preg_match('/^(\w+)$/',$tag,$match) ){     	
        		$alias_repl = Defs::replaceAlias($match[1]);
        		if ($alias_repl != $match[1]) {
            	$searchfield = $search_fields['name_exact'];
            	$searchfor = [Defs::replaceAlias($match[1])];
            }
        }
        
        
        if (empty ($searchfield) &&  preg_match('/^[\w \'\.\-]+$/u', $tag)) {
        #probably matches anything ??
            $searchfield = $search_fields['name_loose'];
            $searchfor = ["%$tag%"];
        } 
        if (empty ($searchfield)) {
            $this->error .= "Cannot understand tag $tag for member lookup";
            return false;
        }
        
        return array ($searchfield,$searchfor);
    }
 
    // same as implode??
 //     private function array_to_string($arr){
//         if (is_string($arr)){return $arr;}
//         if (is_array($arr)){
//             $vstring = '';
//             foreach ($arr as $v){
//                     $vstring .= $arr;
//             }
//             return $vstring;
//         }
//     }  

	public function addSignup($row){
		// get new uid and passwd
			$row['upw'] = $this->randPW();
			$max_uid = $this->pdo->query('select max(user_id) from `members_f2`')->fetchColumn();
			$new_uid = $max_uid + 1;
			$row['user_id'] = $new_uid;
		
			
			$prep = u\pdoPrep($row,'','id');
 /**
 	$prep = u\pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
	$sql = "INSERT into `members_f2` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
	#	echo 'Member before update.' . BRNL;
	#	echo "sql: " . $sql . BRNL;
	#	u\echor($prep['data'],'Data');
		
       $stmt = $this->pdo->prepare($sql);
       #exit;
       $stmt ->execute($prep['data']);
       $new_id = $this->pdo->lastInsertId();
       
       return $new_uid;
	
	}
    private function randPW() {
 //Generate a 5 digit password from 20 randomly selected characters

	static $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$pass = substr(str_shuffle($permitted_chars),0,5);
	 return $pass;
 }
 
  public function updateMember($post){
    if (empty ($uid = $post['user_id'] ?? '')){
        throw new Exception ("update Member called with no user_id.");
    }
	
	// first get existing record
	$sql = "SELECT * from `members_f2` where user_id = $uid";
	$md = $this->pdo->query($sql)->fetch();
	
	// now compare incoming record with existing and build $upd 
	foreach ($post as $var=>$val){
		if ($md[$var] != $val){
			$upd[$var] = $val;
		}
	}
	
	if (empty($upd)) {return true;} #no changes
	
	$upd['user_id'] = $uid;
	
	$upd = $this->check_auto_fields($upd);
	
	// prepare update data
    if (!$prepared =  u\pdoPrep($upd,self::$update_fields, 'user_id') ) {throw new Exception ("failed to prepare vars: " . print_r($post,true) ) ;}
    
       $fields = $prepared['update'];
       $data = $prepared['data'];
    #   echo "Prepared data: "; u\echoR($data); 
       $key = $prepared['key'];
          
    $sql = "UPDATE `members_f2` set $fields  where user_id = $key;";

   if (! $stmt = $this->pdo->prepare($sql)){
     throw new Exception ("pdo prepare failed. ");
    }
    if (! $stmt->execute($data) ){
        throw new Exception ("pdo execute failed. ");
    }
    return true;
  }

private function check_auto_fields($upd) {
	
	if (isset ($upd['user_email'])){ #changed email
		$upd['email_chg_date'] = 'now()';
	}


}
public function getLogins($tag) {
        // can receive a user_id or an email as input
        if (is_numeric($tag)){
            $where =  "user_id = '$tag'";
        }
        elseif (filter_var($tag,FILTER_VALIDATE_EMAIL)){
            $where = "user_email = '$tag'";
        }
        else {throw new Exception ("Request $tag not valid.");}
        
        $sql = "SELECT username,user_id,upw,user_email FROM `members_f2`
            where $where";
         $result = $this->pdo->query($sql) ;
         
        if ($result->rowCount() == 0){ 
            return false;
        }
       
        $format = "   %-25s %-50s\n";
        $msg = sprintf($format,"Member Name",  "login url");
        foreach ($result as $row){ #there may be more than one
            $login = 'https://' . SITE . "/?s=" . $row['upw']  . $row['user_id'];
            //echo $login;
            // send message with login
            $msg .=  sprintf($format,$row['username'], $login );
        }
      
      return $msg;
        
       
    
    }
	public function getMemberListFromAdmin($post) {
		// gets members by email, name, ems, status, or admin 
		// from MemberAdmin
		$q = array ();
		$fields = implode (',',$this->std_fields);
		
		if (!empty($name = $post['name'])){
			$q[] = " username LIKE '%" . addslashes($post['name']) . "%' "; 
		}
		
		if (!empty($email = $post['email'] ??'')){
			$q[] = " user_email LIKE '%$email%' ";
		}
		if (!empty($status = $post['status'] ??'')){
			$q[] = " status LIKE '$status' ";
		}
		if (!empty($ems = $post['ems'] ??'')){
			$q[] = " email_status LIKE '$ems' ";
		}
		if (!empty($admin_status = $post['admin_status'] ??'')){
			$q[] = " admin_status LIKE '$admin_status' ";
		}
		if (empty($q)){
			throw new Exception ("No search fields provided" . u\echor ($post,'post') );
		}
		
		$sql = "SELECT * FROM `members_f2` WHERE " . implode (' AND ',$q) . " ORDER BY status " . " LIMIT 100;";
	#	echo $sql .  BRNL;
		
		
		$result = $this->pdo -> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		
	// enhance and contract	
		$final = [];
		foreach ($result as $row){
			$final[] = $this->enhanceData($row,self::$info_fields);
		
		}
		return $this->returnResult($final);
	}
	
	
    public function getMemberCounts() {
		 $member_status_set =  Defs::getMemberInSet();
		 $select_all_valid	=
	    "  status in ($member_status_set)
	AND email_status NOT LIKE 'X%'
	AND email_status NOT LIKE 'L%'
	";
	 	$counts = array();
	 	$sql = "SELECT count(*) from `members_f2` 
		WHERE status in ($member_status_set)
		";
		$counts['total'] = $this->pdo->query($sql)->fetchColumn();
		
	
		$sql = "SELECT count(*) as count FROM members_f2
		WHERE $select_all_valid
		;";
		$counts['active'] = $this->pdo->query($sql)->fetchColumn();
		
		$sql = "SELECT count(*) as count FROM members_f2
		WHERE status in ($member_status_set)
		AND (email_status LIKE 'L_' OR email_status LIKE 'X%') 
		;";
		
		$counts['lost'] = $this->pdo->query($sql)->fetchColumn();
		
		$sql = "SELECT count(*) as count FROM members_f2
		WHERE status in ($member_status_set)
		AND email_status = 'LA' 
		;";
		
		$counts['aged'] = $this->pdo->query($sql)->fetchColumn();
		
		$sql = "SELECT count(*) from `members_f2` 
		WHERE $select_all_valid
		AND no_bulk = FALSE;
		";
		$counts['bulk'] = $this->pdo->query($sql)->fetchColumn();
		
		$sql = "SELECT count(*) from `members_f2` 
		WHERE $select_all_valid
		AND no_bulk= TRUE;
		";
		$counts['nobulk'] = $this->pdo->query($sql)->fetchColumn();
		
		$sql = "SELECT count(*) from `members_f2` 
		WHERE $select_all_valid
		AND admin_status != '' ;
		";
		$counts['admin'] = $this->pdo->query($sql)->fetchColumn();
		
		$sql = "SELECT count(*) from `members_f2` 
		WHERE $select_all_valid
		AND test_status != ''
		";
		$counts['test'] = $this->pdo->query($sql)->fetchColumn();
		
		
		return $counts;
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
    
    public function getMembersByEmail($email) {
    	$sql = "SELECT username,user_id,upw from `members_f2` WHERE
    		user_email = '$email' limit 100";
    	$members = $this->pdo->query($sql)->fetchAll();
    	return $members;
    }
    	
    	
    public function getMemberList($tag, $limit = 100)
    {
    #echo "Starting memberlist with tag $tag" . BRNL;
        $messages = [];
        #get limited information at this point; enough for returning
        #some simple functions.
        $limitplusone = $limit + 1;
        list ($searchfield,$searchfor) = $this->setSearchCriteria($tag);
   		$list_fields = implode (',',self::$min_fields);
        $sql = "SELECT $list_fields from `$this->memberTable` WHERE $searchfield LIMIT $limitplusone";
       # echo $sql . BRNL . print_r($searchfor,true) . BRNL;
        $stmt = $this->pdo-> prepare($sql);
        $ids = $stmt->execute($searchfor);
        $idcnt = $stmt->rowCount();
        if ($idcnt > $limit) {
            $this->error .= "Got $idcnt results; only $limit allowed (searching on '$tag').";
        }
        
        #return array of all reesults 
        $mb = $stmt->fetchAll();
      # u\echor($mb,'from getMemberList');
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
   public function checkPass($login=''){
   	 if (!$login){return 0;}
   	if ($m = $this->parseLogin($login) ){
   		list($pass,$uid) = $m;
   		$sql = "SELECT count(*) from `members_f2` where user_id = $uid
   		and upw = '$pass';";
   		try {
   			$r = $this->pdo->query($sql)->fetchColumn();
   			if ($r == 1){return $uid;}
   		} catch (Exception $e){
   			return 0;
   		}
   	}
   	return 0;
   }
   
    public function getLoginInfo($user)
    {
    	$login_info = self::$no_member; #initialize
    	if (empty($user)) {
    		return $login_info;
    	}
    	
    	$sql = "SELECT * from `members_f2` where user_id = $user ";  	
    	if (!$result = $this->pdo->query($sql)->fetch() ){
    		throw new Exception ("Attempt to Login with invalid user id");
    	}
    	//add fields and filter result
    	$login_info = $this->enhanceData($result,self::$info_fields);
    	return $login_info;
    }
    	
    

	public function getMemberRecord($uid, $enhanced=false)
	{
		$sql = "SELECT * from `members_f2` WHERE user_id = $uid";
		$row = $this->pdo->query($sql) -> fetch();
		if ($enhanced){$row = $this->enhanceData($row);}
		return $row;
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
    	#check current email status and notify admin
    	# if validating an aged out address
    	
    	 $sql = "Update `members_f2` set email_status='Y',
    	 	email_last_validated = NOW()
            WHERE user_id = $id;";
		  $stmt = $this->pdo->query($sql);
      
        return  date ('M d Y');
	}

	public function verifyProfile ($id) {
     $sql = "Update `members_f2` set 
     profile_validated = NOW()
            WHERE user_id = $id;";
        $stmt = $this->pdo->query($sql);
      $this->verifyEmail($id);
        return  date ('M d Y');

    
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
        if ($md['count'] == 0 or !empty($mb['error'])) {
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
    
    // returns [username,id]
    public function getMemberId($tag)
    {
        $md = $this->getMemberData($tag);
       # u\echor ($md);
        if (empty($md['count']) or !empty($mb['error'])) {
            return false;
        }
        
        return array(
        	$md['data']['username'],
        	$md['data']['user_id']
        	);
    }
    
    
    public function getMemberBasic ($tag)
    {
    	 $md = $this->getMemberData($tag);
       # u\echor ($md);
        if (empty($md['count']) or !empty($mb['error'])) {
            return false;
        }
        
        return array(
        	$md['data']['username'],
        	$md['data']['user_id'],
        	$md['data']['user_email']
        	);
    }
    
   
    public function getLastLogin($tag)
    {
        $md = $this->getMemberData($tag);
        if ($md['count'] == 0 or !empty($mb['error'])) {
            return false;
        }
        $last = $md ['data']['last_login'];
        return u\make_date( $last,'human','datetime');
    }
    
    public function getEmailStatus($uid) {
    	$md = $this->getMemberData($uid) ;
    	if ($md['count'] == 0 or !empty($mb['error'])) {
            return false;
        }
        return $md ['data']['email_status'];
    }
    
     public function setProfileVerified ($id) {
        $sql = "Update `$this->memberTable` set profile_validated = NOW(),email_status='Y',email_last_validated = NOW()
            WHERE user_id = $id;";
            
        $stmt = $this->pdo->query($sql);
       	$this->updateSession(array(
       		'email_status' => 'Y',
       		'profile_validated' => date('Y-m-d'),
       		'email_last_validated' => date('Y-m-d'),
       		'email_verified_age' => 0,
       		'profile_valid_age' => 0,
       		
       		'uid' => $id,
       		));
       		
        return  date ('M d Y');

    }
    public function updateSession($data) {
    	/* after changing member vars, may need
    	to update $_SESSION['login'] as well
    	*/
    	if (empty($_SESSION['login'])){return;}
    	if ($data['uid'] != $_SESSION['login']['user_id']) {
    		return;
    	}
    	foreach ($data as $var=>$val){
    		if (!empty($_SESSION['login'][$var])){
    			$_SESSION['login'][$var] = $val;
    		}
    	}
    	
    }
     public function setEmailVerified ($id) {
        $sql = "Update `$this->memberTable` set profile_validated = NOW(),email_status='Y',email_last_validated = NOW()
            WHERE user_id = $id;";
            
        $stmt = $this->pdo->query($sql);
       
        return  date ('M d Y');

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
    
   public function getNoMember() {
   	return self::$no_member;
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
		//if status is Y but is already Y, status time won't get updated
		// without this intervention
		$set_time = ($ems == 'Y') ?
		', email_last_validated = NOW()' : '';
		
		
		$sql = "UPDATE `members_f2` SET email_status = '$ems' 
			$set_time
			WHERE user_id = '$uid';";
		if ($this->test){
			echo "Test mode.  SQL \n" . $sql . "\n";
			return;
		}
		try {
			$this->pdo->query($sql) ;
			$this->updateSession(array(
       		'email_status' => 'Y',
       		'email_last_validated' => date('Y-m-d'),
       		'uid' => $uid,
       		));
				return date('d M Y');
			
		} catch (Exception $e) {
			echo "PDO failed: " . $e->getMessage();
			echo "SQL: " . $sql  . BRNL . BRNL;;
			exit;
		}
		return false;
	}
	
	public function xoutUser ($uid) {
		$sql = "UPDATE `members_f2` SET status = 'X' 
			WHERE user_id = '$uid'";
		if (! $result = $this->pdo->query($sql) ){
			return false;
		}
		return true;
	}
	public function markContribute ($uid) {
		$sql = "UPDATE `members_f2` SET contributed = now() 
			WHERE user_id = '$uid'";
		if (! $result = $this->pdo->query($sql) ){
			return false;
		}
		return date('d M Y');
	}
	
	public function getUpdatedEmails ($since,$test=false) {
		// returns list of members with updated emails
		// since must be in Y-m-d
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		$member_status_set = Defs::getMemberInSet();
		$test_clause =  ($test)? 
		"AND test_status != '' " : "AND test_status = '' ";
		$list = array();
		$sql = "SELECT  * FROM `members_f2`
			WHERE status in ($member_status_set)
			$test_clause
			AND email_chg_date > '$since';";

		$result = $this->pdo->query($sql) -> fetchAll(\PDO::FETCH_ASSOC) ;
		foreach ($result as $row){
			$list[] = $this->enhanceData($row,self::$info_fields);
		}
		return $list;
	}
	public function getUpdatedProfiles($since,$test=false) {
		
		$member_status_set = Defs::getMemberInSet();
		$test_clause =  ($test)? 
		"AND test_status != '' " : "AND test_status = '' ";
		
		$list = array();
		$sql = "SELECT * FROM `members_f2`
			WHERE status in ($member_status_set)
			$test_clause
			AND profile_updated > '$since'
			AND joined < '$since';";

		
		$result = $this->pdo->query($sql) -> fetchAll(\PDO::FETCH_ASSOC) ;
		foreach ($result as $row){
			$list[] = $this->enhanceData($row);
		
		}
		return $list;
	}
	public function getDeceased ($since,$test=false) {
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		$member_status_set = Defs::getMemberInSet();
		$test_clause =  ($test)? 
		"AND test_status != '' " : "AND test_status = '' ";
		$list = array();
		$sql = "SELECT  * FROM `members_f2`
			WHERE status_updated > '$since'
			AND status like 'D'
			$test_clause
			";

		$result = $this->pdo->query($sql) -> fetchAll(\PDO::FETCH_ASSOC) ;
		foreach ($result as $row){
			$list[] = $this->enhanceData($row,self::$info_fields);
		}
		return $list;
	}
	
	public function getNewMembers($since,$test=false) {
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		//Get New Members
	$test_clause =  ($test)? 
		"AND test_status != '' " : "AND test_status = '' ";
	
	$member_status_set = Defs::getMemberInSet();

	$sql = "SELECT * FROM `members_f2`
	WHERE status in ($member_status_set)
	$test_clause
	
	AND joined > '$since'
	ORDER BY username;
	";
#echo "sql: $sql" . BRNL; exit;

	$stmt = $this->pdo->query($sql);
	$list = [];
   if ($result = $stmt->fetchAll(\PDO::FETCH_ASSOC) ){
		foreach ($result as $row){
			$list[] = $this->enhanceData($row,self::$info_fields);
		}
	}
	return $list;
	}

public function getNewLost($since,$test=false) {
		// if (! u\validateDate($since )){
// 			throw new Exception ("$since is not a valid sql date");
// 		}
		//Get New Members
		$test_clause =  ($test)? 
	"AND test_status != '' " : "AND test_status = '' ";
	$member_status_set = Defs::getMemberInSet();
	$sql = "SELECT * FROM `members_f2`
	WHERE status in ($member_status_set)
	AND email_status_time >= '$since'
	AND email_status like 'L%'
	AND previous_ems not like 'L%'
	$test_clause
	ORDER BY username;
	";// new lost
	
	$result = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
	$list=[];
	foreach ($result as $row){
			$list[] = $this->enhanceData($row,self::$info_fields);
		}
	return $list;
	}
		
	public function getOldLost($limit=0){
	// old lost
		$limit_clause =  (empty($limit)) ? '' : " LIMIT $limit "; 
		$test = false; #no need on this segment
		$test_clause =  ($test)? 
		"AND test_status != '' " : "AND test_status = '' ";
		
		$q = "SELECT * FROM members_f2
		WHERE status in ($this->member_status_set)
		$test_clause
		AND email_status_time < NOW() - INTERVAL 90 DAY
		AND email_status in ('LB','LA','LN')
		ORDER BY RAND()
		$limit_clause
			;
		";
		$result = $this->pdo->query($q)->fetchAll(\PDO::FETCH_ASSOC);
	
		foreach ($result as $row){
				$list[] = $this->enhanceData($row,self::$info_fields);
			}
		return $list;
	}
	public function getSendList($type,$tag='') {
	// gets list of members for bulk mail
		$fields = 
		'username,
		user_email,
		CONCAT(upw,user_id) as slink,
		profile_updated,
		no_bulk,
		user_id'
		;
		
		// these fields are retrieved in bulk_send by sequence, not name.
		$test = false;
		$test = ($type == 'test');
		$test_clause =  ($test) ? 
		"AND test_status != '' " : "AND test_status = '' ";
        
		$sql = "SELECT $fields FROM `members_f2` 
			WHERE status in ($this->member_status_set)
			AND email_status NOT LIKE 'L%'
			$test_clause
			";
		
		switch ($type) {
			case 'test':
				echo "Sending to test_status not empty" . BRNL;
				$sql .= $test_clause;
				break;
			case 'all':
				$sql .= '';
				echo "Sending to all valid emails." . BRNL;
				break;
			case 'bulk':
				$sql .= 'AND no_bulk = FALSE';
				echo "Sending to all on bulk mail." . BRNL;
				break;
			case 'nobulk':
				$sql .= 'AND no_bulk = TRUE';
				echo "Sending to all not getting bulk emails." . BRNL;
				break;
			case 'atag':
				if (strlen($tag) != 1 ){
					throw new Exception("Invalid tag $tag for sending to admin tags.");
				}
				$sql .= "AND admin_tag LIKE '%$tag%' ";
				echo "Sending to admin status with $tag." . BRNL;
				break;
			case 'aged_out':
				$sql = "SELECT $fields FROM `members_f2` 
			WHERE status in ($this->member_status_set)
			AND email_status = 'LA' ";
				echo "Sending to aged out emails." . BRNL;
				break;
			case 'contributors':
				$sql .= "AND status in ('MC','MA') ";
				echo "Sending to all valid emails." . BRNL;
				break;
			
			default: 
				throw new Exception ("Unknown bulk mal selection.");
			}
		
		$sql .= " ORDER BY status DESC ;";
		$list = $this->pdo->query($sql)->fetchAll();
		return $list;

	
	}
		
} #end class

