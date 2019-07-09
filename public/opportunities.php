<?php
<?php
namespace digitalmx\flames;

#ini_set('display_errors', 1);


//BEGIN START
	require_once "init.php";

	#require others

	use digitalmx\flames\DocPage;
	use digitalmx as u;
	use digitalmx\flames\Definitions as Defs;

	$pdo = MyPDO::instance();

	$page = new DocPage;
	$title = "Opportunities"; 
	echo $page->startHead($title, 3);
	echo $page->startBody($title ,2);

//END START

echo <<<EOT
<p>These opportunities have been submitted by FLAMEs members.<br><br>
If you‘d like to post something here, email the information to the 
<a href="mailto:editor@amdflames.org" target="_blank">editor</a>.</p>
EOT;

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
