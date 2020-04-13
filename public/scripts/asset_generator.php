<?php
namespace digitalmx\flames;

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	$login->checkLogin(5);

use digitalmx as u;

//END START

/* script to create multiple
    assets from a directory of images.

*/


// script to generate multiple assets from files in a directory.
// typicaly in assets/uploads/.
// with optional captions.txt file containing captions for assets.



require_once "asset_functions.php";


;
$sql_now = sql_now('date');



?>

<html>
<head>
<title>Bulk Asset Editor</title>
<style>
.red {color:red;}
</style>
<link rel='stylesheet' href='/css/news3.css'>
</head>
<body >
<h4>Bulk Asset Editor</h4>
<p>Use this to create a series of assets from a directory of  files
that have been uploaded).
</p><p>If the directory is in /assets/uploads, then when asset is created, files are moved
from the upload directory to the /assets/files directory (and renamed
with the asset id, like 1013.jpg).  If the files are in any other directory, they
are NOT moved or renamed. </p>
<p>  The directory must also include a file called "titles.txt". This file needs to have 3 tab-dlimited fields: filename, caption, and title.  These will be used when the assets are created.</p>
<p><b>CAUTION: </b> Be sure tabs are not converted to spaces in the file.</p>
<p>The first record MUST have all 3 fields: filename, caption, and title. THose will become the default caption and title to be used for any files that don't have their own.
</p>

<hr>
<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $dir = $_POST['dir'];
        process_uploads($dir);
}

else {#new item
     $itemdata = array(); #store data to display
    $itemdata['date_entered'] = sql_now('date');
    $itemdata['contributor'] = $_SESSION['login']['username'];
    $itemdata['contributor_id'] = $_SESSION['login']['user_id'];
    $itemdata['id']=0;
    $itemdata['dir'] = "/assets/uploads";


    show_form($itemdata);
}

######################################################################


function show_form($itemdata) {
    $id = $itemdata['id'];
// display form using data from itemdata
global $asset_types;


    $need_gallery = "Create new <input type=checkbox name='need_gallery'>";
     $need_toon = "Create new <input type=checkbox name='need_toon'>";



        $show_thumb= (isset($itemdata['has_thumb']))? "&radic;" :
            "Create <input type=checkbox name='need_thumb' checked>";

        $show_gallery= (isset($itemdata['has_gallery'])) ?
             "&radic;" :
            "Create <input type=checkbox name='need_gallery'>";

        $show_toon = (isset($itemdata['has_toon'])) ?
             "&radic;" :
            "Create <input type=checkbox name='need_toon'>";
    ## also post vals in hidden variables to correct file to reality

    global $Aliastext;

     echo <<< EOT

<div class='left'>

<form  method="POST"  style="border:1px solid black;padding:6px;">

<table>

<tr><td>Thumb (200px w):</td><td>$show_thumb</td></tr>
<tr><td>Toon(800px w):</td><td>$show_toon</td></tr>
<tr><td>Gallery (350px w):</td><td>$show_gallery</td></tr>
</table>

<hr>

<table>

<tr><td>Source directory<br>
(from web root)</td><td><input type='text' name='dir' value='${itemdata['dir']}' size='100'></td></tr>


<tr><td>Contributor:</td><td><input type='text' name='contributor'  ><input type='hidden' name='contributor_id' id='contributor_id' value=0><br>$Aliastext</td></tr>


<tr><td>From</td><td>vintage (year): <input type='text' name='vintage'  size="6"> Attribution <input type='text' name='source'  size="40"> </td></tr>



</table>
<input type="submit" value='Submit'>

</form>
</div>


EOT;

}
####################################################
function process_uploads($dir) {
    $dirpath = SITE_PATH . $dir; #upload directory typically

    if (!$filelist = scandir("$dirpath")){
        die ("No files found in $dirpath");
    }
    $file_count = count($filelist);
    echo "found $file_count files in $dirpath. ";
   $titles=array();
   $captions = array();

    if (file_exists("$dirpath/titles.txt")){
        $captionfh = fopen("$dirpath/titles.txt",'r');
        #got caption file
        $default_title = '';
        $default_caption = '';


        while (($line = fgets($captionfh))!==false){
         $params = explode("\t",$line );
           if ( empty($params) ){ #may be 2 or 3 vars
            continue;
         }

         if (empty($default_title) ){
            $default_title = $params[2];
            $default_caption = $params[1];
            echo "Set default title, caption: $default_title; $default_caption" . BRNL;
            if (empty($default_title)) {
             throw new Exception ("Title not set in first record of titles.txt");
            }
         }
         $gfile = $params[0];
         $caption = $params[1] ?? $default_caption;
         $title = $params[2] ?? $default_title;

         $titles[$gfile] = $title;
         $captions[$gfile] = $caption;

        }
      echo "<h3>Titles and Captions</h3>";
      echo "Default: title: $default_title; caption: $default_caption" . BRNL;
      foreach ($titles as $f=>$t){
         echo "$f: $t, " . $captions[$f] . BRNL;
      }

    }
    else {die ("No titles.txt file found in folder.");}

    $finfo = new \finfo(\FILEINFO_MIME_TYPE);
    $new_ids = [];

    foreach ($filelist as $this_file){
        if (substr($this_file,0,1)=='#'){continue;}
        if (substr($this_file,0,1) == '.') {continue;}
        if (empty($this_file)){continue;}
        if ($this_file == "titles.txt"){continue;}


      echo "Processing file $this_file" . BRNL;

        #build the post array, starting with stuff from form
        #make the chosen file look like it was an upload from asset form
        $post_array = $_POST;
        $post_array['id'] = 0;

         $post_array['caption'] = $captions[$this_file] ?? $default_caption;
        	$post_array['title'] = $titles[$this_file] ?? $default_title;
         $post_array['notes'] = "Generated from $this_file.\n";
        $fake_upload = SITE_PATH . '/' . $dir . '/' . $this_file;
        $_FILES['linkfile'] = build_files($fake_upload);
        #recho ($_FILES,'from asset_generator');
       # recho ($post_array,'post array from asset gen');

       $id =  post_asset($post_array);
       $new_ids[] = $id;


    }

    echo "<p>New Ids created: "
        . array_shift($new_ids)
        . ' - '
        . array_pop ($new_ids)
        . "</p>";

    #check to make sure directory is empty
    #files are removed by renaming in the accept_file function
    $remain = 0;
    if (!empty ($filelist = scandir($dirpath))){
        foreach ($filelist as $file){
            if (substr($file,0,1) == '.'){continue;}
            elseif ($file == 'captions.txt'){continue;}
            else {echo "File remaining: $file" . BRNL;++$remain;}
        }

    }
    if ($remain == 0){
        unlink ("$dirpath/*");
        rmdir ("$dirpath");
        echo "Directory $dir removed from server." . BRNL;
    }
    else {echo "Directory $dir retained because not empty." . BRNL;}




    echo "done." . BRNL;


}
  function build_files($loc) {
            /*
            $loc is complete file path
            $loc =  SITE_PATH . '/' . $upload_dir . '/' . $this_file;
            or $loc = PROJ_PATH / ftpf /this_file
             #use _FILES array to look like an upload
            */

         $finfo = new \finfo(\FILEINFO_MIME_TYPE);

        $file_data = array(
            'tmp_name' => $loc,
            'name' => basename($loc),
            'error' => 0,
            'size' => filesize($loc),
            'type' => $finfo->file($loc)
        );
        return $file_data;
}
