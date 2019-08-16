<?php
namespace digitalmx\flames;



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

/*  STARTUP */
$script = basename(__FILE__);

if (! @defined ('INIT')) { include './cron-ini.php';}
if (! @defined ('INIT')) { throw new Exception ("Init did not load"); }

use \digitalmx\flames\Definitions as Defs;

/* MAIN */
 if (!$quiet) 
echo "Starting $script " . BRNL;


$recent_asset_file = REPO_PATH . "/public/news/news_live/recent_assets.html";

$archival_tags = Defs::$archival_tags;

$archive_tag_set = '';
foreach ( str_split($archival_tags) as $t){
    $archive_tag_set .= "'$t',";
}
$archive_tag_set = rtrim($archive_tag_set,',') ;

#$latest_pub = date('M d Y H:m T',strtotime($latest));
#get recents
// any tag ('UI') has code ('U') contained in the set or archival_tags ('ABCUW');
    $sql = "
        SELECT id,title,type,status,vintage,tags,sizekb, date_entered from `assets`
        WHERE date_entered > CURRENT_DATE - INTERVAL 2 WEEK
            AND status in ('R','S')
            AND tags is NOT NULL
            AND tags REGEXP '[$archival_tags]'
        ORDER BY date_entered DESC ;
        ";

//WHERE date_entered > '$latest_dt' AND status in ('R','S') AND tags != ''; "

    #echo $sql;
    $pst = $pdo -> query ($sql);

    $rowc = $pst -> rowCount();
	$cutoff = date('d M Y',strtotime('-2 weeks'));
    # echo  "$rowc articles found.\n";
    if ($rowc == 0) {
        if (file_exists($recent_asset_file)){unlink ($recent_asset_file);}
        if (!$quiet) echo "No recent assets to report";
        exit;
    }

$recent_report = report_recent_assets ($pst,$rowc,$cutoff);

if ($test){ echo ($recent_report);}
file_put_contents($recent_asset_file, $recent_report );
if (! $quiet)
echo "$recent_asset_file updated" . BRNL;

#####################


function report_recent_assets ($pst,$rowc,$cutoff) {
    $asset_tags = Defs::$asset_tags;
    
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

    foreach ($pst as $row) {
        $id = $row['id'];
        $link = "<a href='/scripts/asset_c.php?id=$id' target='asset_view'>"
            . htmlspecialchars($row['title'],ENT_QUOTES)
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
     $report .= "<small>Updated ". date('d M H:i T') . "</small></div>\n\n";


    return $report;
}



//





