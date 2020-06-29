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
				$row['use_msg'] = '';
			} else {
				$row['use_msg'] = ($row['use_me'] > 0 ) ?
					"Queued For Next" : "Not Scheduled";
			}
			// move status message to another function
			//$row['smsg'] = $this->setStatusMessage($row);

			// use can edit if it's his own article or has news admin status
			$credential = $_SESSION['level'] >= 7 || $_SESSION['login']['user_id'] == $row['contributor_id'];

			$row['edit-button'] = ($credential) ?
				"<button type='button' onClick=window.open('/article_editor.php?id=$id','aedit')>Edit</button>"
				: '';
			// news admin can add remove article from queue
			$row['use-button'] = ($_SESSION['level']>= 7) ?
				"<button type='submit' form='alist_form' name='toggle_use' value = $id
				style='background:orange;'>Toggle Use</button>"
				: "";
			$row['view-button'] =
				"<button type='button' onClick = window.open('/get-article.php?$id')>View</button>";

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
	public function getDblock($id) {
	// builds the discussion block

		// get the comments
   		 $carray = $this->comment->getComments($id,'article');

   		$dblock =  "<div class='comment_background'>
         <h2>Reader Comments</h2>
         ";

			foreach ($carray as $row) {
			  //u\echor($row);
				if (!empty($row['asset_list'])) {
					 $row['asset'] = $this->assetv->getAssetBlock($row['asset_list'], 'thumbs', false);
				} else {
					$row['asset'] = '';
				}
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

	// public function getAssetBlock($sdata) {
// 		$ablock = [];
// 		if (!empty($sdata['asset_list'])) {
//             $alist = u\number_range($sdata['asset_list']);
//             $alistcnt = count($alist);
//
//             if ($alistcnt >2) {
//                 $adiv = 'asset-row';
//             } elseif ($alistcnt > 0) {
//                 $adiv = 'asset-column';
//             } else {
//                 $adiv = '';
//             }
//             $ablock ['adiv'] = $adiv;
//
//             foreach ($alist as $aid) {
//                 $ablock ['asset_blocks'][] = $this->asseta->getAssetBlock($aid, 'thumbs', false);
//             }
//         }
//         return  $ablock ;
//         // array with two entries: adiv and ablock
//       }

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
		$adata = array_merge($adata, $this->getAssetBlock($adata)); #2 rows

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

		//u\echor($adata,'Article Data');
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

        if (strpos($sdata['content'], '<p>') === false
            && strpos($sdata['content'], '<table>') === false) {
            $sdata['content'] = nl2br($sdata['content']);
        }
        $sdata['ed_comment'] = nl2br($sdata['ed_comment']);
        $sdata['content'] = u\makeLinks($sdata['content']);


		$sdata['status_message'] = $this->setStatusMessage($sdata);
		$sdata['sfrom'] = ($sdata['source']) ? "From " . $sdata['source'] : '';

        $sdata['more'] = '';
        if (!empty($link = $sdata['link'])) {
            $ltitle = $sdata['link_title'] ?: 'web link';

           $sdata['more'] = "<p class='more'> More: <a href='$link' onClick = 'return countClick(this,$id);' target='_blank'>$ltitle</a></p>";
        }


        return $sdata;
    }

	public function getPblock($cc,$pops) {
	// need comment count, article id, take comments, take votes, this userid
		// params is
	// builds block listing comments and taking votes
		$id = $pops['article_id'];
	    $pblock = '';

        if ($pops['take_comments']) {
        	// link to display aarticle page with comments at bottom
            $pblock .= "<a href='/get-article.php?${id}d'>Comments</a> ($cc) ";
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

		return "<div class='pop'>" . $pblock . "</div>\n\n";

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

