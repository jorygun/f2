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
	


if ($login->checkLogin(4)){
   $page_title = 'The Leader Board';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	echo <<<EOT
	<style type='text/css'>
	
		table {border-collapse:collapse;}
		table tr th {
			 border-bottom:2px solid black;
		}
		tr td {padding-top:6px;padding-bottom:6px;}
		tr.odd td {background:#ddd;}
	</style>
EOT;
	echo $page->startBody();
}
// END START
function add_flames_assoc($username,$user_amd,$upwards){
	$notflame = array(
		'user_id' => '0',
		'username' => $username,
		'user_amd' => $user_amd,
		'upwards'=> $upwards,
		'status' => '',
		'email_status' => '',
	);
	return $notflame;
}


//

require_once "read_functions.php";

$sql="SELECT user_id,username,user_amd,upwards,status,email_status from `members_f2` where upwards is NOT NULL
    ORDER BY username";

$flames = $pdo->query($sql)->fetchAll();

$not_flames = array();
// 	#name,amd,upwards
// $not_flames[] = add_flames_assoc('Umesh Padval','NVD Marketing','C-cube');
// $not_flames[] = add_flames_assoc('Cyrus Tsui','Dir. PLD','Lattice Semi');
// $not_flames[] = add_flames_assoc('Jensen Huang','Prod Engineering','NVIDIA');
// $not_flames[] = add_flames_assoc('Steve Wahl','??','Telechips');
// $not_flames[] = add_flames_assoc('Jiang Li','??','??');
// $not_flames[] = add_flames_assoc('Scott Gardner','??','Nanowatt Design');
// $not_flames[] = add_flames_assoc('Lance Smith','??','Primary Data');
// $not_flames[] = add_flames_assoc('Atiq Raza','??','Raza Foundries');
// $not_flames[] = add_flames_assoc('Narbeh Derhacobian','??','Adesto');
// $not_flames[] = add_flames_assoc('Jim Ready','Software engineering, AMC','Ready Systems');
// $not_flames[] = add_flames_assoc('Jim Shinn','VP Sales, Japan','Founder, Dialogic); CEO Teneo Intelligence');
// $not_flames[] = add_flames_assoc('VP Shenoy','Design Engineering','Founder: Euclid Software');

$movers = array_merge($flames,$not_flames);

?>


<p>Many AMD employees have gone on to run their own companies.  At the urging of Patrick Henry and John East, and with suggestions from many FLAMEs,
we&rsquo;ve compiled this list of AMDers who went on to start or lead companies. For corrections or additions or suggestions to this table, please send email to
<a href='mailto:editor@amdflames.org'>editor@amdflames.org</a>.</p>



<table>
<tr><th style='width:20%'>Name</th>
    <th style='width:40%'>At AMD</th>
    <th style='width:40%'>Company</th></tr>


<?php
$rowcount=0;
foreach ($movers as $row)
{
    $dchar=$lchar=$profile_link = '';
    $rowclass= ($rowcount%2 == 0)?'odd':'even';
    
    if ($row['user_id'] == 0 || substr($row['status'],0,1) == 'Y' ){
    	$profile_link = $row['username'] . ' -'; 
    }
    else {
    	$profile_link="<a href='/profile.php?uid=${row['user_id']}'
    target='profile'>${row['username']}</a>";
    
     	if (substr($row['email_status'],0,1)=='L') {
        	$lchar='@';
    	}
	}

    if ($row['status'] == 'D'){
        $dchar='*';
        $lchar='';
    }
   
   

    echo "<tr class=$rowclass><td>"
    . $profile_link . $dchar . $lchar .  '</td>'
    . '<td>'  . $row['user_amd'] . '</td>'
    . '<td>'  . $row['upwards'] . '</td></tr>' . "\n";
    ++$rowcount;
}

?>




</table>
<hr>
- Not a Flame Member - ask them to join?<br />
* Deceased<br />
@ Have no contact info<br />
<br /$profile_link = $row['username'] . ' -'; >
</body></html>
