<?php

 ini_set('display_errors', 1);


/**
    * This script does two things: it retrieves
    * the title and link for the most recently published
    * articles, AND
    * it retrieves the comments
    * made on articles.
    *
    * It then combines this data to produce a small html file in the
    * /news directory called 'recent_articles.html'.
    * This file is included in the newsletter index file.
    *
    *This script is run by cron
    * to retrieve the latest comment info from Disqus.

    * 6 Jan 2017 added this to run automatically whenever
    * someone adds a comment. (in comment.class.php)
*/

$mydir = dirname(__FILE__);


require_once '/usr/home/digitalm/Sites/flames/live/config/boot.php';
require_once '/usr/home/digitalm/Sites/flames/live/public/scripts/asset_functions.php'; 

$pdo = MyPDO::instance();
$test = 0;

$recent_assets = SITEPATH . '/news/recent_assets.html';

$latest_dt = get_latest_pub_date('sqldt');
$latest_h = get_latest_pub_date('conventional');


$archive_tag_set = '';
foreach ( str_split($archival_tags) as $t){
    $archive_tag_set .= "'$t',";
}
$archive_tag_set = rtrim($archive_tag_set,',') ;

#$latest_pub = date('M d Y H:m T',strtotime($latest));
#get recents
    $sql = "
        SELECT id,title,type,status,vintage,tags,sizekb, date_entered from `assets`
        WHERE date_entered > CURRENT_DATE - INTERVAL 2 WEEK
            AND status in ('R','S')
            AND tags is NOT NULL
            AND (
            	substring(tags,1,1) in ($archive_tag_set)
            	OR
            	substring(tags,2,1) in ($archive_tag_set)
            	OR
            	substring(tags,3,1) in ($archive_tag_set)
            	OR
            	substring(tags,4,1) in ($archive_tag_set)
            	)
        ORDER BY vintage DESC ;
        ";

//WHERE date_entered > '$latest_dt' AND status in ('R','S') AND tags != ''; "

    #echo $sql;
    $pst = $pdo -> prepare ($sql);
    $pst-> execute ();

    $rowc = $pst -> rowCount();
	$cutoff = date('d M Y',strtotime('-2 weeks'));
    # echo  "$rowc articles found.\n";
    if ($rowc == 0) {
        if (file_exists($recent_assets)){unlink ($recent_assets);}
        echo "No recent assets to report";
        exit;
    }

$recent_report = report_recent_assets ($pst,$rowc,$cutoff);
#save note for weekly email
$asset_text = SITEPATH . '/news/news_next/assets.txt';
$recent_text = "\nUpdates to Archives\n----------------------------\n";
$recent_text .= "    $rowc new archival assets have been added since $cutoff.\n";

echo "<pre>$recent_text</pre>";

file_put_contents($asset_text,$recent_text);
if ($test){ echo ($recent_report);}
file_put_contents($recent_assets, $recent_report );
echo "Recent assets updated";

#####################


function report_recent_assets ($pst,$rowc,$cutoff) {
    global $asset_tags;
    if (empty($asset_tags)){die ("Did not get asset_tags");}
    /*

    */


    
	$report = "<div style='margin-left:2em;float:left'>";
    $report .=  "<h4>$rowc new Archival Assets</h4>";
    $report .= "<p style='font-size:0.9em;'>(This list shows archival assets only, not all assets.  Find any asset on the site by using Search &gt; Search Graphics/Video. 'Multimedia' means streaming audio/video. )</p>";
   $report .=  "<table class='alternate article_list'>";
   $report .= "
        <tr style='background:#cfc;'><th>Title (click to view)</th><th>Tags</th><th>Type</th><th>Vintage</th><th>MB</th></tr>
        ";

    while ($row = $pst->fetch()) {
        $id = $row['id'];
        $link = "<a href='/scripts/asset_c.php?id=$id' target='asset_view'>"
            . spchar($row['title'])
            .  "</a>";

        $tags = $row['tags']; $tagnames=[];
        foreach (str_split($tags) as $tag){
            $tagnames[] =  $asset_tags[$tag];
        }
        $tagtext = implode(", ",$tagnames);
        $vintage = (empty($row['vintage']))?'?':$row['vintage'];
		$sizemb = round($row['sizekb'] / 1000,0);
		
        $edit = "<a href='/scripts/asset_edit.php?id=$id' target = 'asset_edit'>Edit</a>";

        $report .=  <<<EOT
            <tr >
            <td>$link</td>
             <td>$tagtext </td><td>${row['type']}</td>
             <td style='text-align:center'>$vintage</td>
			<td style='text-align:center'>$sizemb</td>
            </tr>
EOT;
    }
    $report .=  "</table>\n";
     $report .= "<small>Updated ". date(DATE_RFC2822) . "</small></div>\n\n";


    return $report;
}



//





