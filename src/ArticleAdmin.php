<?php

namespace Digitalmx\Flames;

use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\Definitions as Defs;


// require_once "news_functions.php";
// require_once "asset_functions.php";


class ArticleAdmin
{

	private $article;
	private $articlea;

	public function __construct ($container) {
		$this->article = $container['article'];
		$this->asseta = $container['asseta'];
		$this->voting = $container['voting'];

	}

	public function listArticles($type) {



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


// $now = sql_now();
// $topics = get_topics();
//
// if ($_SERVER['REQUEST_METHOD'] == 'POST'){
// // build the web page
//     $pdo = MyPDO::instance();
//
// 	if (!empty ($id_list = $_POST['idlist'])){
// 		#all ids on the page
//
// 		// check for deletes first
// 		if (!empty($_POST['d_list'])){
//
// 			$delete_list = implode(', ',$_POST['d_list']);
// 			echo "Deleting " . $delete_list . BRNL;
// 			$sql = "DELETE FROM `news_items` WHERE id in ($delete_list)";
// 			$pdo ->query($sql);
// 		}
//
//
// 		$sql = "UPDATE `news_items`
// 			SET use_me = ?, take_comments = ?, take_votes = ?
// 			WHERE id = ?";
// 		$stmt = $pdo->prepare ($sql);
// 		#recho ($_POST['take_comments'],'comments');
// 		foreach ($id_list as $id){
// 			$use_me_val = $take_comments = $take_votes = 0;
//
// 			if (!empty($_POST['use_me']) && in_array($id,$_POST['use_me'])){
// 				$use_me_val = 1;
//
// 				if (!empty($_POST['priority']) && in_array($id,$_POST['priority'])){
// 				$use_me_val = 2;
// 				}
// 			}
//
// 			if (in_array($id,$_POST['tc_list'])){
// 				$take_comments = 1;
// 			}
// 			if (in_array($id,$_POST['tv_list'])){
// 				$take_votes = 1;
// 			}
// 			$value_set = [$use_me_val,$take_comments,$take_votes,$id];
// 			#recho ($value_set, 'execute vals');
//
//    			$stmt -> execute($value_set);
//    		}
// 	}
//
// 	build_next(); #builds the story array
//

/*




<div style="width:150px;float:left;position: fixed">

<p><b>Create New Item</b> <br><input type='button' value=' New '
onclick="new_edit_win('/scripts/news_item_edit.php?id=0','itemedit');" /></p>

<p><b>Show</b><br>
<input type='button' value='UnPublished' onclick="window.location.href ='/scripts/news_items.php?mode=u';return false;">

<input type='button' value='Published' onclick="window.location.href ='/scripts/news_items.php?mode=p';return false;" />
<br />
<p><b>Edit Article</b><br />
id: <input type='text' size='6' name='show_id' id='show_id' value=''>
<input type='button' value='Edit' onclick="show_this_id(getElementById('show_id').value );">

</p>

<p><b>Save and Build Files For Newsletter</b> <br>
<button type='button'  onclick="document.getElementById('article_items_form').submit();"> Build Files </button>
</p>

<p><input type='button' onclick='window.open("/news/next/","preview");'
value='Preview' style='color:green'>
<p style='border:1px solid black;background:#ccc;'><b>Test Files</b><br>

<input type='button' value='Show Test Stories' onclick="window.location.href ='/scripts/news_items.php?mode=t';return false;" /><br />



</div>
<div style='float:left;margin-left:160px;border-left:1px solid gray;width:850px;overflow-y:auto;'>
<br>


<form method="POST" id='article_items_form' style='border:none;'>

<?php
$mode = '';
if (array_key_exists('mode',$_GET)){$mode = $_GET['mode'];}

if (!$mode) {$mode='u';}

if ($mode=='u'){
    $sql= "SELECT n.id,url,title,status,type,use_me,take_votes,take_comments, ed_comment,
    contributor,contributor_id,source, asset_id ,image_data,content,link_title
    	FROM news_items n
    	INNER JOIN news_topics t
       	ON n.type = t.topic
       INNER JOIN news_sections s
       ON s.section = t.section

       	WHERE n.status NOT in( 'P','T') ORDER BY n.use_me DESC, s.section_sequence;";

   echo "<h3>Unpublished Articles</h3>\n";
}
if ($mode == 'p'){
   $sql= "SELECT * from news_items n
   where status = 'P' ORDER BY date_published DESC;";
       echo "<h3>Published Articles</h3>\n";
}
if ($mode == 't'){
       $sql= "SELECT * from news_items n
       	where n.status = 'T' ;";
       echo "<h3>Test Articles</b></h3>\n";
}


echo "<table>";
$pdo = MyPDO::instance();

$result=$pdo->query($sql);
$last_scheduled = '';
while ($row = $result->fetch()){
   # u\echoR($row);
    $image=$image_link=$image_thumb_link='';
    $id = $row['id'];
    $link = 'No Link';
    $htitle = stripslashes($row['title']);
    if ($row['url']){
        $link = "<a href='${row['url']}' target='_blank'>${row['link_title']}</a>";
    }
    $scode = $row['status'];
    $tcode = $row['type'];
    $uchk = ($row['use_me']>0)?'checked':'';
    $pchk = ($row['use_me']>1)?'checked':'';
    $vchk = ($row['take_votes']>0)?'checked':'';
    $cchk = ($row['take_comments']>0)?'checked':'';


    $row['title']=stripslashes($row['title']);
    $hcontent=tbreak(stripslashes($row['content']));
     #$hcontent=tbreak(stripslashes($row['content']));
    $row['ed_comment']=stripslashes($row['ed_comment']);
    $image_size = round(strlen($row['image_data'])/1000,2);
    if (!empty($row['asset_id'])){
         $image= f\get_asset_by_id($row['asset_id']);
         #$image_link="<a href=\"asset_display.php?${row['asset_id']}\" target='asset'>Show Asset</a>";
        # $image_thumb_link="<a href=\"$image\" target='asset'>Show Thumbnail</a>";
    }

    $scheduled = ($row['use_me']>0)?'Scheduled':'Not Scheduled';

    if ($scheduled != $last_scheduled){
        echo "<tr><td><button type='submit'>Build Files</button></td></tr>
        <tr><td colspan='3' style='background:#ccf;color:white;'>
        <b>$scheduled</b>
        </td></tr>\n";
        $last_scheduled = $scheduled;
    }
    else {
        echo "    <tr><td colspan='3' >
        <hr style='border-top:1px solid blue'>

        </td></tr>\n";
    }

     echo <<<EOT
	<tr>
	<td><i>$topics[$tcode]</i></td>
	 <td colspan=2><b>${row['title']}</b></td> </tr>



	<tr><td colspan='3'>Contributor:${row['contributor']} ( ${row['contributor_id']} ) From: ${row['source']}</td>
		</tr>

EOT;



 if ($mode == 'u' || $mode == 't'){  echo <<<EOT
    <tr>
    <td>
    <input type='hidden' name='idlist[]' value='$id'>
    <input type='button' value="Edit #$id" onclick="ewin=window.open('/scripts/news_item_edit.php?id=$id','itemedit','height=1040,width=640,scrollbars');return false;"></td>
    <td><input type='checkbox' name='use_me[]' value='$id' $uchk> Use Me
     <input type='checkbox' name='priority[]' value='$id' $pchk > Use at Top
    <input type='checkbox' name='tc_list[]' value='$id' $cchk > Take comments
   <input type='checkbox' name='tv_list[]' value='$id' $vchk > Take votes <br>
   <input type='checkbox' name='d_list[]' value='$id' > Delete

    </td>
    <td></td>
    </tr>
EOT;

    }


   if (! empty ($image)){ echo "<tr><td>Asset:</td><td style='border:1px solid blue;padding:3px;width:400px;text-align:center;'>$image</td></tr>\n";}

     if ($mode == 'p'){

    echo <<<EOT

<tr><td>Published</td><td> on ${row['date_published']}</td>
</tr>


<tr>
     <td><input type='button' value="Details" onclick="ewin=window.open('/scripts/news_item_edit.php?id=$id','itemedit','height=640,width=640,scrollbars');return false;"></td>
</tr>
EOT;

    }
 }
?>

</table>
<hr>
<button type='submit'>Build Files</button>
</div>

</form>
</div>




<script>
    window.onblur= function() {
        window.onfocus= function () {
            location.reload(true);
        }
    }



</script>

</body></html>
