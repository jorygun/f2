<?php
namespace DigitalMx\Flames;

    use DigitalMx\MyPDO;
    use DigitalMx as u;
    use DigitalMx\Flames as f;
    use DigitalMx\Flames\Definitions as Defs;
    use DigitalMx\Flames\FileDefs;

class Article
{

    private $news;
	private $getarticleprep; // preped getarticlesql
	private $getunpub_prep; // prepped getlist
	private $publish;

	private static $getarticlesql =  <<<EOT
            SELECT n.*, DATE_FORMAT(n.date_entered,'%d %M %Y') as date_entered_human,
            m.username as contributor,m.user_email,
            t.topic_name,s.section_name,s.section_sequence

            ,(SELECT count(*) FROM  comments c
                WHERE n.id = c.item_id AND c.on_db = 'news_items') AS comment_count
            , (SELECT count(*) FROM votes v
                WHERE n.id = v.news_fk AND v.vote_rank <> 0) AS total_votes
            , (SELECT SUM(`vote_rank`) FROM votes v
                WHERE n.id = v.news_fk AND v.vote_rank <> 0) AS net_votes

            FROM articles n
             LEFT JOIN members_f2 m on m.user_id = n.contributor_id
             LEFT JOIN news_topics t  JOIN news_sections s on t.section = s.section on t.topic = n.topic

            WHERE
                n.id = ?;
EOT;


// select articles in two groups: use, sorted by sequence and priority, then
// unused.  Cat is used to keep them separated.

    public function __construct($container)
    {
        $this->news = $container['news'];

         $this->member = $container['member'];
        $this->pdo = $container['pdo'];
        $this->voting = $container['voting'];
//         $this->publish = $container['publish'];

         $this->getarticleprep = $this->pdo->prepare(self::$getarticlesql);



    }

	public function toggle_use($aid) {
		// change item use_me between 0 and 2
		$sql1 = "UPDATE `articles` SET use_me =
			IF(use_me>0, 0, 2)
			WHERE id = $aid;";
		$this->pdo->query($sql1);
		return true;

	}



    public function saveArticle($post)
    {
        try {
            $adata = $this->checkArticle($post);
        } catch (Exception $e) {
            echo "Article data error." . BRNL . $e->getMessage();
            echo "<button type='button'  onclick = 'history.back();'>back</button>" . BRNL;
            exit;
        }
        $id = $post['id'];
        $prep = u\pdoPrep($adata, [], 'id');
   //u\echor ($prep , 'PDO data');


     /**
        $prep = pdoPrep($post_data,$allowed_list,'id');

        $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
           $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
           $new_id = $pdo->lastInsertId();

        $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
           $stmt = $pdo->prepare($sql)->execute($prep['data']);

      **/

        if ($id == 0) {
            $sql = "INSERT into `articles` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
           // u\echor($prep['data'],$sql); exit;
            $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
            $id = $this->pdo->lastInsertId();
        } else {
            $sql = "UPDATE `articles` SET ${prep['updateu']}
                WHERE id =  ${prep['key']} ;";
           //u\echor($prep['udata'] , $sql);

            $stmt = $this->pdo->prepare($sql)->execute($prep['udata']);
        }
        //echo "Saved to $id" . BRNL;
        return $id;
    }

	public function setArticlesPublished ($issue,$pubdate) {
		$article_list = $this->getArticleIds('next');
		$article_in = u\make_inlist_from_list($article_list);
		$sql = "UPDATE `articles` SET use_me = 0, status = 'P',
			date_published = '$pubdate', issue='$issue'
			WHERE id in ($article_in)";
		if (!$this->pdo->query($sql) ){
			throw new Exception ("setArticlesPublished failed.");
		}


	}
	public function getPops($id) {
		// retrieves story extra info, including comments and votes

		$sql = "SELECT take_comments, take_votes, contributor_id FROM `articles`
			WHERE id = $id";
		$pops = $this->pdo->query($sql)->fetch();
		 $user_id = $_SESSION['login']['user_id'];
		// editing privileges
		$pops['edit_credential'] = $_SESSION['level'] > 4
    		|| $pops['contributor_id'] == $user_id;
    	$pops['user_id'] = $user_id;
    	$pops['article_id'] = $id;

		return $pops;
	}

    private function checkArticle($post)
    {
            //u\echor($post, 'Incoming');
            // checks and cleans up data before storing.

        if (! $post['title']) {
            throw new Exception("No Title Specified for Item");
        }
             // title case
        $adata['title'] = ucwords($post['title']);
        $adata['id'] = $id = $post['id']?? 0;
        $adata['topic'] = $post['topic'];
        if (empty($adata['topic'])) {
            throw new Exception("Article must have a topic");
        }
        $adata['content'] = $post['content'];
		$adata['link'] = $post['link'];
		$adata['link_title'] = $post['link_title'];

        $adata['take_votes'] =  (empty($post['take_votes'])) ? 0 : 1 ;
        $adata['take_comments'] = (empty($post['take_comments'])) ? 0 : 1 ;

        // set contributor id if one not set yet and
            // valid member name is in the contributo name field
            // no contributor (=0) is not an error
        $cd = $this->member->setContributor($post['contributor_id'], $post['contributor']);
        //put the new contrib info into the adata array
 			$adata['contributor_id'] = $cd['contributor_id']; // cont name not stored in record

        if (!empty($adata['asset_main'] = trim($post['asset_main']))) {
            if (! preg_match('/^\d{4,5}$/', $adata['asset_main'])) {
                throw new Exception("Non-integer in asset_main");
            }
        }

        if (!empty($adata['asset_list'] = trim($post['asset_list']))) {
            foreach (preg_split('/\s+/', $adata['asset_list']) as $aid) {
                if (! preg_match('/^\d{4,5}$/', $aid)) {
                    throw new Exception("Non-integer in asset_list: $aid");
                }
            }
        }
       // add /ed to editorial comment if it's not already commented
        if (! empty($post['ed_comment'])) {
            $adata['ed_comment'] = $post['ed_comment'];
            if (! preg_match('/.*\n--\/[\w ]+\s*$/s', $post['ed_comment'])) {
                $commenter_name = $adata['contributor'] ?? $_SESSION['login']['username'];
                $adata['ed_comment'] .= "\n--/$commenter_name\n";
            }
        }

        $status = $post['status'];
        if (! in_array($status, array_keys(Defs::$news_status))) {
            throw new Exception("Unknown status code $status");
        }

        $adata['status'] = $status;

         // use use-me field as numeric priority
         // convert queue text to priority

        // echo "Looking for queue " . $post['queue'] . BRNL;
        $pri = array_search($post['queue'], News::$queueOptions) ?? 0;
        if ($pri < 0 || $pri > 4) {
            throw new Exception("priority out of range");
        }
         $adata['use_me'] = $pri;
         //echo "setting use me to $pri type " . gettype($pri) . BRNL;
         // not set from form post: date_published, comment_count, net_votes

       // u\echor($adata, 'After check');
        $this->adata = $adata;
        return $adata;
    }


    public function getArticleInfo($id)
    {
        return u\array_filter_keys(
            $this->adata,
            ['title', 'contributor', 'contributor_email']
        );
    }

    public function getNewArticle()
    {
        $adata = array(
                'id' => 0,
                'title' => '',
            'source' => '',
            'source_date' => '',
            'link' => '',
            'link_title' => '',
            'topic' => '',
            'date_published'  => '',
            'status'=> 'N',
            'content' => '',
            'contributor_id' => $_SESSION['login']['user_id'],
           'contributor' => $_SESSION['login']['username'],
            'contributor_email' => '',
            'total_votes' => 0,
            'net_votes' => 0,
            'comment_count' => 0,
            'asset_main' => 0,
            'asset_list'  => '',
            'ed_comment'  => '',
            'use_me'  => '0',
            'take_comments' => 0,
            'take_votes'  => 0,
            'date_entered' => date('Y-m-d'),
        );
        return $adata;
    }

	private function getWhereForCat($cat) {
	/* produces WHERE clause based on selected category:
		if a date, choose pubdate > date
		if 'recent', chooses pubdate within 2 weeks
		if 'next' chooses articles assigned to next newsletter (issue = 1)
		if and integer, chooses article in issue_id = integer
		if an array, then it's a list of article ids. (from pub[stories])
	*/

		if (u\isInteger($cat)) {
					//if integer, get this article
				$where = "n.id = $cat";
		} elseif (is_array($cat)) {
			// is list of articles, probably retrieved from issue article list
					$idlist = u\make_inlist_from_list($cat);
					$where = "n.id in ($idlist)";
		} else {
			switch ($cat) {
			  case 'unpub':
					$where = " n.status not in ('P','X','T')";
					break;
				case 'recent':
					$where = " n.status = 'P' AND n.date_published > NOW() - INTERVAL 2 week";
					break;
				case  'next':
					$where = "n.use_me > 0 AND n.status not in ('P','X','T')";
					break;

				default:
					throw new Exception (
						"Unrecognized list style $style");

				}
			}
		return $where;
	}
	public function getArticleIds($cat) {
		$where = $this->getWhereForCat($cat);
		$sql = "SELECT n.id
							FROM articles n
							WHERE $where";
		$list = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
		return $list;
	}

	public function getArticleList($cat) {
		$where = $this->getWhereForCat($cat);

	//echo "where: $where" . BRNL;
		$sql = <<<EOT
			 SELECT n.id, n.use_me as use_me, n.topic, s.section_sequence,
             if (n.use_me > 0,1,0) as `cat`,
                 n.title, n.asset_list, n.asset_main, n.status,n.source,
                 n.contributor_id,m.username,
                 DATE_FORMAT('%y %m %d',n.date_published) as pubdate,
                 t.topic_name as topic_name,s.section_name,
                 count(c.id) as comment_count

            FROM articles n
             LEFT JOIN news_topics t  JOIN news_sections s on t.section = s.section on t.topic = n.topic
				LEFT JOIN members_f2 m on m.user_id = n.contributor_id
				LEFT JOIN comments c on n.id = c.item_id and c.on_db = 'news_items'

            WHERE
           		$where
				GROUP BY n.id
            ORDER BY `cat` DESC, section_sequence, topic_name, use_me DESC
            LIMIT 50;
EOT;
// echo $sql . BRNL;
// exit;
		$alist = $this->pdo->query($sql)->fetchAll();;

		return $alist;

	}



    public function getArticle($id)
    {
        // if votes is true, do innerjoin to get vote and comment counts
        if ($id == 0) {
            $adata =  $this->getNewArticle();
            $this->adata = $adata;
            return $adata;
        } else {
		// returns article data along with contributor name, etc
        $this->getarticleprep->execute([$id]);
		if (! $adata = $this->getarticleprep->fetch() ){
        	die ("Invalid article id $id");
        }

        $this->adata = $adata;  // fill this story array
        return $adata;
        }
    }

}
//EOF
