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

    public function __construct($container)
    {
        $this->news = $container['news'];
        $this->asseta = $container['asseta'];
        $this->member = $container['member'];
        $this->pdo = $container['pdo'];
        $this->voting = $container['voting'];

        //$this->adata = $this->getArticle($id);;


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
        #u\echor ($prep , 'PDO data');

     /**
        $prep = pdoPrep($post_data,$allowed_list,'id');

        $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
           $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
           $new_id = $pdo->lastInsertId();

        $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
           $stmt = $pdo->prepare($sql)->execute($prep['data']);

      **/

        if ($id == 0) {
            $sql = "INSERT into `news_items` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
            $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
            $id = $this->pdo->lastInsertId();
        } else {
            $sql = "UPDATE `news_items` SET ${prep['update']}
                WHERE id =  ${prep['key']} ;";
            $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
        }
        echo "Saved to $id" . BRNL;
        return $id;
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

        $adata['take_votes'] =  (empty($post['take_votes'])) ? 0 : 1 ;
        $adata['take_comments'] = (empty($post['take_comments'])) ? 0 : 1 ;

        // set contributor id if one not set yet and
            // valid member name is in the contributo name field
            // no contributor (=0) is not an error
        if (!empty($post['contributor_id']) && $id > 0) {
            $adata['contributor_id'] = $post['contributor_id'];
        } elseif (!empty($post['contributor'])) {
            list ($contributor, $adata['contributor_id'] )
                = $this->member->getMemberId($post['contributor']) ;
            if (empty($contributor)) {
                u\echoalert("No contributor found");
                $adata['contributor_id'] = 0;  #no contributor defined
            }
        } else {
            u\echoalert("No contributor listed");
            $adata['contributor_id'] = 0;
        }
        if (!empty($adata['asset_id'] = trim($post['asset_id']))) {
            if (! preg_match('/^\d{4,5}$/', $adata['asset_id'])) {
                throw new Exception("Non-integer in asset_id");
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

        //u\echor($adata, 'After check');
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
            'url' => '',
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
            'asset_id' => '',
            'asset_list'  => '',
            'ed_comment'  => '',
            'use_me'  => '0',
            'take_comments' => 0,
            'take_votes'  => 0,
            'date_entered' => date('Y-m-d'),
        );
        return $adata;
    }


    public function getArticle($id)
    {
        // if votes is true, do innerjoin to get vote and comment counts
        if ($id == 0) {
            $adata =  $this->getNewArticle();
            $this->adata = $adata;
            return $adata;
        } else {
            $sql = "
            SELECT *
            ,(SELECT m.username  FROM members_f2 m
                WHERE m.user_id = n.contributor_id) AS contributor

            ,(SELECT m.user_email  FROM members_f2 m
                WHERE m.user_id = n.contributor_id) AS contributor_email

            ,(SELECT count(*) FROM  comments c
                WHERE n.id = c.item_id AND c.on_db = 'news_items') AS comment_count
            , (SELECT count(*) FROM votes v
                WHERE n.id = v.news_fk AND v.vote_rank <> 0) AS total_votes
            , (SELECT SUM(`vote_rank`) FROM votes v
                WHERE n.id = v.news_fk AND v.vote_rank <> 0) AS net_votes
            FROM
                news_items n
            WHERE
                n.id = $id;
            ";
        }
        $adata = $this->pdo->query($sql)->fetch();
        $member_info = $this->member->getMemberBasic($adata['contributor_id']);
        $adata['contributor'] = $member_info[0];
        $adata['contributor_email'] = $member_info[2];
        $adata['section'] = $this->news->getSectionForTopic($adata['topic']);
        $adata['section_name'] = $this->news->getSectionName($adata['section']);
        $adata['topic_name'] = $this->news->getTopicName($adata['topic']);

        $this->adata = $adata;  // fill this story array
        return $adata;;
    }

    public function buildStory()
    {
        // compiles article at $aid into html code

        //function build_story($row,$stag=0,$etag=0,$dtag=true){
        #stag is whether or not to show Scheduled status in story
        #etag is whether or not to show Edit button
        #dtag is whether or not to show the "discuss" and voting sections



        $sdata = $this->adata;
        $id = $sdata['id'];



        /* detect if story is already html.  If not, do nl2br.
        // otherwise use as is.
        */

        if (strpos($sdata['content'], '<p>') === false
            && strpos($sdata['content'], '<table>') === false) {
            $sdata['content'] = nl2br($sdata['content']);
        }
        $sdata['ed_comment'] = nl2br($sdata['ed_comment']);
        $sdata['content'] = u\makeLinks($sdata['content']);

        if (!empty($sdata['asset_list'])) {
            $alist = u\number_range($sdata['asset_list']);
            $alistcnt = count($alist);

            if ($alistcnt >2) {
                $adiv = 'asset-row';
            } elseif ($alistcnt > 0) {
                $adiv = 'asset-column';
            } else {
                $adiv = '';
            }
            $sdata['adiv'] = $adiv;

            foreach ($alist as $aid) {
                $sdata['asset_blocks'][] = $this->asseta->getAssetBlock($aid, 'thumb', false);
            }
        }

        // set status message
        if ($sdata['status'] == 'P') {
            $smsg = 'Published';
        } elseif ($sdata['use_me'] > 0) {
            $smsg = 'Queued for Next Newsletter';
        } else {
            $smsg = ' Not Queued';
        }
         $sdata['smsg'] = $smsg;


        $sdata['more'] = '';
        if (!empty($url = $sdata['url'])) {
            $ltitle = $sdata['link_title'] ?: 'web link';
            $sdata['more'] = "<p class='more'> More: <a href='$url' target='_blank'>$ltitle</a></p>";
        }


        // vblock will be put into a pop div before displaying
        $vblock = '';
        if ($sdata['take_comments']) {
            $vblock .= "<a href='/get-article.php?${id}d'>Comments</a> ("
                    . $sdata['comment_count']
                    . ') ' ;
        }
        if ($sdata['take_votes']) {

            $voteicons = $this->voting->getVotePanel($id, $_SESSION['login']['user_id']);
            if (!empty($vblock)) {
                $vblock .= "&nbsp;&nbsp;&bull;&nbsp;&nbsp;";
            }

            $vblock .= "Interesting??  "
                . $voteicons

                ;
        }

        $sdata['vblock'] = "<div class='pop'>" . $vblock . "</div>";
            //echo "Vblock: <pre>$vblock</pre>" . BRNL; exit;


        return $sdata;
    }
}
//EOF
