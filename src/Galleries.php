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

	private $new_gallery = array (
	'id' => 0,
   'title' => '',
   'caption' => '',
   'thumb_file' => '',
   'vintage' => '',
   'gallery_items' => '',
   'contributor' => '',
   'contributor_id' => 0,

	);

	public function __construct($container) {
		$this->pdo = $container['pdo'];
		$this->asseta = $container['asseta'];
		$this->assets = $container['assets'];
		$this->new_gallery['vintage'] = date('Y');
		$this->new_gallery['contributor'] = date('Y');
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

		$gitems = $row['gallery_items'];
		// may be list of numbers or a search assets command
		// as search: tags like '%A%'
		if (preg_match('/^\s*search: (.*)/i',$gitems,$m) ){
			$crit = $m[1];
			$sql = "Select id from `assets` where status not in ('E','X','D') AND $crit";
			$note =  "<p>Searching assets where: $crit </p>";
			$aids = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
			#recho ($assets,"Found $crit");

		} else {
			  $aids = u\number_range($row['gallery_items']);
		}
		if (empty($aids)){die ("No asset list for gallery $gid ");}

		echo "<h2>${row['title']}</h3>";

		foreach ($aids as $aid){
			//echo "Gallery block $aid goes here";
			echo $this->asseta->getAssetBlock($aid,'gallery',false) ;
		}

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
	public function getGalleryData() {
		$sql = "SELECT g.*,m.username as contributor
		FROM galleries g
		LEFT JOIN members_f2 m on g.contributor_id = m.user_id
		ORDER BY vintage DESC, date_created DESC;";
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
		 $gall = $this->getGalleryData(); // all data indexed by gid

		 $last_vintage = 0;
		 foreach ($gall as $gdata) {
		 u\echor($gdata);
		 	$vintage = $gdata['vintage'];
		 	if ($vintage != $last_vintage){
					 if (empty($vintage )){$vintage = "Multiple Years";}
					echo "<div class='clear'><br><p style='background:#393;color:white;font-size:1.2em;'  >$vintage</p>";
			  }

		 	echo $this->getGalleryBlock($gdata) ;
			$last_vintage = $vintage;
		}

	}

	public function getGalleryBlock($gdata) {
		/* returns an asset block, but linked to the gallery gid instead of
		the asset image. gid is this gallery.  aid is the id of the asset to
		display as the gallery thumb.  It will choose /assets/galleries/aid.jpg
		if available.  Otherwise will try to create it first.

		*/

			  $title = u\special($gdata['title']);
			  $caption = u\special($gdata['caption']);
			  $gid = $gdata['id'];

			  // get designated id or first in asset list to use as thumb
			  if (empty($aid = $gdata['thumb_id'])) {
				  preg_match('/^(\d+).*/',$gdata['gallery_items'],$m);
			 	 $aid = $m[1];
			 	}
			 	if (empty($aid)) {
			 		die ("Must designate thumb id for gallery $gid");
			 	}

		$lc = 0; #loop counter to get out
		$image =  '/assets/galleries' . "/$aid.jpg";
		if (!file_exists(SITE_PATH . $image) ) {
			// go make one
			if (!$this->assets->createThumbs($aid,['galleries']) ) {
				die ("Unable to create gallery thumb for as. $aid");
			}
		}

		$attr_block = $this->getAttribute($gdata['contributor']);
		$block = <<<EOT
				<div class='asset'>
					<a href='/galleries.php?$gid' target='gallery'>
					<img src='$image' /> </a>
					$attr_block
					<div class='atitle'>${gdata['title']} </div>

				</div>
EOT;
		return $block;
	}

	public  function getAttribute($source) {
		//$attr = $adata['source'];
			$attr_block = (!empty($source))? "<div class='asource'>-- $source</div>" : '';
			return $attr_block;
		}
	public function edit_gallery($gid) {

		$sql = "SELECT * FROM `galleries` WHERE id ='$gid';";

		if (empty($gid)) {
			$d = $this->new_gallery;
		} elseif (! $d = $pdo->query($sql)->fetch() ) {
            die ("No gallery found at $gid");
      }

      $this->templates->render('edit_gallery', $d);
	}







}

/*


######################################################################
function thumb_for_asset ($asset_list){
    #returns thumb for first asset listed
    preg_match('/^(\d+)/',$asset_list,$m);
    $first_asset = $m[1];
    echo "Obtaining thumb from asset $first_asset";
    $pdo = MyPDO::instance();
    $sql = "SELECT thumb_file from `assets` where id = $first_asset";

    if (!$thumb = $pdo->query($sql)->fetchColumn() ){
        die ("sql failed: $sql");
    }
    echo " $thumb" . BRNL;
    return $thumb;
}


function make_gallery_images($itemlist) {
    foreach ($itemlist as $id){
        if (!empty ($gfile = choose_graphic_url('/assets/galleries',$id))){
            continue; #have the file
        }
        #make gallery file
        #get source for asset; create thumb
        $pdo = MyPDO::instance();
        $sql = "SELECT url , link from `assets` where id = $id";
        $fresult = $pdo->query($sql)->fetch();
        $fsource = '';
        if (! empty($fresult['url'])){$fsource = $fresult['url'];}
        elseif (! empty($fresult['link'])){$fsource = $fresult['link'];}
        else {
        echo "Cannot find source file for thumb at asset id $id";
        	continue;
        	}
        if (!empty ($fsource)){create_thumb($id,$fsource,'galleries');}


    }

}


public function post_gallery($post){
    $pdo = MyPDO::instance();

    #recho ($post,"Incoming post");
     $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $post['gallery_items'],
            $post['thumb_file'],
            $post['admins']
            );


    #make sure there are gallery files for each photo
        make_gallery_images(list_numbers($post['gallery_items']));


    if (empty($post['id']) or $post['id'] == 0){
        # get thumb file for first asset
        $thumb = thumb_for_asset($post['gallery_items']);
        $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $post['gallery_items'],
            $thumb,
            $post['admins']
            );



        $sql = "Insert into `galleries` (title,caption,vintage,gallery_items,thumb_file,admins,status)
            values (?,?,?,?,?,?,'N')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $newid = $pdo->lastInsertId();
        echo "New Gallery id: $newid" . BRNL;
        return $newid;

    }
    else {
        $id = $post['id'];
        if (empty($post['thumb_file'])){
            $post['thumb_file'] = thumb_for_asset($post['gallery_items']);
        }
        if (empty($post['thumb_file'])){
            echo "Error: cannot get thumb_file for this asset" . BRNL;
            exit;
        }
        $values = array(
            $post['title'],
            $post['caption'],
            $post['vintage'],
            $post['gallery_items'],
            $post['thumb_file'],
            $post['admins']
            );

        $sql = "Update `galleries` set title = ?, caption = ?, vintage = ?, gallery_items = ?,thumb_file = ?, admins=? where id = $id;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $id;
    }
}

function get_gallery_data($id){
    $pdo = MyPDO::instance();

     $itemdata = array(); #store data to display
    #echo "Starting get_asset_data";

        // retrieve existing record
        $sql = "SELECT * FROM `galleries` WHERE id =?;";
        $stmt = $pdo->prepare($sql);

         $stmt->execute([$id]);
       if (!$itemdata = $stmt ->fetch(\PDO::FETCH_ASSOC)  ){
                    die ("No gallery found at $id");
        }



    #print_r ($itemdata);
    return $itemdata;
}


function show_gallery_form($itemdata,$gallery_status) {
    $id = (isset($itemdata['id'] ))?$itemdata['id']: 0 ;

// display form using data from itemdata

foreach (['title','caption'] as $f){
    $hte[$f] = hte($itemdata[$f]);
}
$statuscheckedG = $statuscheckedD = $statuscheckedN = '';
if($itemdata['status'] == 'G'){$statuscheckedG = 'checked';}
if($itemdata['status'] == 'D'){$statuscheckedD = 'checked';}
if($itemdata['status'] == 'N'){$statuscheckedN = 'checked';}

$statusfield = <<<EOT
    <input name = 'status' type='radio' value='G' $statuscheckedG >${gallery_status['G']}
    <input name = 'status' type='radio' value='N' $statuscheckedN >${gallery_status['N']}
    <input name = 'status' type='radio' value='D' $statuscheckedD >${gallery_status['D']}
EOT;
//

echo "<button onClick=showDiv('glist')>Show Galleries</button>
    <div class='hidden' id='glist'>";
    echo list_galleries();
echo "</div>\n";

    echo <<< EOT
<h4>Gallery Edit/Entry (main)</h4>
<p>

Edit Gallery (0 for new) <input type=text name='egallery' id='egallery'>
<button onClick = 'choose_gallery()'>Go</button>
</p>







