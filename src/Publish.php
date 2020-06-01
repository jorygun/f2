<?php
namespace DigitalMx\Flames;

/*


	note: the latest news is already archived in newsp, and that's
	the copy people read.  The copy in latest is so alterations
	can be made, tested, and recopied out to archive.

	process:

	(?? copy contents of live to current archive for preservation)

	copy contents of next to latest
	insert publish_data file into latest (pub date...)

	copy latest/ to newsp/news_datecode
	set the current/pubdate


	set the current/pointer
	add datecode to read index

	add to news_index

	update recent


	set all the news items to published
	remove everything from next and copy the index template



*/

//BEGIN START
#ini_set('display_errors', 1);
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;

//END START
	use DigitalMx\MyPDO;
	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\FileDefs;


class Publish {

	private $pdo;
	private $article;

	private $issue;
	private $pubdate;
	private $pubdate_human;



	public static $preview_button = <<<EOT
		<button type='button' onClick=
			"takeAction('preview'); window.open('/news/next','preview');"
			>Preview
		</button>
EOT;

	public static $publish_button = <<<EOT
		<button type='button' onClick=
			"takeAction('publish','','','resp'); "
			>Publish
		</button>
EOT;
//window.open('/news/current','current');

	public function __construct($container){
		$this->pdo = $container['pdo'];
		$this->setTimes();
		$this->article = $container['article'];


	}

public function getLastPub($field = 'pubdate') {
	$sql = "SELECT * from `pubs` ORDER by pubdate DESC limit 1";
	$last_pub = $this->pdo->query($sql)->fetch();
	return $last_pub[$field];

}
 public function setNextUpdated() {
 	$sql = "UPDATE `pubs` SET updated = NOW() WHERE issue = 1";
 	$this->pdo->query($sql);

 }

	private function write_breaking($content) {
		$now = date('d M Y H:i');
		$bnews = "<div style='border:2px solid black;padding:1em;'>"
    . "<p style='color:red;'><b>Update posted at " .$now . "</b></p>\n"
	. u\txt2html($content)
	. "</div>\n";
	file_put_contents(FileDefs::current_dir . '/breaking.html',$bnews);
	}




	private function setTimes(){

// 	$pubdate_dt = new \DateTime('@' . $last_timestamp() );
// 	$this->pubdate_code = $pubdate_dt -> format('ymd');
// 	$this->pubdate_human = $pubdate_dt -> format('j M Y');


// get current date forms

	$now_dt = new \DateTime();
	$this->issue = $now_dt -> format ('Ymd');
	//$this->issue = '2';
	$this->pubdate_human = $now_dt -> format ('j M Y');
	$this->pubdate = $now_dt-> format ('Y-m-d H:i');



}
	public function getNextTitle()
	{

		// returns title of issue 1
		$sql = "SELECT title from `pubs` where issue = 1;";
		$title = $this->pdo->query($sql)->fetchColumn();
		return $title;
	}
	public function setNextTitle($title) {
		$sql = "UPDATE `pubs` set title ='$title' WHERE issue = 1";
		if ($this->pdo->query($sql) ) {
			return true;
		}
		return false; // true|false
	}

	public function publishNews() {
		// these routines publish the new newsletter
		//shell_exec ("chmod -R g+w " . FileDefs::latest_dir);
		$issue = $this->issue;

		$archive = '/news_' . $issue;

	try{

		$this->addIssueToPubs($issue,$this->pubdate);
		$this->setCurrentToArchive($archive);
		$this->copyNextToArchive($archive);

		$this->article->setArticlesPublished($issue,$this->pubdate);
		#$this->initializeNext();


	} catch (Exception $e) {
		echo "Publish failed: " . $e->getMessage();
		return false;
	}
		return $archive;
	}


   public function buildPreview () {
   // prepares issue 1 and views it.
		try {

			$article_list = $this->article->getArticleIds('next');
			$this->setNextArticles($article_list);

		// run all rhe update reports


		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}
		return true;
   }

	private function setCurrentToArchive($archive) {
		$current = FileDefs::current_dir . '/index.php';
		$reloc = <<<EOT
<?php
header("location:/newsp/$archive");
EOT;
		file_put_contents($current,$reloc);
	}


	private function setNextArticles ($article_list) {
	// records article in pub 1 (preview)
		$alistj = json_encode($article_list);
		$sql = "UPDATE `pubs` SET stories = '$alistj' WHERE issue = 1";
		if ($this->pdo->query($sql) ){
			return true;
		}
		return false;
	}

	public function getIssueArticles ($issue) {
	// returns list of article ids from the stories field of pubs
		$sql = "SELECT stories FROM `pubs` WHERE issue = '$issue'";
		$alist = $this->pdo->query($sql)->fetchColumn();
		//$alist = json_decode($alistj);
		return $alist;

	}


	private function addIssueToPubs($issue,$pubdate) {
		// copy the data for issue 1 to the new issue
		// get data for issue 1 and clear it
		$sql = "SELECT title, stories,updated FROM pubs WHERE issue = 1";
		$d = $this->pdo->query($sql)->fetch();
		//u\echor ($d ,'issue data');


		// add new info for this pub
		$darray['title'] = $d['title'];
		$darray['stories'] = $d['stories'];
		$darray['issue'] = $issue;
		$darray['url'] = '/newsp/news_'. $issue;
		$darray['pubdate'] = $pubdate;
		$darray['predate'] = $d['updated']; //preview times stamp is last status updated

		//u\echor($darray);


	 /**
		$prep = pdoPrep($post_data,$allowed_list,'id');

		 $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
			 $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
			 $new_id = $pdo->lastInsertId();

		 $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
			 $stmt = $pdo->prepare($sql)->execute($prep['data']);

	  **/
		$prep = u\pdoPrep($darray,[],'issue');



		$sql = "INSERT into `pubs` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} )
			ON DUPLICATE KEY UPDATE  ${prep['updateu']} ";

		//u\echor($prep['data'] , $sql);

	$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':title',$darray['title']);
		$stmt->bindValue(':stories',$darray['stories']);
		$stmt->bindValue(':issue',$darray['issue']);
		$stmt->bindValue(':url',$darray['url']);
		$stmt->bindValue(':pubdate',$darray['pubdate']);
		$stmt->bindValue(':predate',$darray['predate']);
		$stmt->bindValue(':utitle',$darray['title']);
		$stmt->bindValue(':ustories',$darray['stories']);
		$stmt->bindValue(':uurl',$darray['url']);
		$stmt->bindValue(':upubdate',$darray['pubdate']);
		$stmt->bindValue(':predate',$darray['predate']);

	$stmt->execute();

		$sql = "UPDATE pubs set title='',stories='' WHERE issue = 1";
		//$this->pdo->query($sql);
	}

	private function copyNextToArchive($archive) {
		$dir = FileDefs::archive_dir  . '/' . $archive;
		u\full_copy(FileDefs::next_dir,$dir);

	}

	private function initializeNext() {
		// create empty news/next with just the
		// index file in it.
		u\emptyDir(FileDefs::next_dir);
		copy (FileDefs::template_dir . '/index.php',FileDefs::next_dir . "/index.php");

	}

}

