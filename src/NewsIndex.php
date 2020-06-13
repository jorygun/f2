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
		$this->rebuildJson();
		$this->buildHTML();
	}

  public function rebuildJson() {
  	$file_index = $this->buildFileList( FileDefs::archive_dir);
  	file_put_contents(FileDefs::news_index_json,json_encode($file_index ));

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

        while ($thisfile = readdir($dh)){
            $filename = $dtag = '';
            // first get traditional news-xxx.php files
            if (is_file ("$newsarchive/$thisfile")){
                if(preg_match('/news- ?(\d+)\.php/',$thisfile,$m)){
                    $dtag = $m[1];
                    $filename = $m[0];
                    ++$filecount;
                }
            }
            elseif (is_dir("$newsarchive/$thisfile")){
                if (preg_match('/news[-_](\d+)$/',$thisfile,$m)){
                    $dtag = $m[1];
                    $filename = "$m[0]/index.php";
                    ++$dircount;
                }
            }

        #	echo "$m[1], $m[0]\n";
            if (! empty($filename)){
                $dtags = sprintf("%06d",$dtag);
                if (substr($dtags,0,1) == '9'){$dtags = '19' . $dtags;}
                else {$dtags = '20' . $dtags;}

                $files[$dtags] = $filename; #date tag -> filename
            }


        }

        ksort ($files);
        echo "Indexed $filecount files and $dircount directories<br>\n";

        return $files;
    }



    private function jsonToHtml ($json) {
    // also sticks entry into pubs table
      $letters=0; $lyear=0;
		$file_index = json_decode (file_get_contents(FileDefs::news_index_json),true);
		;
			//clear pubs table;
	   echo count($file_index) . " records in json index" . BRNL;

		$this->pdo->query('DELETE FROM `pubs`;'); #clear the file


		$getreadssql = "SELECT read_cnt from `read_table` where issue = ?  LIMIT 1;";
		$getreadsprep = $this->pdo->prepare($getreadssql);

			//$insertsql = "INSERt INtO pubs (issue, rcount) VALUES (:issue, :rcount)";
		//ON DUPLICATE KEY UPDATE  url=values(url), pubdate=values(pubdate)";




//echo "ok" . BRNL; exit;

   $listcode = "<ul class='collapsibleList' style='margin-bottom:6px;'>\n";

   $insertsql = "INSERT INTO pubs (issue, rcount, title, pubdate, url)
		VALUES (:issue, :rcount, :title, :pubdate, :url);";
	$insertprep = $this->pdo->prepare($insertsql);
	$this->create_pubs() ;

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
            $thisline = "<li style='margin-bottom:6px;'><a href='$url' target='_blank' style='text-align:left'>$cdate </a></li>\n";

            $listcode .= $thisline;

				$reads = 0;
				if (substr($dcode,0,4) >= 2016)  {
					$oldissue =  (int)substr($dcode,2);
					try {
						if ($getreadsprep->execute([$oldissue]) ) {
							$reads = $getreadsprep->fetchColumn() ;
						}
					} catch (\PDOException $e) {
						$reads = 0;
						echo $e->getMessage(); exit;
					}
				}




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

				$pdovars = array(
					':issue' => (int)$dcode,
					':url' => $url,
					':pubdate' => $sdate,
					':rcount' => (int)$reads,
					':title' => $title,
					);

            	#u\echor($pdovars,'pdovars');
            $insertprep->execute($pdovars);
        }
            $listcode .= "</ul>
            </ul>
            ";

        $title = sprintf ("<h3>$letters Newsletters Indexed On %s</h3>\n", date("M j, Y"));

        $html = $title . $listcode;


        return $html;



    }

	private function create_pubs() {



	$sql = "
	DROP TABLE IF EXISTS `pubs`;
CREATE TABLE `pubs` (
  `issue` int(11) NOT NULL COMMENT 'yymmdd',
  `rcount` int(11) NOT NULL DEFAULT '0',
  `title` tinytext DEFAULT NULL,
  `pubdate` datetime DEFAULT NULL,
  `url` tinytext DEFAULT NULL,
  `stories` tinytext DEFAULT NULL ,
  `updated` datetime DEFAULT NULL,
  `predate` datetime DEFAULT NULL,
  PRIMARY KEY (`issue`),
  KEY `pubdate` (`pubdate`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
	$this->pdo->query($sql);

	}


}



