<?php
// functions used in reading a newsletter

// 	function pic_link($loc,$cap,$width){
// 		#returns the pic with caption hotlinked to it's raw image
// 		if (!$width){$width=640;}
// 		$t = "<div class='photo'><a href='$loc' target='_blank' decoration='none'>
// 			<img src='$loc' width=$width></a><p class='caption'>$cap<br>
// 			<small>(Click image to view full size)</small></p>
// 			</div>";
// 		return $t;
// 	}


// require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
 require_once "Voting.class.php";
#echo "Got to " . basename(__FILE__ ) . ' line ' .  __LINE__ . "<br />\n"; exit;
$voting = new Voting();



function get_news_comments($id){
       $pdo = MyPDO::instance();
    $sql = "SELECT count(*) from `comments` where on_db = 'news_items' and item_id = $id;";
    $nRows = $pdo->query($sql)->fetchColumn();
    return $nRows;
}



function get_user_from_name($name){
   $pdo = MyPDO::instance();
    $sql = "Select user_id from `members_f2` where username = '$name';";
    $result = $pdo->query($sql);
    $row = $result -> fetch();
    $id = $row['user_id'];
    return $id;
}

function update_comment_counts(){
    $pdo = MyPDO::instance();
    $sql = "SELECT count(*) as cnt, on_db,item_id from `comments` group by on_db,item_id";
    $sqla = "Update `assets` set comment_count = ? where id = ?";
    $qa = $pdo -> prepare($sqla);
     $sqln = "Update `news_items` set comment_count = ? where id = ?";
    $qn = $pdo -> prepare($sqln);

    $qs = $pdo -> query($sql);
    while ($row =  $qs -> fetch() ){
        $db = $row['on_db'];
        $id = $row['item_id'];
        $cnt = $row['cnt'];
        echo "$db, $id, $cnt\n";

        if ($db == 'assets'){
         $r = $qa -> execute([$cnt,$id]);
        }
        elseif ($db =='news_items'){
             $r = $qn -> execute([$cnt,$id]);
        }


    }

}
function get_comment_count($table,$id){
   $pdo = MyPDO::instance();
    $sql = "SELECT count(*) from `comments` where on_db = '$table' and item_id = $id;";
    $nRows = $pdo->query($sql)->fetchColumn();
    return $nRows;
}




function current_ops(){
    // lists currently open job opportunities
    $pdo = MyPDO::instance();
    $sql = "SELECT count(*) FROM opportunities WHERE
            expired = '0000-00-00' OR
            expired > NOW();";
    $opp_rows = $pdo->query($sql)-> fetchColumn();
    return $opp_rows;
}

function echo_if($filename,$extra=''){
	#pass filename, possible heding text,
	   
	#look in local directory, then news_live, then  in news directory
	   if (file_exists("./$filename")) {
	   		$file = $filename;
	   } elseif (file_exists (SITE_PATH . "/news/news_live/$filename")) {
	   	$file = SITE_PATH . "/news/news_live/$filename"; 
	   } elseif (file_exists (SITE_PATH ."/news/$filename")){
	   	$file = SITE_PATH ."/news/$filename";
	  	
	  } else {return "$filename not found";}
	   
	   $content = file_get_contents($file);
	   
	if (substr($filename,0,5) !== 'news_') {
		return $content;
	}
	
	#for news files, deal with voting
	global $voting; #need the voting object
	    	
 /*   <div class='story_comment_box clear'>
           <? echo get_commenters(1741) ?>
           <br>
           <a href='/scripts/news_article_c.php?id=1741' target='cpage'>Discuss this article</a>
           </div>
*/
	
			
    #replace disucssion old style
			$content1 = preg_replace_callback (
				'|<\? echo get_commenters\((\d+)\) .*?</div>|s',
				function ($matches) {
					$cp = "<!-- comment ${matches[1]} -->
					</div>
					";
					#$cp = "got $matches[1]";
					return $cp;
				},
                $content
                 );
                
     #replace voting content
            $content2 = preg_replace_callback(
            	'|<!-- vote (\d+) -->|',
            	function ($matches) use ($voting){
            		$user = $_SESSION['user_id'];
            		
            		$vp = $voting->show_panel($matches[1],$user);
            		return $vp;
            	}
				,
                $content1
                );
                       
        #replace discussion content - new style
        	$content3 = preg_replace_callback(
            	'|<!-- comment (\d+) -->|',
            	function ($matches){
            		$cp = "
          		 <a href='/scripts/news_article_c.php?id=$matches[1]' target='cpage'>Discuss this article </a>";
          		 	$cp .=  '<br />' . get_commenters($matches[1]);
            		return $cp;
            	}
				,
                $content2
                );
                
            echo $extra;
        	echo $content3;
   
        	return "$extra\n$content3"; #replace echo with using return value

	}


    function news_head($title,$tcomment=''){
        $hcode = "<div class='divh2'>$title\n";
        if ($tcomment != ''){$hcode .= "<br><span class='comment'>$tcomment</span>";}
        $hcode .= "</div>\n";
        return $hcode;
    }

    function news_subhead($title){
        $hcode = "<h3>$title</h3>\n";
        return $hcode;
    }

	function increment_reads($issue){
		#echo "sstart increment reads";

        $pdo = MyPDO::instance();


		if ($_SESSION['level']>7){ return;} #don't count admin access
		$sql1 = "UPDATE read_table SET read_cnt = read_cnt + 1 WHERE issue = $issue;";
		$sql2 = "INSERT INTO read_table SET read_cnt = 1 , issue = $issue;";
#INSERT INTO table (id, name, age) VALUES(1, "A", 19) ON DUPLICATE KEY UPDATE    name="A", age=19
		$sql3 = "INSERT INTO read_table (issue,read_cnt) VALUES ($issue,1)
		    ON DUPLICATE KEY UPDATE read_cnt = read_cnt + 1";
		$pdo->query($sql3);



		return 1;
	}

function get_slogan(){
	// $slogans = [
// 	'MIL-STD-883 for free!,Quality',
// 	'The Age of Asparagus,New products take a long time to mature'
// 	];
    $slogantexts = file(SITE_PATH . '/scripts/slogans.txt'); #reads file into array
    if (empty($slogantexts)){return ("Can't open slogans");}
   # print_r ($slogans) ;

    $slogantext = $slogantexts[array_rand($slogantexts,1)];
    preg_match('/^([^\t]+)\t?(.*)/',$slogantext,$m);
	$slogan = $m[1] ?? '';
	$note = $m[2] ?? '';

	if (!empty ($note)){$note = "<br><span style='font-style:italic;font-weight:normal;'>($note)</span>";}

	return "<p style='text-align:center; border-top:1px solid #393; '>Things we used to say:<br>  <b>$slogan</b> <br>$note </p>";
}

function get_commenters($aid) {
/*
	Script to retrieve the names of commentors on an article
	Call with article_id = nnn
	Returns text string
*/


	$pdo = MyPDO::instance();
//

	$sql = "SELECT user_id FROM `comments` WHERE on_db = 'news_items' AND item_id = $aid;";

	$sql3 = "SELECT DISTINCT m.username FROM `members_f2` m
		LEFT JOIN `comments` c ON
		c.user_id = m.user_id AND c.on_db = 'news_items'
		WHERE c.item_id = $aid

		;";
#echo $sql . BRNL;

	$namec = $pdo -> query($sql3);
	$commenters_list = $namec -> fetchALL(PDO::FETCH_COLUMN);
	if (empty ($commenters_list)){$commenters = "No comments yet.  Be the first.";}
	else{
	    $commenters = 'Comments from ';
	    $ccount = count($commenters_list);
	    $mcount = 0;
	    if ($ccount > 8){
	        $commenters_list = array_slice($commenters_list,0,8);
	        $mcount = $ccount - 8;
	    }

		$commenters .= implode(', ',$commenters_list);
		if ($mcount){ $commenters .= "and $mcount more.";}
	}

	$commenters .= "\n";

	return $commenters;
}



