<?php
namespace digitalmx\flames;

//BEGIN START
	require_once 'init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use f\Definitions as Defs;
	#use digitalmx\flames\DocPage;

	$page_title = 'Asset Display';
	$page_options = ['ajax'];
	
	
    $login->checkLogin(1); 
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);


	
echo <<<EOT
   <script language="JavaScript">

function autoResize(id){
    var newheight;
    var newwidth;

    if(document.getElementById && typeof document.getElementById(id).contentDocument === 'object' ){
    
        newheight = document.getElementById(id).contentDocument.body.scrollHeight;
        newwidth = document.getElementById(id).contentDocument.body.scrollWidth;
        document.getElementById(id).height = (newheight) + "px";
    	document.getElementById(id).width = (newwidth) + "px";
    }
    else {
    	document.write ("Content of this frame cannot be displayed");
    }
    
}

function isObject(obj) {
 	var type = typeof obj;
  	return  type === 'object' && !!obj;
  
}
</script>
EOT;

 	echo $page ->startBody();

	
//END START
	


$this_userid = $_SESSION['login']['user_id'] + 0; #force numeric.
$ucom = new Comment($this_userid);
if( isset ($_GET['id'])){$item_id = $_GET['id'];}
elseif ($item_id = $_SERVER['QUERY_STRING']){}
else {die ("No item requested");}

// if (isset($_SERVER['HTTP_REFERER'])){
//     $referer = $_SERVER['HTTP_REFERER'];
//     preg_match("{(.*/).*$}",$referer,$match);
//     $new_base = $match[1];
// }
   # echo "New Base: $new_base<br>";
#find referer to set base for page so relative refs works no matter what
#directory the original article was called from

/* set up commenting:
*   on_db is the name of the database of items that the comment is on (news,assets,spec)
*   on_id is the id of the specific item in the db
*   single=false = multiple comments/user ok; true=new comment replaces last comment
*   commenting_on enables the add comment form.
*/

$on_db = 'assets';
$on_id = $item_id;
$single = false;
$commenting_on = false;



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//Post data and close window
	#print_r ($_POST);


      $comment = trim($_POST['comment']);

    $contributor_email = trim($_POST['contributor_email']);
    $mailto = array('editor@amdflames.org','all',$contributor_email);

    $r = $ucom->addComment($on_db,$on_id,$comment,$single,$mailto);



}

/*
	Script to generate the an individual page for an
	asset with link for discussion.
*/

#echo "<pre>1. sections \n" , var_dump ($these_sections) , "</pre>";



$show_edit = $show_schedule = 0;
// $discussion_topics = array ('nostalgia'); #prevent discussion link from showing up.
$sql = "SELECT * from `assets` WHERE id = $item_id;";
    #echo $sql,"<br>";

if (! $stmt = $pdo->query($sql) ){
	echo "Asset $item_id not found.";
	exit;
}
$row = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (
         empty($row)
        || in_array($row['status'],['D','X']) != false
    ){
        echo "Asset $item_id has been deleted or is not valid";
        exit;
    }
     $htitle = addslashes($row['title']);
    $discussion = false;
    $contributor_id = $row['contributor_id'];
    $contributor_email = ($contributor_id)? get_user_data_by_id ($contributor_id)[1] : '';
   
   // display asset
    $hte = array ();
    foreach (array('title','caption','source') as $param)
    {
        $hte[$param] = hte($row[$param]);

    }
    $caption = make_links(nl2br($row['caption']));

	$first_date = ($row['first_use_date'] == '0000-00-00')?'Undated' : $row['first_use_date'];
	$first_in = ($row['first_use_in'])? "<a href='${row['first_use_in']}' target='_blank'>${row['first_use_in']}</a>" : "Not Used";
    $url = $row['url']; #source file
    if (!empty($row['link'])){
    	$url = $row['link'];
    	#$url_enc = str_replace('#','%23',$url);
    	$url_enc = myUrlEncode($url);
    	
    	$linkline = "<a href='$url_enc'>$url</a>";
		$urllinked = "<a href='$url_enc' target='_blank'>$url</a>";
    }
    

    $type = $row['type'];
    $mimetype = $row['mime'];

    if (substr($url,0,1) == '/'){$url = 'https://amdflames.org' . $url;}


    if (strpos($url,'youtube.com') !== false || strpos($url,'youtu.be') !== false){
        $m=[];
        if(preg_match('/embed\/(\w+.*)/i',$url,$m)){$vid=$m[1];}
        elseif (preg_match('/watch\?.*v=([\w\-]+)/',$url,$m)){$vid=$m[1];}
        elseif (preg_match('/youtu\.be\/([\w\-]+)/i',$url,$m)){$vid=$m[1];}
       else {echo "video id not found on you tube link.  $url\n";exit;}

        $vidlink = "https://www.youtube.com/embed/$vid";
        $asset_display = "<iframe width=\"560\" height=\"315\" src=\"$vidlink\"  allowfullscreen></iframe>";

    }


     elseif (strpos($mimetype,'video') !== false ){
     $asset_display =  "<video src = '$url_enc' controls autoplay style='max-width:1024px;'>Your browser is not displaying this video.</video>";
     }
     elseif (strpos($mimetype,'audio')!== false ){
     $asset_display =  "<audio src = '$url' controls autoplay style='max-width:1024px;'>Your browser is not playing this audio.</audio>";
     }

     elseif ($type == 'Multimedia' ){
     #$asset_display =  "<video src = '$url' controls autoplay style='max-width:1024px;'>Your browser is not displaying this video.</video>";
    $asset_display= <<<EOT
    <iframe src='$url_enc' id='iframe1'   onLoad='autoResize(this);'>
     Content is displayed in an iframe, which your browser is not showing.  Try this:
     <a href='$url'>${row['url']}</a>.
     </iframe>
EOT;
     }

 elseif ($type == 'Web Page' || $type == 'Document' ){

    $asset_display= <<<EOT
    <iframe src='$url_enc' id='iframe1' 
    onload='javascript:(function(o){o.style.height=o.contentWindow.document.body.scrollHeight+"px";}(this));' style="height:800px;width:100%;border:none;">
    
     Content is displayed in an iframe, which your browser is not showing.  Try this:
     <a href="$url">$url</a>.
     </iframe>
EOT;
     }

elseif (strpos($mimetype,'image') !== false){
        $asset_display =  "<img src = '$url' style='max-width:1024px;'>";
    }
    elseif ( $type == 'Image' || $type == 'Cartoon'){
     $asset_display =  "<img src = '$url' style='max-width:960px;'>";
     }



     else {$asset_display= <<<EOT
    <iframe src='$url_enc' id='iframe1'  >
     Content is displayed in an iframe, which your browser is not showing.  Try this:
     <a href="$url">$url</a>.
     </iframe>
EOT;
# remove  onLoad='autoResize(this)';

    }

    $credit = '';
    if (!empty($row['source'])){
        $credit =  hte($row['source']);
    }
    if ( $row['contributor'] != $row['source']
        ){
            $credit .= " via " . $row['contributor'] ;
    }

##############################
function myUrlEncode($string) {

#forbidden or deprecated characters in url
 $characters = array('<' , '>' , '#' ,' ' , '"', '{' , '}' , ',' , '\\' , '^' , '[' , ']' , '`' );
   $entities = array_map(function ($c){return rawurlencode($c);},$characters);

	#echo "Entities:\n" . print_r($entities,true);

    return str_replace($characters, $entities, $string);
}

?>


        <h3><?=$hte['title']?></h3>
<p>(Note: display size on this page is limited to 1024px wide. Use URL below to retrieve raw file.)<br>
	
       Link to source:  <?=$urllinked?><br>
   
        (Note: some source files cannot be displayed in the iframe below.  Use source link above to view.)
        </p>

    <?=$asset_display?>
    <p class='caption'><?=$hte['caption']?></p>
    <hr>
        <table>
        <tr><td>
        Asset id: <?=$item_id?><br>
        Type: <?=$mimetype?> -> <?=$type?> <br>
        Entered on <?=$row['date_entered']?>  <br>
        Source: <?=$credit?>  <br>
        First use: <?=$first_in ?>(<?=$first_date?>)<br>
        Size: <?=$row['sizekb']?> kB; <?=$row['height']?> h x <?=$row['width']?> w<br>
			Raw url: <?=$url?><br> 
			encoded url:<?=$url_enc?><br>
        </td></tr></table>



</body>
</html>
