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

	private $show = false;


	public function __construct($container) {
		$this->pdo = $container['pdo'];
		$this->article = $container['article'];
		$this->templates = $container['templates'];
		$this->report_dir = REPO_PATH . '/public/news/current';

	}
	public function setShow() {
		$this->show = true;
	}
 public function run(){


		$report =$this->report_recent_assets();
		file_put_contents ($repdir . '/recent_assets.html',$report);

}

 public function report_recent_articles ( $from=14, $limit = 30) {

    /*
    $max_articles = #maximum number of articles to show. 0 = no limit

    $from = days ago

    */
	// set starting date to look from
	$from_dt = new \DateTime("- $from day");
	$from_date = $from_dt->format('Y-m-d');
	$to_date = u\sqlnow();


	$sql = "SELECT n.id,n.title,n.contributor,m.username,
    	DATE_FORMAT('%y %m %d',n.date_published) as pubdate,
    	n.take_votes,n.source,
        count(c.id) as comment_count,
        sum(v.vote_rank) as net_votes,
       l.count as clicks,
       if (n.take_votes,v.vote_rank,'-') as votes

	    FROM articles n

       LEFT JOIN members_f2 m on m.user_id = n.contributor_id
		LEFT JOIN comments c on n.id = c.item_id and c.on_db = 'news_items'
        LEFT JOIN votes v on v.news_fk = n.id
         LEFT JOIN links l on l.article_id = n.id

        WHERE n.date_published >= '$from_date' AND n.date_published < '$to_date'
        GROUP BY n.id,n.title,n.contributor,n.date_published,n.take_votes,n.source, comment_count, net_votes,clicks,votes
        ORDER BY n.date_published DESC
	    LIMIT $limit
	    ;
	    ";

    $rlist = $this->pdo->query($sql)->fetchAll();


//u\echor($rlist);
	$data['articles'] = $rlist;
	$data['run_date'] = date('d M H:i');
	$report = $this->templates->render('recent_articles',$data);
 	file_put_contents ($this->report_dir . '/recent_articles.html',$report);
 	return $report;
}


function report_recent_assets ($from=21,$limit=30) {

 /*
    $from = days ago
    */
	// set starting date to look from
	$from_dt = new \DateTime("- $from day");
	$from_date = $from_dt->format('Y-m-d');
	$to_date = u\sqlnow();

 $archival_tags = Defs::$archival_tags;

$sql = "
	  SELECT a.id,a.title,a.type,a.astatus,a.vintage,a.tags,a.sizekb,
	  a.date_entered, m.username as contributor
	  FROM `assets2` a
	  LEFT JOIN `members_f2` m on a.contributor_id = m.user_id
	  WHERE  a.astatus  in ('W','O','K')
			AND a.tags is NOT NULL
			AND a. tags REGEXP '[$archival_tags]'
			AND date_modified > '$from_date'
	  ORDER BY date_entered DESC
	  LIMIT $limit;
	  ";
//date_entered > '$from_date'


   $rlist = $this->pdo->query($sql)->fetchAll() ;
//     u\echor($rlist); exit;

		// enhance data
	  foreach ($rlist as &$r) {
		$id = $r['id'];
			  $r['link'] = "<a href='/asset_view.php?id=$id' target='viewer'>"
					. u\special($r['title'])
					.  "</a>";

			  $tags = $r['tags']; $tagnames=[];
			  foreach (str_split($tags) as $tag){
					$tagnames[] =  Defs::$asset_tags[$tag];
			  }
			  $r['tagtext'] = implode(", ",$tagnames);
			  $r['vintage'] = (empty($r['vintage']))?'?':$r['vintage'];
			$r['sizemb'] = round($r['sizekb'] / 1000,0);

			  $r['edit'] = "<a href='/asset_editor.php?id=$id' target = 'asset_edit'>Edit</a>";

	  }
	// u\echor($rlist);  exit;

		$data['assets'] = $rlist;
		$data['run_date'] = date('d M H:i');
		$data['assets_from'] = $from_dt->format('M d, Y');
		$report = $this->templates->render('recent_assets',$data);
		file_put_contents ($this->report_dir . '/recent_assets.html',$report);

 	return $report;
 }


/*
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

*/
}

//EOF


