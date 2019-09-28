<?php
namespace digitalmx\flames;


// functions used in reading a newsletter


	use digitalmx\flames\Voting;
 	use digitalmx\MyPDO;
 	use digitalmx\flames\ReadNews;
 	use digitalmx\flames\Opportunities;

	
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
		$this->voting = new Voting();
		$this->pdo = MyPDO::instance();
		$this->member = new Member();
		$this->opps = new Opportunities($_SESSION['level']);
	
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
	
		  $sqln = "Update `news_items` set comment_count = ? where id = ?";
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




	public function current_ops(){
		 // lists currently open job opportunities
		 
		 $opp_rows = $this->opps->getOppCount();
		 return $opp_rows;
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
		if ($heading){echo $this->news_head($heading);}
		if ($subhead){echo $this->news_subhead($subhead);}
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
						 <a href='/scripts/news_article_c.php?id=$matches[1]' target='cpage'>Discuss this article </a>";
							$cp .=  '<br />' . $this->get_commenters($matches[1]);
							return $cp;
						}
					,
						 $content
						 );
		}


		public function news_head($title,$tcomment=''){
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
			
			$sql3 = "INSERT INTO read_table (issue,read_cnt) VALUES ($issue,1)
				 ON DUPLICATE KEY UPDATE read_cnt = read_cnt + 1";
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

