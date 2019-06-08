<?php

// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

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


require_once '/usr/home/digitalm/Sites/flames/dev/config/boot.php';

$test = 0;
$recent_articles = SITEPATH . '/news/recent_articles.html';

$latest_pub_date = get_latest_pub_date('sql');

$recent_report = prepare_recent_report ('','','');
#defaults to from 3 wks ago, to today, limit 30 articles

if ($test){ echo ($recent_report);}
else {file_put_contents($recent_articles, $recent_report );}

###################################

function prepare_recent_report ( $from='', $to='', $max_articles = 30) {

    /*
    $max_articles = #maximum number of articles to show. 0 = no limit

    $from = staryting date in text form. defaults to ending date - 24 days
    $to = ending date in text form, defaults to last publication date,
    which it gets from news/last_pubication.txt
    */


    $pdo = MyPDO::instance();

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
		n.date_published >= ? AND
		n.date_published < ?
        GROUP BY n.id
        ORDER BY n.date_published DESC
	    $limit
	    ;
	    ";

   # echo $sql . BRNL;
	#echo "$from_date,$to_date" . BRNL;
	
	
    $pst = $pdo->prepare ($sql);
    $pst-> execute ([$from_date,$to_date]);

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
        <th>Comments</th><th>Interesting?</th></tr>
        ";
      while ($row = $pst->fetch()) {
        $link = "<a href='/scripts/news_article_c.php?id=${row['id']}' target='discussion'>" .
        $row['title'] .
        "</a>";
		$contributor = (strcmp($row['contributor'] ,'FLAMES editor') == 0)? '' : $row['contributor'];
		
         $ccount = $row['comments'];
        $votes = ($row['take_votes'])? $row['net_votes'] : '-';
        $dt = DateTime::createfromformat('Y-m-d',$row['date_published']);
		$pub_date = $dt->format('M d');


            $report .=  <<<EOT
            <tr >
            <td>$link</td>
            <td>$contributor</td>
             <td style='text-align:center'>$pub_date</td>
            <td style='text-align:center'>$ccount</td>
            <td style='text-align:center'>$votes</td>

            </tr>
EOT;
      }
      $report .=  "</table>\n";
    }
     $report .= "<small>Updated ". date(DATE_RFC2822) . "</small></div>\n\n";


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




function count_comments_cron($id){
       $pdo = MyPDO::instance();
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
