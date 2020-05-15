<?php
namespace digitalmx\flames;

	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\FileDefs;
	
	
	//
// $ifields = array(
// 	'title',
// 	'source',
// 	'source_date',
// 	'url',
//    'link_title',
// 	'topic; (was type)
// 	'date_published',
// 	'status',
// 	'content',
//    'contributor_id',
//		'asset_list'
//  'ed_comment'
//  'use_me' (tinyint) (priority)
//	'take_comments (T/F)
//	'take_votes (T/F)
//	comment_count ?
//	net_votes ?

// );
// does not include id, , date_entered,date_edited


	
class News {

	private $pdo;
	private $member;
	public $sections;
	public $topics;
	
	public static $queueOptions = array ('No','Low','Medium','High');
	
	function __construct() {

		$this->pdo = MyPDO::instance();
		$this->member = new Member();
		$this->sections = $this->getSections();
		$this->topics = $this->getTopics();
	}

	
	public function saveArticle($post) {
		try { $adata = $this->checkArticle ($post);
		} catch (Exception $e) {
			echo "Article data error." . BRNL . $e->getMessage();
			 echo "<button type='button'  onclick = 'history.back();'>back</button>" . BRNL;
			exit;
		}
	$id = $post['id'];
	$prep = u\pdoPrep($adata,[],'id');
	u\echor ($prep , 'PDO data');
	
 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
	if ($id == 0){
		$sql = "INSERT into `news_items` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $id = $this->pdo->lastInsertId();
	
	}
	else {
		 $sql = "UPDATE `news_items` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       }
   echo "Saved to $id" . BRNL;
   return $id;
   }
   
   
	private function checkArticle ($post) {
		//u\echor($post, 'Incoming');
		
	 	if ( ! $post['title'] ) throw new Exception ("No Title Specified for Item"); 
		 // title case
    	$adata['title'] = ucwords($post['title']);
		$adata['id'] = $id = $post['id']?? 0;
		$adata['topic'] = $post['topic'];
		if (empty($adata['topic'])){
			throw new Exception ("Article must have a topic");
		}
	
	$adata['take_votes'] =  (empty($post['take_votes']) ) ? 0 : 1 ;
	$adata['take_comments'] = (empty($post['take_comments']) ) ? 0 : 1 ;
   
    // set contributor id if one not set yet and
		// valid member name is in the contributo name field
		// no contributor (=0) is not an error
		if (!empty($post['contributor_id']) && $id > 0 ){
			$adata['contributor_id'] = $post['contributor_id'];
		} elseif (!empty ($post['contributor'] )) {
			list ($contributor, $adata['contributor_id'] ) 
				= $this->member->getMemberId($post['contributor']) ;
				if (empty($contributor)){
					u\echoalert("No contributor found");
					$adata['contributor_id'] = 0;  #no contributor defined
				}
		} else {
		u\echoalert("No contributor listed");
			$adata['contributor_id'] = 0;
		}
   if (!empty($adata['asset_id'] = trim($post['asset_id'])) ){
   	if (! preg_match('/^\d{4,5}$/' ,$adata['asset_id'])) {
   		throw new Exception ("Non-integer in asset_id");
   	}
   }
   
	if (!empty($adata['asset_list'] = trim($post['asset_list']))){
		foreach (preg_split('/\s+/', $adata['asset_list'] ) as $aid){
			if (! preg_match('/^\d{4,5}$/',$aid) ){
				throw new Exception ("Non-integer in asset_list: $aid");
			}
		}
   }
   // add /ed to editorial comment if it's not already commented
    if (! empty( $post['ed_comment']) ){
    	$adata['ed_comment'] = $post['ed_comment'];
		if (! preg_match('/.*\n--\/[\w ]+\s*$/s',$post['ed_comment']) ){
      	$commenter_name = $adata['contributor'] ?? $_SESSION['login']['username'];
        	 $adata['ed_comment'] .= "\n--/$commenter_name\n";
      }
   }
   
   $status = $post['status'];
     if (! in_array($status,array_keys(Defs::$news_status))){
     	throw new Exception ("Unknown status code $status");
     }
     
     $adata['status'] = $status;
     
     // use use-me field as numeric priority
     // convert queue text to priority
     
    // echo "Looking for queue " . $post['queue'] . BRNL;
     $pri = array_search( $post['queue'] , self::$queueOptions ) ?? 0;
     if ($pri < 0 || $pri > 4 ){throw new Exception ("priority out of range");}
     $adata['use_me'] = $pri;
     //echo "setting use me to $pri type " . gettype($pri) . BRNL;
     // not set from form post: date_published, comment_count, net_votes

	//u\echor($adata, 'After check');

		return $adata;
	}
	
	public function getNewArticle () {
		$adata = array(
			'id' => 0,
			'title' => '',
		'source' => '',
		'source_date' => '',
		'url' => '',
		'link_title' => '',
		'topic' => '',
		'date_published'  => '',
		'status'=> 'N',
		'content' => '',
		'contributor_id' => $_SESSION['login']['user_id'],
		'contributor' => $_SESSION['login']['username'],
		'asset_id' => '',
		'asset_list'  => '',
		'ed_comment'  => '',
		'use_me'  => '0',
		'take_comments' => 0,
		'take_votes'  => 0, 
		'date_entered' => date('Y-m-d'),
 	);
	return $adata;
}
	public function getQueueOptions($ind=''){
		// if called with an index no, returns the associated name
		// otherwise returns the list
		if (!empty($ind) and is_integer($ind) ) return self::$queueOptions[$ind];
	
		return self::$queueOptions;
	}
	
	public function getArticle($id,$votes=false) {
		// if votes is true, do innerjoin to get vote and comment counts
		if ($id == 0) {
			$adata =  $this->getNewArticle();
		} elsif ($votes) {
			$sql = "SELECT * from `news_items` n ,
				count(c.id) as comment_count,
				count(v.id) as total_votes,
				sum(v.votes) as net_votes
		
				INNER JOIN comments c on n.id = c.id,
				votes v on v.id = n .id 
				where id = $id";
		} else {
			$sql = "SELECT * from `news_items` where id = $id";
			$adata = $this->pdo->query($sql)->fetch();
			$adata['contributor'] = $this->member->getMemberName($adata['contributor_id']);
		}
		return $adata;
	}
	
	

public function getSections() {
	
	$sql = 'SELECT section,section_name from news_sections ORDER BY section_sequence';
	$sections = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	return $sections;
}

public function getTopics($access=''){
// use access = '' for all topics including deprecated
// access = 'A' for all current topics 
// access = 'U' for user accessible topics

	$sql = "SELECT `topic`,`topic_name` from `news_topics` T 
		INNER JOIN news_sections  S
		ON T.section = S.section ";
	if ($access == 'A'){ $sql .= " WHERE `access` in ('A','U') "; }
	elseif ($access == 'U'){ $sql .= " WHERE `access` = 'U' "; }
	$sql .= " ORDER BY S.section_sequence, T.topic ";
	
	$topics = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	return $topics;
}

public function getSectionForTopic ($topic){
	
	$sql = "SELECT section from `news_topics` WHERE topic = '$topic'";
	$section = $this->pdo->query($sql)->fetchColumn();
	return $section;
}

public function getSectionName($section){
	return $this->sections[$section];
}
public function getTopicName($topic) {
	return $this->topics[$topic];
}

###############
	public function showVotePanel($item_id,$user_id){
   		
   
		$upok = "enabled";
		$downok = "enabled";
		$vote_rank = 0;
		
	
		#first, get current vote total for the article
		$sql = "SELECT count(*)  FROM `votes` 
		WHERE news_fk = $item_id AND vote_rank != 0;";
		#exclude votes chnaged to meh (0)
		if (! $total_votes = $this->pdo->query($sql)->fetchColumn() ){
			$total_votes = 0;
		}
		#now get net votes for the article
		$sql = "SELECT sum(vote_rank) FROM `votes` 
		WHERE news_fk = $item_id ";
		
		$net_votes = sprintf('%+d',$this->pdo->query($sql) -> fetchColumn() ?? 0);
		
		#now lastest vote from this user. 
		$sql ="SELECT vote_rank  FROM votes WHERE news_fk = $item_id and user_id_fk = $user_id;";
		#vote_rank is the last votes from this user.  Should only be 1, 0, or -1.
		if (! $user_vote = $this->pdo->query($sql)->fetchColumn() ){
			$user_vote = 0;
		}
		
		
	$button_div = "<div class='btn-votes'>
	<p><b>Interesting? or Nah?</b> </p>";

		if($user_vote < 0 ) {
			$button_div .= <<<EOT
			<button type='button' title='Up' class='up' onClick='addVote($item_id,"up");' >
			<img src='/graphics/up.png' />
			</button>
			
			<button type='button' title='Down' class='down' onClick='addVote($item_id,"down-off");'  >
			<img src='/graphics/down-on.png' />
			</button>
EOT;
		}
		elseif($user_vote > 0) {
			$button_div.= <<<EOT
			<button type='button' title='Up' class='up' onClick='addVote($item_id,"up-off");' >
			<img src='/graphics/up-on.png' />
			</button>
			<button type='button' title='Down' class='down' onClick='addVote($item_id,"down");'  >
			<img src='/graphics/down.png' />
			</button>				
EOT;
		}
		else { #if not found or 0
			$button_div .= <<<EOT
			<button type='button' title='Up' class='up' onClick='addVote($item_id,"up");' >
			<img src='/graphics/up.png' />
			</button>
			<button type='button' title='Down' class='down' onClick='addVote($item_id,"down");'  >
			<img src='/graphics/down.png' />
			</button>
EOT;
		}
		
	$button_div .= "</div>\n";	
		
		/* code for the voting buttons */

	
	
	$voting_panelb = <<<EOT
		<div id="vpanel-$item_id" class='vpanel'>
			$button_div
			<div class="net-votes">
			<p>Currently</p>
			
			<p style='margin-top:1em;'>$net_votes of $total_votes</p>
			</div>
			
		</div>
EOT;
		return $voting_panelb;
}

private function saveVote($item_id,$user_id,$vote=''){
	#echo "recording $item_id, $user_id, $vote" . BRNL;
	
	if(empty($item_id) ){return;}
		
	switch ($vote){
		case 'up':
			$votevalue=1;
			#$update_sql ="UPDATE assets SET votes = votes+1 WHERE id='$pid'";
			break;
		case 'down':
			$votevalue = -1;
			#$update_sql ="UPDATE assets SET votes = votes-1 WHERE id='$pid'";
			break;
			
		case 'up-off':
		case 'down-off':
		case 'meh':	
		default:
			$votevalue = 0; #meh vote will set to 0;
			#$update_sql = '';
	}
	if (true or !empty($votevalue)){
	// update the user-asset vote table
		$sql = "INSERT INTO `votes` (user_id_fk,news_fk,vote_rank)
			VALUES ('$user_id','$item_id', $votevalue)
		ON DUPLICATE KEY UPDATE
		vote_rank = $votevalue "   ;

	#pecho ($sql);
		if (! $this->pdo->query($sql) ){
			throw new Exception ("db insert/update failed ");
		}
	}
		
}
    public function news_head($title,$tcomment=''){
        $hcode = "<div class='divh2'>$title\n";
        if ($tcomment != ''){$hcode .= "<br><span class='comment'>$tcomment</span>";}
        $hcode .= "</div>\n";
        return $hcode;
    }

    public function news_subhead($title){
        $hcode = "<h3>" . u\special($title) . "</h3>\n";
        return $hcode;
    }

	public function incrementReads($issue){
		#echo "sstart increment reads";

		if ($_SESSION['level']>7){ return;} #don't count admin access
		$sql1 = "UPDATE read_table SET read_cnt = read_cnt + 1 WHERE issue = $issue;";
		$sql2 = "INSERT INTO read_table SET read_cnt = 1 , issue = $issue;";
#INSERT INTO table (id, name, age) VALUES(1, "A", 19) ON DUPLICATE KEY UPDATE    name="A", age=19
		$sql3 = "INSERT INTO read_table (issue,read_cnt) VALUES ($issue,1)
		    ON DUPLICATE KEY UPDATE read_cnt = read_cnt + 1";
		$this->pdo->query($sql3);
		return 1;
	}


public function getCommentCount($id){
       $pdo = MyPDO::instance();
    $sql = "SELECT count(*) from `comments` where on_db = 'news_items' and item_id = $id;";
    $nRows = $pdo->query($sql)->fetchColumn();
    return $nRows;
}

function getCommenters($aid) {
/*
	Script to retrieve the names of commentors on an article
	Call with article_id = nnn
	Returns text string
*/



	$sql = "SELECT user_id FROM `comments` WHERE on_db = 'news_items' AND item_id = $aid;";

	$sql3 = "SELECT DISTINCT m.username FROM `members_f2` m
		LEFT JOIN `comments` c ON
		c.user_id = m.user_id AND c.on_db = 'news_items'
		WHERE c.item_id = $aid

		;";
#echo $sql . BRNL;

	$namec = $this->pdo -> query($sql3);
	$commenters_list = $namec -> fetchALL(\PDO::FETCH_COLUMN);
	if (empty ($commenters_list)){$commenters = "No comments yet.  Be the first.";}
	else{
	    $commenters = 'Comments from ';
	    $ccount = count($commenters_list);
	    $mcount = 0;
	    if ($ccount > 8){
	        $commenters_list = array_slice($commenters_list,0,8);
	        $mcount = $ccount - 8;
	    }

		$commenters .= implode(', ',$commenters_list);
		if ($mcount){ $commenters .= "and $mcount more.";}
	}

	$commenters .= "\n";

	return $commenters;
}


public function recordVote ($item_id,$user_id,$vote='') {
	if (! in_array($vote,['up','down','meh','down-off','up-off']) ){
		throw new Exception ("Invalid vote $vote");
	}
	if (empty($item_id) or empty($user_id)){
		throw new Exception ("Invalid user or item id");
	}
	
	$this->saveVote($item_id,$user_id,$vote);
	return $this->showVotePanel($item_id,$user_id);
}

}
