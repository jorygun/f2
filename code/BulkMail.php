<?php


class BulkMail {

	private $queue = '/usr/home/digitalm/Sites/flames/bulk_queue';
	private $working='/usr/home/digitalm/Sites/flames/bulk_jobs';
	
	
	public function show_bulk_jobs(){
		#looks for jobs in queue, and displays info about them
	   		$queue = $this->queue;
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
				
				if (! file_exists("$working/list.txt") or
					! file_exists("$working/message.txt") ){
						$joblist .= "<li class='error'>$jobid: no list or message files found";
						continue;
				}
				
				$jcnt = `wc -l "$working/list.txt" | awk '{print $1;}'` ;
				$jmsg = "$working/message.txt";
				$jcancel = '';
				$jsub = fgets(fopen("$jmsg", 'r')); #first line of message
				if ($jstat == 'queued' or $jstat == 'running'){
					$jcancel = "<button type='button' onClick='cancel_bulk($job)'>Cancel</button>";
				}
				$joblist .= "<li class='$jstat'>$jobid $jstat: '$jsub' to $jcnt recipients runs after: $starttime $jcancel<br>\n";
		
			}
		
			$joblist .= "</li>";
			return $joblist;
	}




}
