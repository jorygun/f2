<?php
namespace DigitalMx\Flames;

use DigitalMx\Flames\Definitions as Defs;
use DigitalMx as u;

/**
	callwith php recent.php [-t] [--repo <repo> ]
	uses repo for where to put the result file
	displays output if -t on

    * This script retrieves
    * the title and link for the most recently published
    * articles, and reports on number of comments and vote status.
    *
    * It then combines this data to produce a small html file in the
    * /news_lastest directory called 'recent_articles.html'.
    * This file is included in the newsletter index file.
    *
    *This script is run by cron ...



*/

/*  STARTUP */

$script = basename(__FILE__);
$repoloc = dirname(__FILE__,2);
require_once "$repoloc/public/init.php";
// $init set $pdo as well as container

if (! @defined ('INIT')) { throw new Exception ("Init did not load"); }

/**************   MAIN      ************/
 if (!$quiet)
echo "Starting $script " . BRNL;

$recent_articles = REPO_PATH . '/var/live/recent_articles.html';

$lastest_ts = trim(file_get_contents(REPO_PATH . "/var/data/last_published_ts.txt"));
if (empty ($latest_ts)){ $latest_ts = strtotime(' - 14 days'); }

$latest_pub_date = date('Y-m-d H:i',$lastest_ts);
 if (!$quiet)
echo "Using latest pub date: $latest_pub_date" . BRNL;


$recent_report = prepare_recent_report ($pdo, '',$latest_pub_date,'');
// pdo, from, to
// defaults to from 3 wks ago, to today, limit 30 articles

if ($test){ echo ($recent_report);}
else {file_put_contents($recent_articles, $recent_report );}

###################################

function prepare_recent_report ( $pdo, $from='', $to='', $max_articles = 30) {

    /*
    $max_articles = #maximum number of articles to show. 0 = no limit

    $from = staryting date in text form. defaults to ending date - 24 days
    $to = ending date in text form, defaults to last publication date,
    which it gets from news/last_pubication.txt
    */

	// get article links into an array
	$link_counts = count_links($pdo);

#	u\echor ($link_counts, 'link counts');


    $limit = ($max_articles>0)? " LIMIT $max_articles" : '';
    list ($to_date,$from_date) =  convert_dates($to,$from);

    $sql = "
       SELECT n.id,n.title,n.contributor,n.date_published,n.take_votes,n.source, c.comments, v.net_votes
	    FROM news_items n
        LEFT JOIN  (
            SELECT item_id, on_db, count(*) as comments
            FROM `comments`
            GROUP BY item_id
            ) c ON c.item_id = n.id AND c.on_db = 'news_items'
        LEFT JOIN (
            SELECT news_fk, sum(vote_rank) as net_votes
            FROM `votes`
            GROUP BY news_fk
            ) v ON v.news_fk = n.id
        WHERE
		n.date_published >= '$from_date' AND
		n.date_published < '$to_date' - INTERVAL 2 DAY
        GROUP BY n.id
        ORDER BY n.date_published DESC
	    $limit
	    ;
	    ";

   # echo $sql . BRNL;
	#echo "$from_date,$to_date" . BRNL;


    $pst = $pdo->query ($sql);

    $rowc = $pst -> rowCount();

    # echo  "$rowc articles found.\n";
     $report = "<div style='margin-left:2em;float:left'>";
    $report .=  "<h4>Recent Article Activity</h4>";
    if ($rowc == 0) {
        $report .=  "<p>Nothing found</p>";
    }
    else {
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
    }
     $report .= "<small>Updated ". date('d M H:i T') . "</small></div>\n\n";


    return $report;
}



//

function str_to_ts($str,$interval=0)
{
        // returns sql formatted date of date in string, offset by interval in days
    if (($timestamp = strtotime($str)) === false) {
          die("invalid string $str in str_to_ts");
    } else {
        $offset = $interval*60*60*24;
        return $timestamp-$offset;
    }
}


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

function convert_dates ($to,$from) {
    //takes date and coverts to sql dates using default values
    // if no to, it's today
    // if no from, it's 21 days before to-date

    if ($to != '') {
        $to_date = $to;
    } elseif ( $to_date = date('Y-m-d')) {
    } else {
         die ("No valid ending date");

    }
  # echo "To: $to -> $to_date\n";

    if (empty($from)) {
        $from_ts = str_to_ts($to_date, 21);
    } elseif ($from_ts = str_to_ts($from)) {


    } else {
        die ("No valid starting date");
    }

     $from_date = date('Y-m-d', $from_ts);

    return array($to_date,$from_date);
}
