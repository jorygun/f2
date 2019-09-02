<?php
namespace digitalmx\flames;

/* 
	
	note: the latest news is already archived in newsp, and that's
	the copy people read.  The copy in latest is so alterations
	can be made, tested, and recopied out to archive.
	
	process:

	(?? copy contents of live to current archive)
	copy contents of next to latest
	insert publish_data file into latest
	
	copy latest/ to newsp/news_datecode
	set the current/pubdate
	
	set the current/pointer
	add datecode to read index
	
	add to news_index
	
	update recent artiles and assets
	
Separately, after verifying new newsletter..
	copy data/last_update_run to last_update_published
	set all the news items to published
	remove everything from next and copy the index template


	
	
	
*/

//BEGIN START
#ini_set('display_errors', 1);
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	
//END START
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;

	use digitalmx\flames\NewsIndex;

   

class Publish {



	private  $next_dir = REPO_PATH . "/public/news/next";
	private  $latest_dir = REPO_PATH . "/public/news/latest";
	private  $archive_dir = SITE_PATH . "/newsp";
	private  $current_dir = REPO_PATH . '/public/news/current';
	
	private  $news_template = REPO_PATH . "/templates/news_index.php";
	private  $latest_pointer = REPO_PATH . "/public/news/current/pointer.txt"; #link to newsp
	
	private  $titlefile = REPO_PATH . "/public/news/next/title.txt";
	private  $pubdatafile = REPO_PATH . '/public/news/latest/pubdata.txt';
	
	
	// timestamps
		private  $rtime_file = REPO_PATH . "/var/data/last_update_run.txt";
 		private  $ptime_file = REPO_PATH . "/var/data/last_update_published.txt";
 		private  $last_published =  REPO_PATH . "/var/data/last_pubdate.txt"; 
 		
	private $pubdate_code;
	private $pubdate_human;
	private $now_code;
	private $now_human;
	private $title;
	private $new_archive; #path to new dir in newsp

	public function __construct( ){
	
		$this->setTimes();
		$this->title = $this->getTitle();
	}
	
	
	private function setTimes(){
//get date of last pub
	$pubdate_dt = new \DateTime('@' . f\getLastPub() );
	$this->pubdate_code = $pubdate_dt -> format('ymd');
	$this->pubdate_human = $pubdate_dt -> format('j M Y');
	
// get current date forms
	$now_dt = new \DateTime();
	$this->now_code = $now_dt -> format ('ymd');
	$this->now_human = $now_dt -> format ('j M Y');
	
	$this->new_archive = '/news_' . $this->pubdate_code;
	
}
	public function getTitle(){
#get latest title from news_next

    if (file_exists($this->titlefile)){
        $title = trim(file_get_contents($this->titlefile));
	   
	}
	else {$title = '';}
	return $title;
 }

	public function publishNews() {
		// these routines publish the new newsletter
		$this->copyNextToLatest();
		$this->addPublishFile();
		
	
	}
	public function wrapupNews() {
		// these routines clean up everything once
		// publish is successful
	
	
	}
	
	private function copyNextToLatest() {
	// copy the news_next to the news_latest directory
		u\deleteDir($latest_dir);
		u\full_copy($next_dir,$latest_dir);
	
	}
	private function addPublishFile() {
	// add a file with the publish date
		file_put_contents($this->pubdatafile,$this->pubdate_human);
	}
	
	private function copyLatestToArchive($datecode) {
		$new = $this->archive_dir . '/' . $this->new_archive;
		u\full_copy($this->latest_dir,$new);
	}

	private function setPubdate() {
		file_put_contents ($last_published,time());
	}

	private function setPointer() {
		file_put_contents ($this->current_dir.'/pointer.txt', "/newsp/" . $this->new_archive);
	
	}
	private function fixPtime(){
		// copies the last update run time to the
		// last published run time
		copy ($this->rtime_file, $this->ptime_file);
	}
	
	private function restoreIndex() {
		// copies the news_index template to the next dir
		copy($this->news_template, $this->next_dir . "/index.php");
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
         if (in_array(REPO ,['live','beta','f2'])){
        		$result = $pdo->query($sql);
        	}
      
	}
        public function reindexNews() {
			 
			  if ( $nli = new NewsletterIndex(true) ){
				echo "Updating Newsletter Index" . BRNL;
				#echo "$nli" . BRNL;
			  }
			  else{
				echo "NewsletterIndex failed";
			  }
					#true forces rebuild
	}
	
		public function addReads() {
			  echo "Adding $pubdate_code to reads database<br>";
			  $sql = "INSERT INTO `read_table` SET issue = '$pubdate_code',read_cnt=0;";
			  $result = $pdo->query($sql);
				if (! $result){echo "Add to reads database failed.<br>";}

				#set count for preview issue to 0
				$sql = "UPDATE read_table SET read_cnt=0 WHERE issue = 999999;";
				$result = $pdo->query($sql);
		}
			  
}
			//  include './news_files2.php';
			
        	
		 /* Now build the recent article and assets file */
  //   echo "Updating recent article titles" . BRNL ;
//         require REPO_PATH . '/crons/recent_articles.php';
//         
// 	 echo "Updating recent assets" . BRNL ;
//         require REPO_PATH . '/crons/recent_assets.php';
//         
//     echo "Done.  <button type='button' onClick='window.close()'>Close Window</button>";
// 
// 	exit;






