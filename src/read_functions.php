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
	use DigitalMx\Flames\Voting;
 	use DigitalMx\MyPDO;
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
     $sqln = "Update `articles` set comment_count = ? where id = ?";
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
function echo_if ($filename,$extra='') {
	if ($var = get_news_file($filename,$extra='') ){
		echo $var;
	}
}
function get_news_file($filename,$extra=''){
	#pass filename, possible heding text,


	#look in local directory, then news_live, then  in news directory
	$dirs = array(
		'.',
		SITE_PATH . '/var/live',
		SITE_PATH . '/news'
	);
	$hit = false;
	$content = '';
	foreach ($dirs as $dir ) {
	   if (!$hit && file_exists("$dir/$filename") ) {
	   	$hit = "$dir/$filename";
	   	#echo "hit on $hit" . BRNL;
	   	 $content = file_get_contents($hit);
	   	 if (substr($filename,0,5) == 'news_') { #need to prepocess news files
	   	 	#echo "preprocessing $hit." . BRNL;
				$content = replace_old_discussion($content);

				$content = replace_voting_content($content);
				#echo "2:<br>" . $content2;
				$content = "$extra\n" . replace_new_discussion($content);
				return $content;
			}
			else {return $content;}
		}
		return $content;

	}

}

	function replace_old_discussion ($content) {
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

		return $content1;
	}
	function replace_voting_content ($content) {

	  global $voting; #need access to voting class object
		return preg_replace_callback(
			'|<!-- vote (\d+) -->|',
			function ($matches) use ($voting){
				$user = $_SESSION['login']['user_id'];

				$vp = $voting->show_panel($matches[1],$user);

				return $vp;
			}
		,
			 $content
			 );

}

function get_latest_file($dir) {
	$file = `ls -t $dir | head -n 1 `;
	return "$dir/$file";
}

function get_recent_files($number,$path){
	#returns name of n most recent files in directory.
	#returns a string if only 1; otherwise an array
	$latest_ctime = 0;
	$mods = array();
	if (is_dir($path) == false){return "";}

    foreach (glob($path . '/*') as $f) {
        $mods[filemtime($f)] = $f;
    }
    krsort($mods);
    $fnames = array_values(array_slice($mods, 0, $number, true));
    $fnames = str_replace("$path/",'',$fnames);
    if ($number == 1){return $fnames[0];} #if only 1, just return it
    return $fnames; #otherwise return the list
}



function get_popular() {
}


function replace_new_discussion ($content) {
        #replace discussion content - new style
        	$content1 = preg_replace_callback(
            	'|<!-- comment (\d+) -->|',
            	function ($matches){
            		$id = $matches[1];
            		$cp = "
          		 <a href='/get-article.php?id=$id&m=d' target='article'>Discuss this article </a>";

            		return $cp;
            	}
				,
                $content
                );


        	return "$content1"; #replace echo with using return value

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

// iincluded for compatability with old newsletters


class navBar
{
	public function build_menu() {
		return $_SESSION['menubar'];
	}

}


