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

	}

	public function getArticleList ($cat) {

	$alist = $this->article->getArticleList($cat);
	//u\echor($alist);
	// add thumb image and image count to each item
	// add action buttons to each item
	// divide into editable and non-editable lists
		$editable=[];$noneditable=[];
		foreach ($alist as $row){
			$id = $row['id'];
			$asset_id = 0;
			$asset_count=0;
			$asset_list = [];
			$image='';
			$asset_center = $row['asset_id']?? '';
			if (!empty($r = trim($asset_center . ' ' . $row['asset_list']) )) {
				$asset_list = preg_split('/[\s,]+/',$r);

				$asset_count = count($asset_list);
				$asset_id = array_shift($asset_list);
			}
			if ($asset_id) {
				$image = $this->asseta->getAssetBlock($asset_id,'thumb',false);
			}
			$row['image'] = $image;
			$row['asset_count'] = $asset_count;
			$row['smsg'] = $this->setStatusMessage($row);

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

		$selmsg = array(
			'unpub' => "Unpublished Articles",
			'current' => 'Recently Published',
		);

		$mylist['editable'] = $editable;
		$mylist['noneditable'] = $noneditable;


		$mylist['emsg']['editable'] = "Articles You Can Manage";
		$mylist['emsg']['noneditable'] = "Articles Managed By Others";
		$mylist['emsg']['selected'] = $selmsg[$cat] ?? 'Undefined';
		return $mylist;

	}

	public function buildStory($id)
    {

		$sdata = $this->article->getArticle($id);
        $id = $sdata['id'];

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
		$sdata['smsg'] = $this->setStatusMessage($sdata);


        $sdata['more'] = '';
        if (!empty($url = $sdata['url'])) {
            $ltitle = $sdata['link_title'] ?: 'web link';
            $sdata['more'] = "<p class='more'> More: <a href='$url' onClick = 'return countClick(this,$id);' target='_blank'>$ltitle</a></p>";
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
