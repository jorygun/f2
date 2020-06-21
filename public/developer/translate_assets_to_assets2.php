$bnew = array (
	'astatus' => 'N',
	'thumb_url' =>'',
	'asset_url' => '',
	'errors' => '',

	'mime'=>'',
	'type'=>'Other',
	);

$bsame = array( // 8
	'id',	'keywords','vintage','source','notes','first_use_in','tags', 'mime',

	);
$bchanged = array ( //10
	'title','caption','astatus','sizekb','date_entered','contributor_id',
	'first_use_date','asset_url','type','thumb_url'

$bauto = array ( //2
	'date_modified', 'errors'
	);

function translate_fields($a) {
	// $a is existing assets data, returns new asset2 data
	global $bsame,$bnew;

	// make new array 'b'
	$b = $bnew;

	foreach ($bsame as $v){
		$b[$v] = $a[$v];
	}
	$ostatus = $a['status']; #old status

	$b['title'] = stripslashes($a['title']) ?: 'Untitled';
	$b['caption'] = stripslashes($a['caption']);
	if ( $b['title'] == $b['caption']) {$b['caption'] = '';}
	$b['astatus'] = $ostatus;

	//develop estatus during scan for errors and warnings.
	// at the end set astataus = estatus || original status
	// this preserves the old status settings.
	// status at the end.

	$b['sizekb'] = $a['sizekb'] ?: 0;

	$b['date_entered'] =  $row['date_entered'] ?: date('Y-m-d');
	$b['contributor_id'] = $row['contributor_id'] ?: Defs::$editor_id;

	$fud = $row['first_use_date'];
	if (empty($fud) || $fud == '0000-00-00') {
		$fud = $null;
	}
	$b['first_use_date'] = $fud;



	$b['type'] =  Defs::getMimeGroup($mime) ?: 'Other';
	$thumburl = $a['url'];
	if ( empty($thumburl) || $thumburl == $a['link'] ) {
		$thumburl = '';  // blank for now.  will gt written back to the b array.
	}
	$b['thumb_url'] = $thumburl;
	$b['astatus'] = 'E';


	$src = $a['link'];
		$src = preg_replace('|^/reunions|','/assets/reunions',$src);
		$src = preg_replace('|^/newsp/SalesConf|','/assets/sales_conferences',$src);
		$src = preg_replace('|^/sales_conferences|','/assets/sales_conferences',$src);
	$b['asset_url'] = $src;

	return $b;
}
