<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
require "asset_functions.php";
require "news_functions.php";

/* script to review all the image assets records and create assets from them.
    reads from a search file generated from asset_search, and lets you
    look at each graphic, and choose to insert into db via the asset_edit script.


*/
$assign_limit = 20;  #only show this many records




?>
<html>
<head>
<title>Assign Assets</title>
<style type='text/css'>
body {width:1000px;}
tr td img {width:250px;}
</style>
</head>
<body>
<?
if ($_SERVER[REQUEST_METHOD] == 'GET'){show_form();}
else {
    if (isset($_POST[skip])){
        if (($skips_recorded= update_skips($_POST[skip]))>0){echo "$skips_recorded graphics recorded as skips<br>\n";}
        }
        show_data($_POST[searches]);
}

function show_form(){
    #find all the search files avasilable
    $asset_dir = SITE_PATH . "/assets";
    $files = glob("$asset_dir/search*");

    foreach ($files as $filepath){
        $file = pathinfo($filepath,PATHINFO_BASENAME);
        $ftime = filemtime($filepath);
        $fcolor = ($ftime > (time() - 60*60*24) )? 'green' : 'gray';
        $fdate = date ('Y-m-d H:i',$ftime) ;
        $chooser .= "<p style='color:$fcolor'><input type='radio' name='searches' value='$file'>$file ($fdate)</style></p>";
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
Showing up to $assign_limit unassigned records from file $datafile.
<form method='POST'>
<input type='hidden' name='searches' value='$datafile'>
<table style='width:1200px;'>
";

while (($line = fgets($tags)) !== false && ($tobeassigned <= $assign_limit)){
	list($tagid,$loc,$img,$sql_date,$context,$error) = str_getcsv($line,"\t");
   # echo "$tagid $img <br>\n";
	#SEE if tag is in the asset db already
	$imgenc = mysqli_real_escape_string($GLOBALS['DB_link'],$img);
	$sql = "SELECT id FROM assets WHERE url = '$imgenc';";
	#echo $sql;
    if ($context == 'Orphaned graphic'){$notes = $context;}
	 $result = mysqli_query($GLOBALS['DB_link'],$sql);
	$assetid = '';
	if (($rows = mysqli_num_rows($result)) > 0){
	   # echo " rows: $rows ";
		// $row = mysqli_fetch_assoc($result);
// 		$assetid = $row[id];
		#echo "asset id $assetid for image $img";

		#$thumb = get_asset_by_id($assetid);
		## fix the missing first use info
		// $sql = "update assets set first_use_date = '$sql_date',first_use_in= '$loc' where id = $assetid;";
//         $result = query($sql);

		#echo "<tr><td>$tagid</td><td>Asset $assetid</td><td>$thumb</td></tr>\n";
		#echo "<tr><td colspan='4'><hr></td></tr>\n";
		++$preassigned;
	}


	else {
	    ++$tobeassigned;
	    $year = substr($sql_date,0,4);
	    $irefenc = urlencode($img);
	    $noteenc=$notes;
	    $add_tag = "<a href='/scripts/asset_edit.php?" .
	        "url=$irefenc" .
            "&notes=$noteenc" .
	        "&vintage=$year" .
	        "&first_use=$sql_date" .
	        "&first_in=" . urlencode($loc) .
	        "' target='asset_edit'>Add Asset</a>";
	    $skip_tag = "<input type='checkbox' name='skip[]' value='$irefenc'>Skip this item";

        echo <<<EOT
        <tr><td>$tagid</td>
        <td width='150'>$img</td>
        <td>In: <a href='$loc' target='newspage'>$loc</a></td><td></td></tr>

EOT;
        if (!empty($context)){echo "
		<tr><td></td><td colspan='3'>$context</td></tr>\n";
        }
    $testimage = $img;
     $linkext = strtolower(pathinfo($testimage,PATHINFO_EXTENSION));



    if (empty($error)){
   # echo "no error: $tagid - $img<br>";
        if (in_array($linkext,$image_extensions)){$iref = "<img src='$img' style='maxwidth:250px;'>";}
        elseif (in_array($linkext,$document_extensions)){$iref = "<p style='color:blue;'>Document: <a href='$img' target='otherasset'>$img</a></p>";}
        elseif (in_array($linkext,$mmm_extensions)){$iref = "<p style='color:orange;'>Multimedia: <a href='$img' target='otherasset'>$img</a></p>";}
        else {$iref = "Extension $linkext not in extensions list";}
    }

    elseif (strpos($error,'not')){$iref = "**** $error ****";}
    elseif (strpos($error,'eplaced')){$iref = "**** $error ****<br><img src='$img' style='maxwidth:250px;'>";}
    else {$iref = "$error ????";}


        echo "
        <tr><td></td><td colspan='3'>$iref</td>
       </tr>\n";

    if (empty($error)){echo  "<tr><td></td><td><button>$add_tag</button> $skip_tag</td></tr>";}
        echo "<tr><td colspan='4'><hr></td></tr>\n";

	}




}
echo "</table><input type='submit'></form>";
echo "Also found $preassigned graphics already in asset database.";
}

function update_skips($skips){
    #put urls into the db with status skipped.
    #print_r($skips);

    foreach ($skips as $url){
    $url = urldecode($url);
     $sql = "insert into assets set url = '$url', status = 'S',notes='Orphaned Graphic';";
     #echo "Skip: $sql<br>\n";
    $result = mysqli_query($GLOBALS['DB_link'],$sql);
    ++$skipcnt;
    }
    return $skipcnt;
}


?>


</body></html>
