<?php
namespace DigitalMx\Flames;


// functions used in reading a newsletter


use DigitalMx\Flames\Definitions as Defs;


class ReadNews {

	private $voting;
	private $pdo;
	private $member;
	private $opps;

	#path to search for files in
	private $search_dirs =  array(
			'.',
			SITE_PATH . '/news/current',
			SITE_PATH . '/news'
		);

	public function __construct ( ) {
	global $container;
		$this->voting = $container['voting'];
		$this->pdo = $container['pdo'];
		$this->member = $container['member'];
		$this->opps = $container['opps'];

	}

	public function get_news_comments($id){

		 $sql = "SELECT count(*) from `comments` where on_db = 'news_items' and item_id = $id;";
		 $nRows = $this->pdo->query($sql)->fetchColumn();
		 return $nRows;
	}



	public function get_user_from_name($name){
		return $this->member->getMemberBasic($name)['data']['user_id'];
	}

	private function update_comment_counts(){

		 $sql = "SELECT count(*) as cnt, on_db,item_id from `comments` group by on_db,item_id";

		  $sqln = "Update `articles` set comment_count = ? where id = ?";
		 $qn = $this->pdo -> prepare($sqln);

		 $qs = $this->pdo -> query($sql);
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
	private function get_comment_count($table,$id){

		 $sql = "SELECT count(*) from `comments` where on_db = '$table' and item_id = $id;";
		 $nRows = $this->pdo->query($sql)->fetchColumn();
		 return $nRows;
	}

	public function get_topics($access=''){
// use access = '' for all topics including deprecated
// access = 'A' for all current topics
// access = 'U' for user accessible topics


	$sql = "SELECT `topic`,`topic_name` from `news_topics` T
		INNER JOIN news_sections  S
		ON T.section = S.section ";
	if ($access == 'A'){ $sql .= " WHERE `access` in ('A','U') "; }
	elseif ($access == 'U'){ $sql .= " WHERE `access` = 'U' "; }
	$sql .= " ORDER BY S.section_sequence, T.topic ";

	$topics = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	return $topics;
}

function get_sections(){

	$sql = "SELECT section, concat(section_name,'|',section_subhead) AS section_data from `news_sections` ORDER BY section_sequence";
	$sections = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
	return $sections;
}

	public function current_ops(){
		 // lists currently open job opportunities

		 $opp_rows = $this->opps->getOppCount();
		 return $opp_rows;
	}

	public function user_welcome() {
		$uid = $_SESSION['login']['user_id'];
		if ($uid == 0) {die;}

		$row= $this->member->getMemberWarnings($uid);


		$t = "<div >";
		$t .= "<p>Welcome back {$row['username']}.
		Flames member since ${row['jdate']}.</p>";

		$err = [];
		if ($row['email_status'] != 'Y'){
			$err[] = "There is an issue with your email address: "
				 . Defs::getEmsName($row['email_status'])
				 . NL;
		}
		if ($row['udays'] > 720) {
			$err[] = "Your profile has not been updated for two years.  Please have a look.";
		}


		if (!empty($err)) {
			$t .= "<p><span class='red'>There are some problems
			with your account.</span> <br>
			You can fix these by updating and saving your profile, which is listed under your name in the menu bar.  </p>
				<ul>";
			foreach ($err as $e){
				$t .= '<li>' . $e;
			}
			$t .= "</ul>" . NL;
		}
		$t .= "</div>" . NL;
	return $t;
}


	public function echo_if ($filename,$heading='',$subhead='') {
		#echo "Called echo_if on $filename<br>\n";
		$hit = '';
		foreach ($this->search_dirs as $dir){
			if (file_exists("$dir/$filename") ) {
				$hit = "$dir/$filename";
				break;
			}
		}
		if (!$hit){return '';}
		$content = file_get_contents($hit);
		if (substr($filename,0,5) == 'news_') {
			$content = $this->filter_news($content);
		}
		if ($heading){echo $this->news_head($heading,$subhead);}
		#if ($subhead){echo $this->news_subhead($subhead);}
		echo $content;

	}
	private function filter_news($content,$extra = ''){

			$content = $this->replace_old_discussion($content);
			$content = $this->replace_voting_content($content);
			$content = "$extra\n" . $this->replace_new_discussion($content);
			return $content;
	}

		private function replace_old_discussion ($content) {
			return preg_replace_callback (
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


		}
		private function replace_voting_content ($content) {
			return preg_replace_callback(
				'|<!-- vote (\d+) -->|',
				function ($matches){
					$user = $_SESSION['login']['user_id'];

					$vp = $this->voting->show_panel($matches[1],$user);

					return $vp;
				}
			,
				 $content
				 );

	}

	private function replace_new_discussion ($content) {
			  #replace discussion content - new style
				return  preg_replace_callback(
						'|<!-- comment (\d+) -->|',
						function ($matches){
							$cp = "
						 <a href='/get-article.php?{$matches[1]}d' target='article'>Discuss this article </a>";
							$cp .=  '<br />' . $this->get_commenters($matches[1]);
							return $cp;
						}
					,
						 $content
						 );
		}


		public function news_head($title,$tcomment=''){
			// add class amd to AMD news setion to pick up amd style defs

			  $hcode = "<div class='divh2'>$title\n";
			  if ($tcomment != ''){$hcode .= "<br><span class='comment'>$tcomment</span>";}
			  $hcode .= "</div>\n";
			  return $hcode;
		 }

		public function news_subhead($title){
			  $hcode = "<h3>$title</h3>\n";
			  return $hcode;
		 }

	public	function increment_reads($issue){
			#echo "sstart increment reads";

			if ($_SESSION['level']>7){ return;} #don't count admin access

			$sql3 = "UPDATE issues set rcount = rcount + 1 where issue = 'issue';";
			$this->pdo->query($sql3);

			return 1;
		}

	public function get_slogan(){
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

	public function get_commenters($aid) {
	/*
		Script to retrieve the names of commentors on an article
		Call with article_id = nnn
		Returns text string
	*/



		$sql = "SELECT user_id FROM `comments` WHERE on_db = 'news_items' AND item_id = $aid;";

		$sql3 = "SELECT DISTINCT m.username FROM `members_f2` m
			LEFT JOIN `comments` c ON
			c.user_id = m.user_id AND c.on_db = 'news_items'
			WHERE c.item_id = $aid

			;";
	#echo $sql . BRNL;

		$namec = $this->pdo -> query($sql3);
		$commenters_list = $namec -> fetchALL(\PDO::FETCH_COLUMN);
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
	public function getTitle(){
		$dir = '.';
		$title = '';
		if (file_exists("$dir/title.txt")) {
			$title = trim(file_get_contents("$dir/title.txt"));
		}

		$pubdate = '';
		if (file_exists("$dir/publish.txt")){
			$pubdate = explode('|',trim(file_get_contents("$dir/publish.txt")))[0];
		}
		else {$pubdate = 'Preview';}

		if ($title){
			$title .= " &bull; $pubdate";
		}
		else {
			$title = $pubdate;
		}
		return $title;
	}
}

