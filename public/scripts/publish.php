<?php

//BEGIN START
#ini_set('display_errors', 1);
	require_once "init.php";
	if (f2_security_below(4)){exit;}
//END START


	$today = $DT_now->format('M j, Y');
	$now = $DT_now -> format ('M j, Y H:i');
	$sql_now = $DT_now -> format ('Y-m-d');

// script to get current pub date and publish news_next under that date



$Nav = new navBar(1);


echo <<<EOT
<html><head>
<title>Publish News</title>
<link rel='stylesheet' href='/css/flames2.css'>
</head>
<body>

EOT;


//
	$pubdate = new DateTime();

	$condensed_date = $pubdate -> format('ymd');
	$conventional_date = $pubdate -> format('M j, Y');
	echo "$conventional_date translates to $condensed_date<br>";

	$titlefile = SITEPATH . '/news/news_next/title.txt';
    if (file_exists($titlefile)){
        $current_title = file_get_contents($titlefile);
	    $title = htmlspecialchars($current_title);
	}
	else {$title = $conventional_date;}


	// set all the directories and files
	$newspath = SITEPATH . "/news"; #development
		$nextnews_dir = "$newspath/news_next";

		$modelnews_dir = "$newspath/news_model";
		$rtime_file = "$newspath/last_update_run.txt";
 		$ptime_file = "$newspath/last_update_published.txt";
 		$new_location_surrogate = "$newspath/index.php";
 		$latest_pointer = "$newspath/latest_pointer.txt";
 		$last_published_time = "$newspath/last_published_time.txt";
		$last_published_ts = REPO_PATH . "/data/last_published_ts.txt";


	$latest_dir = "$newspath/news_latest";
	$working_file = "$latest_dir/index.php";
	$publish_file = "$latest_dir/publish.txt";

    $newnewsfile = "news_${condensed_date}";
	$newsppath = SITEPATH . "/newsp"; #archive folder
		$newnews_dir = "$newsppath/$newnewsfile";
			$newsletter = "$newnews_dir/index.php";
		$news_index = "$newspath/index.php";


	$new_location = "/newsp/news_$condensed_date"; #url to latest news folder


	// files to manage date of search for updates.
	// update search looks for >$ptime file and sets $rtime file.
	// when publish, copy rtime to ptime.

   
    /* Now build the recent article and assets file */
    echo "Updating recent article titles" . BRNL ;
        require 'recent.php';

   //  echo "Updating recent asset list" . BRNL;
//         include "recent_assets.php";
//      #copy recent assets from news to news_next so it gets retained

    echo BRNL;

	// copy the news_next to the news_latest directory
	echo "Copying news_next to news_latest: <br>from  $nextnews_dir<br> to $latest_dir<br>";
	deleteDir($latest_dir);
	full_copy($nextnews_dir,$latest_dir);
	

	echo "Adding publish parameters to news_latest directory<br>";

	$working_contents = file_get_contents("$working_file");

    $publish_data = <<<EOT

//PUBLISH DATA INSERTED
    \$mode = 'published';
    \$condensed_date = '$condensed_date';
    \$conventional_date = '$conventional_date';
    # \$base_line = "<base href  = '$new_base'>";
    \$publish_time = '$now';
    \$title = '$title';
    \$page_title='FLAME NEWS';
    \$footer_line = 'Published at $now';

EOT;

$publish_wrapped = "<?php\n" . $publish_data . "?>\n";
   ##put publish_data into a file for inclusion.
    file_put_contents($publish_file,$publish_wrapped);

file_put_contents($last_published_time,"$now\n");
file_put_contents($last_publish_ts,time());



		echo "Copying latest directory to news archive at $newnews_dir<br>";
		full_copy($latest_dir,$newnews_dir);

	    #write new location in news/latest_pointer

		echo "Writing new location index in $latest_pointer:  $new_location<br>";

		$success = file_put_contents ($latest_pointer, "$new_location");
			if (!$success){echo "<b>put contents to latest pointer failed</b>.<br>";}


		echo "Copying update time file to published update time<br>";
		$success = copy ($rtime_file,$ptime_file); #fix the date of last run member updates
		if (! $success){echo "<h3>Failed to copy rtime to ptime!</h3>";}
		// now make a new news_next directory from the model
		//echo "Copying model news to next news<br>";
		//full_copy ($modelnews_dir,$nextnews_dir);
		// Do this manually instaead.
        $pdo = MyPDO::instance();

        echo "Marking News Items as published<br>";
        $sql_today = sql_today();
        $sql = "
            UPDATE news_items
            SET status = 'P',
            date_published = '$sql_today',
            use_me = 0
            WHERE use_me > 0;
            ";
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

        #now rebuild the files for advance
        include './news_files2.php';


    echo "Done.  <button type='button' onClick='window.close()'>Close Window</button>";

	exit;






