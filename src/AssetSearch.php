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
		'status' => '',
		'contributor' => '',
		'use_options' => '',
		'searchuse' => '',
		'relative' => '',

		);


	public function __construct($container){
		$this->member = $container['member'];
		$this->assets = $container['assets'];
		$this->asseta = $container['asseta'];


	}

	public function getEmpty() {
		return self::$empty_search;
	}


	public function  prepareSearch($sdata){
		// fill in blank fields
		$asdata = array_merge(self::$empty_search,$sdata);
		// comopute options, selects
		$asdata['use_options'] = build_options(array('On','Before','After'),$asdata['relative']);
		  $asdata['type_options'] = build_options(Defs::$asset_types,$asdata['type']);
		 //$status_options = build_options($asset_status,$pdata['status']);

		  $asdata['all_active_checked'] = (!empty($asdata['all_active'])) ?
			'checked':'';
			 $asdata['unreviewed_checked'] = (!empty($asdata['unreviewed'])) ?
			'checked':'';

			$tag_data = '';
			if (! empty ($asdata['tags'])){
				$tag_data = u\charListToString($asdata['tags'])  ;
			}
			$search_asset_tags =Defs::$asset_tags;
			$search_asset_tags['Z'] = 'z Any Archival';

			 $asdata['tag_options'] = u\buildCheckBoxSet('tags',$search_asset_tags,$tag_data,3);

		  $asdata['status_options'] = u\buildOptions(Defs::$asset_status,$asdata['status']) ;
		  $asdata['searchon_hte'] =  spchar($asdata['searchon']);
		  $asdata['vintage'] =  $asdata['vintage'] ?? '';
		  $asdata['plusminus'] = $asdata['plusminus'] ?? '';

		  $asdata['$hideme'] = ($_SESSION['level']<6)?"style='display:none'":'';

			return $asdata;
	}



	public function getIdsFromSearch($data) {
		// first turn the search form data into sql
		$sql = $this->getSQLFromSearch($data);
		echo "sql: $sql" . BRNL;
		// now get list of ids that match
		$id_list = $this->assets->getIdsFromWhere($sql);
		# u\echor ($id_list, 'id list');
		return $id_list;
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
    		if (! list($mem,$memid) = $this->member->getMemberId($son) ){
    			echo "Contributor $son not found; ignored" . BRNL;
    		} else {
        		$qp[] = "contributor_id = '$memid'";
        }
    }

    if (!empty($data['no_contributor'])){
            $qp[] = "(contributor is NULL or contributor = '' )";
     }

    if (!empty($son = $data['type'])){
        $qp[] = "type = '$son'";
    }

   if (!empty($son = $data['status'])){
        $qp[] = "astatus = '$son'";
    }
   elseif (!empty($data['all_active'])){
        $qp[] = "astatus  in ('R','N','O','F') ";
    }
	 elseif (!empty ($data['unreviewed'] )){
        $qp[] = "astatus in ('N','O','F') ";
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

        preg_match('/^\s*(\d+)?\s*(\D+)?\s*(\d+)?/',$son,$m);
        $id1 = $m[1] ?? 0;
        $dl = $m[2] ?? '';
        $id2 = $m[3] ?? 0;
			# echo "$id1,$dl, $id2" . BRNL;
        if ($id1>0){
            if ($id2>0){
                if ($id2 <$id1){ #swap
                    $i = $id1; $id1 = $id2; $id2 = $i;
                }
            $sql = "id >='$id1' AND id <= '$id2' ";
            }
            elseif (!empty($dl)){
                $sql = "id >='$id1' ";
            }
            else {$sql = " id = '$id1' ";}
        }

        elseif (!empty($dl)){
            if ($id2>0){
                $sql = " id <= '$id2' ";
            }
            else {die ("id search not understood: $son");}
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

            return addslashes(spchard(trim($keyword)));
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


  		 // returns all matching assets
        $adata = $this->asseta->getAssetData($id);

     # u\echor($asset_set); exit;
			// enhance data
			$adata['status_label'] = Defs::$asset_status[$adata['status']];
			if ($adata['status'] == 'R'){
				 //$adata['status_label'] .= " (On ${data['review_ts']} )";
			}
			$adata['status_style'] = ($adata['status'] == 'D')?"color:red;":'';

			if ($adata['status'] == 'D'){
				 $adata['status_style'] = "color:red;";
				 $adata['image'] = "(Image Deleted)";
			}
			else
				{
			 $adata['image'] = $this->asseta->returnAssetLinked($adata) ;

			}

	  $adata['show_thumbs'] = join(',',$adata['existing_thumbs']) ?:
	  'None';

		$adata['tag_display'] = '';

			$adata['editable'] =
			  (
				 $_SESSION['level'] > 6
				 || strtolower($_SESSION['login']['user_id']) == strtolower($adata['contributor_id'])
				 || strtolower($_SESSION['login']['username']) == strtolower($adata['source'])

			 ) ? true:false;



			$adata['source_warning'] = (u\url_exists($adata['asset_url']) ) ? '' : " <<<< Source cannot be found ";

//
//
//   if ($dt = \DateTime::createFromFormat('Y-m-d H:i:s',$row['date_entered'])){
//   	$date_entered = $dt->format('d M Y');
//   }
//   else {$date_entered = $row['date_entered'];}
//
//
//
//     #2 column table
//
//
//     }
//



		return $adata;
	}

}
