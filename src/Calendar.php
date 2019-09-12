<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	use digitalmx\MyPDO;
	
	
	// script reads the alendar file and produces two outputs:
// an html table of events, and a plain text list of event highlights.
// If run with parameter u (calendar.php?u=1) then lets you add new items.

#list ($calendar_h, $calendar_t) = get_events($event_file);
/*

1.  display list of all calendar items for update form
2.  update individual item from list or add new
3. generate calendar reports (html and teaser)

*/

class Calendar {

	
    private $pdo;
    private $item_list = array();
     #list(id,datetime,$event,$city,$location,$contact,$info)
    private static $empty_row = array(
    	'datetime' => '',
    	'event' => '',
    	'city' => '',
    	'location' => '',
    	'contact' => '',
    	'info' => '',
    	);
    
    
	public function __construct () {
		$this->pdo = MyPDO::instance();
		$this->calendar_items = $this->get_items();
		#u\echor($this->calendar_items);
	}



	private function get_items() {
		  $sql = 'SELECT * FROM `events` WHERE `datetime` >= NOW() ORDER BY datetime;';
   	// fetch_uniuqe returns array indexed by id
    	$items = $this->pdo -> query($sql)->fetchAll(\PDO::FETCH_UNIQUE);
    	return $items;
		
	}
	
	public function show_item_list ($edit = true ){
		$elink = '';
		if (! $this->calendar_items){
			$output = "No Items";
		} else {
			$output = '<table>';
			foreach ($this->calendar_items as $id => $row){
				 if ($edit) {$elink="<a href='/calendar_admin.php?id=$id'>Edit</a>";}
				$event = u\special($row['event']);
				$city = u\special($row['city']);
    			$output .= "<tr class='first'><td class='date'> ${row['datetime']}</td>
		<td><b>$event</b></td><td>$city</td><td>$elink</td></tr>\n";
			}
			$output .= "</table><br>\n";
		}
		$output .= "<form method='get'><input type='submit' name='id' value='New Event' ></form>";
		
		return $output;

   }
	

public function show_event($id) {
		 // form yto enter new calendar item
		 if (!$id) {$row = self::$empty_row;}
		 else {
		 	$row = $this->calendar_items[$id];
		 }
		 $event = u\special($row['event']);
		$city = u\special($row['city']);
		$location = u\special($row['location']);
		$contact = u\special($row['contact']);
		$info = u\special($row['info']);
		
		 $f = <<<EOT
	<hr>
	<b>Edit/Create calendar item</b>
	<form method='post'>
	<input type='text' name = 'id' READONLY value='$id'>
	<table>
	<tr><td>Date and Time</td><td><input type=text name='cdate' value = '${row['datetime']}'> (Local Time at Event)</td></tr>
	<tr><td>Event Name</td><td><input type=text name='cevent' size=40 value = '$event'></td></tr>
	<tr><td>Region/City</td><td><input type=text name='ccity' value = '$city'></td></tr>
	<tr><td>Specific Location (url will be linked)</td><td><textarea name='clocation' rows=3 cols=40>$location</textarea></td></tr>
	<tr><td>Contact (url will be linked)</td><td><input type=text name='ccontact' size=60 value = '$contact'></td></tr>
	<tr><td>More Info (url will be linked)</td><td><textarea name='cinfo' rows=4 cols=60>$info</textarea></td></tr>
	<tr><td><input type=submit name="Submit" value='Enter'></td></tr>
	</table>
	</form>
EOT;
		 return $f;
	}


public function save_event($post){
	

	 $cdata = array(
	 		'id' => $post['id'],
        'event' => u\despecial($post['cevent']),
        'datetime' => date('Y-m-d H:i', strtotime($post['cdate'])),
        'city' => u\despecial($post['ccity']),
        'location' => u\despecial($post['clocation']),
        'contact' => u\despecial($post['ccontact']),
        'info' => u\despecial($post['cinfo'])
    );
    
    $prep = u\pdoPrep($cdata,'','id');
   # u\echor($prep);
  
    if ($post['id'] == 0){ #new entry
    	$sql = "INSERT into `events` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $this->pdo->lastInsertId();
    	
   } else { #update
  	 $sql = "UPDATE `events` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);

	}
	$this->calendar_items = $this->get_items();
}

public function build_calendar () {
	// saves calendar file to news/live and teaser to news/next
	$output = "<div class='calendar'>";
	$output .= "<div class='divh2'>Upcoming Events</div>\n";
	$output .= "<p>To add an event to the calendar, just let the editor know.</p>";
	if (! $this->calendar_items){
			$output .= "No Items";
		} else {
			$output .= "<table id='calendar'>";
			$teaser = "\nOn The Calendar\n----------------------------\n";
			foreach ($this->calendar_items as $id => $row){
				
				$udate = strtotime($row['datetime']);
        		$edate = date('M d, Y',$udate);
        		$etime = date('g:i a', $udate);
        		if ($etime == '12:00 am'){$etime = '';} #date only; no time
				$event = u\special($row['event']);
				$city = u\special($row['city']);
				$location = u\make_links(nl2br(u\special($row['location'])));
				$contact = ($row['contact'])? 
					'Contact: <br>' . u\make_links(u\special($row['contact']))
					:
					'';
					
				$info = u\make_links(nl2br(u\special($row['info'])));
				$output .= 
				"<tr class='first'><td class='date'> $edate $etime</td>
		<td><b>$event</b></td><td>$city</td></tr>
		<tr><td>$contact</td><td>$info</td><td>$location</td></tr>
		";
			$teaser .= sprintf("%15s  %s: %s\n",$edate,$city,$event);
			
			}

		$output .= "</table></div>\n";
		}
	
		
		echo $output;
		echo "<hr>";
		echo "<pre>$teaser</pre>";
		file_put_contents(FileDefs::calendar_html,$output);
		file_put_contents(FileDefs::calendar_tease,$teaser);
		
		

}

######################################

	private function leftover(){
echo "<h3>Current Calendar Items</h3>";
echo "text:<br><pre>",$calendar_t,"</pre><hr>\n";
echo "html:<br>", $calendar_h,"<hr>\n";

echo "<p>Generating Files</p>";
 
file_put_contents($calendar_html_file,$calendar_h);
file_put_contents($calendar_tease_file, $calendar_t);


echo show_calendar_form();
}

function get_events_db(){
    // reads upcoming events from events table and produces
    // html for newsletter and text file for use in email.

    
    $sql = 'SELECT * FROM `events` WHERE `datetime` >= NOW() ORDER BY datetime;';
   # $sql = 'SELECT * FROM `events` ORDER BY datetime;';
    $stmt = $this->pdo -> query($sql);
    if ($stmt->rowCount()==0){return array('<p>No Events Listed</p>','');}
    $output_h = "<table id='calendar'>";
	$output_t = "\nUpcoming Events\n----------------------------\n";
	$output_h .= "<tr><th>Date/Time</th><th >Event</th><th>Where</th></tr>";

   
}

    private function show_item($row,$edit) {
        #list(id,datetime,$event,$city,$location,$contact,$info)
        $udate = strtotime($row['datetime']);
        $edate = date('M d, Y',$udate);
        $etime = date('g:i a', $udate);
        if ($etime == '12:00 am'){$etime = '';} #date only; no time
        #else{echo "|$etime|<br>";}

        $event = $row['event'];
        $city = $row['city'];

 $output_h =
		"<tr class='first'>
		    <td class='date'> $edate $etime</td><td><b>$event</b></td><td>$city</td></tr>\n";

        if (empty($row['location'])){$location = '';}
        else {
            $location = make_links($row['location']);
            $location = nl2br($location);
            $output_h .= "<tr><td align='right'>at</td><td colspan='2'>$location</td></tr>\n";
        }

         if (empty($row['contact'])){$contact = '';}
         else {
          #turn an email or url into a link
            $contact = preg_replace('/([\w\.\-]+\@[\w\.\-]+)/',"<a href='mailto:$1'>$1</a>",$row['contact']);
            $contact = make_links( $contact);

            $output_h .= "
            <tr><td align='right'>Contact</td><td colspan='2'>$contact</td></tr>";
        }

        if(!empty($row['info'])){
            $info = make_links( $row['info']);
            $output_h .= "<tr><td></td><td colspan='2'>"
            . nl2br($info)
            . "</td></tr>";
        }

        $output_t .= sprintf("%15s  %s: %s\n",$edate,$city,$event);
    }


	



function generate_files($h,$t){
    echo "<p>Generating Files</p>";
 
    file_put_contents(calendar_html_file,$h);
    #file_put_contents($calendar_test,$calendar_h);
    file_put_contents(calendar_tease_file, $t);
}

}
############################################





