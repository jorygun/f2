<?php
namespace digitalmx\flames;

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	$login->checkLevel(1);
	

//END START

/* script to manage asset database.
    includes
        upload new asset
        delete existing asset
        edit asset data

*/

require_once "asset_functions.php";

#ini_set('display_errors',true);
# ini_set('error_reporting', E_ALL);



?>

<html>
<head>
<title>Asset Editor</title>
<style>
.red {color:red;}
</style>
<link rel='stylesheet' href='/css/news3.css'>
<script src='/js/f2js.js'></script>


</head>
<body>
<?php


if ($_SERVER['REQUEST_METHOD'] == 'POST'){

   # echo "Post data:<br><pre>" . print_r($_POST,true) . "</pre>"; exit;
#check if id_list
     $id_list = []; $id_list_string = '';
    
	if (!empty($id_list_string = $_POST['id_list_string']) ){
        $id_list = explode (',',$id_list_string);
        
	}
	 

    if (isset($_POST['delete'])){
       $id = $_POST['id'];
	  switch ($_POST['delete']) {
	    
        case 'Mark Deleted' :
            echo "<script>alert( 'Marking asset $id Deleted');</script>";
            delete_asset($id);
            if (!$next_id = next_asset_id($id,$id_list)){exit;}
            
            echo "<script>window.location.href='/scripts/asset_edit.php?id=$next_id';</script>";
			exit;
        
        case 'Delete Linked Files':
        	
            delete_files($id);
            exit;
        
        case 'Confirm Delete':
            echo "<script>alert( 'Confirmed file delete $id ');</script>";
            $unlink_list = json_decode($_POST['unlinkjson']);
            delete_confirmed($id,$unlink_list,true);
            
            exit;
        
        	
        default: die ("Unknown delete '${_POST['delete']}'");
    	}
    }
  
     #post the data from the form
     # recho ($_POST,'incoming to post');
     
 	$id = $_POST['id'] ?? $id_list[0];
	
     switch ($_POST['submit']){
        case 'Go Next':
        	
           if (!$next_id = next_asset_id($id,$id_list)){exit;}

            #echo "Getting next id $next_id"  . BRNL;
            
            $itemdata = get_asset_data($next_id);
            $itemdata['id_list_string'] = $id_list_string; 
            
    		break;
            
      
       		
        case 'Review and Go Next':
        	$_POST['status'] = 'R';
        	$_POST['form'] = 'update';
        	$id = post_asset_update($_POST);
            if (!$next_id = next_asset_id($id,$id_list)){exit;}
             $itemdata = get_asset_data($next_id);
             $itemdata['id_list_string'] = $id_list_string;
        	break;
        
         case 'Delete':
         	$id = $_POST['id'];
       		delete_asset($id);
       		if (!$next_id = next_asset_id($id,$id_list)){exit;}
             $itemdata = get_asset_data($next_id);
             $itemdata['id_list_string'] = $id_list_string;
       		break;
       		
         case 'Post and Go Next':
         	if ($_POST['status'] == 'E') $_POST['status'] = 'N';
            $id = post_asset($_POST);
            if (!$next_id = next_asset_id($id,$id_list)){$next_id = 0;} #new doc
             else{$itemdata = get_asset_data($next_id);}
             $itemdata['id_list_string'] = $id_list_string;
        	
    		break;
    		
        case 'Post':
        		if ($_POST['status'] == 'E') $_POST['status'] = 'N';
            $id = post_asset($_POST);
             $itemdata = get_asset_data($id);
             $itemdata['id_list_string'] = $id_list_string;
             
    		break;
        
      
        case 'Review All':
        	$_POST['form'] = 'update';
          case 'Edit All Found':
			if (empty($id_list)){
				echo "No assets on Edit All list.";
				exit;
			}
			
			$id = $id_list[0];
			
            $itemdata  = get_asset_data($id);
            $itemdata['id_list_string'] = $id_list_string;
           
            
       		break;
        // case 'Repeat Last':
//         	$_POST['tags'] = $_POST['last_tags'];
//             post_asset_update($_POST);
//             $next_id = next_asset_id($id,$id_list);
//             $itemdata = get_asset_data($next_id);
//             $itemdata['id_list_string'] = $id_list_string;
//             $itemdata['last_tags'] = $_POST['tags'];
//             
//             break;
//         
        
        default: 
            echo "Unknown submit command " . $_POST['submit'] . BRNL;
            exit;
    
    }
    	#recho ($itemdata,'itemdata before form');exit;
    	
      if ($_POST['form'] == 'update'){
            	show_asset_update ($itemdata);
            }else{
            	show_asset_form ($itemdata);
            }
}

// SHOW FORM



else {
	$id = $_GET['id'] ?? 0;
	echo "Getting id $id";
	
	$itemdata = get_asset_data($id);
	#recho($itemdata, 'itemdata before show form');
	show_asset_form($itemdata);
	
}
  
 

######################################################################



function go_to_id($id,$type=''){
    $addtype= (!empty($type))? "&type=${type}" : '';
    echo "<script>window.location.href = '/scripts/asset_edit.php?id=${id}${addtype}'</script>";
}

function png_or_jpg_exists($dir,$id){
    if (file_exists(SITE_PATH . "/assets/$dir/${id}.jpg")){return true;}
     if (file_exists(SITE_PATH . "/assets/$dir/${id}.png")){return true;}
     return false;
}


function post_asset_update($post) {
        $id = $post['id'];
        echo "updating asset $id for post_asset_update" . BRNL;
        $pdo = digitalmx\MyPDO::instance();
        $sql = "UPDATE `assets` 
        	SET title=?, caption=?, vintage=? ,tags=? , status= ?
        	WHERE id = $id";
        #echo $sql . BRNL;
       $stmt =  $pdo -> prepare($sql);
       $tags = charListToString($post['tags']);
        $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $tags,
            $post['status']
        );
        #recho ($values,"values to execute on $id");

        $stmt->execute($values);
        return $id;

}





function show_asset_form($itemdata) {
    $id = (isset($itemdata['id'] ))?$itemdata['id']: 0 ;
    $itemdata['id'] = $id;
    
// display form using data from itemdata
// 	recho ($itemdata,'input to show asset form');
// 	 exit;
global $asset_types;
global $asset_status;
global $asset_tags;

	//$typeoptions = build_options($asset_types,$itemdata['type']);
    $status_options = build_options($asset_status,$itemdata['status']);
    $delete_type = ($itemdata['status'] == 'D')?'Delete Linked Files':'Mark Deleted';
    $user_level = $_SESSION['level']; #user security level for reduced display
    $tag_options = buildCheckBoxSet ('tags',$asset_tags,$itemdata['tags'],3);
    $this_type = $itemdata['type'];


    $has_thumb =  png_or_jpg_exists('thumbs',$id);
    $itemdata['has_thumb'] = $has_thumb;
    $has_thumb_tic = ($has_thumb)?'&radic;':'no';
    $need_thumb = ($id>0 && $has_thumb)?false:true;
    $need_thumb_checked = ($need_thumb)?'checked':'' ;
    $need_thumb_checkbox = "<input type='checkbox' name='need_thumb' $need_thumb_checked >";


    $has_gallery =  png_or_jpg_exists('galleries',$id);
    $itemdata['has_gallery'] = $has_gallery;
    $has_gallery_tic = ($has_gallery)?'&radic;':'no';
    $need_gallery = false;
    $need_gallery_checked = ($need_gallery)?'checked':'' ;
    $need_gallery_checkbox = "<input type='checkbox' name='need_gallery' $need_gallery_checked >";


    $has_toon =   png_or_jpg_exists('toons',$id);
    $itemdata['has_toon'] = $has_toon;
    $has_toon_tic = ($has_toon)?'&radic;':'no';
    $need_toon = false;
    $need_toon_checked = ($need_toon)?'checked':'' ;
    $need_toon_checkbox = "<input type='checkbox' name='need_toon' $need_toon_checked >";


       //  $show_thumb= ($has_thumb)? "&radic;  Recreate <input type=checkbox name='need_thumb' >" :
//             "Create <input type=checkbox name='need_thumb' checked>";
//
//         $show_gallery= ($has_gallery) ?
//              "&radic;" :
//             "Create <input type=checkbox name='need_gallery'>";
//
//         $show_toon = ($has_toon) ?
//              "&radic;" :
//             "Create <input type=checkbox name='need_toon'>";

        $cont_style = ($itemdata['contributor_id']) ? 'background-color:#fff;':'background-color:#fcc;';

    ## also post vals in hidden variables to correct file to reality
    global $Aliastext;



    foreach (array('caption','title','user_info','notes') as $v){
        $hte[$v] = spchar(stripslashes($itemdata[$v]));
    }



    $open_first = $itemdata['first_use_in']? "<a href='${itemdata['first_use_in']}' target='newspage'>open...</a>" : '';
	$id_list_string = $itemdata['id_list_string'] ?? '';
	$edit_all = $itemdata['edit_all'] ?? '';
	
	$nextlabel = 'Next higher Id#.';
	if (!empty($id_list_string )){
		$id_list_count = substr_count($id_list_string, "," )+1;
		$nextlabel = "From List of $id_list_count ids";
	}


//
     echo <<< EOT
<h4>Asset Edit/Entry (main)</h4>

<hr>



<p>Go To: <button onClick="gotoNewId('/scripts/asset_edit.php?id=0','newid')">New Asset</button>
OR Enter an ID:<input type='text' name='newid' id='newid'> <button onClick="gotoNewId('/scripts/asset_edit.php?id=newid','newid');"> Go </button></p>
EOT;


echo <<<EOT
<form  method="POST" enctype="multipart/form-data" style="border:1px solid black;padding:6px; name="asset_form" id="asset_form"">

<!--
<input type='hidden' name='sizekb' value='${itemdata['sizekb']}'>
<input type='hidden' name='has_thumb' value='${itemdata['has_thumb']}'>
<input type='hidden' name='has_gallery' value='${itemdata['has_gallery']}'>
<input type='hidden' name='has_toon' value='${itemdata['has_toon']}'>

<input type='hidden' name='width' value='${itemdata['width']}'>
<input type='hidden' name='height' value='${itemdata['height']}'>
<input type='hidden' name='date_entered' value="${itemdata['date_entered']}">
<input type='hidden' name = 'type' value='${itemdata['type']}'>
-->

<input type='hidden' name='orig_url' value="${itemdata['url']}">
<input type='hidden' name = 'id' value='${itemdata['id']}'>

<input type='hidden' name = 'edit_all' value="edit_all">
<input type='hidden' name = 'id_list_string' value="$id_list_string">
 <input type='hidden' name='form' value='edit'>



EOT;
if ($id>0){
	$newlegend = "<h2>Existing ID</h2>";
	echo <<<EOT

(automatic data below)<br>
<b>ID: ${itemdata['id']}</b> <br>
Entered: ${itemdata['date_entered']}<br>
First Use:
    Date <input type=text name='first_use_date' value=${itemdata['first_use_date']}>
    In <input type=text name='first_use_in' value='${itemdata['first_use_in']}' size='40'>
    $open_first<br>

Mime: ${itemdata['mime']} Type: $this_type<br>
Size: ${itemdata['sizekb']} KB. Height: ${itemdata['height']} Width: ${itemdata['width']}<br>
Testdata: ${itemdata['temptest']} <br>

EOT;
}
else {
	$newlegend = "<h2>New Asset</h2";
}
echo "
<hr>
<style>
.this_style tr td{text-align:center; width:8em;}
</style>
";

echo <<<EOT

<table class='this_style'>

<tr><th>Form</th><th>Exists</th><th>Create/Recreate</th></tr>
<tr ><td>Thumb </td><td>$has_thumb_tic</td><td>$need_thumb_checkbox</td></tr>
<tr><td>Gallery </td><td>$has_gallery_tic</td><td>$need_gallery_checkbox</td></tr>
<tr class='center'><td>Toon </td><td>$has_toon_tic</td><td>$need_toon_checkbox</td></tr>
</table>
<hr>

<p>(Edit data below)</p>
<table>
<tr><td colspan='2'>$newlegend</td></tr>
<tr><td>Item Title</td><td><input type='text' size='60' name='title' id='title' value="${hte['title']}"></td></tr>
<tr><td>Caption (not reqd)</td><td><textarea  name='caption' rows=5 cols=60>${hte['caption']}</textarea></td></tr>
<tr><td>Origin</td><td>Vintage: <input type='text' name='vintage' value = "${itemdata['vintage']}" size="6"> Attribute to <input type='text' name='source' value="${itemdata['source']}" size="40"> </td></tr>
<tr><td>FLAME contributor:</td><td><input type='text' name='contributor' value='${itemdata['contributor']}' onfocus="form.contributor_id.value='';"
    style = '$cont_style'> id: <input type='text' name='contributor_id' id='contributor_id' value='${itemdata['contributor_id']}'><br>$Aliastext</td></tr>
    
    <tr><td>Tags</td><td>$tag_options</td></tr>
    
  <tr><td>Asset Status</td><td><select name='status'>$status_options</select></td></tr>
<tr><td colspan='2'>Enter EITHER a url to the asset  OR select a file to upload</td></tr>

<tr><td colspan='2'>Link asset to this file or url </td></tr>
<tr><td></td><td>
Upload file <input type="file" name="linkfile" > <br>
    or URL: <input type='text' name='link' value='${itemdata['link']}' size=80>
    <br>
    Use '/ftp/xxx' for files in ftp dir; 'uploads/xxx' for files in uploads dir.
</td></tr>


<tr><td colspan='2'>Upload Thumb/Source file if not the link<br>
	<small>(Thumbs for pdf files and youtube videos generated automatically.)</small></td></tr>
<tr><td></td><td>-- Upload thumb/source file:
    <input type="file" name="upfile" id="photo"><br>
--- or source URL: <input type='text' name='url' value='${itemdata['url']}' size='80'>
     </td></tr>





<tr><td>Thumb File (normally don't change) </td><td><input type='text' name='thumb_file' value='${itemdata['thumb_file']}' size='40'></td></tr>




<tr><td style="vertical-align:text-top;">Notes (not published)</td><td><textarea rows=2 cols=40 name='notes'>${hte['notes']}</textarea></td></tr>
<tr><td>Other Keywords (comma sep)</td><td><input type='text' name='keywords' value='${itemdata['keywords']}' size='60'/></td></tr>



<tr><td>
('Next' means $nextlabel)<br>
<input type="submit" name='submit' value='Post'>
<input type="submit" name="submit" value="Post and Go Next"> 
<input type='submit' name="submit" value="Go Next"> (skip)
</td><td >
EOT;

        if (!empty($last_tags)){
           echo "<input type='submit' name='submit' value='Repeat Tags' style='background:#cfc'> ($last_tags) ";
        }
        if ($id>0 && $user_level>3){echo <<<EOT
<p style='text-align:right'><input type='submit' style='background-color:red;font-weight:bold;' name='delete' value='$delete_type'></p>
EOT;
		}
    
echo <<<EOT
</td></tr>
</table>
</form>



EOT;
if ($id > 0){
	echo "<p class='clear'><hr></p>
    <div class='left'>
    <hr>
    ";
		echo get_asset_by_id($id);
		echo "<p class='clear'></p></div>\n";
	}
}


function show_asset_update ($row) { #used for update, not full edit

        global $asset_tags;
        global $asset_status;
       $id = $row['id'];
       $title = $row['title'];
       $type=$row['type'];
       $user_level = $_SESSION['level'];
       $caption = $row['caption'];
        $status = $row['status'];
        $status_name = $asset_status[$status];
        #$run = $row['run'];
        #$last_tags = $row['last_tags'];
#	recho ($row,'incoming row to show ');
         $tag_options = buildCheckBoxSet ('tags',$asset_tags,$row['tags'],3); #show codes



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

		$nextlabel = 'Next higher Id#.';
	if (!empty($id_list_string )){
		$id_list_count = substr_count($id_list_string, "," )+1;
		$nextlabel = "From List of $id_list_count ids";
	}
	
	 $editable = false; 	$edit_panel = '';
	 if (
		$_SESSION['level'] > 6
		or
		 strcasecmp($_SESSION['username'],$row['contributor']) == 0
		 or
		 strcasecmp($_SESSION['username'],$row['source']) == 0
	 ){$editable=true;}


 echo <<< EOT
<h4>Asset Edit (Update)</h4>
<div class='left'>

<p>Go To: <button onClick="gotoNewId('/scripts/asset_edit.php?id=0','newid')">New Asset</button>
 </p>
EOT;


echo <<<EOT
<form  method="POST"  style="border:1px solid black;padding:6px; name="asset_form" id="asset_form"">


        <input type='hidden' name='last_tags' value='$last_tags'>
        <table>
       <tr style='border-top:1px solid blue;'> <td>
        <input type='hidden' name = 'id' value = '$id'>
        <input type='hidden' name ='id_list_string' value="${row['id_list_string']}">
        <input type='hidden' name='form' value='update'>
        

        $id</td>
      </tr>
       <tr><td>Info</td><td><i>status:<span style='$status_style'>$status_name ($status)</span> Type: ${row['type']} (${row['mime']})</i></td></tr>

      <tr><td>Title:</td><td colspan='2'><input name='title' value = '$title' size=80></td></tr>
        <tr><td>Caption</td><td colspan='2'><input type='text' name='caption' value='$caption' size='120'></td</tr>
       <tr><td>Vintage</td><td><input type='text' name='vintage' value="${row['vintage']}"></td></tr>
        <tr>

       <td colspan='2'>$tag_options<br>
       </td></tr>
EOT;


echo  <<<EOT
        <tr><td>
EOT;

echo <<<EOT
(Next means $next_label)<br>

<input type="submit" name="submit" value="Review and Go Next">
<input type='submit' name="submit" value="Go Next"> (skip)
EOT;

        if (!empty($last_tags)){
           echo "<input type='submit' name='submit' value='Repeat Tags' style='background:#cfc'> ($last_tags) ";
        }
        if ($editable){echo <<<EOT
<input type='submit' style='background-color:red;font-weight:bold;' name='submit' value='Delete'>
EOT;
		}
echo <<<EOT
</td><td >

        </table>
        </form>
        </div>
EOT;

if ($id > 0){
echo "<p class='clear'><hr></p>
    <div class='left'>
    <hr>
    ";
		echo get_asset_by_id($id);
	   echo "</div>\n";
	
	}
}


####################################################




