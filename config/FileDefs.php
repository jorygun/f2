<?php
namespace DigitalMx\Flames;

class FileDefs {
// directories
	const  next_dir = REPO_PATH . "/public/news/next";
	const  latest_dir = REPO_PATH . "/public/news/latest";
	const  archive_dir = REPO_PATH . "/public/newsp";
	const  current_dir = REPO_PATH . '/public/news/current';
	const template_dir = REPO_PATH . '/templates';

	const asset_dir = REPO_PATH .'/public/assets';
	const thumb_dir = REPO_PATH . '/public/thumbnails';

// model for newsletter index file
	const  news_template = REPO_PATH . "/templates/index.php";

// old news index tests for the existence of this file.  Force true;
	const pubfile = REPO_PATH . "/public/index.php";


// teaser of newsletter headlines (create in next, travels



// tease files are created in news/next and then retrieved by bulk mail
// files are copied to news/latest and newsp/archive
	const status_report =  "/status_report.html"; #member updates
	const tease_status =  "/tease_status.txt"; #member updates
	const tease_opps =   '/tease_opps.txt';
	const tease_calendar = '/tease_calendar.txt';
	const tease_news =   "/tease_news.txt";
	const tease_news_ht = "/tease_news.html";

// breaking news
	const breaking_news = self::current_dir . '/breaking.html';



// files for calendar
	const calendar_html =self::current_dir . '/calendar.html';


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
	const view_chart_url =   "/data/views.png";
}
