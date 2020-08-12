<?php
namespace DigitalMx\Flames;

#ini_set('display_errors', 1);
use DigitalMx\MyPDO;
use DigitalMx as u;
use DigitalMx\Flames\Definitions as Defs;


class Opportunities
{

	private $opp_count = 0;
	private $pdo;
	private $opp_list = array();
	private $level;
	private $templates;



	public function __construct ($container) {
		$this->pdo = $container['pdo'];
		$this->level = $_SESSION['level'];
		$this->templates = $container['templates'];
		$this->opp_list = $this->createOppList(); #indexed by id
		$this->opp_count = count($this->opp_list);
		file_put_contents(FileDefs::next_dir . '/tease_opps.txt',
		  	"<p>There are $this->opp_count employment opportunities listed.</p>");

		# echo $this->opp_count . " Opps retrieved" . BRNL; exit;
	}

	public function getOppList() {
		return $this->opp_list;
	}
	private function createOppList() {


		 $sql = "SELECT * , 'Active' as status FROM opportunities WHERE
					active = 1 and expired > NOW();";

		 $opp_table = $this->pdo -> query($sql) -> fetchAll(\PDO::FETCH_UNIQUE);

		 #u\echor($opp_table); exit;
		 return $opp_table;

	}
	public function getOppCount(){
		return $this->opp_count;
	}
	public function showOpp($oppid) {
	//u\echor($this->opp_list); exit;
		return $this->templates->render('opp',$this->getOpp($oppid));

	}
	public function linkOppList() {
		// returns list of opps linked to display
		// uses user_id to determine if user has editing privieges or not.

		$list = [];
		foreach ($this->opp_list as $id => $opp_row){
			$line = $button = '';

			$list[] = "<a href='/opp-manager.php?id=$id'>${opp_row['title']}</a> ";

			#u\echor($_SESSION,'session'); exit;

		}

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
			 $sql = "SELECT *,
			 if (expired < NOW(),'Expired',
			 	if (active = 1 ,'Active','Inactive')
			 	) as status

			 	 FROM opportunities
			 where id= $id";
			$row = $this->pdo->query($sql)->fetch();
			$row['credential'] = ($_SESSION['level'] > 7
				|| $_SESSION['login']['user_id'] == $row['user_id']) ? 1 : 0;
			$row['edit_button'] = ($row['credential']) ? $this->opp_edit_button($id) : '';
		}
		return $row;

	}

	public function opp_edit_button($id) {
		$buttonlink = "/opp-manager.php?id=$id&edit=true";
		$bname = ($id==0)? 'New Opportunity' : 'Edit';
		$button = "<button type='button' "
				.	"onClick=window.open('$buttonlink','oppe')>$bname</button>\n";
		return $button;

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
		$prep = u\prepPDO($post,$allowed,'id');

		if ($post['id'] == 0){
			 $sql = "INSERT into `opportunities` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']} );";
       	$stmt = $this->pdo->prepare($sql)->execute($prep['idata']);
       	$new_id = $this->pdo->lastInsertId();
      }
      else {
      	 $sql = "UPDATE `opportunities` SET ${prep['uset']} WHERE id = ${prep['ukey']} ;";
      	// u\echor($prep,$sql); exit;
       	$stmt = $this->pdo->prepare($sql)->execute($prep['udata']);

       }

 /**
including key field removes that field from udata and adds value to ukey

PREP:
   $prep = u\prepPDO ($post_data,allowed_list,'key_field_name');

INSERT:
		$sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']})";
		$sth = $pdo->prepare($sql);
		$sth->execute($prep['idata']);
		$new_id = $pdo->lastInsertId();

UPDATE:
		$sql = "UPDATE `Table` SET ${prep['uset']} WHERE id = $prep['ukey'];";
		$sth = $pdo->prepare($sql);
		$sth->execute($prep['udata']);

INSERT ON DUP UPDATE:
   		$sql = INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']} )
    			ON DUPLICATE KEY UPDATE ${prep['uset']};";
    	$sth = $pdo->prepare($sql);
		$sth->execute(array_merge($prep['udata'],$prep['idata']);
		$new_id = $pdo->lastInsertId();


**/


	return $new_id;
	}

}
