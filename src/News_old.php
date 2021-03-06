<?php
namespace DigitalMx\Flames;

	use DigitalMx\MyPDO;
	use DigitalMx as u;
	
class News {


   /**
    * Routines used to read the newsletter articles                           *
   **/



	private $pdo;


	function __construct() {

		$this->pdo = MyPDO::instance();
	}

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
			<img src='/assets/graphics/up.png' />
			</button>
			
			<button type='button' title='Down' class='down' onClick='addVote($item_id,"down-off");'  >
			<img src='/assets/graphics/down-on.png' />
			</button>
EOT;
		}
		elseif($user_vote > 0) {
			$button_div.= <<<EOT
			<button type='button' title='Up' class='up' onClick='addVote($item_id,"up-off");' >
			<img src='/assets/graphics/up-on.png' />
			</button>
			<button type='button' title='Down' class='down' onClick='addVote($item_id,"down");'  >
			<img src='/assets/graphics/down.png' />
			</button>				
EOT;
		}
		else { #if not found or 0
			$button_div .= <<<EOT
			<button type='button' title='Up' class='up' onClick='addVote($item_id,"up");' >
			<img src='/assets/graphics/up.png' />
			</button>
			<button type='button' title='Down' class='down' onClick='addVote($item_id,"down");'  >
			<img src='/assets/graphics/down.png' />
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
