<?php
namespace digitalmx\flames;

	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\FileDefs;
	
	
class CompileNews
{


private $news;


public function __construct() {
	$this->news = new News();
	$this->asseta = new AssetAdmin();
	
	
}

public function buildStory ($id) {
	// compiles article at $aid into html code
	
	//function build_story($row,$stag=0,$etag=0,$dtag=true){
    #stag is whether or not to show Scheduled status in story
    #etag is whether or not to show Edit button
    #dtag is whether or not to show the "discuss" and voting sections 


   
    $sdata = $this->news->getArticle($id);
    
    $sdata['section'] = $this->news->getSectionForTopic($sdata['topic']);
    $sdata['section_name'] = $this->news->getSectionName($sdata['section']);
    $sdata['topic_name'] = $this->news->getTopicName($sdata['topic']);


    /* detect if story is already html.  If not, do nl2br.
    // otherwise use as is.
    */
    
	if (strpos($sdata['content'],'<p>') === false 
	 && strpos($sdata['content'],'<table>') === false ){
		$sdata['content'] = nl2br($sdata['content']);
	}
	
	$sdata['content'] = u\makeLinks($sdata['content']);
	
	if (!empty($sdata['asset_list'] )){
		$alist = u\number_range($sdata['asset_list']);
		$alistcnt = count($alist);
			
		if ($alistcnt >2){
			$adiv = 'asset-row';
		} elseif ($alistcnt > 0){
			$adiv = 'asset-column';
		} else {$adiv = '';}
		$sdata['adiv'] = $adiv;
	
		foreach ($alist as $aid ){
			$sdata['asset_blocks'][] = $this->asseta->getAssetBlock($aid,'thumb',false);
		}
	}
	
	
    if($sdata['use_me'] > 0){
    	$sdata['status_display'] = "<span style='background-color:#9F9;width:15em;'>Queued for Next Newsletter</span>";
    	} else {
    		$sdata['status_display']="<span style='background-color:#F99;width:15em;'>Not Queued</span";
    	}
    

   

  	if ($sdata['take_comments'] == 1 or $sdata['take_votes'] == 1 ) {
	}
	// 	$story .=
//            " <div class='story_comment_box clear'>
//            <table class='voting'><tr>";
//            
//         if ($row['take_comments'] == 1 ){
//             $story .= "<td ><!-- comment $articleid --></td>";
//            
//         }
//         # add voting buttons
//         if ($row['take_votes']== 1 && $_SESSION['level'] > 6){
//         	$story .= "<td ><!-- vote $articleid --></td>";
//         }
//         
//         $story .= '</tr></table></div>';
//     }
//         
// 
//     $edit_button = $etag? show_edit($row['id']) : '';
//     if ($etag || $stag){
// 	    $story .= "<p>$edit_button $this_scheduled</p>\n";
// 	}
// 	$story .= "
// 	<p class='clear' style='margin-top:0px;margin-bottom:0px;'>&nbsp;</p>
// 	</div>
// 	";


  return $sdata;
}

	
	
	
}

