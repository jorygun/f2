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
				articles n
			WHERE
				$where
			";
		$popnews = $this->pdo->query($sql)->fetchAll();
		return $popnews;
	}

 public function getNewsIdsForIssue($issue) {
 	if ($issue == 1) {
 		// preview
 		$where = "use_me > 0";
 	} else {
 		$where = "issue = '$issue' ";
 	}
 	$sql = "SELECT id from `articles`
 		WHERE $where";
 	try {
 		$artlist = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
 	} catch (PDOException $e) {
 		return [];
 	}
 	return $artlist;

 }

public function getNewsIndex() {
	$sql = "SELECT issue,url,title, pubdate, DATE_FORMAT(pubdate,'%d %b, %Y') as hdate FROM `pubs` WHERE issue > 19980000 ORDER BY pubdate DESC";
	$stmt = $this->pdo->query($sql);
	$lyear = 0;
	$listcode = "<ul class='collapsibleList' style='margin-bottom:6px;'>\n";
	foreach ($stmt as $r) {
		$year = substr($r['issue'],0,4);

		 if ($year <> $lyear){
                if ($lyear <> 0){$listcode .= "</ul>\n";}
                $lyear = $year;
                $listcode .= "<li>$year <ul>";
      }

      $listcode .= "<li style='margin-bottom:6px;'><a href='${r['url']}' target='news' style='text-align:left'>" . $r['hdate'] .  "</a> ${r['title']}</li>\n";

	}
	return $listcode;
}

public function getIssueData($issue) {
	$sql = "SELECT * FROM pubs where issue = '$issue' LIMIT 1;";
	$issue_data = $this->pdo->query($sql)->fetch();
	//echo "Issue $issue: " ; u\echor($issue_data) ; exit;

	return $issue_data;
}
public function getLatestIssue(){
	// returns array of issue and human date for last entry in pubs
	$sql = "SELECT issue, url,
	DATE_FORMAT(pubdate,'%b %d, %Y') as date_published ,
	last_scan
	FROM `pubs` ORDER By pubdate DESC LIMIT 1";
	$latest = $this->pdo->query($sql)->fetch();
	if (empty($latest['last_scan'])){
			$latest['last_scan'] = date('M d, Y H:i',strtotime('- 8 days'));
	}
	return $latest;
}

public function getTitle($issue){

	$sql = "SELECT title from pubs where issue = '$issue'";
	$title = $this->pdo->query($sql)->fetchColumn();

	return $title;
 }


public function incrementReads($issue) {
	// sets and uses last_insert_id to return the new value
	$sql = "UPDATE `pubs` SET rcount = last_insert_id(rcount+1) WHERE issue = '$issue';";
	$this->pdo->query($sql);
	$new_val = $this->pdo->lastInsertId();

	return $new_val;

}
public function getReads($issue) {
 $sql = "SELECT rcount from `pubs` where issue ='$issue';";
 $rcount = $this->pdo->query($sql)->fetchColumn();
 return $rcount;
}

public function buildChart($chart_url) {

$month = array(
	'01'=>'Jan',
	'02'=>'Feb',
	'03'=>'Mar',
	'04'=>'Apr',
	'05'=>'May',
	'06'=>'Jun',
	'07'=>'Jul',
	'08'=>'Aug',
	'09'=>'Sep',
	'10'=>'Oct',
	'11'=>'Nov',
	'12'=>'Dec',
);


#$count_file = SITE_PATH . "/views_data/reads.txt";
$out_file = SITE_PATH . $chart_url;

#update the access counts
#get the last 52 entries, then reorder Ascending.
$sql = "SELECT `issue`,`rcount` FROM `pubs` ORDER BY issue DESC LIMIT 60";

$result = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

$dString = '';
foreach($result as $row){
    $dString .= sprintf("%d\t%d\n",$row['issue'],$row['rcount']);
    $dArray[]=array($row['issue'],$row['rcount']);
}


foreach ($dArray as $dline){
  //$line is an array of the elements
    if ($dline[0]=='999999'){$dline[0]='Preview';}
    else{
		$mono = substr($dline[0],4,2);
		$dayno = substr($dline[0],6,2);
		$moname= $month[$mono];
		$dline[0] = "$moname $dayno";
	}

  $data[]=$dline;
}

#draw the graph

$plot = new \PHPlot(800,600);
$plot->SetDataValues($data);
$plot->SetTitle('Views By Issue Last 60 Issues ');

$plot->SetXTitle('Issue');
$plot->SetYTitle('Views');

$plot->SetPlotType('Bars');
$plot->SetDataType('text-data');

$plot->SetOutputFile($out_file);
#$plot->SetPrintImage(0);

$plot->SetXTickLabelPos('none');
$plot->SetXTickPos('none');
$plot->SetFont('x_title', '3');
$plot->SetFont('y_title', '3');
$plot->SetFont('x_label', '3');
$plot->SetFont('y_label', '3');
$plot->SetXLabelAngle(90); #vertical text
$plot->TuneYAutoTicks(0,'decimal',1); #integers
#$plot->SetYDataLabelPos('plotin');
#$plot->SetYTickLabelPos('none');
#$plot->SetYTickPos('none');
$plot -> SetShading(0);

$plot->SetIsInline(1);
$plot->DrawGraph();

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

}
//EOF
