<?php
namespace digitalmx\flames;
use digitalmx\flames as f;

#ini_set('display_errors', 1);


//BEGIN START
	require_once "../init.php";

	#require others

	use digitalmx\flames\DocPage;
	use digitalmx as u;
	use digitalmx\flames\Definitions as Defs;

	$pdo = \MyPDO::instance();

	$page = new DocPage;
	$title = "Assets"; 
	echo $page->startHead($title, 3,['ajax']);
	echo <<<EOT
	<script>
function check_select_all(obj) {
	var sbox = document.getElementById('all_active');
	if (obj.value == ''){
		sbox.checked = true;
	}
	else {sbox.checked = false;}
}
</script>
EOT;
	echo $page->startBody($title ,2);

//END START


/* script to view manage asset database.


*/



#require_once "news_functions.php";
# has the get_asset routines

require_once "asset_functions.php";



$sql_now = sql_now('date');
   



$asset_limit = 25;

if ($_SESSION['level'] >= 6){
    $asset_limit = 500;
// echo <<<EOT
// <hr>
// <button type = "button"
//             onclick = "window.open('/scripts/asset_edit.php?id=0','asset_edit');">
//             New Asset</button>
// </p>
//
// EOT;


    if ($_SESSION['level'] >7){ echo <<<EOT
        <b>Other Asset Links</b>
        <ul>
        <li><a href='/scripts/asset_generator.php' target='asset_generator'>Bulk Asset Generator</a> Create assets from a directory of images
       
        <li><a href='/scripts/assets-review.php' target='asset_review'>Review Asset Tags</a>
        <li><a href='/scripts/recent_assets.php' target='_blank'>Update Recent Asset Report</a> (runs daily on cron + on publish).

        </ul>
    <hr>
EOT;
    }


}


 #shortcut ot allow coming into script directly with a search term.
 $get_token=false;

if (!empty($_POST['submit'])){$_GET=[];} #ignore data in get url

if (!empty ($_GET['searchon'])){
    $_POST['searchon'] = $_GET['searchon'];
    $_POST['all_active'] = 1;
    $get_token=true;
}
if (!empty ($_GET['get_next'])){
    $start=$_GET['get_next'];
    $next = next_asset_id($start);
    $_POST['id_range'] = "$next - ";
    $get_token=true;
}



if (($_SERVER['REQUEST_METHOD'] == 'POST') ||  $get_token){

    if (!empty($_POST['delete'])){
        $id = $_POST['delete'];
        delete_asset($id);
    }
	$pdo = \MyPDO::instance();
    #convert all values to spchars
    $post = array_merge(empty_post(),$_POST);

   #recho ($_POST,'$_POST');
   echo show_asset_search($post); #put form at top and bottom of pages


   if(empty( $sqls = process_asset_search($post))){
        die ("Error processing form data. No search criteria.");
    }
    if (!empty($_GET['get_next'])){$asset_limit = 5;}
	
    $sql = "SELECT id FROM assets WHERE $sqls ORDER BY id LIMIT $asset_limit;";
    echo "<div style='border:1px solid gray;'> $sql</div><br>";
    echo "<p>Selected Assets are shown below.</p>";
    #$stmt = $pdo -> query($sql);
    $id_list = $pdo -> query($sql) ->fetchAll(PDO::FETCH_COLUMN);
    $id_count = sizeof ($id_list);
    if ($id_count >= $asset_limit){
       $last_id = $id_list[$asset_limit - 1];
        ++ $last_id ;

        echo "<p class='red'>Showing first $asset_limit of $id_count results.  To get more,
        repeat search using id = '$last_id - '.</p>";
        }
     
       
    

    if (count($id_list) == 0){echo "<p style='color:red;font-weight:bold;'>Nothing found.</p>";}
    else {
    	$_SESSION['asset_search_ids'] = $id_list;
    	#save the list in the session file so it can be used repeatedly
    	
    	$id_list_string = implode(',',$id_list);
    	echo <<<EOT
    	<form action = '/scripts/asset_edit.php' method='post' target='asset_edit' onsubmit = "window.open('','asset_edit');" >
    	 $id_count results. 
    	<input type=hidden name='id_list_string' value='$id_list_string'>
    	<input type='submit' name='submit'  value='Edit All Found' >
    	<input type='submit' name='submit' value = 'Review All' >
    	
    	</form>
EOT;
    	echo show_assets_from_list($id_list);
       
    }

    echo show_asset_search($post); #repeat form at the end
}



elseif ($_SERVER['REQUEST_METHOD'] == 'GET'){

    $htesc = array('status'=>'','all_active'=>true); #initial status selection
    echo f\show_asset_search($htesc);
}


######################################

function tag_display($tags,$style='string'){
    global $asset_tags;
    #display as style string (x,y,z) or table
    if (empty($tags) ){return '';}

    foreach (str_split($tags) as $tag){
        $tagnames[] =  $asset_tags[$tag];
    }
    if ($style=='string'){$t = implode(", ",$tagnames);}
    elseif ($style == 'table'){
        $t='<table><tr>';
        $c = 0;
        foreach ($tagnames as $tag){
            $t .= "<td>$tag</td>";
            ++$c;
            if ($c%3==0){
                $t .= "</tr>\n<tr>";
            }
        }
        $t .= "</tr></table>\n";
    }



   //  foreach ($taglist as $tag){
//         $t .= $asset_tags[$tag] . " ";
//     }
    return $t;
}

function show_assets_from_list($ids){
        global $asset_status;
        $pdo = \MyPDO::instance();
        if (! isset($ids)){die ("Nothing sent to show_assets_from_list");}
        if (! is_array($ids)){
            if (is_numeric($ids)){$ids = array($ids);}
            else {die ("No valid ids to show_assets_from_list");}
        }
		



         $id_list = implode(',',$ids);

        $sql = "SELECT * FROM assets WHERE id in ($id_list);";
        $stmt = $pdo->query($sql) ;
        if (! $stmt) {return "No assets found";}


        $output = '';
        while ($row = $stmt->fetch() ){
         #check entities
            $id = $row['id'];
            $title = spchar(stripslashes($row['title']));
            $notes = nl2br(spchar($row['notes']));
            $caption = ($row['caption'])?spchar($row['caption']):'(no caption)';
            if (!empty($row['first_use_in'])){
                $first_use =$row['first_use_in'];
                $first_use_url = $row['first_use_in'];
                $first_link = "<a href='$first_use_url' target='newspage'>$first_use</a>";

                $first_date = $row['first_use_date'];
            }
            else {$first_date = 'Not Used';$first_link='';}

            $tag_display = tag_display($row['tags'],'string');
            $reviewed = $row['review_ts'];

            $status_label = $asset_status[$row['status']];
            if ($row['status'] == 'R'){
                $status_label .= " (On ${row['review_ts']} )";
            }
            $status_style = ($row['status'] == 'D')?"color:red;":'';

            if ($row['status'] == 'D'){
                $status_style = "color:red;";
                $image = "(Image Deleted)";
            }
            else {
             $image = get_asset_by_id($id);

            }
             $show_thumb= ($row['has_thumb'])? "&radic;" : "";
             $show_gallery= ($row['has_gallery']) ? "&radic;" : "";
            $show_toon = ($row['has_toon']) ? "&radic;" : "";

            $editable = false; 	$edit_panel = '';
             if (
             $_SESSION['level'] > 6
             or
             strtolower($_SESSION['username']) == strtolower($row['contributor'])
             or
             strtolower($_SESSION['username']) == strtolower($row['source'])
             
             ){$editable=true;}

  
    if ($editable){ 
	$edit_panel = <<<EOT
	<button type='button'
        onclick="ewin = window.open('/scripts/asset_edit.php?id=$id','asset_edit');">
        Edit Asset</button>

EOT;


		if ( $row['status'] == 'D' ){
			$edit_panel .= "Already Deleted.  To Delete linked files, click Edit Asset.";
		}
		else {
			 $edit_panel .= "<button type='submit' name='delete' value='$id' style='background:#f33'>Mark Deleted</button>";
		}
        
	}
        
  if ($dt = DateTime::createFromFormat('Y-m-d H:i:s',$row['date_entered'])){
  	$date_entered = $dt->format('d M Y');
  }
  else {$date_entered = $row['date_entered'];}
  


    #2 column table
    $output .= <<<EOT
    <form method='post'>
    <input type='hidden' name='id' value="$id">

    
    <table>
       <tr style='border-top:1px solid blue;'> <td style='font-size:.9em;'>
        $id <b>$title</b><br>
        $caption<br>
        <i>Source url:</i> ${row['url']} <br>
        <i>Type:</i> ${row['type']}  <i>Vintage</i> ${row['vintage']}<br>
        <i>Status:</i> $status_label <br>
    
        <i>Entered:</i> $date_entered <i> First Used:</i> $first_link<br> 
        <i>Contributor:</i> ${row['contributor']} <br>

        <i>Size</i> ${row['sizekb']} KB<i> Has:</i> Thumb $show_thumb &middot; Gallery $show_gallery &middot; Toon $show_toon<br>
        <br><b>Tags</b> (* archival)<br>$tag_display<br>

        $edit_panel
        </td><td>
        <a href='imagelink' target='image'>$image</a>
        </td></tr>
        
        </table>
		</form>
EOT;

    }


    return $output;
}
function empty_post(){
    return  array(
'searchon' => '',
'vintage' => '',
'plusminus' => '',
'type' => '',
'id_range' => '',
'all_active' => '1',
'contributor' => '',
'searchuse' => '',
'url' => '',
'sqlspec' => '',
'tags' => '',
'relative' => '',
'no_contributor' => false,

);
}

function show_asset_search($pdata){
    global $asset_types;
    global $asset_status;
    global $asset_tags;
    #if (empty($pdata['status'])){$pdata['status'] = 'G';}

    // if ($_SESSION['level']>6){$asset_status_a = $asset_status;}
//     else{
//         foreach ($asset_status as $c=>$v){
//             if (in_array($c,array('G','U'))){
//                 $asset_status_a[$c] = $v;
//             }
//         }
//     }


	$pdata = array_merge(empty_post(),$pdata);


   $use_options = build_options(array('On','Before','After'),$pdata['relative']);
    $type_options = build_options($asset_types,$pdata['type']);
    //$status_options = build_options($asset_status,$pdata['status']);
    $no_c_checked = (!empty($pdata['no_contributor'])) ?'checked':'';
    $all_active_checked = (!empty($pdata['all_active'])) ?
    	'checked':'';
   
    	$tag_data = charListToString($pdata['tags'])  ;
    	$search_asset_tags =$asset_tags;
    	$search_asset_tags['Z'] = 'z Any Archival';
    	
    	$tag_options = buildCheckBoxSet('tags',$search_asset_tags,$tag_data,3);
    
    $status_options = build_options($asset_status,$pdata['status']) ;
    $searchon_hte =  spchar($pdata['searchon']);
    $vintage =  $pdata['vintage'] ?? '';
    $plusminus = $pdata['plusminus'] ?? '';
    $about_div = <<<EOT
        <div id='AboutAssets'  style="display:none;"  >
        <p>"Assets" are the various pictures, documents, and audio/video items that
have been uploaded to the site over the years.  The assets have been (mostly)
indexed in a database for easy search and retrieval.  Use this page to
find and retrieve an asset.</p>
<p>Graphic assets are stored in the original resolution and a 200 pixel wide version (called "thumb" and sometimes others.</p>

<p>Assets have various attributes, including a title, caption, and keywords.
Searching for a word (including multi-word phrases) will return assets matching
the search term in any of the three fields mentioned.</p>
<p>Assets also have a "vintage" which is the year the item was produced.  
You can search for a range of years.  For many items, the vintages are guesses.
Items also have a first-use, generally when it was first published in a newsletter.</p>

<p>Assets may also have "tags" - checkboxes for a series of archival classifications, like "Marketing bulletin", "Facility", "Corporate".  These can be used in searching assets.  Assets that aren't AMD archives (like photos that go with off-topic articles) don't have any tags.</p>

<p>Enjoy!</p>


<hr>
        </div>
EOT;
    $hideme = ($_SESSION['level']<6)?"style='display:none'":'';
    $output = <<<EOT

    <hr>
    <h3>Search Assets  <input type='button' name='ShowAbout' onclick="showDiv('AboutAssets')" value="What Are Assets?" />
   <button type = "button" onclick = "window.open('/scripts/asset_edit.php?id=0','asset_edit');">
             New Asset</button></h3>


    $about_div


</p>

    <form method='post' name='select_assets' id='select_assets'>
    <p>Choose any of the parameters below to find photos, videos, and audio files.
    <hr>
    <table>
    <tr><td width='25%'></td><td ><button type='button' onclick="clearForm(this.form);">Clear Form</button></td></tr>
    <tr><td>Search Terms</td><td><input type='text' name='searchon' value = '$searchon_hte'></td></tr>
    <tr><td>&nbsp;</td><td><small>Search is not case sensitive. Search terms can include spaces (like 'John East'). Separate multiple terms with commas, multiple terms are ORed.</small></td></tr>
    <tr><td>Vintage (year)</td><td><input type='text' name='vintage' size=8 value='$vintage'> +/- years: <input type=text name='plusminus'  size=3 value='$plusminus'></td></tr>
    <tr><td>Asset Type</td><td><select name='type'>$type_options</select></td></tr>

    <tr><td>Tags (* tags are 'archival')</td><td>$tag_options</td></tr>

     <tr><td>Asset id or range</td><td><input type=text name='id_range' value='${pdata['id_range']}'></td></tr>

    <tr><td>Status</td><td><input type='checkbox' name='all_active' id='all_active' value=1 $all_active_checked>All Active .. or .. <select name='status' onChange = 'check_select_all(this)' >$status_options</select></td></tr>

 <tr $hideme><td>Contributor<br>
    </td><td><input type='text' name='contributor' id='contributor' value='${pdata['contributor']}'
    onchange='document.getElementById("no_contributor").checked = false;'><input type='checkbox' name='no_contributor' id='no_contributor' $no_c_checked  onclick="document.getElementById('contributor').value = '';">Unattributed
   </td></tr>

   <tr><td>First Use</td><td><select name='relative'> $use_options</select> <input type='text' name='searchuse' value='${pdata['searchuse']}'></td></tr>


EOT;
    if ($_SESSION['level'] > 7){
    $output .= "<tr class='grayback'><td colspan='3'>Admin Functions</td></tr>\n";
     $output .= "<tr class='grayback'><td>source URL</td><td><input type='text' name='url' size='100' value='${pdata['url']}'></td></tr>\n";
     $output .= "<tr class='grayback'><td>addt'l sql WHERE clause</td><td>AND <input type='TEXT' name='sqlspec' value='${pdata['sqlspec']}' size=100></td></tr>";
    

    }
$output .= <<<EOT
<tr><td><button type='submit' name='submit' id='submit' value=true >Submit</button></td> <td></td></tr>
<tr><td colspan='3'><hr></td></tr>
    </table>

</form>


EOT;
    return $output;
}

function process_asset_search($data){
    if (!empty ($son = $data['sqlspec'])){
        $qp[] = $son;
    }

    if (! empty ($son = $data['id_range'])){
        $qp[] = id_search($son);
    }


    if (! empty($son = $data['tags'])){
       $qp[] = tag_search ($son);

    }

    if (! empty($son = $data['searchon'])){

        #produce query phrase for the search terms
        $qp[] = token_search($son);

    }
     if (! empty($son = $data['relative']) && !empty($suse = $data['searchuse'])){
        #produce query phrase for the use date terms
        $qp[] = use_search($son,$suse);

    }
    if (! empty($son = trim($data['vintage']))){
        $qp[] = use_vintage($data['vintage'],$data['plusminus']);
    }
    if (!empty($son = trim($data['contributor']))){
        $qp[] = "contributor LIKE '%$son%'";
    }
    if (!empty($data['no_contributor'])){
            $qp[] = "(contributor is NULL or contributor = '' )";
     }

    if (!empty($son = $data['type'])){
        $qp[] = "type = '$son'";
    }

     if (!empty($son = $data['status'])){
        $qp[] = "status = '$son'";
    }
   elseif ($data['all_active'] == 1){
        $qp[] = "status not in ('X','D','E','T') ";
    }

    if (!empty($son = $data['url'])){
        $qp[] = "(url like '%" . $son . "' OR link like '%" . $son . "')";
    }




    if (!empty($qp)){
        $sqls = implode(' AND ',$qp);
    }

      return $sqls;
}



function id_search($son){

        preg_match('/^\s*(\d+)?\s*(\D+)?\s*(\d+)?/',$son,$m);
        $id1 = $m[1] ?? 0;
        $dl = $m[2] ?? '';
        $id2 = $m[3] ?? 0;

        if ($id1>0){
            if ($id2>0){
                if ($id2 <$id1){ #swap
                    $i = $id1; $id1 = $id2; $id2 = $i;
                }
            $sql = "id >='$id1' AND id <= '$id2' ";
            }
            elseif (!empty($dl)){
                $sql = "id >='$id1' ";
            }
            else {$sql = " id = '$id1' ";}
        }

        elseif (!empty($dl)){
            if (!$id2>0){
                $sql = " id <= '$id2' ";
            }
            else {die ("id search not understood: $sol");}
        }
        return $sql;
    }



function use_vintage($year,$range){
	$year = (int)$year;
	$range = (int)$range;
	
    if ($range == '0'){
        $sql = "vintage = $year";
    }
    else {
        $min = $year-$range;
        $max = $year + $range;
        $sql = "vintage >= $min AND vintage <= $max";

        }

    return $sql;
}

function use_search($relative,$date){
    $rmap = array(
        '' => '(no term)',
        'On' => ' = ',
        'Before' => ' <= ',
        'After' => ' >= '
        );
    $sql = "first_use_date != '0000-00-00' AND first_use_date " .
        $rmap[$relative] .
        # "(From $relative)" .
        " '" .
        date('Y-m-d',strtotime($date)) .
        "' ";


    return $sql;
}

function token_search ($searchstring){
    $keyword_tokens = array_filter(explode(',',$searchstring));

    $keyword_tokens = array_map(
        function($keyword) {

            return addslashes(spchard(trim($keyword)));
        },
        $keyword_tokens
    );

   $concat = "CONCAT_WS(' ', title, caption, keywords,source) ";

#    $sql = "SELECT * FROM tbl_address WHERE address LIKE'%";
    $sql = '('
        . " $concat LIKE '%"
        . implode("%' OR $concat LIKE '%", $keyword_tokens) . "%'"
        . ')';

    return $sql;
}

function tag_search ($clist) {
     #turn list into an sql array
		
		
     $slist = [];
     foreach ($clist as $c){
     	if ($c == 'Z') { #all archival
     		$slist[] = "tags in (" . get_archival_tag_list() . ")";
     	}
     	else {
       	 $slist[] = "tags like '%$c%' ";
       	}
    }
    $sql = '(' . implode(' OR ',$slist) . ')';


    return $sql;
}



