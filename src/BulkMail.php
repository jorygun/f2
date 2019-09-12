<?php
namespace digitalmx\flames;


use digitalmx\flames\Definitions as Defs;


class BulkMail {

	private static $queue = FileDefs::bulk_queue;
	private static $job_dir = FileDefs::bulk_jobs;
	private static $sender_program = FileDefs::bulk_processor;
	
	public $teaser_files = array(
		FileDefs::news_tease,
		FileDefs::status_tease,
		FileDefs::calendar_tease, 
		FileDefs::opp_tease,
		
	);
	

// location of directory with last_published, etc
	private static  $news_info = REPO_PATH . "/public/news/";

	private static $news_latest = REPO_PATH . "/public/news/news_latest";
	
	

	public function __construct() {
	
	}
	
	
	
	public function getNextJob (){
	$jfiles = [];
	$qfiles = scandir(self::$queue);
	foreach ($qfiles as $qfile){
			/* get job id for jobs, including  a status suffix (-cancelled)
				looking for ddddddd plus option -text
			*/
			
			if (! preg_match('/^(\d+)/',$qfile,$matches) ){
				continue;
			}
			
			if (strpos($qfile,'-cancelled') !== false){
				if (filemtime("$queue/$$qfile") < (time() - 86400) ) { #more than 24 hours old
				unlink ("$queue/$qfile");
				}
				continue;
			}
			if (strpos($qfile,'-') !== false){
				// there is a -status on the job
				continue;
			}
			$jobid = $qfile;
			
			#echo "$$qfile > $jobid, $jstat\n";
			#skip files with a status tag
			
			#only have files with just jobid now
			if (filemtime("$queue/$qfile") > time() ){ continue;} #not due yet
			
			#have a job to run
			rename ("$queue/$jobid","$queue/${jobid}-running");
			return $jobid; 
			
		}
		return false;
	}
		
		
	public function show_bulk_jobs(){
		#looks for jobs in queue, and returns a 
		// ul list with status and a cancel button
	   		$queue = self::$queue;
	   		$working = self::$job_dir;
	   		
			$joblist = "<b>Jobs In Bulk Queue:</b><br>";
			$jobs_in_queue = array_filter(scandir($queue), function($v){return substr($v,0,1) != '.';}); #files not staring with .
		
			 if (empty($jobs_in_queue)){return 'Nothing in queue';}
			 
			$joblist .= "<ul>";
			foreach ($jobs_in_queue as $job){
		 
				$starttime = date('M d H:i  T',filemtime("$queue/$job"));
				if ($c = strpos($job,'-cancelled')){
					$jstat = 'cancelled';
					$jobid = substr($job,0,$c);
				}
				elseif ($c = strpos($job,'-running')){
					$jstat = 'running';
					$jobid = substr($job,0,$c);
				}
				elseif ($c = strpos($job,'-error')){
					$jstat = 'error';
					$jobid = substr($job,0,$c);
					$joblist .= "<li class='error'>$jobid is marked as an error";
					continue;
				}
				else {
					$jstat = 'queued';
					$jobid = $job;
				}
				
				$job_dir= "$working/$jobid";
				if (!is_dir($job_dir)){
					$joblist .= "<li class='error'>$job dir not found in bmail";
					continue; #next job
				}
				
				if (! file_exists("$job_dir/list.txt") or
					! file_exists("$job_dir/message.txt") ){
						$joblist .= "<li class='error'>$jobid: no list or message files found";
						continue;
				}
				
				$jcnt = `wc -l "$job_dir/list.txt" | awk '{print $1;}'` ;
				$jmsg = "$job_dir/message.txt";
				$jcancel = '';
				$jsub = fgets(fopen("$jmsg", 'r')); #first line of message
				if ($jstat == 'queued' or $jstat == 'running'){
					$jcancel = "<button type='button' onClick='cancel_bulk($jobid)'>Cancel</button>";
				}
				$joblist .= "<li class='$jstat'>$jobid $jstat: '$jsub' to $jcnt recipients runs after: $starttime $jcancel<br>\n";
		
			}
		
			$joblist .= "</li>";
			return $joblist;
	}

	public function assemble_teaser() {
	$teaser = '';
	foreach ($this->teaser_files as $tfile){
		if (file_exists($tfile)){
			$teaser .= file_get_contents($tfile);
		}
		return $teaser;
	
	}
}

}
