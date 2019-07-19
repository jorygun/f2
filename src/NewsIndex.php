<?php
/*  script to build directory index file
  *
  * need a file of newsletters for building the
  * newsletter index and also for feeding the
  * search script.
  *
  * run with any query string like ?x

*/


class NewsIndex {
	

	private $newsindexdir = '/newsp';
    private $jfile;
  	private $hfile;
   // actual source directory for archived newsletters
    private $newsarchivedir='/newsp';

    private $file_index;
	
	
    function __construct($rebuild) {
    	
    	$newsdir	= SITE_PATH . $this->newsindexdir;
   	    $this->jfile = "$newsdir/index.json";
  		$this->hfile = "$newsdir/index_inc.html";
  		$newsarchivedir = SITE_PATH . $this->newsarchivedir;
  		
   // actual source directory for archived newsletters
      
		echo "<p>NewsIndex is indexing $this->newsarchivedir.</p>" ;
      $this->file_index = array ();
	
	
        if ( (! file_exists($this->jfile)) or ($rebuild == true) ){
            echo "rebuilding json file. ";
            $this->file_index = $this->rebuild_index($newsarchivedir);
            if (empty ($this->file_index)){throw new Exception ("empty jfile");}
            $this->save_index ();
        }
       else { $this->file_index = json_decode (file_get_contents($this->jfile)); 
       	if (empty ($this->file_index)){
       		unlink ($this->jfile);
       		throw new Exception ("no jfile");
       	}
       }

    }
    private function save_index () {
        // flist is array of datecode => path
        file_put_contents($this->jfile,json_encode($this->file_index ));
    }

    private function rebuild_index($newsarchive) {
        $dh = opendir($newsarchive);
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

    public function append_index ($datecode , $path) {
        $this -> file_index [$datecode] = $path;
         $this->save_index();
    }

    public function build_html() {
      $letters=0; $lyear=0;

        $listcode = "<ul class='collapsibleList' style='margin-bottom:6px;'>\n";
        foreach ($this->file_index as $dcode => $f){
           # echo "$dt => $f<br>\n";
           $letters++;
            $url = "/newsp/$f";
            $dt = DateTime::createFromFormat('Ymd',$dcode);

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


        file_put_contents($this->hfile,$html);
        #echo "<pre>\n", htmlentities($html), "</pre>\n";



    }


}



