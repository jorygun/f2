<?php
//BEIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(0)){exit;}
//END START
#ini_set('display_errors', 1);
	use digitalmx\MyPDO;
$nav = new navBar(false);
$navbar = $nav -> build_menu();

// See if user is logged in; then they can post an opportunity.

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">

 <meta name="viewport" content="width=device-width, initial-scale=1">
 <link rel="stylesheet" href="/css/news3.css">

<style type="text/css">


</style>
<title>Job Opportunities</title>

</head>
<body>
<div class='head'>
	<img class="left" alt="AMD Flames" src="/graphics/logo-FLAMEs.gif">
	<p class='title'>FLAME<i>news</i><br>

	</p>
</div>

<?=$navbar?>
<hr style="width: 100%; height: 2px;clear:both;">
<h3>Job Opportunities</h3>
<p>
 <?php
if (!isset($_GET['id'])){
    echo "Job listing requested without an id";
    exit;
}

$oid = $_GET['id'];

$pdo = MyPDO::instance();
 $sql = " SELECT *
            FROM opportunities
            WHERE id = $oid;";
$row = $pdo->query($sql)->fetch();
if (!$row){
    echo "No job listing for id $oid.";
    exit;
}

 $status = 'Expired';
            $xtime = strtotime($row['expired']);
            $ctime = time();
            if ($row['expired'] == '0000-00-00'){$status = 'Active';}
            elseif ($ctime < $xtime ){$status = 'Active';}

 $description = thtml($row['description']);
            $listings .=
            "<p><b>${row['title']} -  ${row['location']}</b></p>
             Posted By: ${row['owner']}; Contact: <a href=mailto:${row['owner_email']}>${row['owner_email']}</a><br>
             Posted ${row['created']}<br>
             Expires: ${row['expired']}<br>
             Description: $description<br>
             More Info: <a href='${row['link']}' target='_blank'>${row['link']}</a><br>
             <i>Status: $status ($oid)</i><br>

             ";
            if (substr($status,0,7)=='Expired'){$listings .= "Expired: ${row['expired']}";}

        echo $listings;

echo "</body></html\n";


#####################################

