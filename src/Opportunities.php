<?php
namespace digitalmx\flames;

#ini_set('display_errors', 1);
use digitalmx\MyPDO;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;


class Opportunities 
{

	private $opp_count = 0;
	private $pdo;
	private $opp_list = array();
	private $level=0;


	public function __construct ($level=0) {
		$this->pdo = MyPDO::instance();
		// need to pass level for menu because session not set yet.
		// otherwise, use the session level
		$this->level = ($level > 0) ? $level : $_SESSION['level'] ?? 0;
		$this->opp_list = $this->getOppList();
		 $this->opp_count = count($this->opp_list);
		# echo $this->opp_count . " Opps retrieved" . BRNL;
	}
	
			
	public function getOppList() {
		
		
		 $sql = "SELECT id,title,location,owner,user_id FROM opportunities WHERE
					active = 1 and expired > NOW();";

		 $opp_table = $this->pdo -> query($sql) -> fetchAll(\PDO::FETCH_ASSOC);
		 return $opp_table;

	}
	public function getOppCount(){
		return $this->opp_count;
	}
	
	public function linkOppList() {
		// returns list of opps linked to display
		// uses user_id to determine if user has editing privieges or not.
		
		$list = [];
		$level = $this->level;
		foreach ($this->opp_list as $opp_row){
			$line = $button = '';
			$id = $opp_row['id'];
			$line = "<a href='/show_opp.php?id=$id'>${opp_row['title']}</a> ";
			#u\echor($_SESSION,'session'); exit;
			
			if ($level > 7
				or (!empty($_SESSION['login']) && $_SESSION['login']['user_id'] == $opp_row['user_id'])
			){
				$buttonlink = "/show_opp.php?id=$id&edit=true";
				$button = "<button type='button' "
				.	"onClick=window.open('$buttonlink')>Edit</button>\n";
			}
			
				
			$list[] = $line . $button;
		}
		if ($level > 0) {$list[] = "<a href='/show_opp.php?id=0&edit=true'> Create New Opp</a> ($level) ";}
		#u\echor($list,'list'); exit;
		return $list;
	}
	
	// public function newOpp(){
// 		
// 		return $row;
// 	}
	
	public function getOpp($id) {
		if ($id == 0){
			$dt = new \DateTime();
			$dt->add(new \DateInterval('P90D'));
			$row = array (
				'id' => 0,
				'user_id' => $_SESSION['login']['user_id'],
				'expired' => $dt->format('M d, Y'),
				'owner' => $_SESSION['login']['username'],
				'created' => date('Y-m-d'),
				'owner_email' => $_SESSION['login']['user_email'],
				'title' => '',
				'description' => '',
				'link'=>'',
				'location' => '',
				
			);
		}
		else {
			$sql = "Select * from `opportunities` where id= $id";
			$row = $this->pdo->query($sql)->fetch();
		}
		return $row;
	
	}
	public function postOpp ($post) {
		if (empty($post['title'])){
			throw new Exception ("No data submitted for opportunity");
		}
		
		$new_id = $post['id'];
		$expires = strtotime($post['expired']);
		$post['created'] = date('Y-m-d');
		$post['active'] = 1;
		$post['user_id'] = $_SESSION['login']['user_id'];
		$xdt = new \DateTime(date('M d, Y',$expires));
	#echo "x time interpreted as " . $xdt->format ('M d Y') . BRNL;
		
		$x90 = new \DateTime();
		$x90 ->add (new \DateInterval('P90D'));
#	echo "+90 interpreted as " . $x90->format('M d Y') . BRNL;
	
		if ($xdt > $x90){$xdt = $x90;} #max 90 days
		$post['expired'] = $xdt->format('Y-m-d');
		
		$allowed = array(); #accept all fields
		$prep = u\pdoPrep($post,$allowed,'id');
		
		if ($post['id'] == 0){
			 $sql = "INSERT into `opportunities` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       	$stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       	$new_id = $this->pdo->lastInsertId();
      }
      else {
      	 $sql = "UPDATE `opportunities` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       	$stmt = $this->pdo->prepare($sql)->execute($prep['data']);

       }

 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/

	
	return $new_id;
	}

}
