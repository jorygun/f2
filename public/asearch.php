<?php
namespace digitalmx\flames;
#ini_set('display_errors', 1);



//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;
	


if ($login->checkLogin(4)){
   $page_title = 'Search Assets';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}
	
//END START
show_asset_search (empty_as(),$templates );
function empty_as(){
	$a=array(
		'searchon' => '',
		'vintage' => '',
		'plusminus' => '',
		'type' => '',
		'tags' => '',
		'id_range' => '',
		'all_active' => 0,
		'status' => '',
		'contributor' => '',
		'use_options' => '',
		'searchuse' => '',
		'relative' => '',
		
		);
		return $a;
}
		
function show_asset_search($asdata,$templates){

   $asdata['use_options'] = build_options(array('On','Before','After'),$asdata['relative']);
     $asdata['type_options'] = build_options(Defs::$asset_types,$asdata['type']);
    //$status_options = build_options($asset_status,$pdata['status']);
    
     $asdata['all_active_checked'] = (!empty($asdata['all_active'])) ?
    	'checked':'';
   
    	$tag_data = charListToString($asdata['tags'])  ;
    	$search_asset_tags =Defs::$asset_tags;
    	$search_asset_tags['Z'] = 'z Any Archival';
    	
    	 $asdata['tag_options'] = buildCheckBoxSet('tags',$search_asset_tags,$tag_data,3);
    
     $asdata['status_options'] = build_options(Defs::$asset_status,$asdata['status']) ;
     $asdata['searchon_hte'] =  spchar($asdata['searchon']);
     $asdata['vintage'] =  $asdata['vintage'] ?? '';
     $asdata['plusminus'] = $asdata['plusminus'] ?? '';
    
     $asdata['$hideme'] = ($_SESSION['level']<6)?"style='display:none'":'';
    
	echo $templates->render('asearch',$asdata);
	
}
