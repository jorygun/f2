<?php

//BEGIN START
	require_once "init.php";
	if (f2_security_below(2)){exit;}
//END START

global $G_member_status_set;



$decade_choices = radio_choices('amd_when',$G_decades);
$location_choices = radio_choices('amd_where',$G_locations);
$department_choices = radio_choices('amd_dept',$G_departments);

	$headings = array (
		'username'	=>	'Name',
		'user_from'	=>	'Location',
		'user_amd'	=>	'At AMD',
		'user_email'	=>	'Email',
		'profile_updated'	=>	'Profile last Updated'
	);

$nav = new navBar(1);
$navbar = $nav -> build_menu();

//// FUNCTIONS ////
function show_found ($title,$stobject){
	//result of sql query from statement object
	// headings is array of variable names and headings (if not supplied, then show all)
	 global $G_member_desc;
	 $jurl = "window.open(this.href,'profile','height=700,width=1200,resizeable,scrollbars');return false";
    $fcount = $stobject->rowCount();

	$o = "<tr><td colspan='5'><i>$title</i> ($fcount found)</td></tr>";

	foreach ($stobject as $row){
        $recid = $row[id];
        $username = $row['username'];
        $status_name = $G_member_desc[$row['status']] ;
        $profile_url = "/scripts/profile_view.php?id=$recid";
        $profile_validated = $row['profile_updated'];
        switch ($row['status']) {
            case 'D' :
                $user_status = '*';
                break;
            case 'I' :
                $user_status = '#';
                break;
            default:
                $user_status = '';
        }

        $user_val = "<a href = '$profile_url' target='_blank' onclick=\"$jurl\">$username</a> $user_status";



		$show_email = display_email($row);
		++$row_count;
		$o .= "<tr>"
		    . '<td>' . $user_val . '</td>'
		    . '<td>' . $row['user_from'] . '</td>'
		    . '<td>' . $row['user_amd'] . '</td>'
		    . '<td>' . $show_email . '</td>'
		    . '<td>' . $row['profile_updated'] .  '</td>'
		    . "<tr>\n";


	}

	return $o;
}



function radio_choices($field,$array){
// compute radio buttons from field name array
	$choices = "<input type='radio' name='$field' value='' checked>Any ";
	foreach ($array as $k=>$v){
		$choices .= "<span class='nobreak'><input type='radio' name='$field' value='$k' >$v</span> ";
	}
	return $choices;
}

////// END FUNCTIONS  ////


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="iso-8859-1" />

	 <meta name="viewport" content="width=device-width, initial-scale=1">
	 <meta http-equiv="X-UA-Compatible" content="IE=edge">
	 <link rel="apple-touch-icon" href="apple-touch-icon.png">
			<!-- Place favicon.ico in the root directory -->
	 <link rel="stylesheet" href="../css/normalize.css">
	 <link rel="stylesheet" href="../css/main.css">
	 <link rel="stylesheet" href="../css/flames2.css">

	<script src="../js/vendor/modernizr-2.8.3.min.js"></script>
	<script src="../js/f2js.js"></script>

	<style type="text/css">


		</style>


		<title>Flame Member Search (R2)</title>
		<meta name="generator" content="BBEdit 11.0" />


</head>

<body >
<?=$navbar?>


<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		global $DB_link;

		$pdo = MyPDO::instance();
		echo "<h3>Search Results</h3>
		<p>Click on a Member's Name to view Profile.</p>";

		list ($CLEAR,$SAFE) = clear_safe($_POST);
	$is_member = 'status is NOT NULL ';
#    $is_member = "status IN ($G_member_status_set) ";

	$sql = "SELECT * FROM $GV[members_table] WHERE $is_member ";

	if (! empty ($CLEAR['amd_where'])) {
			$v=trim ($CLEAR['amd_where']);
			$sql .= " AND amd_where like '%$v%' ";
			$q=true;
		}
	if(! empty ($CLEAR['amd_when'])){
			$v=trim ($CLEAR['amd_when']);
			$sql .= " AND amd_when like '%$v%' ";
			$q=true;
		}
	if(! empty ($CLEAR['amd_dept'])){
			$v=trim ($CLEAR['amd_dept']);
			$sql .= " AND amd_dept like '%$v%' ";
			$q=true;
		}

	// This sql can now be used to buil all the queries from
#echo "<pre>Searching on: \n$sql\n</pre>";

	$found = 0;
	$output = "<p>* Deceased; # Inactive</small></p>";
	$output .=	"<table class='bordered'><tr>";
		foreach ($headings as $k => $v){
			$output .= "<th>$v</th>";
		}
		$output .= "</tr>\n";

	// if no name request, then one search and yer done.
	if ($q && empty($CLEAR['name'])){
		if ($st = $pdo->query ($sql)) {
		    $rc = $st->rowCount();
            if ($rc > 0){
                $found += $rc;
			    $output .= show_found('No name: searching attributes only',$st);
			}
		}
	}

	else {

			$whole_name = $CLEAR['name'];
			$last_name = parse_name($CLEAR['name'],'last');
			echo "Parsing $whole_name into last name $last_name<br>";

		// first try using exact match
			$sql2 = $sql . " AND username = '$whole_name' " . "LIMIT 50;";

			if ($st2 = $pdo->query ($sql2)) {
		        $rc = $st2->rowCount();
		        $found += $rc;
			    $output .= show_found("Searching on user name exactly matches $whole_name",$st2);
			    if ($rc >49){
		    $output .= "<tr><td colspan='5'>(First 50 shown.  Please change search to be more selective.)</td></tr>";
                 }
		    }
        if ($found == 0) {
            // now try last name
            $more = " AND username REGEXP \" $last_name\$\" AND username <> '$whole_name' ";

            $sql3 = $sql . $more . "LIMIT 50;";
            if ($st3 = $pdo->query ($sql3)) {
		        $rc = $st3->rowCount();
		        $found += $rc;
			    $output .= show_found("Searching on user name ends with $last_name ",$st3);
			    if ($rc >49){
		    $output .= "<tr><td colspan='5'>(First 50 shown.  Please change search to be more selective.)</td></tr>";
                 }
		    }
        }

		//this is too dangerous
		// now try last name anywhere

		if ($found == 0) {
            $more = " AND username like '%$last_name%' AND username NOT LIKE '% $last_name' AND username <> '$whole_name' ";
            $sql4 = $sql . $more . "LIMIT 50;";

            if ($st4 = $pdo->query ($sql4)) {
		        $rc = $st4->rowCount();
		        $found += $rc;
			    $output .= show_found("Searching on $last_name anywhere in user name ",$st4);
			    if ($rc >49){
		    $output .= "<tr><td colspan='5'>(First 50 shown.  Please change search to be more selective.)</td></tr>";
                 }
		    }

        }
	}

	if ($found){
		$output .= "</table> $found found.";
		 echo $output;
	}
	else {echo "<h3>Nothing Found</h3>";}
}
?>

<br>
<hr>
<h3>New Search</h3>

<p>You can search for a member here.  Enter your search criteria, and you will get a list of up to 10 matching members.</p>
<p>Enter a name (first, last, or both) (not case sensitive, but not a partial.  Enter the whole last name).  I will search first for an exact match ('First Last', e.g.), then for matching last name.  This system stores names in a single name field, so I have to kind of guess about first and last names.</p>
<p>Note that the when/where/dept choices below are new, and not many people
have filled them in yet, so you may not get many results.</p>

<form method="POST">

<table >
<tr><td >Name</td><td ><input type="text" name="name" class="input" value=""></td></tr>
<tr><td colspan='2'>The when/where/dept choices below are new, and not many people
have filled them in yet, so they may not be useful in searching.</td></tr>

<tr><td>At AMD </td><td><?=$decade_choices?></td></tr>
<tr><td>Where</td><td><?=$location_choices?></td></tr>
<tr><td>In Dept</td><td><?=$department_choices?></td></tr>
</table>
<input type="submit" value="Search">
</form>

</body></html>
