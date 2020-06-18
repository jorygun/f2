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
		<button type='button' onClick='window.open("/news/next" )'>
		Preview
		</button>
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
    copy news/next to new archive and to news/latest
    add new issue to pubs db
    set pub date on all articles
    set first use date/in on all assets for all articles

    remove everything from next and copy the index template

*/


		// if (file_exists($this->archive_path)){
// 			die ("$this->archive directory already exists. Please remove before proceeding");
// 		}


// copy news/next to news/latest
		$this->copyNextToLatest();
// copy news/latest into the new archive newsp/news_yymmdd
		$this->copyLatestToArchive($this->archive);
// create a new pub record with some info from preview issue
// storylist is list of stories in this issue
		$storylist = $this->addArchiveToPubs($this->archive,$this->issue);

// mark all the stories published and set first use date on any assets referenced.
		$this->publishStories($storylist);
// sets issue 1 data to defaults
		$this->initializePreview();


	}





	public function copyNextToLatest() {
	// copy the news_next to the news_latest directory
		if (file_exists (FileDefs::latest_dir)) {
			u\deleteDir(FileDefs::latest_dir);
		}
		u\full_copy(FileDefs::next_dir,FileDefs::latest_dir);

	}
	private function publishStories($storylist) {
		// mark each story as published, and set first use for any assets it references
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
			// $arth->execute([$story]);
			//echo "Getting assets from $story" . BRNL;
			$asseth->execute([$story]);
			$assets = $asseth->fetchColumn();
			$alist = u\range_to_list($assets);
			// u\echor($alist, 'Assets in ' . $story);
			foreach ($alist as $asset){
				if ($fuh->execute([$asset]) ) {
					echo "Updating first use: asset $asset in story $story." . BRNL;
				}

			}
		}







	}
	private function addArchiveToPubs($archive,$issue) {
		$preview = $this->news->getIssueData(1);
		$storylist = $this->article->getArticleIds('next');
		//u\echor($storylist,'stories');

		$newpub = array(
		'issue' => $issue,
		'pubdate' => $this->pubdate,
		'title' => $preview['title'],
		'rcount' => 0,
		'last_scan' => $preview['last_scan'],
		'url' => '/newsp/' . $archive,
		'stories' => implode (' ',$storylist),
		);
	//u\echor($newpub,'newpub');
		$sql = "DELETE FROM pubs WHERE issue='$issue'";
		$this->pdo->query($sql);

		$prep = u\pdoPrep($newpub,'');
		$sql = "INSERT into `pubs` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
 /**
 	$prep = pdoPrep($post_data,$allowed_list,'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $pdo->prepare($sql)->execute($prep['data']);

  **/



		return $storylist;



	}

	private function initializePreview () {

		$sql = "UPDATE `pubs` SET
			pubdate = null,
			title = 'Preview',
			rcount = 0,
			last_scan = null,
			url = null,
			stories = ''
		WHERE issue = 1 ;";
		//u\echor($prep,$sql); exit;
       $stmt = $this->pdo->query($sql);
	}


	private function copyLatestToArchive($archive='') {
	// if no archive, gets latest archive.  This routine can be
	// used to update the archive directory if changes are made in latest.
	// full_copy creates the target directory
		if (!$archive) {
			$latest = $this->news->getLatestIssue();
			$archive = str_replace('/newsp','',$latest['url']); // after /newsp
		}
		$archive_path = FileDefs::archive_dir  . '/' . $archive;
		if (file_exists($archive_path)){
			u\deleteDir($archive_path);
		}

		u\full_copy(FileDefs::latest_dir,$archive_path);

	}



	public function setLastScan(){

		$sql = "UPDATE pubs set last_scan = NOW() WHERE issue = 1";
		$this->pdo->query($sql);
		return true;
	}

}

//EOT;
