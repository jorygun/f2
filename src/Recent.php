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

if (! @defined ('INIT')) { throw new Exception ("Init did not load"); }

class Recent
{

	public function __construct($container) {
		$this->pdo = $container['pdo'];
		$this->article = $container['article'];

	}


 public function prepareRecent ( $from=14, $limit = 30) {

    /*
    $max_articles = #maximum number of articles to show. 0 = no limit

    $from = days ago

    */
	// set starting date to look from
	$from_dt = new \DateTime("- $from day");
	$from_date = $from_dt->format('Y-m-d');


    $article_list = $this->article->getArticleList($from_date);

	u\echor($article_list);


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

private function count_comments_cron($id,$pdo){

    $sql = "SELECT count(*) from `comments` where on_db = 'news_items' and item_id = $id;";
    $nRows = $pdo->query($sql)->fetchColumn();
    return $nRows;
}

}
