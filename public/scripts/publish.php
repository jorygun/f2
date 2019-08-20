<?php
namespace digitalmx\flames;

//BEGIN START
#ini_set('display_errors', 1);
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	
//END START
	use digitalmx\MyPDO;
	use digitalmx as u;

   

if ($login->checkLogin(4)){
   $page_title = 'Publish News';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}

	$now = date ('M j, Y H:i');


// script to get current pub date and publish news_next under that date



//
	$pubdate = new \DateTime();

	$condensed_date = $pubdate -> format('ymd');
	$conventional_date = $pubdate -> format('j M Y');
	echo "$conventional_date translates to $condensed_date<br>";

#get title from news_next
	$titlefile = SITE_PATH . '/news/news_next/title.txt';
    if (file_exists($titlefile)){
        $current_title = file_get_contents($titlefile);
	    $title = htmlspecialchars($current_title);
	}
	else {$title = $conventional_date;}


	// set all the directories and files
	$newspath = SITE_PATH . "/news"; #development
	$datapath = REPO_PATH . "/var/data";
	
		$nextnews_dir = "$newspath/news_next";

		
		$rtime_file = "$datapath/last_update_run_ts.txt";
 		$ptime_file = "$datapath/last_update_published_ts.txt";
 		$latest_pointer = "$datapath/latest_pointer.txt";
		$last_published_ts =  "$datapath/last_published_ts.txt";
		
 		$new_location_surrogate = "$newspath/index.php";
 		


	$latest_dir = "$newspath/news_latest";
	$working_file = "$latest_dir/index.php";
	$publish_file = "$latest_dir/publish.txt";

    $newnews_dir = "news_${condensed_date}";
	
		$newnews_path = SITE_PATH . "/newsp/$newnews_dir";
			
		$news_index = "$newspath/index.php";


	$new_location = "/newsp/$newnews_dir"; #url to latest news folder


	// files to manage date of search for updates.
	// update search looks for >$ptime file and sets $rtime file.
	// when publish, copy rtime to ptime.

   
   

   //  echo "Updating recent asset list" . BRNL;
//         include "recent_assets.php";
//      #copy recent assets from news to news_next so it gets retained

    echo BRNL;

	// copy the news_next to the news_latest directory
	echo "Copying news_next to news_latest<br>";
	u\deleteDir($latest_dir);
	u\full_copy($nextnews_dir,$latest_dir);
	

	echo "Adding publish parameters to news_latest directory<br>";

	$working_contents = file_get_contents("$working_file");

    $publish_data = <<<EOT

//PUBLISH DATA INSERTED
    \$mode = 'published';
    \$condensed_date = '$condensed_date';
    \$conventional_date = '$conventional_date';
    
    \$publish_time = '$now';
    \$title = '$title';
    \$page_title='FLAME NEWS';
    \$footer_line = 'Published at $now';

EOT;

$publish_wrapped = "<?php\n" . $publish_data . "?>\n";
   ##put publish_data into a file for inclusion.
    file_put_contents($publish_file,$publish_wrapped);


file_put_contents($last_published_ts,time());



		echo "Copying latest to $new_location<br>";
		u\full_copy($latest_dir,$newnews_path);

	    #write new location in news/latest_pointer

		echo "Writing new location index in $latest_pointer:  $new_location<br>";

		$success = file_put_contents ($latest_pointer, "$new_location");
			if (!$success){echo "<b>put contents to latest pointer failed</b>.<br>";}


		echo "Copying update time file to published update time<br>";
		$success = copy ($rtime_file,$ptime_file); #fix the date of last run member updates
		if (! $success){echo "<h3>Failed to copy rtime to ptime!</h3>";}
		// now make a new news_next directory from the model
		//echo "Copying model news to next news<br>";
		
		copy($newspath . "/model-index.php" , $nextnews_dir . "/index.php");
		
       

        echo "Marking News Items as published<br>";
        $sql_today = sql_today();
        $sql = "
            UPDATE news_items
            SET status = 'P',
            date_published = '$sql_today',
            use_me = 0
            WHERE use_me > 0;
            ";
         if (REPO == 'live'){
        		$result = $pdo->query($sql);
        	
        
       		 if (! $result){echo "Failed to set pub date on news items.<br>";}

        
			  require_once "newsletter_index.class.php";
			  if ( $nli = new NewsletterIndex(true) ){
				echo "Updating Newsletter Index" . BRNL;
				#echo "$nli" . BRNL;
			  }
			  else{
				echo "NewsletterIndex failed";
			  }
					#true forces rebuild

			  echo "Adding $condensed_date to reads database<br>";
			  $sql = "INSERT INTO `read_table` SET issue = '$condensed_date',read_cnt=0;";
			  $result = $pdo->query($sql);
				if (! $result){echo "Add to reads database failed.<br>";}

				#set count for preview issue to 0
				$sql = "UPDATE read_table SET read_cnt=0 WHERE issue = 999999;";
				$result = $pdo->query($sql);

			  #now rebuild the files
			  include './news_files2.php';
			} else {
				echo "News items not updated on repo " . REPO . BRNL;
			}
        	
		 /* Now build the recent article and assets file */
    echo "Updating recent article titles" . BRNL ;
        require REPO_PATH . '/crons/recent_articles.php';
        
	 echo "Updating recent assets" . BRNL ;
        require REPO_PATH . '/crons/recent_assets.php';
        
    echo "Done.  <button type='button' onClick='window.close()'>Close Window</button>";

	exit;






