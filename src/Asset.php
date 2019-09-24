<?php
namespace digitalmx\flames;

use digitalmx\MyPDO;
use digitalmx\flames as f;
use digitalmx as u;
use digitalmx\flames\Definitions as Defs;


class Asset {
    
    private static $pdo;
    
    
    
    public function __construct(){
        self::$pdo = MyPDO::instance();
        
    }
    
    public function getAssetsByName($name) {
        $sql = "SELECT id from `assets` where 
        concat('', caption,title) like '%$name%' ";
        $alist = self::$pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
        return $alist;
      }
      
   public function getGalleryAsset($id) {
      
		if (empty($id)){return '';}
		 
		 $sql = "SELECT * from `assets` WHERE id = $id";
		 $row = self::$pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
		 if (empty($row)){return '';}
		 
		 $url = $row['url'];
		 $link = $row['link'];
		 $target = (empty($row['link'])) ? $row['url'] : $row['link'];
		 $caption =  make_links(nl2br($row['caption']));
		 if (empty($caption)){$caption = $row['title'];}

		 $source_line = "<p class='source'>";
		 $source_line .=  (! empty($row['source']))? "${row['source']}" : 'Unattributed';
		 if (! empty ($row['vintage'] )) {
			 $source_line .=  " (${row['vintage']})";
		 }
		 $source_line .= "</p>\n";

		 $title_line = u\special($row['title']);

		 $click_line = (!empty($target))? "<p class='small centered'> (Click image for link.)</p>":'';

		  $thumb_url = "/assets/thumbs/${row['thumb_file']}";

		  $editable = (strcasecmp ($_SESSION['login']['username'] ,$row['contributor']) == 0) ? true : false;
			  if ($_SESSION['level'] > 7) {$editable=true;}


		$edit_field = ($editable) ? "<a href='/scripts/asset_edit.php?id=$id&type=specadmin'>Edit</a> " : '';


		if ( empty($row['thumb_file']) or !file_exists(SITE_PATH . "/$thumb_url") ){ return "Attempt to link to asset with no thumb: id $id" . BRNL; }


		 $out =  "<div class='album'>";
		 $gfile = choose_graphic_url('/assets/galleries',$id);
		 if (empty($gfile) && file_exists(SITE_PATH . "/$thumb_url")  ) {
			  $gfile = $thumb_url;
		 }

		 if (! empty ($gfile)){
			  $out .= "
			  <a href='/asset_display.php?$id' target='asset' decoration='none'>
			  <img src='$gfile' ></a>
			  <p class='caption'>$caption</p>
			 $source_line
			  <p class='clear'>$edit_field [$id]</p>
		 ";
		 }
		 else  {$out .= "(No gallery image for id $id)";}
		 $out .= "</div>";



		 #update the first used if it's blank and not an admin access
		 $first_date = $row['first_use_date'];
		 if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){

			  $out  .= set_first_use($id,$status);
		 }

		 return $out;
	}

}

    

