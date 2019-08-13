<?php
namespace digitalmx\flames;

	use \digitalmx\MyPDO;
class Voting {

	private $pdo;


	function __construct() {
		#$this->pdo = $pdo;
		$this->pdo = MyPDO::instance();
	}

	public function show_panel($item_id,$user_id){
   		
   		
   		$pdo = $this->pdo;
   		
		$upok = "enabled";
		$downok = "enabled";
		$vote_rank = 0;
		
	
		#first, get current vote total for the article
		$sql = "SELECT count(*)  FROM `votes` 
		WHERE news_fk = $item_id AND vote_rank != 0;";
		#exclude votes chnaged to meh (0)
		if (! $total_votes = $pdo->query($sql)->fetchColumn() ){
			$total_votes = 0;
		}
		#now get net votes for the article
		$sql = "SELECT sum(vote_rank) FROM `votes` 
		WHERE news_fk = $item_id ";
		
		$net_votes = sprintf('%+d',$pdo->query($sql) -> fetchColumn() ?? 0);
		
		#now lastest vote from this user. 
		$sql ="SELECT vote_rank  FROM votes WHERE news_fk = $item_id and user_id_fk = $user_id;";
		#vote_rank is the last votes from this user.  Should only be 1, 0, or -1.
		if (! $user_vote = $pdo->query($sql)->fetchColumn() ){
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

private function record_vote($item_id,$user_id,$vote=''){
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

public function tally_vote ($item_id,$user_id,$vote='') {
	if (! in_array($vote,['up','down','meh','down-off','up-off']) ){
		throw new Exception ("Invalid vote $vote");
	}
	if (empty($item_id) or empty($user_id)){
		throw new Exception ("Invalid user or item id");
	}
	
	$this->record_vote($item_id,$user_id,$vote);
	return $this->show_panel($item_id,$user_id);
}

}