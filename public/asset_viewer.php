<?php
namespace digitalmx\flames;

//BEGIN START
	require_once 'init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\Assets;
	#use digitalmx\flames\DocPage;
	

	$page_title = 'Asset Viewer';
	$page_options = [];
	
	
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

</script>

<style>
	iframe { width:960px; }
</style>

EOT;

 	echo $page ->startBody();

	
//END START
$assets = new Assets();

$item_id = $_GET['id'] ??  $_SERVER['QUERY_STRING'] ?? '0';
if (!$item_id || ! is_numeric($item_id) || !$item_id > 0){
	die ("Invalid asset item id: $item_id");
}

if (!$adata = $assets->getAssetDataById($item_id) ){
	die ("No asset at id $item_id");
}

#u\echor ($adata, "Retrieve adata");



if ( in_array($adata['status'],['D','X']) != false){
        die ( "Asset $item_id has been deleted or is not valid");
}

if (empty($url = $adata['asset_url'])){
	die ("No source url for asset $item_id");
 }
#$url_enc = urlencode ($url);
$url_enc = $url;
$adata['url_enc'] = $url_enc;  	
$adata['linkline'] = $linkline = "<a href='$url_enc'>$url</a>";
$adata['urllinked'] = $urllinked = "<a href='$url_enc' target='_blank'>$url</a>";
   

     
$adata['linked_caption'] = u\make_links(nl2br($adata['caption']));

$credit = '';
 if (!empty($row['source'])){
	  $credit =  $adata['source'];
 }
 if ( $adata['contributor'] != $adata['source'] ){
			$credit .= " via " . $adata['contributor'] ;
 }
$adata['credit'] = $credit;


$mimetype = $adata['mime'];
   $type = $adata['type'];



// set asset display based on url or mime type
// if you tube, put an embed in a iframe
 if (strpos($url,'youtube.com') !== false || strpos($url,'youtu.be') !== false){
	  $m=[];
	  if(preg_match('/embed\/(\w+.*)/i',$url,$m)){$vid=$m[1];}
	  elseif (preg_match('/watch\?.*v=([\w\-]+)/',$url,$m)){$vid=$m[1];}
	  elseif (preg_match('/youtu\.be\/([\w\-]+)/i',$url,$m)){$vid=$m[1];}
	 else {echo "video id not found on you tube link.  $url\n";exit;}

	  $vidlink = "https://www.youtube.com/embed/$vid";
	  $asset_display = "<iframe width=\"560\" height=\"315\" src=\"$vidlink\"  allowfullscreen></iframe>";

 } elseif (strpos($mimetype,'video') !== false ){
		  $asset_display =  "<video src = '$url_enc' controls autoplay style='max-width:1024px;'>Your browser is not displaying this video.</video>";
} elseif (strpos($mimetype,'audio')!== false ){
     	$asset_display =  "<audio src = '$url' controls autoplay style='max-width:1024px;'>Your browser is not playing this audio.</audio>";
} elseif ($type == 'Multimedia' ){
     #$asset_display =  "<video src = '$url' controls autoplay style='max-width:1024px;'>Your browser is not displaying this video.</video>";
    $asset_display= <<<EOT
    <iframe src='$url_enc' id='iframe1'  onLoad='autoResize(this);'>
     Content is displayed in an iframe, which your browser is not showing.  Try this:
     <a href='$url'>${row['url']}</a>.
     </iframe>
EOT;
} elseif ( $type == 'Document' ){
    $asset_display= <<<EOT
    <iframe src='$url_enc' id='iframe1'   onLoad='autoResize(this);'>
    
     Content is displayed in an iframe, which your browser is not showing.  Try this:
     <a href="$url">$url</a>.
     </iframe>
EOT;
} elseif (strpos($mimetype,'image') !== false){
        $asset_display =  "<img src = '$url' style='max-width:1024px;'>";
} elseif ( $type == 'Image' || $type == 'Cartoon'){
     $asset_display =  "<img src = '$url' style='max-width:960px;'>";
     
} elseif (substr($url,0,1) != '/') {$asset_display= "
   <p class='red'>Asset is on an external site.  Please use the source link to access it.</p>
";
} else {
	$asset_display = "Uncertain how to display $url.  Please let the admin know.";
}
$adata['asset_display'] = $asset_display;
  
#echo "<hr style='border:10px solid red'>";
echo $templates->render('asset_view',$adata);


##############################
// function myUrlEncode($string) {
// 
// #forbidden or deprecated characters in url
//  $characters = array('<' , '>' , '#' ,' ' , '"', '{' , '}' , ',' , '\\' , '^' , '[' , ']' , '`' );
//    $entities = array_map(function ($c){return rawurlencode($c);},$characters);
// 
// 	#echo "Entities:\n" . print_r($entities,true);
// 
//     return str_replace($characters, $entities, $string);
// }



