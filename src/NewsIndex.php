<?php
namespace digitalmx\flames;

/*  script to build news index file
	file catalogs all the newsletters in newsp and builds a data
	file of them, then uses data file to build an html ul of the 
	data when can be displayed as a collapsible list.
	
  file has 3 functions:
  rebuild database from scratch
  add item to database (new issue, for example)
  rebuild html from database.

*/

use digitalmx\flames\FileDefs;

class NewsIndex {
	
	// produces html ul file at this location:
	private static $newsindexinc=FileDefs::news_index_inc;

   //  source directory for archived newsletters
    private static $newsarchivedir = FileDefs::archive_dir;
    
    private static $json_file = FileDefs::news_index_json;

    private $file_index = array();
	
	
    function __construct() {
    	
    }
  		
  public function rebuildJson() {
  	$this->file_index = $this->buildFileList( FileDefs::archive_dir);
  	file_put_contents(FileDefs::news_index_json,json_encode($this->file_index ));
  	
  	}
      
	public function append_index ($datecode , $path) {
		$this->file_index = json_decode(file_get_contents(FileDefs::news_index_json),true);
      $this -> file_index [$datecode] = $path;
      file_put_contents(FileDefs::news_index_json,json_encode($this->file_index ));
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
      $letters=0; $lyear=0;
		$file_index = json_decode (file_get_contents(FileDefs::news_index_json),true);
		
        $listcode = "<ul class='collapsibleList' style='margin-bottom:6px;'>\n";
        foreach ($file_index as $dcode => $f){
           # echo "$dt => $f<br>\n";
           $letters++;
            $url = "/newsp/$f";
            $dt = \DateTime::createFromFormat('Ymd',$dcode);

            $year = $dt->format('Y');
            $cdate = $dt->format('M j, Y');

            if ($year <> $lyear){
                if ($lyear <>''){$listcode .= "</ul>\n";}
                $lyear = $year;
                $listcode .= "<li>$year <ul>";
            }
            $thisline = "<li style='margin-bottom:6px;'><a href='$url' target='_blank' style='text-align:left'>$cdate </a></li>\n";
         
            $listcode .= $thisline;

        }
            $listcode .= "</ul>
            </ul>
            ";

        $title = sprintf ("<h3>$letters Newsletters Indexed On %s</h3>\n", date("M j, Y"));

        $html = $title . $listcode;


        return $html;
       


    }


}



