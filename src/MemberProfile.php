<?php
namespace Digitalmx\Flames;

use digitalmx as u;
use digitalmx\flames\Member;
use digitalmx\MyPDO;



class MemberProfile {

    // needed classes
    private $member; #instance of Member lass
    private $asset_controller;
    
// data for return
    private $info='';
    private $error='';
    private $credential = false;
  private $records = 0;
    
    private $member_data = [];
    private $member_id;
    private $table_defs = [];
    private $pdo;

public function __construct() {
    		$this->member = new Member();
        $this->pdo = MyPDO::instance();
    }
    
    /// routines to prepare data for forms ///

private function getMemberData($tag) {
    
    $md = $this->member->getMemberData($tag);
    $this->member_data = $md['data'];
    $this->error = $md['error'];
    $this->info = $md['info'];
    $this->records = $md['records'];
     
    $id= $this->member_data['user_id'] ?? 0;
    return $id;
}

 public function getProfileData($tag) {
 
    $id = $this->getMemberData($tag);
    if ($this->records == 0 or !empty($this->error)) {
          return $this->getReturn(0,[]);
    }
    $prep = $this->member_data;

# u\echoR ($prep ,'profiledata');
        $loginid = $_SESSION['login_user']['user_id'];
        $this->credential = ($id == $loginid )
          || ($_SESSION['login_user']['seclevel']>7);
          
       $prep = $this->member_data;
        

        $prep ['locations'] = u\decompress(
            $prep['amd_where'],Definitions::$locations );
        $prep ['decades'] = u\decompress (
            $prep ['amd_when'], Definitions::$decades );
        $prep ['departments'] = u\decompress (
            $prep['amd_dept'], Definitions::$departments);
        $prep ['profile_date'] = u\make_date('CD',$this->member_data['profile_verified']);
         $prep ['linkedinlink'] = u\linkHref($this->member_data['linkedin'],'Me on Linked In');
        $prep ['photo'] =  u\createPhotoLink( "/assets/users/{$id}.jpg");
        
       
        return $this->getReturn($this->records,$prep);
    }
    
 private function getReturn ($records,$data) {
    #mnenomic rdc  
    $r['data'] = $data;
    $r['info'] = $this->info;
    $r['error'] = $this->error;
    $r['records'] = $records;
    $r['credential'] = $this->credential;

   
    return $r;
 }

 


public function getMemberEditData ($id){
     $id = $this->getMemberData($id);
    if ($this->records == 0 or !empty($this->error)) {
          return $this->getReturn(0,[]);
    }
    $prep = $this->member_data;

    $prep['hide_checked'] =  ($prep['email_hide'] == 1)? "checked check='checked' ":'';
	$prep['no_bulk_checked'] = ($prep['no_bulk'] == 1)? "checked check='checked' ":'';
	
	// set the ems options that make sense for edit
	$ems_for_edit = array();
	foreach (['Y','LB','E1'] as $s){    
	    $ems_for_edit[$s]  = Definitions::$ems_names[$s];
	}
	$prep['ems_options'] = 
	    u\buildOptions($ems_for_edit,$prep['email_status']);
	    
	        
	    
    return $this->getReturn($this->records,$prep);
    



}
public function getProfileEditData ($tag) {
  $id = $this->getMemberData($tag);
    if ($this->records == 0 or !empty($this->error)) {
          return $this->getReturn(0,[]);
    }
    $prep = $this->member_data;

	
    $hide_checked =  ($prep['email_hide'] == 1)? "checked check='checked' ":'';
	$no_bulk_checked = ($prep['no_bulk'] == 1)? "checked check='checked' ":'';
    
      $photo = u\createPhotoLink( "/assets/users/{$id}.jpg");
      $teeny_photo = "(no photo)";
      if (!empty($photo)){
        $teeny_photo =  "
            <img src='$photo' class='teenypic'><br>
            Remove <input type='checkbox' name='remove_photo' value='1'>
            ";
        }
        
        $prep ['location_boxes'] =  u\buildCheckBoxSet ('amd_where',Definitions::$locations,$check=$prep['amd_where'],3);
         $prep ['decade_boxes'] = u\buildCheckBoxSet
            ('amd_when',Definitions::$decades,$check=$prep['amd_when'],3);
         $prep ['department_boxes'] = u\buildCheckBoxSet
            ('amd_dept',Definitions::$departments,$check=$prep['amd_dept'],3);
         $prep ['email_status_name'] = Definitions::$ems_names[$prep['email_status']];
        
         $prep ['joined'] = u\formatDate('CD',$prep['join_date']);
    
         $prep ['hide_checked'] = $hide_checked;
         $prep ['no_bulk_checked'] = $no_bulk_checked;
         $prep ['photo'] = $photo;
         $prep ['teeny_photo'] = $teeny_photo;
    
      
        return $this->getReturn (1,$prep);
    }
    
    
    
    ///  routines to prepare data for storing ///
    
    public function updateMember ($post) {
        $this->updateMemberData($post);
 
    }
    
    public function updateMemberData($post) {
      if (! $user_id = $post['user_id'] ){
        throw new Exception ("Invalid array in _POST for updateProfile");
      }

    
      echo "starting member data update in member controller.  " . BRNL; 
    
    // get the current member data ... why??
   # $current = $this->member->getMemberData($user_id);
   
       if (isset($post['deceased'] )) {
        $post['status'] = 'D';
        $post['email_status'] = 'LD';
    }
    if (isset($post['validate'] )) {
        $post['email_status'] = 'Y';
       
    }
    if (isset($post['contributed'] )) {
        $post['contributed'] = date('Y-m-d');
    }
    if (isset($post['bounces'] )) {
        $post['email_status'] = 'LB';
       
    }
   
    $post['profile_verified'] = date('Y-m-d');
    $post['email_status'] = 'Y';
    
    
    
  
     
    
    // fix check box fields
        if (isset($post['hide_present'])){
            if (isset($post['set_email_hide']) ){$post['email_hide'] = 1;}
            else {$post['email_hide'] = 0;}
        }
         if (isset($post['bulk_present'])){
            if (isset($post['set_no_bulk'])){$post['no_bulk'] = 1;}
            else {$post['no_bulk'] = 0;}
        }
   
     if (isset($post['remove_photo'])){
        unlink (SITEPATH . "/assets/users/{$user_id}.jpg");
    }
    

     
    #fix up the multiple choice fields
		if (isset($post['amd_where']) ){
			$post['amd_where'] = implode ('',$post['amd_where'] );
		}

		if (isset ($post['amd_when'] )){
			$post['amd_when'] = implode ('',$post['amd_when'] );
		}
		
		if (isset ( $post['amd_dept']) ){
				$post['amd_dept'] = implode ('',$post['amd_dept'] );
		}
		

   // if a profile photo, upload it
   if (!empty($_FILES['profile_photo']['name'] )){
       try {
       echo "photo upload"; 
         list ($filename,$mime,$size) = $this->asset_controller->install_file($user_id,'profile_photo');
        # echo "$user_id > $filename,$size";exit;
         }
        catch (Exception $e){echo "Error uploading photo: " . $e->getMessage(); exit;}
    }
  

 //     
    // save this uid to return to profile page
    $_SESSION['last_profile'] = "/profile/$user_id";
    
   
   # u\echoR($post);
  
    $this->member->updateMember($post);  
    
}
   
  
 
 private function obscureEmail($email)
    {
        preg_match('/^(..?.?.?).*\@/', $email, $m); #first up to 4 before @
    #   echo "first: ";print_r ($m);echo "\n";
        $obscure    =   "$m[1]---";
        preg_match('/.*?\@(..?.?.?).*(\.\w+)$/', $email, $m); #first up to 4 after @ + domain
    #       echo "second: ";print_r ($m);echo "\n";
        $obscure    .=  "@$m[1]---$m[2]";
        return $obscure;
    }
 
   
    
} #end class
 

