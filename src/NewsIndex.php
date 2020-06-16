<?php

namespace Digitalmx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;

use DigitalMx\Flames\FileDefs;
use DigitalMX\MyPDO;
require 'read_functions.php';

//END START



//EOF


/*  script to build news index file
	file catalogs all the newsletters in newsp and builds a data
	file of them, then uses data file to build an html ul of the
	data when can be displayed as a collapsible list.

  file has 3 functions:
  rebuild database from scratch
  add item to database (new issue, for example)
  rebuild html from database.

*/



class NewsIndex {

	// produces html ul file at this location:
	private static $newsindexinc=FileDefs::news_index_inc;

   //  source directory for archived newsletters
    private static $newsarchivedir = FileDefs::archive_dir;

    private static $json_file = FileDefs::news_index_json;

    private $file_index = array();
	private $pdo;

    function __construct() {
		$this->pdo= MyPDO::instance();

    }

	public function rebuildAll() {
		echo "Rebuild indexes at " . date('H:i') . BRNL;
		$this->rebuildJson();
		$this->buildHTML();
	}

  public function rebuildJson() {
  	$json = $this->buildFileList( FileDefs::archive_dir);
  	$test = json_decode($json ,true);
  	$testc = count($test);
  	echo "$testc items in json file" . BRNL;

  	file_put_contents(FileDefs::news_index_json,$json);

  	}

	public function append_index ($datecode , $path) {
		$file_index = json_decode(file_get_contents(FileDefs::news_index_json),true);
      $file_index [$datecode] = $path;
      file_put_contents(FileDefs::news_index_json,json_encode($file_index ));
      $this->buildHTML();

    }

	public function buildHTML() {
		$html = $this->jsonToHtml(FileDefs::news_index_json);
		file_put_contents(FileDefs::news_index_inc,$html);
	}



    private function buildFileList($newsarchive) {

    	echo "<p>NewsIndex is indexing $newsarchive.</p>" ;
       if (! $dh = opendir($newsarchive) ){
       	die ("No dir at $newsarchive");
       }
        $filecount=$dircount=0;
		$files = [];
        while ($thisfile = readdir($dh)){

            $filename = $dtag = $dtagstr = '';
            // first get traditional news-xxx.php files
            if (is_file ("$newsarchive/$thisfile")){
                if(preg_match('/news[-_](\d+)\.php/',$thisfile,$m)){
                    $dtag = $m[1];
                    $filename = $m[0];
                    ++$filecount;
                }
            }
            elseif (is_dir("$newsarchive/$thisfile")){
                if (preg_match('/news[-_](\d+)\/?$/',$thisfile,$m)){
                    $dtag = $m[1];
                    $filename = "$m[0]/";
                    ++$dircount;
                }
            } else { continue; } // what could it be?

			 if (empty($dtag) ){
            	//echo "No dtag on $filename" . NL;
            	continue;
            }
            if (empty($filename)){
            	echo "No filename on $dtag" . NL;
            	continue;
            }

				$dtagstr = sprintf("%06d",$dtag);
				if ($dtagstr != $dtag) {echo "changed dtag $dtag" . NL;}
				// is yymmdd; change to Ymd
				 if (substr($dtagstr,0,1) == '9'){$dtagstr = '19' . $dtagstr;}
				 else {$dtagstr = '20' . $dtagstr;}

				if (!empty($files[$dtagstr]) ) {
					echo "Duplicate dtag: $dtag old : new \n" . $files[$dtagstr] ." : " . $thisfile . NL;
				}
				 $files[$dtagstr] = $filename; #date tag -> filename
        }

        ksort ($files);
        $total_items = $filecount + $dircount;
        $fcount = count($files);
        echo "Indexed $filecount files + $dircount directories = $total_items items." . BRNL;
        echo $fcount . " Entries in file array" . NL;


        return json_encode($files);
    }



    private function jsonToHtml ($json) {
    // also sticks entry into pubs table
      $letters=0; $lyear=0;
		$file_index = json_decode (file_get_contents($json),true);

			//clear pubs table;
	   echo count($file_index) . " records in json index" . BRNL;

		$getreadssql = "SELECT read_cnt from `read_table` where issue = ?  LIMIT 1;";
		$getreadsprep = $this->pdo->prepare($getreadssql);

			//$insertsql = "INSERt INtO pubs (issue, rcount) VALUES (:issue, :rcount)";
		//ON DUPLICATE KEY UPDATE  url=values(url), pubdate=values(pubdate)";




//echo "ok" . BRNL; exit;

   $listcode = "<ul class='collapsibleList' style='margin-bottom:6px;'>\n";


        foreach ($file_index as $dcode => $f){
           // echo "$dcode => $f<br>\n";
           $letters++;
            $url = "/newsp/$f";
            if(! $dt = \DateTime::createFromFormat('Ymd',$dcode) ){
            	echo "Cannot convert date on $dcode:$f" . BRNL;
            	continue;
            }

            $year = $dt->format('Y');
            $cdate = $dt->format('M j, Y');
            $sdate = $dt->format('Y-m-d');

            if ($year <> $lyear){
                if ($lyear <>''){$listcode .= "</ul>\n";}
                $lyear = $year;
                $listcode .= "<li>$year <ul>";
            }
            $thisline = "<li style='margin-bottom:6px;'><a href='$url' target='news' style='text-align:left'>$cdate </a></li>\n";

            $listcode .= $thisline;


// look for title file if the url is a subdir of newsp
				$title = '';
				$dir = dirname($url);
				if (substr($dir,-5) != 'newsp') {
					#echo "path $dir" . BRNL;
						$titlefile = SITE_PATH . $dir . '/title.txt';
						#echo "looking for $titlefile" . BRNL;
						if (file_exists($titlefile)) {
							$title = file_get_contents($titlefile);
						}
				}


        }
            $listcode .= "</ul>
            </ul>
            ";

        $title = sprintf ("<h3>$letters Newsletters Indexed On %s</h3>\n", date("M j, Y"));

        $html = $title . $listcode;


        return $html;



    }


}
//EOT



