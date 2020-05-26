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

	copy data/last_update_run to last_update_published
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

	private $now_code;
	private $now_human;
	private $title;
	private $new_archive; #name of  new dir:  news_yymmdd
	private $nli; //news index object

	public function __construct($container ){
		$this->pdo = $container['pdo'];
		$this->setTimes();
		$this->title = $this->getTitle();

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
	$now_dt = new \DateTime();
	$this->now_code = $now_dt -> format ('ymd');
	$this->year_code = $now_dt -> format ('Ymd');
	$this->now_human = $now_dt -> format ('j M Y');

	$this->new_archive = 'news_' . $this->now_code;

}
	public function getTitle(){
#get latest title from news_next

    if (file_exists(FileDefs::titlefile)){
        $title = trim(file_get_contents(FileDefs::titlefile));

	}
	else {$title = '';}
	return $title;
 }

	public function publishNews() {
		// these routines publish the new newsletter

		$this->copyNextToLatest();
		$this->addPublishFile();
		$this->setPointers();
		shell_exec ("chmod -R g+w " . FileDefs::latest_dir);

		$this->copyLatestToArchive();

		$this->addToPubs();
		$this->setPtime();
		$this->markPublished();
		$this->initializeNext();


	}



	public function copyNextToLatest() {
	// copy the news_next to the news_latest directory
		if (file_exists (FileDefs::latest_dir)) {
			u\deleteDir(FileDefs::latest_dir);
		}
		u\full_copy(FileDefs::next_dir,FileDefs::latest_dir);

	}

	private function addPublishFile() {
	// add a file with the publish date to latest dir.
		file_put_contents(FileDefs::pubfile,$this->now_human . '|' . $this->now_code);
	}

	private function copyLatestToArchive() {
		$new = FileDefs::archive_dir  . '/' . $this->new_archive;
		u\full_copy(FileDefs::latest_dir,$new);


	}


	private function setPointers() {
		$pointer="/newsp/" . $this->new_archive;
		file_put_contents (FileDefs::latest_pointer,  $pointer);
		file_put_contents (FileDefs::current_dir.'/index.php', "<?php\n header('location:$pointer');\n");
		file_put_contents (FileDefs::last_pubdate,$this->nowtime);

	}

	private function setPtime(){
		// copies the last update run time to the
		// last published run time
		copy (FileDefs::rtime_file,FileDefs::ptime_file);
	}


	private function markPublished() {

        $sql = "
            UPDATE news_items
            SET status = 'P',
            date_published = now(),
            use_me = 0
            WHERE use_me > 0;
            ";
         // only change db on live, beta, or f2 repos
         // not on test or trial or dev
         if (in_array(REPO ,['live','f2'])){
        		$result = $this->pdo->query($sql);
        	}

	}

	public function addToReads() {
			$pubdate_code = $this->now_code;

			  $sql = "INSERT INTO `read_table` SET issue = '$pubdate_code',read_cnt=0;";
			  try {
			 	$result = $this->pdo->query($sql);
			 	} catch (\Exception $e){
					echo "Add to $pubdate_code to reads database failed. Probably already exists.<br>";
					return false;
				}
				echo "Adding $pubdate_code to reads database<br>";
				return true;
	}
	private function initializeNext() {
		// create empty news/next with just the
		// index file in it.
		u\emptyDir(FileDefs::next_dir);
		copy (FileDefs::news_template,FileDefs::next_dir . "/index.php");
		copy (FileDefs::git_ignore,FileDefs::next_dir . "/.gitignore");
	}

}

