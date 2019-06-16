<?php
#ini_set('display_errors', 1);
ini_set('error_reporting', -1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';);

// script reads the alendar file and produces two outputs:
// an html table of events, and a plain text list of event highlights.
// If run with parameter u (calendar.php?u=1) then lets you add new items.

#list ($calendar_h, $calendar_t) = get_events($event_file);

echo start_page();

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
#   echo "<pre>", print_r($_POST,true),"</pre>";
   if (!empty($_POST['cevent'])){ update_calendar();}
}

list ($calendar_h, $calendar_t) = get_events_db();


 generate_files($calendar_h,$calendar_t);


echo "<h3>Current Calendar Items</h3>";
echo "text:<br><pre>",$calendar_t,"</pre><hr>\n";
echo "html:<br>", $calendar_h,"<hr>\n";

echo show_calendar_form();




######################################
function get_events_db(){
    // reads upcoming events from events table and produces
    // html for newsletter and text file for use in email.

    $pdo = MyPDO::instance();
    $sql = 'SELECT * FROM `events` WHERE `datetime` >= NOW() ORDER BY datetime;';
   # $sql = 'SELECT * FROM `events` ORDER BY datetime;';
    $stmt = $pdo -> query($sql);
    if ($stmt->rowCount()==0){return array('<p>No Events Listed</p>','');}
    $output_h = "<table id='calendar'>";
	$output_t = "\nUpcoming Events\n----------------------------\n";
	$output_h .= "<tr><th>Date/Time</th><th >Event</th><th>Where</th></tr>";

    while ($row = $stmt->fetch()){
        #list(id,datetime,$event,$city,$location,$contact,$info)
        $udate = strtotime($row['datetime']);
        $edate = date('M d, Y',$udate);
        $etime = date('g:i a', $udate);
        if ($etime == '12:00 am'){$etime = '';} #date only; no time
        #else{echo "|$etime|<br>";}

        $event = $row['event'];
        $city = $row['city'];

 $output_h .=
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

	$output_h .= "</table>";
	$output_t .= "\n";

	return array($output_h,$output_t);
}


function show_calendar_form() {
    // form yto enter new calendar item
    $f = <<<EOT
<hr>
<b>Enter new calendar item</b>
<form method='post'>
<table>
<tr><td>Date and Time</td><td><input type=text name='cdate'></td></tr>
<tr><td>Event Name</td><td><input type=text name='cevent' size=40></td></tr>
<tr><td>Region/City</td><td><input type=text name='ccity'></td></tr>
<tr><td>Specific Location (url will be linked)</td><td><textarea name='clocation' rows=3 cols=40></textarea></td></tr>
<tr><td>Contact (url or email will be linked)</td><td><input type=text name='ccontact' size=60></td></tr>
<tr><td>More Info (url will be linked)</td><td><textarea name='cinfo' rows=4 cols=60></textarea></td></tr>
<tr><td><input type=submit name="Submit" value='Submit/Update'></td></tr>
</table>
</form>
EOT;
    return $f;
}

function update_calendar(){
    echo "Updating Calendar";
    $pdo = MyPDO::instance();
    $sql = "INSERT INTO `events` set event=:cevent,datetime=:cdate,city=:ccity,
        location=:clocation,contact=:ccontact,info=:cinfo;";
    $stmt = $pdo -> prepare ($sql);
    $cdata = array(
        'cevent' => $_POST['cevent'],
        'cdate' => date('Y-m-d H:i', strtotime($_POST['cdate'])),
        'ccity' => $_POST['ccity'],
        'clocation' => $_POST['clocation'],
        'ccontact' => $_POST['ccontact'],
        'cinfo' => $_POST['cinfo']
    );

    #echo "<pre>", print_r($cdata,true),"</pre>";
    $stmt->execute($cdata);


}

function start_page(){
    $t=<<<EOT
<html>
<head>
<link rel=stylesheet href='/css/news3.css'>
</head>
</body>
EOT;
    return $t;
}

function generate_files($h,$t){
    echo "<p>Generating Files</p>";
    file_put_contents(SITEPATH . "/news/calendar.html",$h);
    #file_put_contents($calendar_test,$calendar_h);
    file_put_contents(SITEPATH . "/news/news_next/tease_calendar.txt", $t);
}


############################################





