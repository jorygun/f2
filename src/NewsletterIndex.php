<?php
namespace DigitalMx\Flames;

/*  script to build directory index file
  *
  * need a file of newsletters for building the
  * newsletter index and also for feeding the
  * search script.
  *
  * run with any query string like ?x

*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


class NewsletterIndex {

    private $ndir = SITE_PATH . "/newsp";
    private $jfile = SITE_PATH . "/newsp/index.json";
    private $hfile = SITE_PATH . "/newsp/index_inc.html";
    private $newsfile = SITE_PATH . "/news/index_inc.html";

    private $file_index = array ();

    function __construct($rebuild=false) {
        echo "<p>Indexing</p>" ;
        // chec all files
          if ( $rebuild==true or ! file_exists($this->jfile) ){
            // rebuild index
            echo "Rebuilding index". "<br>\n";
               $this->file_index = $this->rebuild_index($this->ndir);
            }

        else {$this->file_index = json_decode (file_get_contents($this->jfile));}

        if (empty ($this->file_index))
        {
        $this->file_index = $this->rebuild_index($this->ndir);
        if (empty ($this->file_index)){ throw new Exception ("Unable to build index");}
        }
        // now have index_file or thrown exception


        if ( (! file_exists($this->hfile)) or (filemtime($this->hfile) < filemtime($this->jfile) ) )
        {
          $this->rebuild_html();
        }

    }


    private function rebuild_index($ndir) {
        $dh = opendir($this->ndir);
        $filecount=$dircount=0;

        while ($thisfile = readdir($dh)){
            $filename = $dtag = '';
            // first get traditional news-xxx.php files
            if (is_file ("$ndir/$thisfile")){
                if(preg_match('/news- ?(\d+)\.php/',$thisfile,$m)){
                    $dtag = $m[1];
                    $filename = $m[0];
                    ++$filecount;
                }
            }
            elseif (is_dir("$ndir/$thisfile")){
                if (preg_match('/news[-_](\d+)$/',$thisfile,$m)){
                    $dtag = $m[1];
                    $filename = "$m[0]/index.php";
                    ++$dircount;
                }
            }

        #	echo "$m[1], $m[0]\n";
            if (! empty($filename)){
                #adjust date for century
                $dtags = sprintf("%06d",$dtag);
                if (substr($dtags,0,1) == '9'){$dtags = '19' . $dtags;}
                else {$dtags = '20' . $dtags;}

                $files[$dtags] = $filename; #date tag -> filename
            }


        }

        ksort ($files);
        echo "Indexed $filecount files and $dircount directories<br>\n";
        if (empty ($files)){
            throw new Exception ("Cannot index files in news archive.");
            return false;
        }

        file_put_contents($this->jfile,json_encode($files) );
        return $files;
    }



    private function rebuild_html() {
      $letters=0; $lyear=0;

        $listcode = "<ul class='collapsibleList'>\n";
        ;
        foreach ($this->file_index as $dcode => $f){
           $letters++;
            $url = "/newsp/$f";
            try {

           if (! ($dt = \DateTime::createFromFormat('Ymd',trim($dcode) )  ) )
                {throw new Exception ("Cannot create dt from $dcode");}
            }
            catch (Exception $e){
                echo $e->getMessage() . BRNL;
                echo "$dcode => $f<br>\n";
                var_dump(DateTime::getLastErrors());
                exit;
            }
            $year = $dt->format('Y');
            $cdate = $dt->format('M j, Y');

            if ($year <> $lyear){
                if ($lyear <>''){$listcode .= "</ul>\n";}
                $lyear = $year;
                $listcode .= "<li>$year <ul>";
            }
            $thisline = "<li><a href='$url' target='_blank' style='text-align:left'>$cdate </a></li>\n";

            $listcode .= $thisline;

        }
            $listcode .= "</ul>
            </ul>
            ";

        $title = sprintf ("<h3>$letters Newsletters Indexed On %s</h3>\n", date("M j, Y"));

        $html = $title . $listcode;

        file_put_contents($this->hfile, $html);
        file_put_contents($this->newsfile,$html);

        #echo "<pre>\n", htmlentities($html), "</pre>\n";

    }


}



