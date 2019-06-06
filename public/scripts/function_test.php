<?php


//envent processor 
function get_events($filename){
	$fh = fopen($filename,'r') or die("can't open $filename");
	$calendar = array();
	while ($line = fgets($fh)){
		if (empty($line) or substr($line,0,1) == '#'){continue;}
		list($date,$rest) = explode("\t",$line,2);
		if (preg_match('/^\s+$/',$date)){continue;}
		
		$udate = strtotime($date);
		if ($udate < time()){continue;} #old data
		#echo "$date ($udate) -- $rest<br>";
		$calendar[$udate] = $rest;
	}
	if (!$calendar){return "No Events";}
	
	ksort($calendar);
	
	#print_r ($calendar);
	$output = "<table class='calendar'>";
	$output .= '<tr><th>Date</th><th>Location</th><th>Local Time</th><th>Place</th><th>Contact</th></tr>';
	
	foreach ($calendar as $udate => $line){
		list($time,$loc,$place,$contact,$more) = explode("\t",$line);
		$edate = date('M d, Y',$udate);
		$contact = preg_replace('/([\w\.\-]+\@[\w\.\-]+)/',"<a href='mailto:$1'>$1</a>",$contact);
		$place = preg_replace('|(http://)?([\S\.]+\.\w+)\b|',"<a href='http://$2'>$0</a>",$place);
		$place = str_replace(';','<br>',$place);
		$output .= "<tr><td> $edate</td><td>$loc</td><td>$time</td><td>$place</td><td>$contact</td></tr>";
		if($more){$output .= "<tr><td colspan='2'></td><td colspan='3'>$more</td></tr>";}
	}
	
	return $output;
}
?>


