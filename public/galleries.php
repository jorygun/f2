<?php
//BEGIN START
	require_once 'init.php';
	require_once "./scripts/asset_functions.php";
	use digitalmx\MyPDO;
	if (f2_security_below(1)){exit;}

//END START





if ($gal = $_SERVER['QUERY_STRING']){display_gallery($gal);}
else{show_galleries();}
exit;

#########################################
function display_gallery($gal){
    $nav = new navBar(1);
    $navbar = $nav -> build_menu("<p><a href='/galleries.php/' target='gallery'>Return to All Galleries</a></p>");
    $pdo = MyPDO::instance();

    if (! is_numeric($gal)){
        preg_match('/^(.*)\.\w+$/',$gal,$m);
        $name = $m[1] ;
        if (empty($name)){$name = $gal;}
        $tran_table = array(
            '1AMD' =>  1296,
            '915Art' => 1329,
            '915Groundbreaking' => 1330 ,
            'Beppos2004' => 1331,
            'IDC' => 1332,


        );
        $new_gal = $tran_table[$name];
        echo "(Translated $gal to $name to asset $new_gal)";
        $gal = $new_gal;


    }

    if (! $gal){show_galleries("No Gallery Specified.  Please choose.");}

    $sql = "select * from `galleries` where id = '$gal' ;";

    if (! $row = $pdo->query($sql)->fetch() ){
        show_galleries("No such gallery $gal");
    }
    $items = $row['gallery_items'];
   
   # echo "testing " . $row['admins'] . ' vs ' . $_SESSION['username'] . BRNL;

    $notice='Click images to view full size';
    if (preg_match('/\s*search: (.*)/i',$items,$m) ){
	    $crit = $m[1];
	    $sql = "Select id from `assets` where status not in ('E','X','D') AND $crit";
	    $note =  "<p>Searching assets where: $crit </p>";
	    $assets = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
	    #recho ($assets,"Found $crit");

	}
    else {
        $assets = list_numbers($row['gallery_items']);
    }
    if (empty($assets)){$notice = "No assets found";}

    $title = hte($row['title']);
    $caption = hte ($row['caption']);
   if ($row['title'] != $row['caption']){ $comments = "<p>" . nl2br($caption). "</p>";}

   
    echo <<<EOT
    <html>
    <head>
    <title>$title</title>
    <link rel='stylesheet' href='/css/news3.css'>
    </head>
    <body>
    $navbar
    <h4>$title</h4>
    $comments
    $note
    <p>$notice</p>

    <hr>
EOT;
   # echo "nav option: " , $nav -> get_option();

    foreach ($assets as $asset){
        if (is_numeric($asset) ){
            if (!empty( $out = get_gallery_asset($asset) )){
                echo $out;
            }
            else {continue;} #compensate for missing assts from the list
        }
        elseif (substr($asset,0,4)=='http'){
            echo "<a href='$asset' target='_blank'>$asset</a>";
        }
    }
}
function get_gallery_asset($id){
    #editable is true or false, allowing user to edit the asset data

   if (empty($id)){return '';}
    $pdo = MyPDO::instance();
    $sql = "SELECT * from `assets` WHERE id = $id";
    $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (empty($row)){return '';}
    #recho($row);


    $id = $row['id'];

    $url = $row['url'];

    $link = $row['link'];
    $target = (empty($row['link'])) ? $row['url'] : $row['link'];
    $caption =  make_links(nl2br($row['caption']));
    if (empty($caption)){$caption = $row['title'];}


    $source_line = "<p class='source'>";
    $source_line .=  (! empty($row['source']))? "${row['source']}" : 'Unattributed';
    if (! empty ($row['vintage'] )) {
       $source_line .=  " (${row['vintage']})";
    }
    $source_line .= "</p>\n";

    $title_line = hte($row['title']);

    $click_line = (!empty($target))? "<p class='small centered'> (Click image for link.)</p>":'';

     $thumb_url = "/assets/thumbs/${row['thumb_file']}";

     $editable = (strcasecmp ($_SESSION['username'] ,$row['contributor']) == 0) ? true : false;
        if ($_SESSION['level'] > 7) {$editable=true;}


   $edit_field = ($editable) ? "<a href='/scripts/asset_edit.php?id=$id&type=specadmin'>Edit</a> " : '';


   if ( empty($row['thumb_file']) or !file_exists(SITE_PATH . "/$thumb_url") ){ return "Attempt to link to asset with no thumb: id $id"; }


    $out =  "<div class='album'>";

    $gfile = choose_graphic_url('/assets/galleries',$id);

    if (empty($gfile) && file_exists(SITE_PATH . "/$thumb_url")  ) {
        $gfile = $thumb_url;
    }

    if (! empty ($gfile)){
        $out .= "
        <a href='/asset_display.php?$id' target='asset' decoration='none'>
        <img src='$gfile' ></a>
        <p class='caption'>$caption</p>
       $source_line
        <p class='clear'>$edit_field [$id]</p>
    ";
    }
    else  {$out .= "(No gallery image for id $id)";}
    $out .= "</div>";



    #update the first used if it's blank and not an admin access
    $first_date = $row['first_use_date'];
    if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){

        $out  .= set_first_use($id,$status);
    }

    return $out;


}




function show_galleries($note=''){
    $nav = new navBar(true);
    $navbar = $nav -> build_menu();
    $pdo = MyPDO::instance();

    echo <<<EOT
    <html><head><title>Galleries</title>
     <link rel='stylesheet' href='/css/news3.css'>
    </head>
    <body>
    $navbar
    <p>$note</p>
    <h4>Choose a Gallery</h4>
    <p>Galleries are collections of photos that have been uploaded
    to the AMDFlames site.  Each photo is about 350px wide,
    large enough to view, but if you click on the photo, you will
    get the "full resolution" version, whatever it is.</p>


EOT;
    $sql = "Select * from galleries where status != 'D' order by vintage DESC;";
     $result = $pdo->query($sql);
    $last_vintage = 0;
    while ($row = $result->fetch()){
        $title = hte($row['title']);
        $caption = hte($row['caption']);
        $id = $row['id'];
        $vintage =$row['vintage'];
        if ($vintage != $last_vintage){
             if (empty($vintage )){$vintage = "Multiple Years";}
            echo "<div class='clear'><br><p style='background:#393;color:white;font-size:1.2em;'  >$vintage</p>";
        }

        $link = "/galleries.php?$id";
        $image = $row['thumb_file'];

    #recho($row);
         echo <<<EOT
         <div class='thumb' style='height:300px;'>
         <p ><a href='$link' target='gallery'>$title</a></p>
       <!-- <p class='caption'>$caption</p> -->
         <a href='$link' target='gallery'><img src = "/assets/thumbs/$image"></a>
         <small>(id $id)</small>
        </div>
EOT;
        $last_vintage = $vintage;
    }



}

?>

</body></html>

