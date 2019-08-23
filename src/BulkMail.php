<?php
namespace digitalmx\flames;


class BulkMail {

	private $queue = REPO_PATH  . "/var/queue";
	private $working= REPO_PATH . "/var/bulk_jobs";
	
	
	public function show_bulk_jobs(){
		#looks for jobs in queue, and returns a 
		// ul list with status and a cancel button
	   		$queue = $this->queue;
	   		$working = $this->working;
	   		
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




}
