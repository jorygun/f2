<?php
namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = 'Views By Issue';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START

echo <<<EOT
<h4>Count of views by issue date</h4>
<p>(started Jan 18, 2016)</p>
EOT;

//EOF



$login->checkLevel(4);
//
// $page_title = 'Views By Issue';
// $page_options=[]; #ajax, votes, tiny
//
// $page = new DocPage($page_title);
// echo $page -> startHead($page_options);
// # other heading code here
//
// echo $page->startBody();
//

//END START
$pdo = $container['pdo'];


$month = array(
	'01'=>'Jan',
	'02'=>'Feb',
	'03'=>'Mar',
	'04'=>'Apr',
	'05'=>'May',
	'06'=>'Jun',
	'07'=>'Jul',
	'08'=>'Aug',
	'09'=>'Sep',
	'10'=>'Oct',
	'11'=>'Nov',
	'12'=>'Dec'
);


#$count_file = SITE_PATH . "/views_data/reads.txt";
$out_file = SITE_PATH . FileDefs::view_chart_url;

#update the access counts
#get the last 52 entries, then reorder Ascending.
$sql = "SELECT DATE_FORMAT(pubdate,'%m/%d'), rcount FROM issues
	WHERE pubdate >= DATE_SUB(NOW(),INTERVAL 1 year)
    ORDER by pubdate DESC;";

$result = $pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
//u\echor ($result);
foreach ($result as $date=>$val){
	//$moday = date('m/d',$date);
	$data[] = [$date,$val];
}
//u\echor($data); exit;
//
// foreach ($dArray as $dline){
//   //$line is an array of the elements
//     if ($dline[0]=='999999'){$dline[0]='Preview';}
//     else{
// 		$mono = substr($dline[0],2,2);
// 		$dayno = substr($dline[0],4,2);
// 		$moname= $month[$mono];
// 		$dline[0] = "$moname $dayno";
// 	}
//
//   $data[]=$dline;
// }

#draw the graph

$plot = new \PHPlot(800,600);
$plot->SetDataValues($data);
$plot->SetTitle('Views By Issue Last 60 Issues ');

$plot->SetXTitle('Issue');
$plot->SetYTitle('Views');

$plot->SetPlotType('Bars');
$plot->SetDataType('text-data');

//$plot->SetOutputFile($out_file);
$plot->SetPrintImage(false);

$plot->SetXTickLabelPos('none');
$plot->SetXTickPos('none');
$plot->SetFont('x_title', '3');
$plot->SetFont('y_title', '3');
$plot->SetFont('x_label', '3');
$plot->SetFont('y_label', '3');
$plot->SetXLabelAngle(90); #vertical text
$plot->TuneYAutoTicks(0,'decimal',1); #integers
#$plot->SetYDataLabelPos('plotin');
#$plot->SetYTickLabelPos('none');
#$plot->SetYTickPos('none');
$plot -> SetShading(0);

$plot->SetIsInline(true);
$plot->DrawGraph();

?>
<img src="<?php echo $plot->EncodeImage();?>" alt="Plot Image">









