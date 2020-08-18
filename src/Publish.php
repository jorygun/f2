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
			">Set News Preview</button>
EOT;

// window.open('/news/next','preview');

	public function __construct($container){
		foreach (['pdo','news','article'] as $dclass) {
			$this->$dclass = $container[$dclass];
		}
		$this->logger = $container['logger-dbug'];
		$this->setTimes();
		$this->title = $this->news->getTitle(1);
		$this->logger->info('Constructed publish');
	}


	public function setPreview() {
		/* get list or articles marked as next and set issue to 1;
		1 for preview, xxxx for publsihed


		*/
		echo "Setting preview" . BRNL;
		// gets id sequenced by section topic priority
		$storylist = $this->article->getArticleIds('next');
		// remove old issue 1 tags
		$sql = "UPDATE articles set issue = 0 where issue = 1";
		$this->pdo->query($sql);

		$sql = "UPDATE articles SET issue = '1' where id = ? ";
		$pubin = $this->pdo->prepare($sql);
		foreach ($storylist as $story) {
			//echo "sertting $story to issue 1" . BRNL;
			$pubin->execute([$story]);
		}

	}


	public function setNextTitle($title) {
		$title = u\special($title);
		$sql = "UPDATE issues SET title = '$title' WHERE issue = '1' ";
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

	// put news teaser in next; will be retrieved from latest.
		$this->buildTeaser();
// copy news/next to news/latest  and to new archive- copies reports and stuff

		$this->copyNextToLatest();
		$this->copyLatestToArchive($this->archive);

// create a new issue record with some info from preview issue
		$this->createNewPub($this->archive,$this->issue);

// update stories to new issue

	// mark all the stories published and set first use date on any assets referenced.



		 $this->publishStories();

		$this->initializePreview();

		// set index of current to latest
		file_put_contents(FileDefs::current_dir . "/index.php",
			"<?php
			header('Location:" . $this->archive_url . "');" . NL
		);

	}


	public function buildTeaser() {
		$sql = "SELECT a.title,u.username as contributor
		FROM articles a
		JOIN members_f2 u on a.contributor_id = u.user_id
		where issue = '1' ";
		$artlist = $this->pdo->query($sql);

		$t = "News Stories: \n------------------\n";
		$nbsp3 = "&nbsp;&nbsp;&nbsp;";

		//u\echor ($artlist); //exit;
		foreach ($artlist as $article) {
			$t .= $nbsp3 . $article['title'] . " (" . $article['contributor'] . ")" . NL;
		}
		$t .= "\n";
		file_put_contents(FileDefs::next_dir . '/' . FileDefs::tease_news,$t);

	}

	public function copyNextToLatest() {
	// copy the news_next to the news_latest directory
		if (file_exists (FileDefs::latest_dir)) {
		echo "deleting old news/latest";
			u\deleteDir(FileDefs::latest_dir);
		}
		u\full_copy(FileDefs::next_dir,FileDefs::latest_dir);

	}
	private function publishStories() {
		// mark each story as published, and set first use for any assets it references
		$storylist = $this->getPreviewArticles();
		echo "Publishing articles" . BRNL;
		$sql = "UPDATE articles
			SET date_published = '$this->pubdate',
				status = 'P',
				use_me = 0,
				issue = '$this->issue'
			WHERE id = ?";
		$arth = $this->pdo->prepare($sql);

		$sql = "SELECT CONCAT (asset_list, ' ', asset_main)
				from articles
			WHERE id = ?";
		$asseth = $this->pdo->prepare($sql);

		$sql = "UPDATE assets2
			SET
				first_use_in = '$this->archive_url'
			WHERE id = ? ";
		$fuh = $this->pdo->prepare($sql);


		foreach ($storylist as $story) {
			// update status, issue, pubdate
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
	/* change references to preview (issue 1)
		to the new issue.

		issues: issue 1 -> issue issue

	*/
	// get issue 1 data
		$prevdata = $this->pdo->query(
			"Select title, last_scan from issues where issue = '1' "
			) ->fetch();
		$title = $prevdata['title'];
		$last_scan = $prevdata['last_scan'];

		$sql = "INSERT INTO issues
			SET issue = '$issue',
				rcount=0,
				title='$title',
				pubdate='$this->pubdate',
				url = '$this->archive_url',
				last_scan = '$last_scan'
				";
		$this->pdo->query($sql);
	}

	private function initializePreview () {

		$sql = "UPDATE `issues` SET
			pubdate = null,
			title = '',
			rcount = 0,
			last_scan = null,
			url = '/news/next'
		WHERE issue = 1 ;";
		//u\echor($prep,$sql); exit;
       $stmt = $this->pdo->query($sql);
	}

	public function getPreviewArticles() {
		$sql = "SELECT id from articles where issue = '1'";
		$storylist = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
		return $storylist;
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


	public function getIssueList() {
		// returns array of issues and dates that have articles listed
		// in the last year
		$sql = "SELECT issue,DATE_FORMAT(pubdate,'%Y %M %d') as pdate
			FROM issues
			WHERE  pubdate > DATE_SUB(NOW(),INTERVAL 1 year)
			ORDER BY pubdate DESC
			";
		$list = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
		//$list = array_flip($list); // swap keys and values
		return $list;
	}
	public function setLastScan(){

		$sql = "UPDATE issues set last_scan = NOW() WHERE issue = 1";
		$this->pdo->query($sql);
		return true;
	}

}

//EOT;
