<?php
namespace DigitalMx\Flames;

	use DigitalMx\MyPDO;
	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\FileDefs;


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
	public $topic_sections;

	// use_me options.  Data stored as integer 0-3 pointer to one of these.
	// rank is used to sort articles when multiple displayed
	public static $queueOptions = array ('No','Low','Medium','High');

	function __construct($container) {

		$this->pdo = $container['pdo'];
		$this->member = $container['member'];
		$this->sections = $this->getSections();
		$this->topics = $this->getTopics();
		$this->topic_sections = $this->getTopicToSection();
	}



	 public function getQueueOptions($ind = '')
    {
        // if called with an index no, returns the associated name
        // otherwise returns the list
        if (!empty($ind) and is_integer($ind)) {
            return self::$queueOptions[$ind];
        }
        return self::$queueOptions;
    }


	public function getNewsSummary ($where) {
		if (empty($where)){throw new Exception ("No where clause for getNewsSummary");}
		$sql = "
		SELECT n.id,n.title
			,(SELECT count(*) FROM	comments c
				WHERE n.id = c.item_id AND c.on_db = 'news_items') AS comment_count
			, (SELECT count(*) FROM votes v
				WHERE n.id = v.news_fk AND v.vote_rank <> 0) AS total_votes
			, (SELECT SUM(`vote_rank`) FROM votes v
				WHERE n.id = v.news_fk AND v.vote_rank <> 0) AS net_votes
			FROM
				news_items n
			WHERE
				$where
			";
		$popnews = $this->pdo->query($sql)->fetchAll();
		return $popnews;
	}




public function getSections() {
	// returns array of section => section name
	$sql = 'SELECT section,section_name from news_sections ORDER BY section_sequence';
	$sections = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	return $sections;
}

public function getTopics($access=''){
//	returns array of topic=>topic name grouped and ordered by sections they are in.
// use access = '' for all topics including deprecated
// access = 'A' for all current topics
// access = 'U' for user accessible topics

	$sql = "SELECT `topic`,`topic_name` from `news_topics` t
		INNER JOIN news_sections  s
		ON t.section = s.section ";
	if ($access == 'A'){ $sql .= " WHERE `access` in ('A','U') "; }
	elseif ($access == 'U'){ $sql .= " WHERE `access` = 'U' "; }
	$sql .= " ORDER BY s.section_sequence, t.topic ";

	$topics = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);

	return $topics;
}

private function getTopicToSection () {
		$sql = "SELECT `topic`,`section` from `news_topics` t
		";

	$topic_sections = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	return $topic_sections;
}

public function getSectionForTopic ($topic){
	$section = $this->topic_sections[$topic];
	// $sql = "SELECT section from `news_topics` WHERE topic = '$topic'";
// 	$section = $this->pdo->query($sql)->fetchColumn();
	return $section;
}

public function getSectionName($section){
	return $this->sections[$section];
}
public function getTopicName($topic) {
	return $this->topics[$topic];
}

###########################################################

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




}
