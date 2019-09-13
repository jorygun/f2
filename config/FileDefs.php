<?php
namespace digitalmx\flames;

class FileDefs {
// contains preview of next newsletter
	const  next_dir = REPO_PATH . "/public/news/next";
// contains master coppy of current newsletter
	const  latest_dir = REPO_PATH . "/public/news/latest";
// contains archive of all newsletters inclduoing current
	const  archive_dir = REPO_PATH . "/public/newsp";
// contains only current pointer and index file that redirects to it
	const  current_dir = REPO_PATH . '/public/news/current';
// contains things updated during life of newsletter: calendr, alert, recents
	const live_dir = REPO_PATH . '/public/news/live';
	
// model for newsletter main file
	const  news_template = REPO_PATH . "/templates/news_index.php";
	
// text file contains url of latest newsletter (/newsp/news_yymmdd);
	const  latest_pointer = REPO_PATH . "/public/news/current/pointer.txt"; 
	
// text file containing title of newsletter
	const  titlefile = REPO_PATH . "/public/news/next/title.txt";
	
// text file containing teaser of newsletter headlines
	const news_tease =  REPO_PATH . "/public/news/next/tease_news.txt";

//#publish date; created in latest at publish time
// contains publish date human|date_code
	const  pubfile = REPO_PATH . "/public/news/latest/publish.txt"; 
	
// tease files are created in news/next and then retrieved by news/index 
// as files are copied to news/latest and newsp/archive
	const status_report = REPO_PATH . "/public/news/next/status_report.html"; #member updates
	const status_tease = REPO_PATH . "/public/news/next/tease_status.txt"; #member updates

// file for index of all newsletters
	const news_index_inc = REPO_PATH . "/var/data/index_inc.html";
// json file for index
	const news_index_json = REPO_PATH . "/var/data/news_index.json";
	
// files for calendar
	const calendar_html = REPO_PATH . '/public/news/live/calendar.html';
    const calendar_tease = REPO_PATH . '/public/news/next/tease_calendar.txt';

// opportunities
	const opp_tease = REPO_PATH . '/public/news/next/tease_opps.txt';
	
// timestamps
	const  rtime_file = REPO_PATH . "/var/data/last_update_run.txt";
	const   ptime_file = REPO_PATH . "/var/data/last_update_published.txt";
	const  last_pubdate =  REPO_PATH . "/var/data/last_pubdate.txt"; 
 	
 	
 		
// bulkmail
	const bulk_queue = REPO_PATH . "/var/queue"; #dir for queued jobs
	const bulk_jobs = REPO_PATH . "/var/bulk_jobs"; #dir for job files
	const bulk_processor = REPO_PATH . "/crons/bulk_send.php";


}
