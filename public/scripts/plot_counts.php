<?php
	#Script reads the count file generated in /output
	# and produces a graphic in the same directory.



use davefx\phplot;
	

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


#$count_file = SITEPATH . "/views_data/reads.txt";
$out_file = SITEPATH . "/graphics/views_2016.png";

#update the access counts
#get the last 52 entries, then reorder Ascending.
$sql = "SELECT * FROM (
    SELECT `issue`,`read_cnt` FROM `read_table` ORDER BY issue DESC LIMIT 60
    ) as t
    ORDER by t.issue;";
$pdo = MyPDO::instance();

 $result = $pdo->query($sql);

$dString = '';
foreach($result as $row){
    list($issue,$count) = $row;
   # echo "$issue -> $count<br>";
   #was saving as a file; now just continuing on.
    $dString .= sprintf("%d\t%d\n",$issue,$count);
    $dArray[]=array($issue,$count);
}

// file_put_contents($count_file,$dString);
// 
// 
// #$file = fopen($count_file, 'r');
// #while (($dline = fgetcsv($file,0,"\t")) !== FALSE) {
foreach ($dArray as $dline){
  //$line is an array of the elements
    if ($dline[0]=='999999'){$dline[0]='Preview';}
    else{
		$mono = substr($dline[0],2,2);
		$dayno = substr($dline[0],4,2);
		$moname= $month[$mono];
		$dline[0] = "$moname $dayno";
	}

  $data[]=$dline;
}

#draw the graph

$plot = new PHPlot(800,600);
$plot->SetDataValues($data);
$plot->SetTitle('Views By Issue Last 60 Issues ');

$plot->SetXTitle('Issue');
$plot->SetYTitle('Views');

$plot->SetPlotType('Bars');
$plot->SetDataType('text-data');

$plot->SetOutputFile($out_file);
#$plot->SetPrintImage(0);

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

$plot->SetIsInline(1);
$plot->DrawGraph();

?>
