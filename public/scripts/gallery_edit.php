<?php
#ini_set('display_errors',true);


//BEGIN START
	require_once 'init.php';
	if (login_security_below(6)){exit;}
    require_once 'asset_functions.php';

//END START


//  ini_set('max_execution_time',3600);
//     ini_set('set_time_limit',3600);
//     ini_set('max_input_time',3600);


$gallery_status = array(
    'G' => 'Good.  Publish',
    'D' => 'Delete',
    'N' => 'New'
    );

$nav = new navBar(1);
$navbar = $nav -> build_menu();



?>

<html>
<head>
<title>Gallery Editor</title>
<link rel='stylesheet' href='/css/news3.css'>
<script>
function choose_gallery(id=''){
    var gid = 0;
    if (id.length !== 0) {gid = id;}
    else {
         gid = document.getElementById('egallery').value;
    }
    window.location.href='/scripts/gallery_edit.php?id=' + gid ;
}
</script>
<script src='/js/f2js.js'></script>
</head>
<body>
<?=$navbar?>

<?

if ($_SERVER['REQUEST_METHOD'] == 'POST'){

         $id = post_gallery($_POST);
         echo "gallery id $id has been posted." . BRNL;

        $_GET['id'] = $id;

}

// SHOW FORM
if (!empty($id = $_GET['id'])){
    $itemdata = get_gallery_data($id);
    #recho ($itemdata,'result from get_gallery_data');

}
else {$itemdata=[];}


show_gallery_form($itemdata,$gallery_status);


######################################################################
function thumb_for_asset ($asset_list){
    #returns thumb for first asset listed
    preg_match('/^(\d+)/',$asset_list,$m);
    $first_asset = $m[1];
    echo "Obtaining thumb from asset $first_asset";
    $pdo = MyPDO::instance();
    $sql = "SELECT thumb_file from `assets` where id = $first_asset";

    if (!$thumb = $pdo->query($sql)->fetchColumn() ){
        die ("sql failed: $sql");
    }
    echo " $thumb" . BRNL;
    return $thumb;
}


function make_gallery_images($itemlist) {
    foreach ($itemlist as $id){
        if (!empty ($gfile = choose_graphic_url('/assets/galleries',$id))){
            continue; #have the file
        }
        #make gallery file
        #get source for asset; create thumb
        $pdo = MyPDO::instance();
        $sql = "SELECT url from `assets` where id = $id";
        $fsource = $pdo->query($sql)->fetchColumn();

        create_thumb($id,$fsource,'galleries');


    }

}

function list_galleries(){
    $pdo = MyPDO::instance();
    $sql = "SELECT * from `galleries` where status != 'D' ORDER BY vintage DESC";
    $result = $pdo->query($sql);
    $c = 0;
    $v = '';

    $out = "<table>\n<tr><td>";
    foreach ($result as $row){
        if ($v != $row['vintage']){
            $v = $row['vintage'];
            $out .= "<h4>$v</h4>\n";
        }
        $out .= "<a href='#' onClick = 'choose_gallery(${row['id']})'>"
        . spchar($row['title'])
        . '</a>';

        $out .= '<br>';
    }
    $out .= "</td></tr></table>";
    return $out;
}

function post_gallery($post){
    $pdo = MyPDO::instance();

    #recho ($post,"Incoming post");
     $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $post['gallery_items'],
            $post['thumb_file'],
            $post['admins']
            );


    #make sure there are gallery files for each photo
        make_gallery_images(list_numbers($post['gallery_items']));


    if (empty($post['id']) or $post['id'] == 0){
        # get thumb file for first asset
        $thumb = thumb_for_asset($post['gallery_items']);
        $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $post['gallery_items'],
            $thumb,
            $post['admins']
            );



        $sql = "Insert into `galleries` (title,caption,vintage,gallery_items,thumb_file,admins,status)
            values (?,?,?,?,?,?,'N')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $newid = $pdo->lastInsertId();
        echo "New Gallery id: $newid" . BRNL;
        return $newid;

    }
    else {
        $id = $post['id'];
        if (empty($post['thumb_file'])){
            $post['thumb_file'] = thumb_for_asset($post['gallery_items']);
        }
        if (empty($post['thumb_file'])){
            echo "Error: cannot get thumb_file for this asset" . BRNL;
            exit;
        }
        $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $post['gallery_items'],
            $post['thumb_file'],
            $post['admins']
            );

        $sql = "Update `galleries` set title = ?, caption = ?, vintage = ?, gallery_items = ?,thumb_file = ?, admins=? where id = $id;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $id;
    }
}

function get_gallery_data($id){
    $pdo = MyPDO::instance();

     $itemdata = array(); #store data to display
    #echo "Starting get_asset_data";

        // retrieve existing record
        $sql = "SELECT * FROM `galleries` WHERE id =?;";
        $stmt = $pdo->prepare($sql);

         $stmt->execute([$id]);
       if (!$itemdata = $stmt ->fetch(PDO::FETCH_ASSOC)  ){
                    die ("No gallery found at $id");
        }



    #print_r ($itemdata);
    return $itemdata;
}


function show_gallery_form($itemdata,$gallery_status) {
    $id = (isset($itemdata['id'] ))?$itemdata['id']: 0 ;

// display form using data from itemdata

foreach (['title','caption'] as $f){
    $hte[$f] = hte($itemdata[$f]);
}
$statuscheckedG = $statuscheckedD = $statuscheckedN = '';
if($itemdata['status'] == 'G'){$statuscheckedG = 'checked';}
if($itemdata['status'] == 'D'){$statuscheckedD = 'checked';}
if($itemdata['status'] == 'N'){$statuscheckedN = 'checked';}

$statusfield = <<<EOT
    <input name = 'status' type='radio' value='G' $statuscheckedG >${gallery_status['G']}
    <input name = 'status' type='radio' value='N' $statuscheckedN >${gallery_status['N']}
    <input name = 'status' type='radio' value='D' $statuscheckedD >${gallery_status['D']}
EOT;
//

echo "<button onClick=showDiv('glist')>Show Galleries</button>
    <div class='hidden' id='glist'>";
    echo list_galleries();
echo "</div>\n";

    echo <<< EOT
<h4>Gallery Edit/Entry (main)</h4>
<p>

Edit Gallery (0 for new) <input type=text name='egallery' id='egallery'>
<button onClick = 'choose_gallery()'>Go</button>
</p>
<hr>


<form  method="POST"  style="border:1px solid black;padding:6px;" name="gallery_form" >


<input type='hidden' name = 'id' value='$id'>


EOT;

echo <<<EOT
<hr>

<table>
<tr><td>ID</td><td>$id</td></tr>
<tr><td>Gallery Title</td><td><input type='text' size='60' name='title' id='title' value="${hte['title']}"></td></tr>
<tr><td>Caption</td><td><input type='text' size='60' name='caption'  value="${hte['caption']}"></td></tr>


<tr><td>Thumb File (normally don't change) </td><td><input type='text' name='thumb_file' value='${itemdata['thumb_file']}' size='40'></td></tr>

<tr><td>Origin</td><td>Vintage: <input type='text' name='vintage' value = "${itemdata['vintage']}" size="6">  </td></tr>
<tr><td>Assets</td><td><textarea rows='4' cols='20' name='gallery_items'>${itemdata['gallery_items']}</textarea></td></tr>
<tr><td>Status</td><td>$statusfield</td></tr>
<tr><td>User Name to Admin Photos</td><td><input type='text' name='admins' value='${itemdata['admins']}'</td></tr>

<tr><td>

<input type="submit" value='Submit'>

</td><td >
EOT;



echo <<<EOT
</td></tr>
</table>
</form>



EOT;

}







####################################################




