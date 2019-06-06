<?php
require "init.php";
require "asset_functions.php";
require "news_functions.php";

/*
This script shows all the search files (generated from asset_search.php and placed in /assets), lets you choose one, and all that urls in that one that are not already in the db get completely loaded into the database

    Used frequently in Jun 2016; probably not needed again.


*/


?>
<html>
<head>
<title>Insert Assets</title>
<style type='text/css'>
body {width:1000px;}
tr td img {width:250px;}
</style>
</head>
<body>
<?
if ($_SERVER[REQUEST_METHOD] == 'GET'){show_form();}
else {

        show_data($_POST[searches]);
}

function show_form(){
    #find all the search files avasilable
    $asset_dir = "$GLOBALS[sitepath]/assets";
    $files = glob("$asset_dir/search*");

    foreach ($files as $filepath){
        $file = pathinfo($filepath,PATHINFO_BASENAME);
        $chooser .= "<input type='radio' name='searches' value='$file'>$file<br>";
    }

    echo "
        <form method='POST'>
        Choose File:<br>
        $chooser

        <input type=submit>
        </form>
    ";
}

function show_data($datafile){
global $assign_limit;
global $document_extensions;
global $image_extensions;
global $mmm_extensions;




$tag_file = "$GLOBALS[sitepath]/assets/$datafile";

$tags = fopen($tag_file,'r') or die ("Can't open $tag_file");
$tobeassigned = $preassigned = 0;

echo "
Inserting unassigned records from file $datafile.
<form method='POST'>
<input type='hidden' name='searches' value='$datafile'>
<table style='width:1200px;'>
";

while (($line = fgets($tags)) !== false ){
	list($tagid,$loc,$img,$sql_date,$context,$error) = str_getcsv($line,"\t");
	++$recs_read;
	#echo "$line<br>\n";
    #echo "$tagid $img <br>\n";
	#SEE if tag is in the asset db already
    $imgenc = mysqli_real_escape_string($GLOBALS[DB_link],$img);
	$sql = "SELECT id FROM assets WHERE url = '$imgenc';";
	#echo $sql;

	 $result = mysqli_query($GLOBALS['DB_link'],$sql);
	$assetid = '';
	if (($rows = mysqli_num_rows($result)) > 0){
	   //  $row=mysqli_fetch_assoc($result);
// 	    echo "Found at $img at asset $row[id] . <br>\n";
		++$preassigned;
	}

	else {
	    ++$inserted;
	     $linkext = strtolower(pathinfo($img,PATHINFO_EXTENSION));
        $notes = '';
	     if (in_array($linkext,$document_extensions)){$type='Document';}
	      elseif (in_array($linkext,$image_extensions)){$type='Image';}
	       elseif (in_array($linkext,$mmm_extensions)){$type='Multimedia';}
            else {$type='Other';}
            #echo "$linkext -> $type<br>\n";
        if(!empty($error)){$notes = "*Error*: $error) \n";}
	    if(!empty($context)){$notes .= "Context: $context\n";}


	    $sq = array();
	     if(!empty($error)){$sq[status] = 'E';}
	    $sq[vintage] = substr($sql_date,0,4);
	   $sq[url] = $img;
	   $sq[first_use_in] = $loc;
	   $sq[first_use_date] = $sql_date;
	    $sq[notes]  = mysqli_real_escape_string($GLOBALS[DB_link],$notes);
        $sq[date_entered] = sql_now('date');
        $sq[type] = $type;

        $sqv = set_item_data($sq);
        $sql = "Insert assets SET $sqv;";
        if (0){ echo "<p>$sql</p>";}
        else {
         $result = mysqli_query($GLOBALS['DB_link'],$sql);
        $newid = mysqli_insert_id($GLOBALS['DB_link']);
        echo "inserting $img as id $newid.<br>\n";
       }
    }



    }

echo "$recs_read Records Read. Inserted $inserted;  already present: $preassigned.";
}




?>


</body></html>
