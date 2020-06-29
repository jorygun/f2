<?php
namespace DigitalMx\Flames;

use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;
	use DigitalMx\Flames\Assets;
	use DigitalMx\Flames\AssetAdmin;




class AssetSearch
{

private $member;
private $assets;

private $asseta;

private static $empty_search = array (
		'searchon' => '',
		'vintage' => '',
		'plusminus' => '',
		'type' => '',
		'tags' => '',
		'id_range' => '',
		'all_active' => 1,
		'status' => 'active',
		'contributor' => '',
		'use_options' => '',
		'searchuse' => '',
		'relative' => '',

		);


	public function __construct($container){
		$this->member = $container['member'];
		$this->assets = $container['assets'];
		$this->asseta = $container['asseta'];
		$this->assetv = $container['assetv'];



	}

	public function getEmpty() {
		return self::$empty_search;
	}


	public function  prepareSearch($sdata){
		// fill in blank fields
		$asdata = array_merge(self::$empty_search,$sdata);
		// comopute options, selects
		$asdata['use_options'] = u\buildOptions(array('On','Before','After'),$asdata['relative']);
		  $asdata['type_options'] = u\buildOptions(Defs::getAssetTypes(),$asdata['type']);
		 //$status_options = build_options($asset_status,$pdata['status']);



			$tag_data = '';
			if (! empty ($asdata['tags'])){
				$tag_data = u\charListToString($asdata['tags'])  ;
			}
			$search_asset_tags =Defs::$asset_tags;
			$search_asset_tags['Z'] = 'z Any Archival';

			 $asdata['tag_options'] = u\buildCheckBoxSet('tags',$search_asset_tags,$tag_data,3);

			$status_special = array(
				'active' => 'All Active',
				'errors' => "Include Errors",
				'unreviewed' => 'Unreviewed',
			);

			$status_options =array_merge( Defs::$asset_status, $status_special);
		  $asdata['status_options'] = u\buildOptions($status_options,$asdata['status']) ;

		  $asdata['searchon_hte'] =  u\special($asdata['searchon']);
		  $asdata['vintage'] =  $asdata['vintage'] ?? '';
		  $asdata['plusminus'] = $asdata['plusminus'] ?? '';

		  $asdata['$hideme'] = ($_SESSION['level']<6)?"style='display:none'":'';

			return $asdata;
	}



	public function getIdsFromSearch($post) {
		// first turn the search form data into sql
		$data = array(
			'error' => '',
			'list' => [],
			'sql' => '',
		);

		try{
			$sql = $this->getSQLFromSearch($post);
			$data['sql'] = $sql;
		} catch (Exception $e) {
			$data['error'] = "Search not understood: " . $e->getMessage();
			return $data;
		}

//		u\echoc ($sql,'sql'); exit;
		// now get list of ids that match
		if(!$data['list'] = $this->assets->getIdsFromWhere($sql) ) {
				$data['error'] = 'Nothing returned';
		}
		//u\echor ($id_list, 'id list'); exit;
		return $data;
	}

	private function getSQLFromSearch($data){

	/* data is array of search parameters
		each on ha a function to create the sql forthat search
		The sql is compiled in array qp[]

	*/
	// u\echor($data,'Input to process search');

    if (!empty ($son = $data['sqlspec'] ?? '')){
        $qp[] = $son;
    }

    if (! empty ($son = $data['id_range'])){
        $qp[] = $this->id_search($son);
    }


    if (! empty($son = $data['tags'] ?? []) ){
       $qp[] = $this -> tag_search ($son);

    }

    if (! empty($son = $data['searchon'])){

        #produce query phrase for the search terms
        $qp[] = $this->token_search($son);

    }
     if (! empty($son = $data['relative']) && !empty($suse = $data['searchuse'])){
        #produce query phrase for the use date terms
        $qp[] = $this->use_search($son,$suse);

    }
    if (! empty($son = trim($data['vintage']))){
        $qp[] = $this->use_vintage($data['vintage'],$data['plusminus']);
    }
    if (!empty($son = trim($data['contributor']))){
    		if (! $memid = $this->member->getMemberId($son)[0] ){
    			echo "Contributor $son not found; ignored" . BRNL;
    		} else {
        		$qp[] = "contributor_id = '$memid'";
        }
    }


    if (!empty($son = $data['type'])){
        $qp[] = "type = '$son'";
    }

   if (!empty($son = $data['status'])){
      //echo "son $son" . BRNL;
   	if ($son == 'active') $qp[] =  " astatus  not in ('T','X','E') ";
   	elseif ($son == 'errors') $qp[] =  " astatus  not in ('T','X') ";
      elseif ($son == 'unreviewed')$qp[] =   " astatus in ('N','O','K') " ;
      else {$qp[] = "astatus = '$son'";}


   }


    if (!empty($son = $data['url'] ?? '')){
        $qp[] = "(url like '%" . $son . "' OR link like '%" . $son . "')";
    }




    if (!empty($qp)){
        $sqls = implode(' AND ',$qp);
    }

	#echo $sqls . BRNL; exit;

      return $sqls;
}



private function id_search($son){
		// get format: n -m, n, n- , -m
			$sql = '';
			$son = trim($son);
			if (preg_match('/^\s*(\d+)\s*$/',$son,$m)) {
				//echo "siingle number" . BRNL;
				$sql = " id = $m[1] ";

			} elseif (preg_match('/^(\d+)?\s*-\s*(\d+)?$/',$son,$m) ) {
        		$id1 = $m[1] ?? 0;
        		$id2 = $m[2] ?? 0;
			//u\echor  range $id1,$id2" . BRNL;

			if ($id1>0 && $id2 == 0) {
				$sql = "id >= $id1 ";
			} elseif ($id1 > 0 && $id2 > 0 ) {
					$sql = " (id BETWEEN $id1 and $id2) " ;
			} elseif ($id1 == 0 && $id2 > 0 )  {
					 $sql = " id <= $id2 ";
			}

		} elseif (preg_match ('/^(\d+([\s,]*|$))+$/',$son) ) {
			//echo  "individual numbers.  split into list" . BRNL;
				$list = preg_split('/[\s,]+/',$son);
				$inlist = implode(',',$list);
				$sql = " id in ($inlist) ";

		} else {
			throw new Exception ("Bad id search: $son");
		}

        return $sql;
    }



function use_vintage($year,$range){
	$year = (int)$year;
	$range = (int)$range;

    if ($range == '0'){
        $sql = "vintage = $year";
    }
    else {
        $min = $year-$range;
        $max = $year + $range;
        $sql = "vintage >= $min AND vintage <= $max";

        }

    return $sql;
}

private function use_search($relative,$date){
    $rmap = array(
        '' => '(no term)',
        'On' => ' = ',
        'Before' => ' <= ',
        'After' => ' >= '
        );
    $sql = "first_use_date != '0000-00-00' AND first_use_date " .
        $rmap[$relative] .
        # "(From $relative)" .
        " '" .
        date('Y-m-d',strtotime($date)) .
        "' ";


    return $sql;
}

private function token_search ($searchstring){
    $keyword_tokens = array_filter(explode(',',$searchstring));

    $keyword_tokens = array_map(
        function($keyword) {

            return u\despecial(trim($keyword));
        },
        $keyword_tokens
    );

   $concat = "CONCAT_WS(' ', title, caption, keywords) ";

// #    $sql = "SELECT * FROM tbl_address WHERE address LIKE'%";
//     $sql = '('
//         . " $concat LIKE '%"
//         . implode("%' OR $concat LIKE '%", $keyword_tokens) . "%'"
//         . ')';
   $token = array_pop($keyword_tokens); #get first token
   $sql =  "( INSTR ($concat ,'${token}') > 0 ";

	foreach ($keyword_tokens as $token){ #OR any additional toekns
		$sql .=  " AND INSTR ($concat ,'${token}') > 0 ";
	}
	$sql .= " ) ";
    return $sql;
}

private function tag_search ($clist) {
     #turn list into an sql array

     $slist = [];
     foreach ($clist as $c){
     	if ($c == 'Z') { #all archival
     		$slist[] = "tags in (" . Defs::getArchivalTagList() . ")";
     	}
     	else {
       	 $slist[] = "tags like '%$c%' ";
       	}
    }
    $sql = '(' . implode(' OR ',$slist) . ')';


    return $sql;
}

 public function  getAssetSummary($id){


  		 // returns

        $adata = $this->assets->getAssetDataEnhanced($id);


     // u\echor($adata); return ;
			// enhance data





		$adata['tag_display'] = '';

			$adata['editable'] =
			  (
				 $_SESSION['level'] > 6
				 || strtolower($_SESSION['login']['user_id']) == strtolower($adata['contributor_id'])
				 || strtolower($_SESSION['login']['username']) == strtolower($adata['source'])

			 ) ? true:false;



			$adata['source_warning'] = (!empty($adata['errors'])) ? $adata['errors']  : '';
			//(u\url_exists($adata['asset_url']) ) ? '' : " <<<< Source cannot be found ";


		return $adata;
	}

}
