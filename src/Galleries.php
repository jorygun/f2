<?php
namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

use DigitalMx as u;
use DigitalMx\Flames as f;
use DigitalMx\Flames\Definitions as Defs;

class Galleries
{
 	private $pdo;
 	private $assets;
 	private $asseta;
 	private $templates;
 	private $member;

	private $new_gallery = array (
	'id' => 0,
   'title' => '',
   'caption' => '',
   'thumb_file' => '',
   'vintage' => '',
   'gallery_items' => '',
   'contributor' => '',
   'contributor_id' => 0,
   'contributor' => '',
   'thumb_id' => 0,

	);

	public function __construct($container) {
		$this->pdo = $container['pdo'];
		$this->asseta = $container['asseta'];
		$this->assets = $container['assets'];
		$this->assetv = $container['assetv'];
		$this->templates = $container['templates'];
		$this->member = $container['member'];

		$this->new_gallery['vintage'] = date('Y');
		$this->new_gallery['contributor_id'] = $_SESSION['login']['user_id'];
		$this->new_gallery['contributor'] = $_SESSION['login']['username'];
		$this->credential = ($_SESSION['level'] > 6); #user can edit


	}


	public function display_gallery($gid){
		if(empty($gid) || ! u\isInteger($gid) ) {
			throw new Exception ("Invalid gallery id $gid");
		}

		 $sql = "select * from `galleries` where id = '$gid' ;";

		 if (! $row = $this->pdo->query($sql)->fetch() ){
			  show_galleries("No such gallery $gid");
		 }

		$aids = $this->getGalleryItems($row['gallery_items']);
		if (empty($aids)){die ("No asset list for gallery $gid ");}

		 echo "<div class = gallery>";
		echo "<h3>${row['title']}</h3>";
		echo "<p>${row['caption']}</p>";
		echo "<hr>" . NL;

		foreach ($aids as $aid){
			//echo "Gallery block $aid goes here";
			echo $this->assetv->getAssetBlock($aid,'medium',false) ;
		}
		echo "</div>";
	}



//     #update the first used if it's blank and not an admin access
//     $first_date = $row['first_use_date'];
//     if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){
//
//         $out  .= set_first_use($id,$status);
//     }

	public function getCredential() {
		return $this->credential;
	}

	private function getGalleryItems($gitems) {

		// may be list of numbers or a search assets command
		// as search: tags like '%A%'
		if (preg_match('/^\s*search: (.*)/i',$gitems,$m) ){
			$crit = $m[1];
			$sql = "Select id from `assets` where status not in ('E','X','D') AND $crit";
			$note =  "<p>Searching assets where: $crit </p>";
			$aids = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
			#recho ($assets,"Found $crit");

		} else {
			  $aids = u\number_range($gitems);
		}
		return $aids;

	}

	public function getGalleryData($gid=0) { #gets all unless gid specified
		$where = ''; $limit = '';
		if ($gid > 0) {
			$where = "WHERE g.id = '$gid' ";
			$limit = "LIMIT 1 ";
		}


		$sql = "SELECT g.*,m.username as contributor
		FROM galleries g
		LEFT JOIN members_f2 m on g.contributor_id = m.user_id
		$where
		ORDER BY vintage DESC, date_created DESC
		$limit
		;";

		$gdata = $this->pdo->query($sql)->fetchAll();

		return $gdata;
	}



	public function show_galleries($note=''){


		echo <<<EOT
			<p>$note</p>
		 <h4>Choose a Gallery</h4>
		 <p>Galleries are collections of photos that have been uploaded
		 to the AMDFlames site.  Each photo is about 350px wide,
		 large enough to view, but if you click on the photo, you will
		 get the "full resolution" version, whatever it is.</p>
		 <p>Galleries are ordered by year, except for multi-year collections, which
		 are at the end.</p>
EOT;
	if ($_SESSION['level'] >=7 ) { echo <<<EOT
		<button type='button' onClick="window.open('/galleries.php?id=0&mode=edit')">New Gallery</button>
EOT;
	}

		 $gall = $this->getGalleryData(); // all data  if no gid

		 $last_vintage = 0;
		 echo "<div class = 'asset-row'>";
		 foreach ($gall as $gdata) {
		// u\echor($gdata);
			$vintage = $gdata['vintage'];
		 	if ($vintage != $last_vintage){
					 if (empty($vintage )){$vintage = "Multiple Years";}
					echo "<div class='clear'><br>
					<p style='background:#393;color:white;font-size:1.2em;'  >$vintage</p></div>";
			}

		 	echo $this->makeGalleryBlock($gdata) ;
			$last_vintage = $vintage;
		}
		echo "</div>\n";
	}




	public function makeGalleryBlock($gdata) {
		/* returns an asset block, but linked to the gallery gid instead of
		the asset image. gid is this gallery.  aid is the id of the asset to
		display as the gallery thumb.  It will choose /assets/galleries/aid.jpg
		if available.  Otherwise will try to create it first.

		*/

			  $title = u\special($gdata['title']);
			  $caption = u\special($gdata['caption']);
			  $gid = $gdata['id'];

				$error = '';

			  // if no thumbget designated id or first in asset list to use as thumb

			  if (empty($aid = $gdata['thumb_id'])) { #try standard thumb
			 	 echo "No thumb listed for gallery $gid" . BRNL;
			  		// ok try first asset in gallery asset list
			  		$aid = u\range_to_list($gdata['gallery_items'])[0];
			  	}
				if ($aid) {
					//echo "getting thumb for asset $aid" . BRNL;
					$th = $this->assetv->getThumb($aid,'medium') ;

					if (strpos($th,'**') !== false) {$image_data = $th;}
					else {$image_data = "<img src='$th' />";}

				} else {
					throw new Exception ("No assets are listed for gallery $gid");
				}

			$edit_button = '';
		if ($this->credential) {
			$edit_button = "<button type='button' onClick = window.open('/galleries.php?id=$gid&mode=edit','gedit')>Edit</button>
			";
			}
		$attr_block = $this->getAttribute($gdata['contributor']);
		$block = <<<EOT
				<div class='asset' style='height:300px;'>
					<a href='/galleries.php?$gid' target='gallery'>
					$image_data </a>
					$attr_block
					<div class='atitle'>${gdata['title']} </div>
					$error
					$edit_button
				</div>

EOT;
		return $block;
	}

	public function getAttribute($source) {
		//$attr = $adata['source'];
			$attr_block = (!empty($source))? "<div class='asource'>-- $source</div>" : '';
			return $attr_block;
		}
	public function edit_gallery($gid) {

		if (empty($gid)) {
			$d = $this->new_gallery;

		} else {
			if(! $gd = $this->getGalleryData($gid) ) {
            die ("No gallery found at $gid");
     		 }
     		 $d = $gd[0]; // gd is an array

		}

		 $d['alias_list'] = Defs::getMemberAliasList();
		 //u\echor($d);
      echo $this->templates->render('gallery', $d);
	}

	public function post_gallery($post) {
	#make sure there are gallery files for each photo

		$aids = $this->getGalleryItems($post['gallery_items']);
	//u\echor($aids);

		foreach ($aids as $aid) {
			if (! $post['thumb_id'] ) {
				$post['thumb_id'] = $aid;
			}
			// if (! file_exists(SITE_PATH . '/assets/galleries' . "/$aid.jpg")) {
// 				echo "Need new gallery file at $aid.jpg";
// 			}

		}



       $allowed = array(
       	'id','title','caption','vintage','gallery_items',
       	'thumb_id','contributor_id');

       	// set contributor id if one not set yet and
            // valid member name is in the contributo name field
            // no contributor (=0) is not an error
        $cd = $this->member->setContributor($post['contributor_id'], $post['contributor']);
        //put the new contrib info into the adata array
 			$post = array_merge($post,$cd);

		$prep = u\prepPDO($post,$allowed,'id');
/**
including key field removes that field from udata and adds value to ukey

PREP:
   $prep = u\prepPDO ($post_data,allowed_list,'key_field_name');

INSERT:
		$sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']})";
		$sth = $pdo->prepare($sql);
		$sth->execute($prep['idata']);
		 $new_id = $pdo->lastInsertId();

UPDATE:
		$sql = "UPDATE `Table` SET ${prep['uset']} WHERE id = $prep['ukey'];";
		$sth = $pdo->prepare($sql);
		$sth->execute($prep['udata']);

INSERT ON DUP UPDATE:
   		$sql = INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']} )
    			ON DUPLICATE KEY UPDATE ${prep['uset']};";
    	$sth = $pdo->prepare($sql);
		$sth->execute(array_merge($prep['udata'],$prep['idata']);
		$new_id = $pdo->lastInsertId();


**/


       $sql = "INSERT into `galleries` ( ${prep['ifields']} )
    		VALUES ( ${prep['ivalues']} )
    		ON DUPLICATE KEY UPDATE ${prep['uset']};
    		";

    		$sth = $this->pdo->prepare($sql);
		$sth->execute(array_merge($prep['udata'],$prep['idata']));
		$new_id = $this->pdo->lastInsertId();


       $id = $post['id'] ?: $new_id;


        echo "New Gallery id: $id" . BRNL;
        return $id;

  }
}

