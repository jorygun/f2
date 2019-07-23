<?php
//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(1)){exit;}
	use digitalmx\MyPDO;
//END START


#two codes [R,T] alternated as status codes for reviewing entries
#set run2 to true or false to reverse sense


/* script to view manage asset database.


*/



#require_once "news_functions.php";
# has the get_asset routines

require_once "asset_functions.php";

$pdo = MyPDO::instance();

$sql_now = sql_now('date');
// $rcodes = array (
//     'S' => 'S',
//     'R' => 'R',
//     );
#two codes alternated as status codes for reviewing entries
#set run2 to true or false to reverse sense

$rcode = 'R'; #tag to look NOT for, and set to
$andwhere = '' ; #additional where clauses

#$andwhere = "AND status = 'N'";


?>
<html>
<head>
<title>Asset Tag Review</title>



<script type='text/javascript' src = '/js/f2js.js'></script>


<link rel='stylesheet' href='/css/flames2.css'>
<link rel='stylesheet' href='/css/news3.css'>
</head>
<body>


<?php

if (($_SERVER['REQUEST_METHOD'] == 'GET') ){
   //  if ( ! isset($_SERVER['QUERY_STRING'])
//    || ! in_array($_SERVER['QUERY_STRING'],['R','S'])   ){
//         die ("Must run with ?S or ?R");
//     }
//     $run = $_SERVER['QUERY_STRING'] ;

    get_unreviewed_items($rcode,$andwhere);

}

elseif ($_SERVER['REQUEST_METHOD'] == 'POST' ){
    $last_tags = '';
     $id = $_POST['id'];
    if (isset($_POST['last_tags']) ){$last_tags = $_POST['last_tags'];}

     if (isset($_POST['delete']) && $_POST['delete'] == 'Delete' ){
        delete_asset($id);
        get_unreviewed_items($rcode,$andwhere,$last_tags);
        exit;
    }
    if (!empty($_POST['skip']) ){
        set_skip($id);
        echo "<p>ID $id will be skipped for 2 days.</p>";
       get_unreviewed_items($rcode,$andwhere,$last_tags);
       exit;
    }

    $row = $_POST;

    #recho ($_POST,'post data');
    $last_tags = post_review($rcode,$row);

    get_unreviewed_items($rcode,$andwhere,$last_tags);
}

echo "
</body></html>
";

##############################################
function set_skip ($id){
    $pdo = MyPDO::instance();
    $sql = "Update `assets` set skip_ts = NOW() where id=$id;";
    $pdo->query($sql);
}

function post_review($rcode,$row) {
    $pdo = MyPDO::instance();
    global $asset_status;
    $tagu = array();
    $tags = '';

   # recho ($row,'Into post_review');

    $tags =  trim(charListToString($row['tags']));
    if (
    	isset($row['submit'])
    	&& 
   	strpos($row['submit'],'repeat') !== false
		&&  ! empty ($row['last_tags']) 
		){
        $tags = $row['last_tags'];
    }

    $id = $row['id'];

    if (!in_array($rcode,array_keys($asset_status))){die ("Error: rcode '$rcode' on id $id not in status list");}


    if (strpos($tags,'X') !== false ){ #problem
        $rcode = 'E'; #error
    }

    $title = spchard($row['title']);
    $caption = spchard($row['caption']);

    if (empty($id)){die ("No id");}
    $sql = "Update `assets` set tags = ?, title = ?, caption = ?, status = '$rcode', review_ts=NOW() where id = ?";
    $sqlu = $pdo->prepare($sql);
    #echo "Updating $id -> $tags,$rcode" . BRNL;

    $sqlu -> execute([$tags,$title, $caption,$id]);
    return $tags;
}

function get_unreviewed_items($rcode, $andwhere='',$last_tags=''){

        $get_limit = 1;
        $pdo = MyPDO::instance();

        #skip Deleted,allready checked, Hard delete, Test, Error
        $sql = "SELECT * FROM `assets`
            WHERE status in ('N','U')
            AND (skip_ts is  null OR CURDATE() > skip_ts + INTERVAL 2 DAY)
            $andwhere
            ORDER BY review_ts,id LIMIT $get_limit";
        #echo "<div style='border:1px solid gray;'> $sql</div><br>";

        $a = $pdo->query($sql);
        $c = $a->rowCount();
        if ($c == 0){echo "<p style='color:red;font-weight:bold;'>Nothing found.</p>"; exit;}
        else {
            echo "<p>$c Found.</p>\n";
        }



        while ($row = $a->fetch()){
            $id = $row['id'];
            $row['last_tags'] = $last_tags;
            echo show_asset($row);
        }

    #submit form




}



function show_asset($row) {
        global $asset_tags;
        global $asset_status;
       $id = $row['id'];
       $title = $row['title'];
       $type=$row['type'];
       $tags=$row['tags'];
       $caption = $row['caption'];
        $status = $row['status'];
        $status_name = $asset_status[$status];
        $run = $row['run'];
        $last_tags = $row['last_tags'];

        $tag_options = buildCheckBoxSet('tags',$asset_tags,$tags,1,true); #show codes

       # $tag_options = tag_options($id,$tags);


        if ($title == $caption){$caption = '';}

       $title=spchar(stripslashes($title));
        $caption = spchar(stripslashes($caption));

        $status_style = '';

        if ($status == 'D'){
            $status_style = "color:red;";
            $image = "(Image Deleted)";

        }
        else {
         $image = get_asset_by_id($id);

        }





    $output = <<< EOT
    <form method='post'>
        <input type='hidden' name='last_tags' value='$last_tags'>
        <table>
       <tr style='border-top:1px solid blue;'> <td>
        <input type='hidden' name = 'id' value = '$id'>

        $id</td>
      </tr>
      <tr><td>Title:</td><td colspan='2'><input name='title' value = '$title' size=80></td></tr>
        <tr><td>Caption</td><td colspan='2'><input type='text' name='caption' value='$caption' size='120'></td</tr>
        <tr><td>Info</td><td>status:<span style='$status_style'>$status_name ($status)</span> Type: ${row['type']} (${row['mime']})</td></tr>

        <tr>

       <td colspan='2'>$tag_options<br>
       <input type='submit' name='accept'>
EOT;

        if (!empty($last_tags)){
            $output .=  "<input type='submit' name='submit' value='repeat $last_tags' style='background:#cfc'> ";
        }

    $output .=  <<<EOT
        <input type='submit' name='skip' value='Skip'>
       </td>
       <td $image <br class='clear'>
        <a href='/scripts/asset_edit.php?id=$id' target='asset_edit'> edit</a><br>
       <a href='/scripts/assets-review.php'>Refresh</a>
       <p class='right'><input type='submit' style='background-color:red;font-weight:bold;' name='delete' value='Delete'></p>
    </td></tr>


        </table>
        </form>
EOT;

    return $output;
}

function tag_options($id,$tag) {
    global $asset_tags;
    $tagopt = '';
    foreach ($asset_tags as $t => $label){
        if (strpos($tag,$t) !== false){
        #tag is present
         $tc = 'checked';
        }
        else {$tc = '';}
        $tagopt .= "<input type='checkbox' name='tag_${id}[]' value='$t' $tc>$label ";
    }
    return $tagopt;
}


