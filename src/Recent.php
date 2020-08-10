<?php
namespace DigitalMx\Flames;

use DigitalMx\Flames\Definitions as Defs;
use DigitalMx as u;

/**


    * This script produces two reports in news/current:
    *  one is for activity on recent articles, and the other is
    *  listing recent archival assets


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
		$this->news = $container['news'];

		$this->run();

	}
	public function setShow() {
		$this->show = true;
	}


 public function run(){


		$report =$this->report_recent_assets();
		file_put_contents ($this->report_dir. '/recent_assets.html',$report);

		$report =$this->report_recent_articles();
		if($this->show) {echo $report;}
		file_put_contents ($this->report_dir. '/recent_articles.html',$report);

}

 public function report_recent_articles ( $from=40) {

    /*
    $from = days ago
    */

	$rlist = $this->news->getRecentArticles($from);


//  u\echor($rlist, $sql);
	$data['articles'] = $rlist;
	$data['run_date'] = date('d M H:i');
	$report = $this->templates->render('recent_articles',$data);
 	file_put_contents ($this->report_dir . '/recent_articles.html',$report);
 	return $report;
}


function report_recent_assets ($from=21) {

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
			AND date_entered > '$from_date'
	  ORDER BY date_entered DESC
	  LIMIT 50;
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



}

//EOF


