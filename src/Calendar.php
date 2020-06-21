<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;
	use DigitalMx\MyPDO;


	// script reads the alendar file and produces two outputs:
// an html table of events, and a plain text list of event highlights.
// If run with parameter u (calendar.php?u=1) then lets you add new items.

#list ($calendar_h, $calendar_t) = get_events($event_file);
/*

1.  display list of all calendar items for update form
2.  update individual item from list or add new
3. generate calendar reports (html and teaser)

*/

class Calendar {


    private $pdo;
    private $item_list = array();
     #list(id,datetime,$event,$city,$location,$contact,$info)
    private static $empty_item = array(
    	'id' => 0,
    	'datetime' => '',
    	'event' => '',
    	'city' => '',
    	'location' => '',
    	'contact' => '',
    	'info' => '',
    	'contributor_id' => '',
    	'map_link' => '',
    	'link' => '',

    	);


	public function __construct ($container) {
		$this->pdo = $container['pdo'];

	}



	public function getItems($select='') {
		$items = [];
		$citems = [];
		$spec = [];

		if ($select != 'new'){
			$whereid =  ($select) ? " AND e.id = $select " : '';
			$sql = "SELECT e.*,
				DATE_FORMAT(e.datetime,'%M %d, %Y') as edate,
				DATE_FORMAT(e.datetime,'%H:%i') as etime,
				m.username,m.user_email
				FROM `events` e
				LEFT JOIN `members_f2` m  on m.user_id = e.contributor_id
				WHERE e.datetime >= NOW() $whereid
				ORDER BY datetime" ;
	//	echo $sql . BRNL;
    		if (!$items = $this->pdo -> query($sql)->fetchAll() ) {
    			return $citems;
    		}

			foreach ($items as $row){
				$id = $row['id'];
				foreach ($row as $var => $val){
					$spec[$var] = $val;
				}
				//$spec['edit_link'] = "<a href='/calendar.php?edit=${row['id']}'>Edit</a>";
				$spec['edit_link'] = u\makeButton('loc','Edit',"/calendar.php?edit=$id");
				$spec['loc_link'] = ($row['map_link']) ?
					"<a href='${row['map_link']}>'>Map</a>" : '' ;

				$spec['linked_contact'] = u\makeLinks($spec['contact']);
				$spec['linked_info'] = u\makeLinks($spec['info']);
				$citems[] = $spec;
			}

		} else {
			$citems[] = self::$empty_item;
		}
//u\echor ($citems);

    	return $citems;

	}





	public function saveEvent($post){

		//u\echor($post, 'POST') . BRNL;

		if (! $ctime = strtotime($post['datetime']) ){
			echo "<script>
				alert('Date ${post['datetime']} not recognized');
				history.back();
				</script>
			";
		}
		if ($ctime < time()){
			echo "<script>
				alert('You cannot enter an event for a past date');
				history.back();
				</script>
			";
		}
		$post['datetime'] =  date('Y-m-d H:i', $ctime);

		$id = $post['id'];
		foreach ($post as $var => $val) {
			$despec[$var] = $val;
		}

		$prep = u\prepPDO($despec,array_keys(self::$empty_item),'id');
		 if ($despec['id'] == 0){ #new entry

			$sql = "INSERT into `events` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']} );";
			//u\echor($prep,$sql);
			 $stmt = $this->pdo->prepare($sql);

			 $stmt->execute($prep['idata']);
			 $new_id = $this->pdo->lastInsertId();

		} else { #update
			$prep = u\prepPDO($despec,array_keys(self::$empty_item),'id');
			$sql = "UPDATE `events` SET ${prep['uset']} WHERE id = ${prep['ukey']};";
			//	u\echor($prep,$sql);
			 $stmt = $this->pdo->prepare($sql);

			 $stmt->execute($prep['udata']);
			$new_id = $id;
		}
		return $new_id;
}

	public function display_calendar() {
		$data['citems'] = $this->getItems();
		$data['credential'] = false;

		return $container['templates']->render('calendar',$data);
	}

}
//EOF;




