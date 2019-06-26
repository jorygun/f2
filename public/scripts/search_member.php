<?php

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(2)){exit;}
	use digitalmx\flames\Definitions as Defs;
//END START

global $G_member_status_set;



$decade_choices = radio_choices('amd_when',Defs::$decades);
$location_choices = radio_choices('amd_where',Defs::$locations);
$department_choices = radio_choices('amd_dept',Defs::$departments);

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
function show_found ($row){
	//result of sql query from statement object
	// headings is array of variable names and headings (if not supplied, then show all)

	 $jurl = "window.open(this.href,'profile','height=700,width=1200,resizeable,scrollbars');return false";
   
        $recid = $row['id'];
        $username = $row['username'];
        $status_name = Defs::getMemberDescription($row['status']) ;
        $profile_url = "/scripts/profile_view.php?id=$recid";
        $profile_updated = date('M d, Y',(int)$row['profile_updated']);
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
		$out = "<tr>"
		    . '<td>' . $user_val . '</td>'
		    . '<td>' . $row['user_from'] . '</td>'
		    . '<td>' . $row['user_amd'] . '</td>'
		    . '<td>' . $show_email . '</td>'
		    . '<td>' . $profile_updated .  '</td>'
		    . "<tr>\n";


	

	return $out;
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

		$pdo = MyPDO::instance();
		echo "<h3>Search Results</h3>
		<p>Click on a Member's Name to view Profile.</p>";

		list ($CLEAR,$SAFE) = clear_safe($_POST);
	$is_member = 'status is NOT NULL ';
#    $is_member = "status IN ($G_member_status_set) ";

	$sql = "SELECT username,user_email,user_amd,user_from,TIMESTAMP(profile_updated) as profile_updated FROM `members_f2` WHERE $is_member ";
	$q=false;
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
	if (!empty($name = $CLEAR['name'])){
		$sql .= " AND username like '%$name%' " ;
		
	}
	$sql .= "LIMIT 50;";
	
	if ($st = $pdo->query ($sql) -> fetchAll(PDO::FETCH_ASSOC) ){
			foreach ($st as $row){
				$output .= show_found($row);
				++$found;
		    }
		    if ($found > 49) {
		    	$output .= "<tr><td colspan='5'>(First 50 shown.  Please change search to be more selective.)</td></tr>";
		    }
		    $output .= "</table> $found found.";
     
      
	} else {$output =  "<h3>Nothing Found</h3>";}
	echo $output;
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
