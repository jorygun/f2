<?php
namespace DigitalMx\Flames;

class FileDefs {
// directories
	const  next_dir = REPO_PATH . "/public/news/next";
	const  latest_dir = REPO_PATH . "/public/news/latest";
	const  archive_dir = REPO_PATH . "/public/newsp";
	const  current_dir = REPO_PATH . '/public/news/current';
	const template_dir = REPO_PATH . '/templates';
	const shared_dir = PROJ_PATH . '/shared';
	const asset_dir = self::shared_dir .'/assets';
	const thumb_dir = self::shared_dir . '/thumbnails';

// model for newsletter index file
	const  news_template = REPO_PATH . "/templates/news_index.php";

// url of latest newsletter (/newsp/news_yymmdd);
	const  latest_pointer =  self::current_dir . "/pointer.txt";

// title of newsletter (travels with newsdir
	const  titlefile = self::next_dir . "/title.txt";

// teaser of newsletter headlines (create in next, travels
	const news_tease =  self::next_dir . "/tease_news.txt";

//#publish date; created in latest at publish time
// contains publish date human|date_code.  Stays with newsletter
	const  pubfile =  self::latest_dir . "/publish.txt";

// tease files are created in news/next and then retrieved by news/index
// as files are copied to news/latest and newsp/archive
	const status_report =  self::next_dir . "/status_report.html"; #member updates
	const status_tease =  self::next_dir . "/tease_status.txt"; #member updates
	const opp_tease =  self::next_dir . '/tease_opps.txt';


// breaking news
	const breaking_news = self::current_dir . '/breaking.html';

// file for index of all newsletters
	const news_index_inc = self::current_dir . "/index_inc.html";
	const news_index_json = self::current_dir . "/news_index.json";

// files for calendar
	const calendar_html =self::current_dir . '/calendar.html';
    const calendar_tease =  self::next_dir . '/tease_calendar.txt';

// timestamps
	const  rtime_file = self::current_dir . "/last_update_run.txt";
	const   ptime_file = self::current_dir . "/last_update_published.txt";
	const  last_pubdate =  self::current_dir . "/last_pubdate.txt";

// bulkmail
	const bulk_queue = REPO_PATH . "/var/queue"; #dir for queued jobs
	const bulk_jobs = REPO_PATH . "/var/bulk_jobs"; #dir for job files
	const bulk_processor = REPO_PATH . "/crons/bulk_send.php";

// special
	const git_ignore = REPO_PATH . "/templates/gitignore";

//
	const view_chart_url =   "/graphic_data/views.png";
}
