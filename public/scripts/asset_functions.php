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

function getMimeGroup ($mime) {
	if (strncmp($mime,'image/',6) == 0 ){return 'image';}
	elseif (strncmp($mime,'video/',6) == 0) {return 'av';}
	elseif (strncmp($mime,'audio/',6) == 0) {return 'av';}
	elseif (strncmp($mime,'application/',12) == 0) {return 'doc';}
	else {return '';}
}
	
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
	global $assetfields;
     $itemdata = array(); #store data to display
    #echo "Starting get_asset_data";

    if ( $id == 0){
    	foreach ($assetfields as $f){
    		$itemdata[$f] = '';
    	}
    	$itemdata['status'] = 'N';
    	$itemdata['date_entered'] = 'NOW()';
    	$itemdata['contributor'] = $_SESSION['username'];
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
    #set hsa thumb, gallery, and toon
    #if (file_exists(get_gfile(SITE_PATH . "/assets/thumbs/${id}.jpg"))){$itemdata['has_thumb'] = true;}
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


function create_thumb($id,$fsource,$ttype='thumbs'){

    global $image_extensions;
    global $document_extensions;
    global $mmm_extensions;
    global $thumb_width;

    #if (!$id || !$type){die "Create thumb called with $id,$type empty";}
   /* returns url (/assets/thumbs/$id.png) to thumbnail file at $source
    strategy is to always store link to thumb in the db with the
    asset, as opposed to figuring it out and creating it on the fly.
	
	fsource is url to source (from asset url column).  Maybe remote or local
	
    tType is array of types:
    If thumbs, creates a 200w thumb in the thumb file.
    If galleries, it creates a 300w copy
    If toons, it creates an 800w copy.


    if asset is local, set thumb to either 200w copy of the image
    or to generic pdf image or generic document image

    if image is on a url, set to generic url image (or
    curl the url and build a png thumb)


 */
 	$fsource = trim($fsource);
 	
 	#check to see if ttype requested is recognized width
    if (! $max_dim = $thumb_width[$ttype]){die ("Invalid thumb type requested for thumbnail: $ttype");}
   
    if (empty($fsource)){die ("No file specified to create thumb  from.<br>\n");}
    else {echo "Creating thumb from $fsource" . BRNL;}
	
	 $thumb = '';
	 
	if ($videoid = youtube_id_from_url($fsource)){
		#echo "got videoid $videoid" . BRNL;
		$yturl = "http://img.youtube.com/vi/$videoid/mqdefault.jpg" ;
		#echo "yturl $yturl". BRNL;
		$thumb = "${id}.jpg";
		copy ($yturl , SITE_PATH . "/assets/$ttype/$thumb"); 
		return $thumb;
	   
	}
	
	
	 #set source path to either absolute file path or url
	 
	 if (substr($fsource,0,1) == '/') { #local file
	 	$source_path = SITE_PATH . $fsource;
	 }	
	 else {
	 	$source_path = $fsource;
	 	
	 }
	 	
	 	$finfo =  finfo_open(FILEINFO_MIME_TYPE);
      if (! $source_mime = $finfo_file($finfo, $source_path)) {
      	echo "unable to get mime type from source $source_path" . BRNL;
      }
      else {echo "Mime: $source_mime" . BRNL;}
      
      
	switch ($source_mime) {
		case 'application/msword' :
			$use_icon="doc.jpg";
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
			break;
		case 'application/pdf' :
		case 'image/gif':
		case 'image/jpeg':
		case 'image/png':
		case 'image/tiff':
			$thumb = build_im_thumbnail($id,$source_path,$ttype,$max_dim);
			return $thumb;
			break;
		case 'text/html':
			$use_icon="web.jpg";
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
			break;
		case 'video/mp4':
			$use_icon = 'mp4.jpg';
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
			break;
		case 'audio/mp3':
		case 'audio/m4a':
			$ext = substr($source_mime,6,3);
			$use_icon = "${ext}.jpg";
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
			break;
		case 'video/quicktime':
			$use_icon = 'mov.jpg';
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
			break;
			
		default:
			$use_icon = 'default.jpg';
			$thumb = "${id}.jpg";
			copy (SITE_PATH . "/assets/icons/$use_icon" , SITE_PATH . "/assets/$ttype/$thumb"); 
			return $thumb;
			break;
			
	}
	 #if still haven't created a thumb...
  	   die("Cannot determine how to build thumb on $fsource (mime: $source_mime)");



}

function build_im_thumbnail ($id,$source,$ttype,$max_dim){
    $thumb = $id . '.jpg';
    if (mime_content_type ($source ) == 'application/pdf'){
    	$source = trim($source) . '[0]'; #page 1
    }
     $im = new imagick ( $source);
    $im->setImageFormat('jpg');
    $im->thumbnailImage($max_dim, $max_dim,true); #best fit
    $im->writeImage(SITE_PATH . "/assets/$ttype/$thumb");
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
            /* 
            $loc is complete file path
            $loc =  SITE_PATH . '/' . $upload_dir . '/' . $this_file;
            or $loc = PROJ_PATH / ftpf /this_file
             #use _FILES array to look like an upload
            */
            
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
   # recho ($post_array,'Post_array');
   # recho ($_FILES,'FILES array');
    
    $form_link = $post_array['link'] ?? '';
      
   
    
/**
	 relocate uploads
	 
	 Files are either uploaded from form or uploaded some other way
	 into specific directories ftp or uploads.
	 These files need to be moved into correct location in assets, and
	 then the asset created with the appropriate link.
	 
	 From asset form:
	 	
	 	'link_sourcc' used for both source file and link to.
	 	'thumb_source' used for additional file just to use for thumbnail
	 	in either one, you can have
	 	* a url
	 	* a local directory/file
	 	* ftp/filename
	 	* uploads/filename 
	 	
	 	or you can use an uploaded file
	 	'link_upload is the uploaded main fail
	 	'thumb_upload' is the uploaded thumb source file
	 	
	  	uploaded file always takes priority for main link
	 	else use the link directory name
	 	
	 	For link possibility
	 		check to make sure file exists
	 		move file to appropriate directory, renamed in most cases
	 		set link in asset to new loacation/name
	 		
	 		file uploaded with form has priority
	 	Then set thumb from uplink if supplied
	 	
	 	*/
	 	
	 	// get the main source
	 	
	 	if (!empty($_FILES['linkfile']['name'])){
	 		$link = relocate ($id,'link_upload');
	 		
	 	} elseif (strncmp ($form_link, '/uploads',8) == 0) {
          $link = relocate($id, 'uploads',$form_link);
    	} elseif (strncmp ($form_link, '/ftp',4) == 0) {
    		$link = relocate($id, 'ftp',$form_link);
    	} else {
    		$link = $form_link;
    	}
    if (substr($link,0,1) == '/') { #local file
    	 $finfo = new finfo(FILEINFO_MIME);
		 $post_array['mime'] = $finfo->file(SITE_PATH . "/$link");
		 $post_array['sizekb'] =  round(filesize(SITE_PATH . "/$link")/1000,0);
   	 $post_array['link'] = $link;
   	}
   	$linkdata = add_link_data($link);
   	$post_array = array_merge ($post_array,$linkdata);
   	
    echo "post_array[link] set to $link" . BRNL;
  
    	#now check for separate thumb file source
	 	#remove old duplicate of link
	 	if ($post_array['url'] == $post_array['link'] ){
	 		$post_array['url'] = '';
	 	}
	 	
	 	if (!empty($_FILES['upfile']['name'])) {
	 		$thumb_source = relocate ($id,'thumb_upload' );
	 		$post_array['url'] = $thumb_source;
	 	}
	 	if (!empty ($post_array['url'])){
	 		$thumb_source = $post_array['url'];
	 	} else {
	 		$thumb_source = $link;
	 	}
	 	
	
  

 #test to see if url has changed; if so update thumb
     $row = $pdo->query("SELECT link,url from `assets` where id = $id;")->fetch(PDO::FETCH_ASSOC);
		$orig_link = $row['link'];
		$orig_url = $row['url'];
		
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
        

   

        if (isset($post_array['need_thumb'])){
            echo "Need new thumbnail from $thumb_source... " . BRNL;
            if($thumb = create_thumb ($id,$thumb_source,'thumbs')){
                //$post_array['has_thumb'] = true;
                $post_array['thumb_file'] = $thumb;
                echo "Thumb $thumb created. ";
            }
            echo "<br>";
        }
        if (isset($post_array['need_gallery'])){
            echo "Need new gallery ... ";
            if($thumb = create_thumb ($id,$thumb_source,'galleries')){
                echo "Gallery $thumb created. ";
                //$post_array['has_gallery'] = true;
            }
            echo "<br>";
        }
        if (isset ($post_array['need_toon']) ){
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
        $post_array[$v] = spchard($post_array[$v]) ?? '';
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
            if (array_key_exists(1,$matches)){
            	$vid = $matches[1] ;
           	 	echo "Matched youtube $matches[0] to video id $vid " . BRNL;
           		return $vid; 
           	}
            else {
            	#echo "No youtube id in $url" . BRNL;
            	return false;
            }
 }

    




function check_file_uploads ($upload_name){
	// checks for upload errors, file exits,
	// returns the original name of the file.
	
	global $accepted_mime;
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
    if (!file_exists($_FILES[$upload_name]['tmp_name'] )){
    	throw new RuntimeException ("uploaded $upload_name does not exists.");
    }
    $fmime = $_FILES[$upload_name]['type'];
    $original = $_FILES[$upload_name]['name'];
   $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    #check if ext matches mime
    if ($fmime != $accepted_mime[$ext]){
        echo "Warning: ext $ext does not match mime $fmime" . BRNL;
    }
    return $original;
}


function relocate ($id,$type,$link=''){
   /**
		@type is upfile,linkfile,ftp, or upload
		@link is supplied link if any
		@id is id this asset will have; may be used as file name.
		@returns url to new location/file
		
		
    
**/
    echo "Starting relocation $type ... " . BRNL;
    $finfo = new finfo(FILEINFO_MIME);
	switch ($type) {
		case 'link_upload' :
			
			$orig = check_file_uploads('linkfile');
			$orig_path = $_FILES['linkfile']['tmp_name'];
			$new_mime = $finfo->file($orig_path) ;
		
			$orig_ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
			$new_url = '/assets/files/' . $id . ".$orig_ext";
			$new_path = PROJ_PATH . '/shared' . $new_url;
			
			break;
			
		case 'thumb_upload' :
			$orig = check_file_uploads('upfile');
			$orig_path = $_FILES['upfile']['tmp_name'];
			$new_mime = $finfo->file($orig_path) ;
			$orig_ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
			$new_url = '/assets/thumb_sources/' . $id . ".$orig_ext";
			$new_path = PROJ_PATH . '/shared' . $new_url;
			
			break;
			
		case 'uploads':
			$orig_path = PROJ_PATH . '/' . $link;
			if (! file_exists($orig_path)) {
				throw new RuntimeException ("file $link does not exist");
			}
			$new_mime = $finfo->file($orig_path) ;
			$orig_ext = strtolower(pathinfo($link, PATHINFO_EXTENSION));
			$new_url = '/assets/files/' . $id . ".$orig_ext";
			$new_path = PROJ_PATH . '/shared' . $new_url;
			break;
			
		case 'ftp':
			$orig_path = PROJ_PATH . "/$link";
			if (! file_exists($orig_path)) {
				throw new RuntimeException ("file $link does not exist");
			}
			$orig_ext = strtolower(pathinfo($link, PATHINFO_EXTENSION));
			$orig_name = substr($link,5); # remove the /ftp/ from beginning.
			$new_mime = $finfo->file($orig_path) ;
			
			if (getMimeGroup($new_mime) == 'av'){
				$new_url = "/assets/av/" . $orig_name;
			}
			else {	
				$new_url = '/assets/files/' . $id . ".$orig_ext";
			}
			$new_path = PROJ_PATH . '/shared' . $new_url;
		
			break;
		default:
			throw new RuntimeException ("file relocate type $type not recognized");
			
	}
	echo "WIll now move $orig_path to $new_path" . BRNL;
	rename ($orig_path,$new_path);
	 chmod ($new_path,0644);
	echo "New url: $new_url" . BRNL;

 	return $new_url;
}
