<?php
namespace DigitalMx\Flames;

//BEGIN START
require_once 'init.php';

use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\Definitions as Defs;
use DigitalMx\Flames\Assets;
use DigitalMx\Flames\AssetAdmin;

#use DigitalMx\Flames\DocPage;

$page_title = 'Asset Viewer';
$page_options = [];


 $login->checkLogin(1);
$page = new DocPage($page_title);
$asseta = $container['asseta'];
$assets = $container['assets'];

echo $page -> startHead($page_options);

echo <<<EOT
   <script language="JavaScript">

function getDocHeight(doc) {
    doc = doc || document;
    // stackoverflow.com/questions/1145850/
    var body = doc.body, html = doc.documentElement;
    var height = Math.max( body.scrollHeight, body.offsetHeight,
        html.clientHeight, html.scrollHeight, html.offsetHeight );

   alert ("doc height: "+height);
    return height;
}

function setIframeHeight(id) {
    var ifrm = document.getElementById(id);
    var doc = ifrm.contentDocument? ifrm.contentDocument:
        ifrm.contentWindow.document;
    ifrm.style.visibility = 'hidden';
    ifrm.style.height = "10px"; // reset to minimal height ...
    // IE opt. for bing/msn needs a bit added or scrollbar appears
    ifrm.style.height = getDocHeight( doc ) + 4 + "px";
    ifrm.style.visibility = 'visible';
}


</script>

<style>
    iframe { width: 100%; height:1024px; max-width:960px; max-height:1024px; }
</style>

EOT;

echo $page ->startBody();


//END START


$item_id = $_GET['id'] ??  $_SERVER['QUERY_STRING'] ?? '0';
if (!$item_id || ! is_numeric($item_id) || !$item_id > 0) {
    die("Invalid asset item id: $item_id");
}

if (!$adata = $assets->getAssetDataEnhanced($item_id)) {
    die("Asset $id does not exist");
}

#u\echor ($adata, "Retrieve adata");



if (in_array($adata['status'], ['D','X']) != false) {
        die("Asset $item_id has been deleted or is not valid");
}

if (empty($url = $adata['asset_url'])) {
    die("No source url for asset $item_id");
}
$url_enc = urlencode($url);
#$url_enc = $url;
$adata['url_enc'] = $url_enc;
$adata['linkline'] = $linkline = "<a href='$url'>$url</a>";
$adata['urllinked'] = $urllinked = "<a href='$url' target='_blank'>$url</a>";



$adata['linked_caption'] = u\make_links(nl2br($adata['caption']));

$credit = '';
if (!empty($row['source'])) {
      $credit =  $adata['source'];
}
if ($adata['contributor'] != $adata['source']) {
    $credit .= " via " . $adata['contributor'] ;
}
$adata['credit'] = $credit;


$mime = $adata['mime'];
$type = $adata['type'];

// if ($substr($url,0,1) != '/' && strpos($url,'youtube') == false){
//  header("location:$url");
//
// }

// set asset display based on url or mime type
// if you tube, put an embed in a iframe
if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
    $m=[];
    if (preg_match('/embed\/(\w+.*)/i', $url, $m)) {
        $vid=$m[1];
    } elseif (preg_match('/watch\?.*v=([\w\-]+)/', $url, $m)) {
        $vid=$m[1];
    } elseif (preg_match('/youtu\.be\/([\w\-]+)/i', $url, $m)) {
        $vid=$m[1];
    } else {
        echo "video id not found on you tube link.  $url\n";
        exit;
    }
    $vidlink = "https://www.youtube.com/embed/$vid";
    $asset_display = "<iframe id='myframe' width=\"560\" height=\"315\" src=\"$vidlink\"  allowfullscreen></iframe>";
} elseif (substr($url, 0, 1) != '/') {
    $asset_display= "
   <p class='red'>Asset is on an external site.
   Please use the source link to access it.</p>
    ";
} elseif (! file_exists(SITE_PATH . $url)){
	$asset_display = "<p class='red'>The source for this asset is missing.</p>";

} elseif (strpos($mime, 'video') !== false) {
          $asset_display = "<video src = '$url' controls autoplay style='max-width:1024px;'>
          Your browser is not displaying this video.</video>";
} elseif (strpos($mime, 'audio')!== false) {
        $asset_display =  "<audio src = '$url' controls autoplay
        style='max-width:1024px;'>
        Your browser is not playing this audio.</audio>";
} elseif ($type == 'Multimedia') {
    $asset_display= <<<EOT
    <iframe src='$url' id='myframe'  >
     Content is displayed in an iframe, which your browser is not showing.

     </iframe>
EOT;
} elseif ($type == 'Document' || $type == 'Web Page') {
    $asset_display= <<<EOT
    <iframe src='$url' id='myframe'  >

     Content is displayed in an iframe, which your browser is not showing.  Try this:
     <a href="$url">$url</a>.
     </iframe>
EOT;
} elseif (strpos($mime, 'image') !== false) {
    $asset_display =  "<img src = '$url' style='width:100%;max-width:1024px;'>";
} elseif ($type == 'Image' || $type == 'Cartoon') {
     $asset_display =  "<img src = '$url' style='width:100%;max-width:960px;'>";
} else {
    $asset_display = "Uncertain how to display $url.  Please let the admin know.";
}

$adata['asset_display'] = $asset_display;

#echo "<hr style='border:10px solid red'>";
echo $container['templates']->render('asset_view', $adata);

//EOF
