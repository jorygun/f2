<?php
namespace digitalmx\flames;

/*


	note: the latest news is already archived in newsp, and that's
	the copy people read.  The copy in latest is so alterations
	can be made, tested, and recopied out to archive.

	process:

copy news/next to new archive and to news/latest
add new issue to pubs db
set pub date on all articles
set first use date/in on all assets for all articles


remove everything from next and copy the index template



*/

//BEGIN START
#ini_set('display_errors', 1);
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;

//END START
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\FileDefs;

	use digitalmx\flames\NewsIndex;



class Publish {

	private $pdo;

	private $ymd_code;
	private $now_human;
	private $title;
	private $archive; #name of  new dir:  news_yymmdd
	private $issue;
	private $archive_url;
	private $pubdate;
	private $archive_path;


	public static $previewbutton = <<<EOT
		<button type='button' onClick='window.open("/news/next","preview" )'>
		Preview
		</button>
EOT;
	public static $previewaction = <<<EOT
		<button type="button" onclick= "
			takeAction('preview','0','','');
			 window.open('/news/next','preview');
			">Show News Preview</button>
EOT;



	public function __construct($container){
		foreach (['pdo','news','article'] as $dclass) {
			$this->$dclass = $container[$dclass];
		}
		$this->logger = $container['logger-dbug'];
		$this->setTimes();
		$this->title = $this->news->getTitle(1);
		$this->logger->info('Constructed publish');
	}


	public function preview() {
		// get article list and put into the pub 1 record
		$storylist = $this->article->getArticleIds('next');
		$jstories = json_encode($storylist);
		$sql = "UPDATE pubs SET stories = '$jstories' WHERE issue = 1";
		//echo $sql . BRNL; exit;
		$this->pdo->query($sql);
		return false;
	}


	public function setNextTitle($title) {
		$sql = "UPDATE pubs SET title = '$title' WHERE issue = 1";
		if ($this->pdo->query($sql) ) {
			return "OK";
		} else {
			return false;
		}

	}
	public function wrapupNews() {
		// these routines clean up everything once
		// publish is successful
	}
	private function write_breaking($content) {
		$now = date('d M Y H:i');
		$bnews = "<div style='border:2px solid black;padding:1em;'>"
    . "<p style='color:red;'><b>Update posted at " .$now . "</b></p>\n"
	. u\txt2html($content)
	. "</div>\n";
	file_put_contents(FileDefs::breaking_news,$bnews);
	}




	private function setTimes(){
//get date of last pub (never used)
// 	if ($last_timestamp = f\getLastPub() ){
// 		$last_timestamp = strtotime('- 7 days');
// 		echo "<p class='red'>No last pub timestamp; set to -7 days</p>";
// 	}
// 	$pubdate_dt = new \DateTime('@' . $last_timestamp() );
// 	$this->pubdate_code = $pubdate_dt -> format('ymd');
// 	$this->pubdate_human = $pubdate_dt -> format('j M Y');


// get current date forms
	$this->nowtime = time();
	$now_dt = new \DateTime(); // will be gmt?

	$this->ymd_code = $now_dt -> format ('ymd');
	$this->issue = $now_dt -> format ('Ymd');
	$this->now_human = $now_dt -> format ('j M Y');
	$this->pubdate = $now_dt -> format('Y-m-d H:i');

	$this->archive = 'news_' . $this->ymd_code;
	$this->archive_url = '/newsp/' . $this->archive;
	$this->archive_path = REPO_PATH  . '/' . $this->archive_url;
}

	public function publishNews() {

/*
	create new archive
    copy news/next to new archive
  	 get article list
    set pub date on all articles
     set first use date/in on all assets for all articles
     add new issue/archive to pubs db, with article list
    set first use date/in on all assets for all articles

    remove everything from next and copy the index template

*/


		// if (file_exists($this->archive_path)){
// 			die ("$this->archive directory already exists. Please remove before proceeding");
// 		}


// copy news/next to news/latest - copies reports and stuff
		$this->copyNextToLatest();

// get list of stories to publsh
//	$storylist = $this->article->getArticleIds('next');

// copy news/latest into the new archive newsp/news_yymmdd
		$this->copyLatestToArchive($this->archive);
// create a new pub record with some info from preview issue
// storylist is list of stories in this issue
		$storylist = $this->createNewPub($this->archive,$this->issue);

// mark all the stories published and set first use date on any assets referenced.
		 $this->publishStories($storylist);

		 $this->buildTeaser($storylist);

// sets issue 1 data to defaults
		$this->initializePreview();


	}


	public function buildTeaser($storylist) {
		$artlist = $this->article->getArticleList('list',$storylist);
		$t = "News Stories: \n------------------\n";
		$nbsp3 = "&nbsp;&nbsp;&nbsp;";

		//u\echor ($artlist); //exit;
		foreach ($artlist as $article) {
			$t .= $nbsp3 . $article['title'] . " (" . $article['contributor'] . ")" . NL;
		}
		$t .= "\n";
		file_put_contents(FileDefs::tease_news,$t);

	}

	public function copyNextToLatest() {
	// copy the news_next to the news_latest directory
		if (file_exists (FileDefs::latest_dir)) {
		echo "deleting old news/latest";
			u\deleteDir(FileDefs::latest_dir);
		}
		u\full_copy(FileDefs::next_dir,FileDefs::latest_dir);

	}
	private function publishStories($storylist) {
		// mark each story as published, and set first use for any assets it references
		echo "Updating articles" . BRNL;
		$sql = "UPDATE articles
			SET date_published = '$this->pubdate',
				status = 'P',
				use_me = 0,
				pub_issue = '$this->issue'
			WHERE id = ?";
		$arth = $this->pdo->prepare($sql);

		$sql = "SELECT CONCAT (asset_list, ' ', asset_main)
				from articles
			WHERE id = ?";
		$asseth = $this->pdo->prepare($sql);

		$sql = "UPDATE assets2
			SET first_use_date = '$this->pubdate',
				first_use_in = '$this->archive_url'
			WHERE id = ? AND first_use_date is NULL";
		$fuh = $this->pdo->prepare($sql);


		foreach ($storylist as $story) {
			 $arth->execute([$story]);
			//echo "Getting assets from $story" . BRNL;
			$asseth->execute([$story]);
			$assets = $asseth->fetchColumn();
			$alist = u\range_to_list($assets);
			// u\echor($alist, 'Assets in ' . $story);
			foreach ($alist as $asset){
				if ( !empty($asset) && $fuh->execute([$asset]) ) {
					echo "Updating first use: asset $asset in story $story." . BRNL;
				}

			}
		}







	}
	private function createNewPub($archive,$issue) {
		$preview = $this->news->getIssueData(1);

		//u\echor($preview,'preview');

		$newpub = array(
		'issue' => $issue,
		'pubdate' => $this->pubdate,
		'title' => $preview['title'],
		'rcount' => 0,
		'last_scan' => $preview['last_scan'],
		'url' => '/newsp/' . $archive,
		'stories' => $preview['stories'], // is json
		);
	//u\echor($newpub,'newpub');
		$sql = "DELETE FROM pubs WHERE issue='$issue'";
		$this->pdo->query($sql);

		$prep = u\pdoPrep($newpub,'');
		$sql = "INSERT into `pubs` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
		// u\echor($prep['data'],$sql);

       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/
  		$storylist = json_decode($preview['stories']);
  		return $storylist;

	}

	private function initializePreview () {

		$sql = "UPDATE `pubs` SET
			pubdate = null,
			title = '',
			rcount = 0,
			last_scan = null,
			url = '/news/next',
			stories = '[]'
		WHERE issue = 1 ;";
		//u\echor($prep,$sql); exit;
       $stmt = $this->pdo->query($sql);
	}


	private function copyLatestToArchive($archive='') {
	// if no archive, gets latest archive.  This routine can be
	// used to update the archive directory if changes are made in latest.
	// full_copy creates the target directory
		if (!$archive) {
			echo "No archive requested; getting latest issue";
			$latest = $this->news->getLatestIssue();
			$archive = str_replace('/newsp','',$latest['url']); // after /newsp
		}
		$archive_path = FileDefs::archive_dir  . '/' . $archive;
		if (file_exists($archive_path)){
			u\deleteDir($archive_path);
		}

		u\full_copy(FileDefs::latest_dir,$archive_path);

	}

	public function getArticlesFromIssue($issue) {
		$sql = "SELECT stories from pubs
			WHERE issue = '$issue'
		";
		$stories = $this->pdo->query($sql)->fetchColumn();
		$story_list = u\number_range($stories);
		return $story_list;

	}

	public function getIssueList() {
		// returns array of issues and dates that have articles listed
		// in the last year
		$sql = "SELECT issue,DATE_FORMAT(pubdate,'%Y %M %d') as pubdate
			FROM pubs
			WHERE stories is not null AND pubdate > DATE_SUB(NOW(),INTERVAL 1 year)
			ORDER BY pubdate DESC
			";
		$list = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
		//$list = array_flip($list); // swap keys and values
		return $list;
	}
	public function setLastScan(){

		$sql = "UPDATE pubs set last_scan = NOW() WHERE issue = 1";
		$this->pdo->query($sql);
		return true;
	}

}

//EOT;
