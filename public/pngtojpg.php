<?php
#ini_set('display_errors', 1);
#ini_set('error_reporting',E_ALL);

/* One-time prog to convert thumbs from png to jpeg
*/
require HOMEPATH . 'Sites/flames/libmx/MxConstants.php';
require HOMEPATH . '/Sites/flames/libmx/MyPDO.php';

$pdo = digitalmx\MyPDO::instance();
$thumbs = SITE_PATH . '/assets/thumbs';
$limit = 3;

$getst = $pdo->prepare("select thumb_file from assets where id = ?");
$putst = $pdo->prepare( "update assets set thumb_file = ? where id = ?");

$filelist = scandir("$thumbs");
$n = 0;
echo "Found files ",count($filelist),"\n";

foreach ($filelist as $filename){
	if (! preg_match('/(\d+)\.png/',$filename,$m)){continue;}

	++$n;
	$thisid = $m[1];
	$getst->execute([$thisid]);
	$row=$getst->fetch();
	if ($row){#id exists in the db}
		$tfile = $row['thumb_file'];
		$jpgfile = "${thisid}.jpg";
		png2jpg("$thumbs/$filename","$thumbs/$jpgfile",85);
			// quality is 0 to 100 (best)

		$putst->execute([$jpgfile,$thisid]);

		echo "$n: $tfile -> $jpgfile. ";
	}
	else {echo "$n: $tfile not in db. ";}
#	unlink "$thumbs/$tfile";
	echo "$tfile deleted.\n";


	if ($limit && $n>$limit){break;}
}


function png2jpg($originalFile, $outputFile, $quality) {
    $image = imagecreatefrompng($originalFile);
    imagejpeg($image, $outputFile, $quality);
    imagedestroy($image);
}

