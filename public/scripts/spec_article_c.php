<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

#this script reproduces a single article with the flames comments attached.

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(1)){exit;}

//END START


	use digitalmx\MyPDO;
require_once "MyPDO.class.php" ;
$pdo = MyPDO::instance();
$itemdb = 'spec_items';
$test = 0;

if(isset($_GET['id'])){$item_id = $_GET['id'];}
else {echo "No article requested"; exit;}
if (isset($_GET['test'])){$test =1;}



/*
	call up specific page referred to by item_id
	all commenting is embedded in the page itself.
	(Will be in spec folder, indexed in spec_item table
	manually entered).
*/



$sql = "SELECT * from $itemdb WHERE id = $item_id;";
    #echo $sql,"<br>";
    $sq = $pdo -> query($sql);
    if ($sq){
        $row = $sq -> fetch(PDO::FETCH_ASSOC);

         $htitle = htmlentities($row['title']);
         $pubdate = $row['date_published'];
         $spec_url = $row['url'];

        echo "Fetching item $item_id, $htitle<br>\n";

        $header = "location:" . SITE_URL . "/special/$spec_url";
        if ($test){
            pecho ($header);
        }
        else {header ($header);}
    }
else {echo "oops";}






