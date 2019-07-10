<?php
namespace Digitalmx\Flames;

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;

use Digitalmx as u;
use digitalmx\flames\Definitions as Defs;

class Signup {

private $pdo = null;
private $table_defs=[];
private $messenger;
private $take_action;


public function __construct() {
        
   
        
        $this->pdo = $ci->get('pdo');
        $this->messenger = $ci->get('messenger');
        $this->take_action = $ci->get('take_action');
        $this->member = $ci->get('member');
    }
    
    
public function prepForm($post) {
    // creates data for signup form
    
    
    return $prep;
}
            
public function addSignup ($post) {
   // set up  data for new form
    $prep = [];
    $prep['amd_where'] = '';
     $prep['amd_when'] = '';
    $prep['amd_dept'] = '';
    
    $prep['user_from'] = (isset($post['user_from']))?
     filter_var($post['user_from'],FILTER_SANITIZE_STRING)
     :'';
     $prep['user_amd'] = (isset($post['user_amd']))?
     filter_var($post['user_amd'],FILTER_SANITIZE_STRING)
     :'';
       
    $prep['name'] = (isset($post['name']))?
        filter_var($post['name'],FILTER_SANITIZE_STRING)
        :'';
    $prep['email'] = (isset($post['email']))?
        $post['email']
        :'';
    $prep['comment'] = (isset($post['comment']))?
        filter_var(stripslashes($post['comment']),FILTER_SANITIZE_STRING)
        :'';
  
   //   #fix up the multiple choice fields
//     if (isset($post['amd_where']) ){
//         $prep['amd_where'] = implode ('',$post['amd_where'] );
//     }
// 
//     if (isset ($post['amd_when'] )){
//         $prep['amd_when'] = implode ('',$post['amd_when'] );
//     }
//     
//     if (isset ( $post['amd_dept']) ){
//             $prep['amd_dept'] = implode ('',$post['amd_dept'] );
//     }


    
   //  $prep ['location_boxes'] =  u\buildCheckBoxSet ('amd_where',Definitions::$locations,$check=$prep['amd_where'],3);
//     $prep ['decade_boxes'] = u\buildCheckBoxSet
//             ('amd_when',Definitions::$decades,$check=$prep['amd_when'],3);
//     $prep ['department_boxes'] = u\buildCheckBoxSet
//             ('amd_dept',Definitions::$departments,$check=$prep['amd_dept'],3);
// 

    if ($_SERVER['REQUEST_METHOD'] == 'GET'){ #new blank form
        $data['prep'] = $prep;
        return $data;
    }
    
    #else {u\echoR($post); 
   # exit;
    #}
   

    $e=[];
    if (strpos(trim($post['name']),' ') === false ){$e[] = "Please enter your full name";}
    if(!filter_var($post['email'],FILTER_VALIDATE_EMAIL))
        {$e[] = "Email is not valid";}
    if(empty($post['user_amd']) )
        {$e[] = "You must specify what you did at AMD";}
    
    if(empty($post['user_from']) )
        {$e[] = "Please indicate where you currently live.";}
    
   if (!empty($e)){$error = implode('; ',$e);
        
         
         $data['error'] = $error;
         $data['prep'] = $prep;
        return $data;
   }
    
    $prep['ip'] = $_SERVER['REMOTE_ADDR'];
   
   
    $valid_fields = ['name','email','user_amd','user_from','ip','comment'];
    #add comment, make sure it's clean
    
   if (! $prepared =  u\prepareVars($prep,$valid_fields) ) {throw new Exception ("failed to prepare vars: " . print_r($prep,true) ) ;}
    
    $fieldlist = $prepared['field_list'];
    $data = $prepared['data'];
      

    
    $a=[];
      foreach ($fieldlist as $f){
        $a[] = ":$f";
      }
    $fields = implode(', ',$fieldlist);
    $values = implode (', ',$a);
      
    $table = $this->table_defs['signupTable'];
    
    
   $sql = "INSERT into `$table` ($fields) VALUES ($values);";
    

   #echo $sql . BRNL; u\echoR($data);

   if (! $stmt = $this->pdo->prepare($sql) ){
     throw new Exception ("pdo prepare failed. ");
    }
    if (! $stmt->execute($data) ){
        throw new Exception ("pdo execute failed. ");
    }
    $id = $this->pdo->lastInsertId();
    
    // success
    $rstring = $this->take_action->record_action('suv',$id);
    
    $data['email'] = $post['email'];
    $data['verify_link'] = SITE_URL . "/action/suv/$rstring";
    
    $this->messenger->sendit('new_signup', $data);
    $data['success'] = "Congratulations";
    return $data;
 
 }

     public function admin_signups($post){
        // gets sginup and sends to signup_admin.html
        // return goes to admin_signups_post($post)
    
        $table = $this->table_defs['signupTable'];
        
        if (!empty($post)){
        // get the ids marked accepted
            $idarr = $post['accept'];
            $idlist = join(', ',$idarr);
            if (!empty($idlist)){
                $sql = "SELECT * from `signups`  where id in ($idlist)";
                 echo $sql;
                $newmembers = $this->pdo->query($sql)->fetchAll();
                // now for each accepted record, add to members
                // and send welcome message.
                foreach ($newmembers as $row){
                    //prepare data array and send to member->addMember()
                    $id = $row['id'];
                    $nm = []; #new member array
                    $nm['username'] = $row['name'];
                    $nm['user_amd'] = $row['user_amd'];
                    $nm['user_from'] = $row['user_from'];
                    $nm['user_email'] = $row['email'];
                    $nm['admin_note'] = $row['comment'];
                    $nm['email_status'] = 'Y';
                    $nm['status'] = 'M';
                    
                   
                
                #u\echoR($nm,'Sending to addMember');
                
                
                    $this->member->addMember($nm);
                
                    $nmsql = "UPDATE `signups` set status = 'X' where id = $id";
                    $this->pdo->query($nmsql);
                
                
                } #done each member 
            } #done member adds

          
        } #end post    
                
                
        $sql = "SELECT * from `$table`";
        $signups = $this->pdo->query($sql)->fetchAll();
       //$signups_preped = [];
        // foreach ($signups as $row){
//             $row ['locations'] = decompress( $row['amd_where'],Definitions::$locations );
//             $row ['decades'] = decompress ($row ['amd_when'], Definitions::$decades );
//             $row ['departments'] = decompress ($row['amd_dept'], Definitions::$departments);
//             
//             $signups_preped[] = $row;
//             
//         }
       # u\echoR($signups_preped);
        $data['signups'] = $signups;
        
        return $data;
    
    }
 



}
