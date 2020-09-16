<?php

namespace Digitalmx\Flames;

use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\Definitions as Defs;



class ArticleAdmin
{

	private $article;
	private $articlea;

	public function __construct ($container) {
		$this->article = $container['article'];
		$this->asseta = $container['asseta'];
		$this->voting = $container['voting'];
		$this->templates = $container['templates'];
		$this->assetv = $container['assetv'];
		$this->comment = $container['comment'];

	}

	public function getArticleList ($cat,$data=[]) {

	$alist = $this->article->getArticleList($cat,$data);
	//u\echor($alist, 'from article->getArticleList');
	// add thumb image and image count to each item
	// add action buttons to each item
	// divide into editable and non-editable lists
		$editable=[];$noneditable=[];
		foreach ($alist as $row){
			$id = $row['id'];

			$asset_count=0;
			$asset_list = [];
			$image='';
			// count the assets and get one
			$asset_center = $row['asset_main']?? '';
			if (!empty($r = trim($asset_center . ' ' . $row['asset_list']) )) {
				$asset_list = preg_split('/[\s,]+/',$r);

				$asset_count = count($asset_list);
				$asset_id = array_shift($asset_list);


				if ($asset_id) {
					$image = $this->assetv->getAssetBlock($asset_id,'small',false);
				}
			}
			$row['image'] = $image;
			$row['asset_count'] = $asset_count;

			if ($row['status'] == 'P' ) {
				$row['use_msg'] = 'Published';
			} else {
				$row['use_msg'] = ($row['use_me'] > 0 ) ?
					"Queued For Next Issue" : "Not Scheduled";
			}

			// use can edit if it's his own article or has news admin status
			$credential = $_SESSION['level'] >= 7 || $_SESSION['login']['user_id'] == $row['contributor_id'];

			$row['edit-button'] = ($credential) ?
				"<button type='button' onClick=window.open('/article_editor.php?id=$id','aedit')>Edit</button>"
				: '';
			// news admin can add remove article from queue
			$row['use-button'] = ($_SESSION['level']>= 7 && in_array($row['status'], ['N','Q'] ) )?
				"<button type='submit' form='alist_form' name='toggle_use' value = $id
				style='background:orange;'>Toggle Queued</button>"
				: "";
			$row['delete-button'] = ($_SESSION['level']>= 7  )?
				"<button type='submit' form='alist_form' name='delete_article' value = $id
				style='background:red;'>Delete</button>"
				: "";

			$row['view-button'] =
				"<button type='button' onClick = window.open('/get-article.php?$id','article')>View</button>";

			if ($credential) {
				$editable[] = $row;
			} else {
				$noneditable[] = $row;
			}
		}

		// get title message for the listing
			$seltok = trim(strtok($cat, ' '));
			switch ($seltok) {
				case 'unpub':
					$selmsg = "Unpublished Articles";
					break;
				case  'current' :
					$selmsg = 'Recently Published';
					break;
				case 'issue':
					$selmsg = 'From ' . $cat ;
					break;
				default:
					$selmsg = $cat;
		};

		$mylist['editable'] = $editable;
		$mylist['noneditable'] = $noneditable;



		$mylist['emsg']['editable'] = "Articles You Can Manage";
		$mylist['emsg']['noneditable'] = "Articles Managed By Others";
		$mylist['emsg']['selected'] = $selmsg;
		return $mylist;

	}

	public function getSortedArticleList ($cat,$data=[]) {

	$alist = $this->article->getArticleList($cat,$data);
	//u\echor($alist, 'from article->getArticleList');
	// add thumb image and image count to each item
	// add action buttons to each item
	// divide into queued , editable and non-editable lists
		$queued = []; $editable=[];$noneditable=[];


		foreach ($alist as $row){
			$id = $row['id'];

			$asset_count=0;
			$asset_list = [];
			$image='';
			// count the assets and get one
			$asset_center = $row['asset_main']?? '';
			if (!empty($r = trim($asset_center . ' ' . $row['asset_list']) )) {
				$asset_list = preg_split('/[\s,]+/',$r);

				$asset_count = count($asset_list);
				$asset_id = array_shift($asset_list);


				if ($asset_id) {
					$image = $this->assetv->getAssetBlock($asset_id,'small',false);
				}
			}
			$row['image'] = $image;
			$row['asset_count'] = $asset_count;


			// use can edit if it's his own article or has news admin status
			$credential = $_SESSION['level'] >= 7 || $_SESSION['login']['user_id'] == $row['contributor_id'];

			$row['edit-button'] =
				"<button type='button' onClick=window.open('/article_editor.php?id=$id','aedit')>Edit</button>"
				;
			// news admin can add remove article from queue
			$row['use-button'] =
				"<button type='submit' form='alist_form' name='toggle_use' value = $id
				style='background:orange;'>Toggle Queued</button>"
				;
			$row['delete-button'] =
				"<button type='submit' form='alist_form' name='delete_article' value = $id
				style='background:red;'>Delete</button>"
				;

			$row['view-button'] =
				"<button type='button' onClick = window.open('/get-article.php?$id','article')>View</button>";

			$row['take_c'] = ($row['take_comments'] )? 'yes' : 'no';
			$row['take_v'] = ($row['take_votes'] ) ? 'yes' : 'no';

			if ($row['status'] == 'P' ) {
				$row['use_msg'] = 'Published';
			} else {
				$row['use_msg'] = ($row['use_me'] > 0 ) ?
					"Queued For Next Issue" : "Not Scheduled";
			}

			$mylist['list'][] = $row;

		}

		// get title message for the listing
			$seltok = trim(strtok($cat, ' '));
			switch ($seltok) {
				case 'unpub':
					$mylist['selected'] = "Unpublished Articles";
					break;
				case  'recent' :
					$mylist['selected'] = 'Recently Published';
					break;
				case 'issue':
					$mylist['selected'] = 'From ' . $cat ;
					break;
				default:
					$mylist['selected'] = $cat;
		};


		return $mylist;

	}


	public function getDblock($id) {
	// builds the discussion block

		// get the comments
   		 $carray = $this->comment->getComments($id,'article');

   		$dblock =  "<div class='comment_background'>
         <h2>Reader Comments</h2>
         ";

			foreach ($carray as $row) {
			  //u\echor($row);
				if (!empty($row['asset_list'])) { #should only be one
					 $row['asset'] = $this->getAssetDiv($row['asset_list']);
				} else {
					$row['asset'] = '';
				}
//	u\echor($row); //exit;
				$dblock .= $this->templates->render('comment', $row);
			}

        $dblock .=  "</div>" . NL;


		return $dblock;
	}

	function renderStory($id) {
		$story_data = $this->buildStory($id); // date for story
		$story = $this->templates->render('article',$story_data);
		return $story;
	}

	public function getAssetDiv($asset_list) {
		$ablock = [];
		if (!empty($asset_list)) {
            $alist = u\number_range($asset_list);
            $alistcnt = count($alist);

            if ($alistcnt >2) {
                $adiv = 'asset-row';
            } elseif ($alistcnt > 0) {
                $adiv = 'asset-column';
            } else {
                $adiv = '';
            }
            $ablock ['adiv'] = $adiv;

            foreach ($alist as $aid) {
                $ablock ['asset_blocks'][] = $this->assetv->getAssetBlock($aid, 'small', false);
            }
        }
        return  $ablock ;
        // array with two entries: adiv and ablock
      }

	function getLiveArticle ($id,$show='') {
		// user includes user_id, username,
		// show is 'comments','pops', or ''
		// returns array of data ready for rendering in the article template
	$user = array(
		 'user_id' => $_SESSION['login']['user_id'],
		 'username' => $_SESSION['login']['username'],
    );


		$pops = $this->article->getPops($id); // array take_votes, credential,etc
//u\echor($pops,'pops');
		$adata = $this->article->getArticle($id);
//u\echor($adata,'adata');
		$article = "<div class='article'>";

		// adata asset block needs to a list of asset blocks
		$adata = array_merge($adata, $this->getAssetDiv($adata['asset_list'])); #2 rows

		$adata = array_merge($adata,$this->buildStory($adata) ); // date for story
//u\echor($adata); exit;

		$pblock = '';
		if ($show == 'pops') {
			$pblock = $this->getPblock($adata['comment_count'],$pops)  ;
			// is a div of text

		}
				$adata['pblock']  = $pblock;
	$dblock = '';
		if ($show == 'comments'){
				$dblock = $this->getDblock($id);
			if ( $pops['take_comments'])  {
				$nc_data = $user;
				$nc_data['on_id'] = $id;
				$nc_data['admin_note'] = '';

			  $dblock .=  $this->templates->render('new_comment', $nc_data);
			 } else {
				$dblock .=  "New comments are disabled on this article" . BRNL;
			}

				//is a div of text

		}
		$adata['dblock'] = $dblock;

		if ($pops['edit_credential']) {
			$adata['status'] = "<div class='clear status-display'>"
				. $adata['status_message']
				. "</div>
				";
		}

		//u\echor($adata,'Article Data'); exit;
		return $adata;

	}


	public function buildStory($sdata)
    {
		if (empty($sdata)){die ("Trying to build story on no story data");}

	// builds html for a story

      $id = $sdata['id']; // might be noew article, so orignal was id = 0

#u\echor ($sdata,'sdata');exit;
        /* detect if story is already html.  If not, do nl2br.
        // otherwise use as is.
        */


		$adata['content'] = '';
			//insert main graphic
		if (!empty($sdata['asset_main'])){
			$adata['content'] =
			"<div class='asset-main'>"
			. $this->assetv->getAssetBlock($sdata['asset_main'],'large')
			. "</div>" . NL;
		}

		if (strpos($sdata['content'], '<p>') === false
            && strpos($sdata['content'], '<table>') === false) {
            $adata['content'] .= nl2br($sdata['content']);
        } else {
        		$adata['content'] .= $sdata['content'];
		}

      $adata['content'] = u\makeLinks($adata['content']);

		// ADD comment tag if none present
		$ed_comment = trim($sdata['ed_comment']);
		if (!empty($ed_comment)){
			if (! preg_match ('/\n--\/\w.*$/',$ed_comment)) {
				$ed_comment .= "\n&nbsp;&nbsp;--/editor";
			}
		}
      $adata['ed_comment'] = nl2br($ed_comment);

		$adata['status_message'] = $this->setStatusMessage($sdata);
		$adata['sfrom'] = ($sdata['source']) ? "From " . $sdata['source'] : '';

      $adata['more'] = '';
      if (!empty($link = $sdata['link'])) {
            $ltitle = $sdata['link_title'] ?: 'web link';

           $adata['more'] = "<p class='more'> More: <a href='$link' onClick = 'return countClick(this,$id);' target='_blank'>$ltitle</a></p>";
      }


        return $adata;
    }

	public function getPblock($cc,$pops) {
	// need comment count, article id, take comments, take votes, this userid
		// params is
	// builds block listing comments and taking votes
		$id = $pops['article_id'];
	    $pblock = '';

        if ($pops['take_comments']) {
        	// link to display aarticle page with comments at bottom
            $pblock .= "<a href='/get-article.php?${id}d' target='article'>Comments</a> ($cc) ";
        }
        if ($pops['take_votes']) {

            $voteicons = $this->voting->getVotePanel($id, $pops['user_id']);
            if (!empty($pblock)) {
                $pblock .= "&nbsp;&nbsp;&bull;&nbsp;&nbsp;";
            }

            $pblock .= "Interesting??  "
                . $voteicons
                ;
        }
		if (!empty($pblock)){
			return "<div class='pop'>" . $pblock . "</div>\n\n";
		} else {
			return '';
		}

	}


	private function setStatusMessage($sdata) {
        // set status message
        if ($sdata['status'] == 'P') {
            $smsg = 'Published';
        } elseif ($sdata['use_me'] > 0) {
            $smsg = 'Queued for Next';
        } else {
            $smsg = ' Not Queued';
        }
      return $smsg;
	}

}

