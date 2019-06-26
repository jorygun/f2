<?php
namespace Digitalmx\Flames;

use Digitalmx\Lib as u;

/*
    class to use random tags to trigger actions securly
*/


class TakeAction  {


private $table_defs = [];
private $pdo = null;
private $messenger;
private $news;


public function __construct(\Slim\Container $ci) {;
        $settings = $ci->get('settings');
        $this->table_defs  = $settings['appvars']['DB_TABLES'];
        $this->pdo = $ci->get('pdo');
        $this->member_controller = $ci->get('member_controller');
        $this->member = $ci->get('member');
        $this->messenger = $ci->get('messenger');
        $this->news = $ci->get('news');
        
}
    
    
public function record_action($action,$id,$lifetime='24') {
    #lifetime in hours
    $rstring = u\random_strings(32);
    $xdate = new \DateTime();
    $xdate->add(new \DateInterval('PT'. $lifetime . 'H'));

    $expiration = $xdate->format( 'Y-m-d H:m:s');
    $sql = "INSERT into `actions` (tag,action,id,expires) VALUES ('$rstring','$action',$id,'$expiration')";
   # echo $sql;
    $this->pdo->query($sql);
    return $rstring;
}

public function take_action ($tag) {
    $sql = "SELECT action, id from `actions` where tag = '$tag' #and expires < now()"; 
   if (! $result = $this->pdo->query($sql)->fetch() ) {
    return [];
    }
   # u\echoR($result);
    
   return array($result['action'],$result['id']);
    
}    
    
public function invokev ($args){
    $method =  $args['v'];

 #u\echoR($args,'invoked args');

    if (method_exists($this, $method)){
        return $this->$method($args);
    }
    else { throw new Exception ("Bad function to invokev");}
    
}

// confirm profile
 public function c ($args)
{
    $result = "OK";
    $id = $args['id'];
    $result = $this->member_controller->verifyProfile($id);
    
    return $result;
    
}

 public function v ($args)
{
    // verify usr email
   
    $id = $args['id'];
    $result = $this->member_controller->verifyEmail($id);
    
    return $result;
    
}

 
// send login to email
 public function sli ($args)
{
    // id may be user_id or an email
    $id = $args['id'];
   # echo "ID in sli: $id" . BRNL;
    $result = $this->member->sendLogin($id);
    return $result;
}

 public function sch ($args)
{
    // schedule an artilce
   
    $id = $args['id'];
    $result = $this->news->setArticleStatus($id,'S');
    
    return $result;
    
}

 public function schu ($args)
{
    // unschedule an artilce
   
    $id = $args['id'];
    $result = $this->news->setArticleStatus($id,'N');
    
    return $result;
    
}

  public function suv ($args)
{
    // verify signup email
    $rid = $args['id'];
    list ($action,$id)  = $this->take_action($rid);
    if (empty($action)){ #nothing return
        $result =  "Tag not valid";
    }
    else {
        #echo "updating $id in $action". BRNL;
        $sql = "UPDATE `signups` set status = 'V' where id = $id;";
        if (!$this->pdo->query($sql) ) {
            throw new Exception ("Failed to update signup record");
        }
        $sql = "Select name,email from `signups` where id = $id";
        $data = $this->pdo->query($sql)->fetch();
        
        $this->messenger->sendit('signup_verified',$data,true);
        $result = 'OK.  Your email has been verified.';
    }

    return $result;
    
}






} #end clss
