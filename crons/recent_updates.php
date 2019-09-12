<?php
namespace digitalmx\flames;

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


/**
    * This script produces two reports:
    * recent_assets.html - archival assets added to assets
    * recent_articles.html - comments and votes on recent articles.
     (replaces old recent_assets and recent_articles)
     
    does two things: it retrieves
    * the title and link for the most recently published
    * articles, AND
    * it retrieves the comments
    * made on articles.
    *
    *
    *This script is run by cron or other triggers
    
*/

/*  needs: Defs, pdo

*/


if (! @defined ('INIT')) { include './cron-ini.php';}
if (! @defined ('INIT')) { throw new Exception ("Init did not load"); }

use digitalmx\flames\Definitions as Defs;
use digitalmx as u;
use digitalmx\flames as f;
use digitalmx\MyPDO;
use digialmx\flames\FileDefs;

/* MAIN */

$recent_article_file = FileDefs::live_dir . '/recent_articles.html';
$recent_asset_file =  FileDefs::live_dir . '/recent_assets.html';

#get latest pub date

$latest_sql = date('Y-m-d H:i',f\getLastPub () );
$from = date('Y-m-d',strtotime('-2 weeks'));

echo "From $from To $latest_sql\n"; 
#build asset report

if( $recent_asset_report = report_recent_assets ($from,0,30 ) ){
	if ($test){ echo ($recent_asset_report);}
	file_put_contents($recent_asset_file, $recent_asset_report );
} else {
	if(file_exists ("$recent_asset_file")){unlink ("$recent_asset_file");}
	echo "No asset report\n";
}


#build article report

if ($recent_article_report = report_recent_articles ($from,0,30,$test) ){
	if ($test){ echo ($recent_article_report);}
	file_put_contents($recent_article_file, $recent_article_report );
} else {
	if(file_exists ("$recent_article_file")){unlink ("$recent_article_file");}
	echo "No article report\n";
}



###################################

function report_recent_articles ( $from, $to, $max=0,$test) {
	$pdo = MyPDO::instance();
	
	// get article links into an array
	$link_counts = count_links($pdo);	
#	u\echor ($link_counts, 'link counts');

    $limit = ($max>0) ? " LIMIT $max" : '';
    $to_date = ($to)?   date('Y-m-d',strtotime($to)) :  date('Y-m-d');
    if ($test){
    	$tomorrow = new \DateTime('+2 days');
    	$to_date = $tomorrow->format('Y-m-d');
   }
   
   $from_date = date('Y-m-d',strtotime($from));


    $sql = "SELECT n.id,n.title,n.contributor,n.date_published,n.take_votes,n.source, 
       c.comments, v.net_votes
	    FROM news_items n
        LEFT JOIN  (
            SELECT item_id, on_db, count(*) as comments
            FROM `comments` 
            GROUP BY item_id , on_db
            ) c ON c.item_id = n.id AND c.on_db = 'news_items'
        LEFT JOIN (
            SELECT news_fk, sum(vote_rank) as net_votes 
            FROM `votes`
            GROUP BY news_fk
            ) v ON v.news_fk = n.id
        WHERE n.date_published >= '$from_date' AND n.date_published < '$to_date' - INTERVAL 1 DAY
        GROUP BY n.id,n.title,n.contributor,n.date_published,n.take_votes,n.source, c.comments, v.net_votes
        ORDER BY n.date_published DESC
	    $limit
	    ;
	    ";

if ($test) {echo "starting article report from $from_date to $to_date.\n" ; }

    if (!$pst = $pdo->query ($sql) ){return false;}
    $rowc = $pst -> rowCount();
     if ($rowc == 0) {return false;}
     
     $report = "<div style='margin-left:2em;float:left'>";
    $report .=  "<h4>Recent Article Activity</h4>";
   
   
     $report .=  "<table class='alternate article_list'>";
     $report .= "
        <tr><th>Article</th><th>Contributor</th><th>Published</th>
        <th>Comments</th><th>Link Clicks</th><th>Interesting?</th></tr>
        ";
      while ($row = $pst->fetch()) {
			$article_id = $row['id'];
			$link = "<a href='/scripts/news_article_c.php?id=$article_id' target='discussion'>" .
			$row['title'] .
			"</a>";
			$contributor = (strcmp($row['contributor'] ,'FLAMES editor') == 0)? '' : $row['contributor'];

			$ccount = $row['comments'];
			$votes = ($row['take_votes'])? $row['net_votes'] : '-';
			$dt = \DateTime::createfromformat('Y-m-d',$row['date_published']);
			$pub_date = $dt->format('M d');
			$clicks = $link_counts[$article_id] ?? '-';

			$report .=  <<<EOT
			<tr >
			<td>$link</td>
			<td>$contributor</td>
			 <td style='text-align:center'>$pub_date</td>
			<td style='text-align:center'>$ccount</td>
			<td style='text-align:center'>$clicks</td>
			<td style='text-align:center'>$votes</td>

			</tr>
EOT;
      }
      $report .=  "</table>\n";
    
     $report .= "<small>Updated ". date('d M H:i T') . "</small></div>\n\n";


    return $report;
}



//



function count_links($pdo) {
	#retrieves clicks on urls on articles
	$sql = "Select article_id, count from `links` 
		where last > now() - interval 4 week";
	$result = $pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	// result should be array of id, count
	return $result;
}

function count_comments_cron($id,$pdo){
     
    $sql = "SELECT count(*) from `comments` where on_db = 'news_items' and item_id = $id;";
    $nRows = $pdo->query($sql)->fetchColumn();
    return $nRows;
}



#####################


function report_recent_assets ($from,$to,$max=0) {
	$pdo = MyPDO::instance();

	 $limit = ($max > 0) ? " LIMIT $max" : '';
    $to_date = ($to) ? date('Y-m-d') : date('Y-M-d',strtotime($to));
   $from_date = date('Y-M-d',strtotime($from));
   $archival_tags = Defs::$archival_tags;
   
// any tag ('UI') has code ('U') contained in the set of archival_tags ('ABCUW');
// regexp [abc] matches if string contains any of those characters.
 $sql = "
	  SELECT id,title,type,status,vintage,tags,sizekb, date_entered from `assets`
	  WHERE date_entered > '$from_date'
			AND status in ('R','S')
			AND tags is NOT NULL
			AND tags REGEXP '[$archival_tags]'
	  ORDER BY date_entered DESC ;
	  ";


if (! $pst = $pdo -> query ($sql) ) {return false;} 
 $rowc = $pst -> rowCount();
 # echo  "$rowc articles found.\n";
 if ($rowc == 0) { return false;}
 
    if (!$asset_tags = Defs::$asset_tags) {
    	die ("Did not get asset_tags");
    }
   

    
	$report = "<div style='margin-left:2em;float:left'>";
	$report .=  "<h4>$rowc new Archival Assets</h4>";
	$report .= "<p style='font-size:0.9em;'>(This list shows archival assets only, not all assets.  Find any asset on the site by using Search &gt; Search Graphics/Video. 'Multimedia' means streaming audio/video. )</p>";
	$report .=  "<table class='alternate article_list'>";
	$report .= "
	  <tr style='background:#cfc;'><th>Title (click to view)</th><th>Tags</th><th>Type</th><th>Vintage</th><th>MB</th></tr>
        ";

    foreach ($pst as $row) {
        $id = $row['id'];
        $link = "<a href='/scripts/asset_display.php?$id' target='asset_view'>"
            . htmlspecialchars($row['title'],ENT_QUOTES)
            .  "</a>";

			$tagnames=[];
        if ($tags = $row['tags'] ){
        
			  foreach (str_split($tags) as $tag){
					$tagnames[] =  $asset_tags[$tag];
			  }
			  $tagtext = implode(", ",$tagnames);
			}
        $vintage = (empty($row['vintage']))?'?':$row['vintage'];
			$sizemb = round($row['sizekb'] / 1000,0);
		
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





