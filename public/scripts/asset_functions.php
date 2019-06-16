<?php
/* contains a bunch of definitions and scripts used by multiple asset
    related scripts.
*/

$asset_types = array(
    'Image' ,
    'Multimedia' ,
    'Document' ,
    'Web Page' ,
    'Other'
    );

// $asset_tags_old = array(
//
//     'A' => 'Ad', .
//     'B' => 'Stories About AMD', - D
//     'C' => 'Corporate Literature', .
//     'D' => 'Facilities', - F
//     'E' => 'Events', .
//     'F' => 'Flames', -H
//
//     'L' => 'Cartoons', -T
//     'O' => 'Old Electronics',  .
//     'P' => 'Product Literature', .
//     'Q' => 'Sales Literature', -S
//     'R' => 'AMD Reports', .
//     'S' => 'Sales Conference', -W
//     'T' => 'Themes and Symbols', -Z
//     'V' => 'Cars', .
//
//
//
//     );

$asset_tags = array(
    'A' => 'Ad *',

    'C' => 'Corp - External *',
    'D' => 'Stories About AMD *',
    'E' => 'Events *',
    'F' => 'Facilities *',
    'G' => 'Gatherings',

    'H' => 'Flames People',
    'I' => 'Corp - Internal *',

    'M' => 'Marketing Pub *',
	

    'O' => 'Historical Electronics *',

    'P' => 'Data sheet/Apps *',
    'R' => '',
    'S' => 'Sales/Mktg Bulletins *',
    'T' => 'Cartoons',
	'U' => 'Interviews *',
    'V' => 'Car stuff',
    'W' => 'Sales Conference *',
    'X' => 'x-Problem',
    'Y' => 'Posters and Symbols ',
    
    

    );
    #tag starting with Z is reserved for special searches, e.g., all archives

$archival_tags = "ACDEFIMOSWY";

$accepted_mime =
    array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'tif' => 'image/tiff',
            'doc' => 'application/msword',
            'docx' => 'application/msword',
            'html' => 'text/html'

        );

 $image_extensions = array('jpg','gif','png','jpeg');
    $document_extensions = array('doc','docx','pdf','html');
    $mmm_extensions = array('mov','mp4','mp3','m4a');

$editable_fields = array(

    'title',
    'source',

    'url',
    'contributor',
    'contributor_id',
    'caption',
    'notes',
    'keywords',
    'thumb_file',
    'vintage',
    'gallery_items',
    'status',
    'user_info',
    'link',
    'tags'

   );

$auto_fields = array(
    'date_entered',
    'height',
    'width',
    'sizekb',
    'first_use_date',
    'first_use_in',

    'mime',
    'type'

);

$deprecated_fields = array(

    'thumb',
    'has_thumb',
    'has_toon',
    'has_gallery',

    'file',
    'source_date',

);

 $thumb_width = array(
                'thumbs' => 200,
                'galleries' => 330,
                'toons' => 800
                );


$asset_status = array(
    'R' => 'Reviewed-R',
    'S' => 'S',
    'U' => 'Updated: re-review',
    'T' => 'temp holding',
    'E' => 'Has Error',
    'D' => 'Deleted',
    'X' => 'Deleted and Unlinked',
    'N' => 'New',
    'O' => 'OK'

);

$assetfields = array_merge($editable_fields,$auto_fields);


function get_archival_tag_list ()  {
	global $asset_tags;
	$archival_tags = '';
	foreach ($asset_tags as $tag=>$label){
		if (strpos($label,'*') !== false){
			$archival_tags .= $tag;
		}
	}
	
	$archive_tag_set = '';
	foreach ( str_split($archival_tags) as $t){
		$archive_tag_set .= "'$t',";
	}
	$archive_tag_set = rtrim($archive_tag_set,',') ;
	return $archive_tag_set;
}

function get_asset_by_id($id,$style='thumb'){
    if (empty($id)){return array ();}
    $pdo = MyPDO::instance();
    $sql = "SELECT * from `assets` WHERE id = $id";
    $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    #recho($row);


    $id = $row['id'];
    $type = $row['type'];
    $url = $row['url'];
    $status = $row['status'];
    $link = $row['link'];
    $target = (empty($row['link'])) ? $row['url'] : $row['link'];
    $caption =  make_links(nl2br($row['caption']));
    if (empty($caption)){$caption = $row['title'];}


   $source_line = '';
    if (! empty($row['source'])) {$source_line = $row['source'];}
    if ($row['source'] != $row['contributor']
        && strncasecmp($row['contributor'], 'Flames',6) != 0
        ){
        $source_line .= ' via ';
        $source_line .= " ${row['contributor']} ";
    }

    if (empty($source_line)){ $source_line = "unattributed ";}
    if (! empty ($row['vintage'] )) {
       $source_line .=  " (${row['vintage']})";
    }




    $title_line = spchar($row['title']);

    $click_line = (!empty($target))? "<p class='small centered'> (Click image for link.)</p>":'';

     $thumb_url = "/assets/thumbs/${row['thumb_file']}";
   if ( empty($row['thumb_file']) or !file_exists(SITE_PATH . "/$thumb_url") ){ 
   	#try to make thumb from source
   		
   		return "Attempt to link id $id to asset with no thumb: $thumb_url"; 
   		
   	}
   	

    switch ($style){

    case '':
    case 'thumb':
        $out = "<div class='thumb'>";
        if ($type == 'Album') {
        	$out .= "Asset type 'Album' has been removed.  Please contact admin and let them know you got this. </div>"; 
        	break;
        }
        elseif (substr($target,0,1) == '/' ) {#on site
            if (strpos($target,'/galleries') !== false){
                $href = $target;
            }
            else { $href=  "/asset_display.php?$id' target='asset' decoration='none'";
            }
        }
        else {$href = "$target";}

        $out .= "
            <a href='$href' target='asset' decoration='none'>
            <img src='$thumb_url'></a>
            <p class='caption'>$title_line</p>
            <p class='source'>$source_line</p>
            $click_line
        ";

        $out .= "</div>";
        break;


    case 'link':
        $out = "<a href='$target' target='_blank'>$title_line</a>";
        break;

   case 'album':
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
           <p class='source'>$source_line</p>
            <p class='clear'>[$id]</p>
        ";
        }
        else  {$out .= "(No gallery image for id $id)";}
        $out .= "</div>";
        break;

    case 'toon':
        $gfile='';
         $gfile = choose_graphic_url('/assets/toons',$id);
       if (empty ($gfile) ){
            $gfile = $row['url'];
        }


       if ( ! empty($gfile)) {$out = "
            <img src='$gfile' width='800'>
            ";

        }
        else {$out = "(No toon image for id $id)";}
        $out .= "<p class='center'><b>"
        .$row['title'] . "</b>";
        if ($row['title'] != $row['caption']){
            $out .= "<br>" . $row['caption'];
        }
        $out .= "</p>\n";
        $out .= "<p style='text-align:right;font-size:small'>
        $source_line •
        <a href='$target' target='_blank'>View source file</a></p>";
        break;

    default:
        $out = "(prepare image failed;  style  $style not understood)";
    }

    #update the first used if it's blank and not an admin access
    $first_date = $row['first_use_date'];
    if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){

        $out  .= set_first_use($id);
    }

    return $out;
}

function set_asset_skip_time ($id){
    $pdo = MyPDO::instance();
    $sql = "Update `assets` set skip_ts = NOW() where id=$id;";
    $pdo->query($sql);
}

function get_asset_data($id){
    $pdo = MyPDO::instance();

     $itemdata = array(); #store data to display
    #echo "Starting get_asset_data";

    if ( $id == 0){
     //shw blank screen for new entry
        $itemdata = array (
        'status' => 'N',
        'date_entered' => 'NOW()',
        'contributor' => $_SESSION['username'],
        'contributor_id' => $_SESSION['user_id'],
        'id'=> 0,
        );

	}

    else{
        // retrieve existing record

        $sql = "SELECT * FROM assets WHERE id =?;";
        $stmt = $pdo->prepare($sql);
        $iterations = 0; #save stawring id
        $itemdata=array();

         $stmt->execute([$id]);
       if (!$itemdata = $stmt ->fetch(PDO::FETCH_ASSOC)  ){
                    die ("No assets found at $id");
        }


    }
    #recho ($itemdata , 'from get_asset_data');
    return $itemdata;
}



function next_asset_id( $id,$id_list = [] ){
    #get next valid id in sequence from database or next from id_list
    if (!empty($id_list)){
    	$akey = array_search($id,$id_list);
    	$nkey = $akey+1;
    	#echo "getting next list item $nkey" . BRNL;
    	if (($next_id = $id_list[$nkey]) === false){
    		echo "No Additional Ids in List";
    		return false;
    	}
    	
    }
    
    else {
    	$pdo = MyPDO::instance();
    	$sql = "SELECT id FROM `assets` WHERE id > $id  AND status != 'D' ORDER by id LIMIT 1;";
    	if (! $next_id = $pdo->query($sql)->fetchColumn()){
    		echo "No ids above $id in database";
    		return false;
    	}
	}
    return $next_id;
}

function set_first_use($id){
    #sets first use date on an asset
            $sqlnow = sql_now('date');
            $ref = $_SERVER['REQUEST_URI'];
            // dont count if it's coming from the asset search script
            if (strpos ($ref, '/scripts/assets.php' ) === false){return null;}
            if ($_SESSION['level'] > 5){return null;} #anythning over member

            $pdo = MyPDO::instance();
            $sqld = "UPDATE `assets` set first_use_date = NOW(), first_use_in = '$ref' where id = '$id';";
            if ($pdo->query($sqld)){return true;}
        }

function list_numbers($text){
	/* accepts a string of numbers separated by anything
		AND ALSO expansion of pairs of numbers separated by a -
		and returns a php array of numbers
		AND ALSO accepts a search string
	*/
	$number_list = [];



	#look for \d - \d
	if (preg_match_all('/(\d+)\s*\-\s*(\d+)/',$text,$m)){#number range
		#print_r($m);
		#count instances of n - m
		$jc = count($m[0]); #echo "ranges = $jc\n";
         for ($j = 0; $j < $jc; ++$j){
            for ($i=$m[1][$j];$i<=$m[2][$j];++$i){
                $number_list[] = $i;
            }
            #now remove the pair from the string
           $text = str_replace ($m[0][$j],' ',$text);
       }
   }

   #npw add in the rest of the numbers in the string
   if (preg_match_all('/(\d+)/',$text,$m)){
   	$jc = count($m[0]); #echo "numbers = $jc\n";
		for ($j = 0; $j < $jc; ++$j){
			$number_list[] = $m[1][$j];
		}

  	 }

	return $number_list;
}


function create_thumb($id,$fsource,$type='thumbs'){

    global $image_extensions;
    global $document_extensions;
    global $mmm_extensions;
    global $thumb_width;

    #if (!$id || !$type){die "Create thumb called with $id,$type empty";}
   /* returns url (/assets/thumbs/$id.png) to thumbnail file at $source
    strategy is to always store link to thumb in the db with the
    asset, as opposed to figuring it out and creating it on the fly.
	
	fsource is url to source (from asset url column).  Maybe remote or local
	
    Type is array of types:
    If thumbs, creates a 200w thumb in the thumb file.
    If galleries, it creates a 300w copy
    If toons, it creates an 800w copy.


    if asset is local, set thumb to either 200w copy of the image
    or to generic pdf image or generic document image

    if image is on a url, set to generic url image (or
    curl the url and build a png thumb)


 */
 	$fsource = trim($fsource);
 	
 	#check to see if type requested is recognized width
    if (! $max_dim = $thumb_width[$type]){die ("Invalid type requested for thumbnail: $type");}
   
    if (empty($fsource)){die ("No file specified to create thumb  from.<br>\n");}
    else {echo "Creating thumb from $fsource" . BRNL;}
	
	 $thumb = '';
	 
	if ($videoid = youtube_id_from_url($fsource)){
		#echo "got videoid $videoid" . BRNL;
		$yturl = "http://img.youtube.com/vi/$videoid/mqdefault.jpg" ;
		#echo "yturl $yturl". BRNL;
		$thumb = "${id}.jpg";
		copy ($yturl , SITE_PATH . "/assets/$type/$thumb"); 
		return $thumb;
	   
	}
	
	
	 #set source path to either absolute file path or url
	 
	 if (substr($fsource,0,1) == '/') { #local file
	 	$source_path = SITE_PATH . $fsource;
	 }	
	 else {
	 	$source_path = $fsource;
	 	
	 }
    #set  thumbnail based on source type
  
    
	//     preg_match('/.*\.(\w+)$/',$fsource,$m);
//         $my_ext = strtolower($m['1']);
        
//         if (strcasecmp($fsource,'pdf') == 0) {
//         	#set to use page 1 of the pdf [0] for the thumb 
//             if ($thumb = build_im_thumbnail($id,SITE_PATH."$fsource" ,$type, $thumb_width[$type]) ){
//                 echo "Created thumb from pdf" . BRNL;
//              
//             }
//             else {echo "Failed to create thumb from pdf"; exit;}
//             
//         }
        
	 // If it's a youtube url, get a thumb from youtube
	
        
      $source_mime = mime_content_type ($source_path );
      echo "source mime: $source_mime" . BRNL;
      
	switch ($source_mime) {
		case 'application/msword' :
			$use_icon="doc.jpg";
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$type/$thumb"); 
			return $thumb;
			break;
		case 'application/pdf' :
		case 'image/gif':
		case 'image/jpeg':
		case 'image/jpeg':
		case 'image/png':
		case 'image/tiff':
			$thumb = build_im_thumbnail($id,$source_path,$type,$max_dim);
			return $thumb;
			break;
		case 'text/html':
			$use_icon="web.jpg";
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$type/$thumb"); 
			return $thumb;
			break;
		case 'video/mp4':
			$use_icon = 'mp4.jpg';
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$type/$thumb"); 
			return $thumb;
			break;
		case 'audio/mp3':
		case 'audio/m4a':
			$ext = substr($source_mime,6,3);
			$use_icon = "${ext}.jpg";
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$type/$thumb"); 
			return $thumb;
			break;
		case 'video/quicktime':
			$use_icon = 'mov.jpg';
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$type/$thumb"); 
			return $thumb;
			break;
			
		default:
			$use_icon = 'default.jpg';
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$type/$thumb"); 
			return $thumb;
			break;
			
	}
	 #if still haven't created a thumb...
  	   die("Cannot determine how to build thumb on $fsource (mime: $source_mime)");



}

function build_im_thumbnail ($id,$source,$type,$max_dim){
    $thumb = $id . '.jpg';
    if (mime_content_type ($source ) == 'application/pdf'){
    	$source = trim($source) . '[0]'; #page 1
    }
     $im = new imagick ( $source);
    $im->setImageFormat('jpg');
    $im->thumbnailImage($max_dim, $max_dim,true); #best fit
    $im->writeImage(SITE_PATH . "/assets/$type/$thumb");
    return $thumb;
}


function get_gfile($filepath) {
    #looks for designated file and returns its full path
    # if not found looks for either jpg or png or gif with same name

    $path = SITE_PATH . $filepath;
   # ,);
    if (file_exists($path)){return $path;}
    else {
        $ext = pathinfo($path,PATHINFO_EXTENSION);
        $dir = pathinfo($path,PATHINFO_DIRNAME);
        #drop extension
        preg_match('/(^.*?)\.\w+$/',$path,$m);
        $path2 = $m[1];
        foreach (['jpg','png','gif'] as $ex){
            if ($ex == $ext){continue;}
            $tfile = $path2 . ".$ex";
            if (file_exists($tfile)){return $tfile;}
        }
    }
}


function delete_asset($id){
    #mark an item as deleted.
    # if already marked as deleted, then delete assetse,
    $pdo = MyPDO::instance();

   $sql = "select * from assets where id = '$id';";
    if (! $row = $pdo->query($sql)->fetch()){
        echo "No asset found at id $id";
        return;
    }
    if ($row['status'] != 'D'){ #fresh delete
        $sql = "UPDATE `assets` set status = 'D' where id = $id";
        $pdo->query($sql);
        echo "Asset Marked Deleted (D)" . BRNL;
    }
}
function delete_files($id){
    $pdo = MyPDO::instance();
	echo "Deleting files associated with id $id". BRNL;
   $sql = "select * from assets where id = '$id';";
    if (! $row = $pdo->query($sql)->fetch()){
        echo "No asset found at id $id";
        exit;
    }

   
    $unlink_list = []; #build list of affected files
    if ($row['type'] == 'Album'){echo "Cannot use this on Albums"; exit;}
    if (!empty($row['first_use_in'])){
        echo "Cannot delete asset that has been used.  In {$row['first_use_in']} on {$row['first_use_date ']}";
        exit;
    }

	$thumb = $row['thumb_file'] ;

    if (!empty($thumb)){
    	if (! preg_match('/$id\.[jpg|png]/',$thumb))
    		 {echo "Cannot delete thumb $thumb" . BRNL;}
    	else {
			$file = SITE_PATH . "/assets/thumbs/$thumb";
			if (file_exists($file)){
				$unlink_list['thumb'] = $file;
			}
		}
    }

    if (!empty($file = get_gfile("/assets/toons/$id.png"))){
            $unlink_list['toon'] = $file;
    }

    if (!empty($file = get_gfile("/assets/galleries/$id.png"))){
            $unlink_list['galleries'] = $file;
    }

    $url = $row['url'];
    if (substr($url,0,1) == '/'){
        $file = SITE_PATH . "$url";
        if (file_exists($file)){
            $unlink_list['source'] = $file;
        }
    }
    $link = $row['link'];
    if (substr($link,0,1) == '/'){
        $file = SITE_PATH . "$link";
        if (file_exists($file)){
            $unlink_list['link'] = $file;
        }
    }

    #show results and ask for confirmation
    echo "The following files will be deleted from the server:" . BRNL;
    foreach ($unlink_list as $t => $f){
        echo "$t: $f" . BRNL;
    }
    $unlinkjson = json_encode ($unlink_list);
    echo <<<EOT

    <form method='post'>
    To confirm, press Confirm:
    <input type='hidden' name='unlinkjson' value='$unlinkjson'>
    <input type='hidden' name='id' value='$id'>
    <input type='submit' name='delete' value='Confirm Delete'>
    </form>
EOT;
    exit;
}
function delete_confirmed($id,$unlink_list,$doit='true') {
	$doitmsg =  ($doit)?'':'NOT';
	
    $pdo = MyPDO::instance();
    echo "Deleting files for id $id" .BRNL;

    foreach($unlink_list as $t=>$f){
        echo "Deleting $t file $f $doitmsg" . BRNL;
       if ($doit){ unlink ($f);}
    }

    echo "Updating asset record to status 'x' $doitmsg".BRNL;
    $sql = "Update `assets` set status = 'X' WHERE id = $id;";
    if ($doit){$pdo->query($sql);}
    exit;
}


function check_jpeg($f, $fix=false ){
# [070203]
# check for jpeg file header and footer - also try to fix it
    if ( false !== (@$fd = fopen($f, 'r+b' )) ){
        if ( fread($fd,2)==chr(255).chr(216) ){
            fseek ( $fd, -2, SEEK_END );
            if ( fread($fd,2)==chr(255).chr(217) ){
                fclose($fd);
                return true;
            }else{
                if ( $fix && fwrite($fd,chr(255).chr(217)) ){return true;}
                fclose($fd);
                return false;
            }
        }else{fclose($fd); return false;}
    }else{
        return false;
    }
}

function update_asset($post_array){
    #updates record with all the editable fields,
    #and returns the (new) id;.
    echo "starting update_asset. ";

    global $editable_fields;
    global $auto_fields;

    $pdo = MyPDO::instance();

    $id = $post_array['id'];
    if ($id == 0) {throw new Exception ("attempt to update asset with id = 0");}


    #if contributor id not set, look up from name.
    if (empty($post_array['contributor_id'])){
    echo "Getting contributor id for ${post_array['contributor']}" . BRNL;
        list ($post_array['contributor'],$post_array['contributor_id']) =
            set_userid( $post_array['contributor'],$post_array['contributor_id']);
   }
// recho ($post_array,'after setuid');
// exit;


   $valid_keys = array_merge($editable_fields,$auto_fields);
#echo "<p>Post:</p>\n"; print_r ($post_array);
#echo "<p>Valid:</p>\n"; print_r ($valid_keys);

    $prep = pdoPrep($post_array,$valid_keys,'id');
#   echo "<p>Prep:</p>\n"; print_r ($prep);
    $id = $prep['key'];
    $sql = "UPDATE `assets` SET ${prep['update']} WHERE id=${prep['key']};";
    #echo $sql,"<br>";
    $stmt = $pdo->prepare($sql);
     $stmt->execute($prep['data']);
	echo ".. done. " . BRNL;
    return true;
}

function add_link_data($url){
    #adds mime type, asset type, size, and image dimensions if graphic to link
    #ensure required automatic data is present
        $mime = '';
        $size = $width = $height = 0;
        $sqls = array (
       		'mime' => '',
		    'sizekb' => 0,
		    'width' => 0,
		    'height' => 0,
		    'type' => ''
            );
        if (substr($url,0,10) == '/galleries'){
            $sqls['type'] = 'Album';
            return $sqls;
        }
        elseif (substr($url,0,1) == '/'){# local
             $filepath = SITE_PATH .  "$url";
             $mime = mime_content_type($filepath);
             $size = filesize($filepath);
             if (substr($mime,0,5) == 'image'){
                list($width, $height) =  getimagesize($filepath);
            }
        }
        elseif ( strpos($url,'youtube') !== false
            || strpos($url,'youtu.be') !== false) {
            $mime = "video/youtube";
            $size = 0;
        }

        elseif (substr($url,0,4) == 'http') {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        }

        else {
            echo "cannot get mime data from url $url";
            return $sqls;
        }

       $sqls['mime'] = $mime;
       $sqls['sizekb'] = round($size/1000,0);
        $sqls['width'] = $width;
        $sqls['height'] = $height;
        #set asset type based on mime type

        if (strpos($mime,'image') !== false){$sqls['type'] = 'Image';}
        elseif (strpos($mime,'audio') !== false){$sqls['type'] = 'Multimedia';}
        elseif (strpos($mime,'video') !== false){$sqls['type'] = 'Multimedia';}
        elseif (strpos($mime,'application') !== false){$sqls['type'] = 'Document';}
        elseif (strpos($mime,'html') !== false){$sqls['type'] = 'Web Page';}

#recho($sqls,"Link Data");
    return $sqls;
}


function update_galleries($galleryid,$ids){
    #make sure each id in the list ha a gallery entry
        $ids = array_filter($ids, 'is_numeric');

        $id_list = "'" . implode("','",$ids) . "'";
        $sql = "Select id,url,has_gallery from assets where id in ($id_list)";
       # echo $sql; exit;
        $pdo = MyPDO::instance();
        $result = $pdo->query($sql);
        echo "Updating info on each gallery item. ";
       while( $row = $result->fetch()){
            $sq = array();
             $id = $row['id'];
             $url = trim($row['url']);
            if (! $row['has_gallery']){
                create_thumb($id,$url,'galleries');
                $sq[] = "has_gallery = true";
            }

            if (empty($row['first_use_in'])){
                $sq[] = "first_use_in = '/galleries.php/?$galleryid'";
            }
            if (isset($sq)){
                $sqs = implode(', ',$sq);
                $sql = "update assets set $sqs WHERE id = '$id';";
 #   echo $sql;
                 $result = $pdo->query($sql);
            }
        }
  echo "Gallery items updated" . BRNL;
}

 function build_files_array($loc) {
            #$loc =  SITE_PATH . '/' . $upload_dir . '/' . $this_file;
             #use _FILES array to look like an upload
            
         $finfo = new finfo(FILEINFO_MIME_TYPE);
       
        $file_data = array(
            'tmp_name' => $loc,
            'name' => basename($loc),
            'error' => 0,
            'size' => filesize($loc),
            'type' => $finfo->file($loc)
        );
        return $file_data;
}


function post_asset($post_array){
   /*
        note: doesn't use $_POST because array may come
        from the news_item_edit form instead.
        if POST, strip slashes first
    */
    $changed_asset= false;
	$datetag=date('m/d/y');
 if (! isset($post_array['id'])){throw new Exception ("Posting asset with no id.");}
    $id = $post_array['id'];

    

     global $image_extensions;
     $pdo = MyPDO::instance();

     if ($id == 0){

        $title = "temp holding place";
        $sql = "INSERT into `assets` (status,title,date_entered) values ('T','$title',NOW() );";
        echo $sql . BRNL;
        $pdo->query($sql);
        $last_id = $pdo->lastInsertId();
        $post_array['id'] = $id = $last_id;
        $post_array['status'] = 'T';
        echo "New ID created (temp): $id<br>\n";
    }

    echo "<hr>Starting post_asset on id $id. " . BRNL;
    #first see if file is in uploads directory.
    #if so, build a FILES array for it so it looks like
    #an uploaded file
    
    $link = $post_array['link'] ?? '';
      
    if (strncmp ($link, '/assets/uploads',15) == 0) {
          $_FILES['linkfile'] = build_files_array(SITE_PATH . $link);
    }
#if there's a file specified in _FILES (uploaded or bulk), do it

    # if file uploaded the _FILES array will be set.
    # for bulk uploads, the asset_generator script sets it for each file.
    
    #first get linked file
     $upload_name = 'linkfile';
    if (!empty($_FILES[$upload_name]['name'])){
    try {
       list($file_name,$orig_name) =
            accept_upfile($upload_name,"/assets/files",$id);
             $post_array['need_thumb'] = true;
    }

    catch (RuntimeException $e) {
    echo $e->getMessage();
     }


        $post_array['notes'] = "$datetag Link Source uploaded from $orig_name\n"
            . $post_array['notes']  ;

       $post_array['link'] = "/assets/files/$file_name";


    }
    #check that link exists, get size, mime, etc

        if (!empty ($post_array['link'])){
            $link = trim($post_array['link']);
        }
       
        if (substr($link,0,1) == '/' and strpos($link,'/galleries')===false){ #local
        	$postfile = SITE_PATH . $link;
        	
           if ( is_file ($postfile) === false){

            echoAlert( "post_asset: no file found at link: $link");
            echo "<script>window.location.href='/scripts/asset_edit.php?id=$id'</script>";
            }

        }

	if (empty($link)){echo "No link data specified for asset";
		exit;
	}
    $post_array = array_merge ($post_array , add_link_data($link));

#now get any extra file for the thumbnail
    $upload_name = 'upfile';
    if (!empty($_FILES[$upload_name]['name'])){
    try {
       list($file_name,$orig_name) =
            accept_upfile($upload_name,"/assets/thumb_sources",$id);
             $post_array['need_thumb'] = true;
    }

    catch (RuntimeException $e) {
    echo $e->getMessage();
     }

    $post_array['notes'] = "$datetag Thumb source Uploaded from $orig_name\n"
            . $post_array['notes']  ;

       $post_array['url'] = "/assets/thumb_sources/$file_name";

       $post_array['need_thumb'] = true;
       // if (in_array ( ['Cartoon','Ad'], $post_array['type'] ) && !$post_array['has_toon'] ) {
//             $post_array['need_toon'] = true;
//         }

    }
    $link = $post_array['link'];

/* below needs rewrite */
    #if file is in bulk upload directory.  Move into files directory
        # and change the url
       
   //  if (substr($url,0,15) == '/assets/uploads'){
//        
//         $old_path = SITE_PATH . $url;
//         $ext = strtolower(pathinfo($url,PATHINFO_EXTENSION));
//         $new_url = "/assets/files/${id}.$ext";
//         $new_path = SITE_PATH . $new_url;
//            copy ($old_path, $new_path); 
//         $post_array['url'] = $new_url;
//         #check to make sure it copied
//         if (file_exits($new_path)) {
//             unlink ($old_path);
//         }
//         else {echo "Error moving file from uploads. $old_path retained.";}
// 
//     }




 #test to see if url has changed; if so update thumb
    $orig_link = $pdo->query("SELECT link from `assets` where id = $id;")->fetchColumn();

      if( $orig_link != $post_array['link'] ){
        if (! empty($orig_link)) {
            echo "Source has changed (was $orig_link); will regenerate thumb" . BRNL;
                $changed_asset = true;
        }
        $post_array['need_thumb'] = true;
    }
    if( $orig_url != $post_array['url'] ){
        if (! empty($orig_url)) {
            echo "Thumb source has changed (was $orig_url); will regenerate thumb" . BRNL;
                $changed_asset = true;
        }
        $post_array['need_thumb'] = true;
    }

#now create thumbs
        $thumb_source = $post_array['link'];

        if (!empty($post_array['url'])){$thumb_source = $post_array['url'];}

        if ($post_array['need_thumb']){
            echo "Need new thumbnail for $thumb_source... " . BRNL;
            if($thumb = create_thumb ($id,$thumb_source,'thumbs')){
                //$post_array['has_thumb'] = true;
                $post_array['thumb_file'] = $thumb;
                echo "Thumb $thumb created. ";
            }
            echo "<br>";
        }
        if ($post_array['need_gallery']){
            echo "Need new gallery ... ";
            if($thumb = create_thumb ($id,$thumb_source,'galleries')){
                echo "Gallery $thumb created. ";
                //$post_array['has_gallery'] = true;
            }
            echo "<br>";
        }
        if ($post_array['need_toon']){
            echo "Need new toon ... ";
            if($thumb = create_thumb ($id,$thumb_source,'toons')){
                echo "Toon $thumb created";
                //$post_array['has_toon'] = true;
            }
            echo "<br>";
        }

    // $post_array['has_thumb'] = png_or_jpg_exists('thumbs',$id);
// 	$post_array['has_gallery'] = png_or_jpg_exists('galleries',$id);
//     $post_array['has_toon'] =  png_or_jpg_exists('toons',$id);;

#recho ($post_array,"Ready to Update"); exit;
    // Decomptress the tag options
    $post_array['tags'] = charListToString($post_array['tags']);

    #remove entities from title, caption, notes
    foreach (['caption','title','notes'] as $v){
        $post_array[$v] = spchard($post_array[$v]);
    }
   
   if ($post_array['status'] == 'T'){$post_array['status'] = 'N';}
   # else { $post_array['status'] = $itemdata['status'];}

#recho ($post_array,'Post array ');
      update_asset($post_array);

    return $id;

}

function youtube_id_from_url($url) {
			
             $pattern = 
				'%#match any youtube url
                (?:https?://)?  # Optional scheme. Either http or https
                (?:www\.)?      # Optional www subdomain
                (?:             # Group host alternatives
                  youtu\.be/    # Either youtu.be,
                | youtube\.com/
                )				# or youtube.com
                (?:          # Group path alternatives
                    embed/     # Either /embed/
                  | v/         # or /v/
                  | watch\?v=  # or /watch\?v=			
                ) ?            # or nothing# End path alternatives.
                               # End host alternatives.
                ([\w-]+)  # Allow 10-12 for 11 char youtube id.
                %x'
                ;	          
            $result = preg_match($pattern, $url, $matches);
            if (!empty($vid = $matches[1] )){
           	 	echo "Matched youtube $matches[0] to video id $vid " . BRNL;
           		return $vid; 
           	}
            else {
            	#echo "No youtube id in $url" . BRNL;
            	return false;
            }
        }

    




function accept_upfile($upload_name,$upload_dir,$asset_id){
    #called with path to directory to save file in and the id to use in the name.
    #returns the filename (/id.ext);
    echo "Starting accept $upload_name... " . BRNL;

    #recho ($_FILES[$upload_name],"Files array to accept_file");

    #ame will be id.ext where ext comes from mime
    global $accepted_mime;

    $size_limit_mb = 50;
    $size_limit = $size_limit_mb * 1000000;

 // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.

    if (
        !isset($_FILES[$upload_name]['error']) ||
        is_array($_FILES[$upload_name]['error'])
    ) {
        throw new RuntimeException("Error: Multiple file named $upload_name.");
    }

    // Check $_FILES[$upload_name]['error'] value.
    switch ($_FILES[$upload_name]['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    // Ycheck filesize here.
    $size_mb = $_FILES[$upload_name]['size']/1000000;
    if ($size_mb > MAX_UPLOAD_MB) {
        throw new RuntimeException("Exceeded filesize limit " . MAX_UPLOAD_MB . "MB.");
    }
    $orig_name = $_FILES[$upload_name]['name'];
    $tmp_file = $_FILES[$upload_name]['tmp_name'];
    #$finfo = new finfo(FILEINFO_MIME_TYPE);
    #$fmime =  $finfo->file($tmp_file);
    $fmime = $_FILES[$upload_name]['type'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    #check if ext matches mime
    if ($fmime != $accepted_mime[$ext]){
        echo "Warning: ext $ext does not match mime $fmime" . BRNL;
    }

    // if (false === $ext = array_search(
//         $finfo->file($_FILES[$upload_name]['tmp_name']),
//         $accepted_mime,
//         true
//     )) {
//         throw new RuntimeException("file mime type $fmime not permitted.");
//     }

    if (false === in_array($fmime,array_values($accepted_mime)) ){
        throw new RuntimeException("file mime type $fmime not permitted.");
    }


    $file_name = "${asset_id}.${ext}";
    $file_path = SITE_PATH . "/$upload_dir/$file_name";

    if (empty($file_name)){throw new RuntimeException("Cannot assign name to $upload_name file");}

    if (!rename ($tmp_file, $file_path)){

        throw new RuntimeException("Failed to move $upload_name from $tmp_file to $file_path" . BRNL);
    }
    else {
        chmod ($file_path,0644);
        echo "File $orig_name was uploaded successfully to $file_name<br>
        Mime type: $fmime, size $size_mb MB <br>";
    }

    return  [$file_name,$orig_name];
}


?>
