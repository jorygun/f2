<?php
//BEGIN START
	require_once 'init.php';
	if (f2_security_below(0)){exit;}
//END START

$pdo = MyPDO::instance();
$nav = new NavBar(1);
$navbar = $nav -> build_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">

 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta http-equiv="X-UA-Compatible" content="IE=edge">
 <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root directory -->
 <link rel="stylesheet" href="/css/normalize.css">
 <link rel="stylesheet" href="/css/main.css">
 <link rel="stylesheet" href="/css/flames2.css">

<script src="js/vendor/modernizr-2.8.3.min.js"></script>

<style type="text/css">


	</style>
	<title>Opportunties</title>
	<meta name="generator" content="BBEdit 11.0" />
</head>
<body>

<?=$navbar?>



<h3>Opportunities</h3>
<p>These opportunities have been submitted by FLAMEs members.<br><br>
If you‘d like to post something here, email the information to the 
<a href="mailto:editor@amdflames.org" target="_blank">editor</a>.</p>

 <?php

  $sql = "
        SELECT title,owner,owner_email,location,created,link
        FROM opportunities
        WHERE active = TRUE

        ;";
    if ($result = $pdo->query($sql) ){
         $opp_report = '';

        foreach ($result as $row){
            $listing =
            "<p><b>$row[title] -  $row[location]</b></p>
             Contact $row[owner] <a href=mailto:$row[owner_email]>$row[owner_email]</a><br>
             Posted $row[created]<br>
             Info: <a href='$row[link]' target='_blank'>$row[link]</a>
             <br><br>
             ";
              $opp_report .= "<hr> $listing";

        }
        echo $opp_report;

    }
    else {echo "No Current Listings";}
?>


</body></html>


</html>
