<?php
//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
#	if (f2_security_below(2)){exit;}
//END START

echo <<<EOT
 <!DOCTYPE html>
 <html>
 <head>
 <title>Search Newsletters</title>

 </head>
 <body>

EOT;

if ($_SERVER[REQUEST_METHOD] == 'GET'){
    show_form($params);

}
else {
#if post...
    $params = process_form();
    search ($params);
}
?>
</pre></body></html>
<?
#############################

function search($params){
        global $document_extensions;
        global $image_extensions;
        global $mmm_extensions;

        require_once 'asset_functions.php';


	$back = 0;

	$this_year = date('Y');

	$found = 0;
	if (! $limit_year = $params[year]){
	    if ($params[all_years]){$limit_year = 'ALL';}
	    else {die ("No years specified");}
	}
	$type = $params[ext];

    if ($type == 'doc'){$extensions = implode('|',$document_extensions);}
    elseif ($type == 'mmm'){$extensions = implode('|',$mmm_extensions);}
    elseif ($type == 'img'){$extensions = implode('|',$image_extensions);}
    elseif ($type == 'all'){
        $extensions =
        implode('|',array_merge($document_extensions,$image_extensions,$mmm_extensions))
            ;}
    else {die ("No extensions requested");}


	echo "Search for $type extensions in newsletters published in y=$limit_year<br>\n";

// Open the news folder to array $files[] )
$format = "%6d\t%s\t%s\t%s\t%s\t%s\n";
 $news_index = json_decode (file_get_contents(SITE_PATH . "/newsp/index.json"));
 $result_file = SITE_PATH . "/assets/search_${type}_${limit_year}.txt";
 $tag_count = 0;
 $logfh = fopen($result_file,'w');

$filenames = array();


$filecount=$checkedcount =0;

echo "<pre>\n";
	 foreach ($news_index as $dt=>$filename){
	    ++$filecount;
		if (!$filename){continue;}
        // $dto = DateTime::createFromFormat('Ymd', $dt);
//         $sql_date = $dto->format('Y-m-d');
		$year = substr($dt,0,4);
		$month = substr($dt,4,2);
		$day = substr($dt,6,2);
		$sql_date = "$year-$month-$day";
		if ($limit_year != 'ALL'){if($year != $limit_year){continue;}}
        ++$checkedcount;
#echo "> $filename... \n";
        $dirinfo = pathinfo($filename)['dirname'];

        if(substr($dirinfo,0,1)=='.'){$thisdir = '/newsp';}
        else {$thisdir = "/newsp/$dirinfo";}



        $thisfile = pathinfo($filename)['basename'];

# echo "dirinfo $dirinfo, thisdir $thisdir, thisfile $thisfile\n";

        $thispath = "/newsp/$filename";
        $adminlog = '?s=VQk9P13146';
        $thisurl = "https://amdflames.org/newsp/$filename" . $adminlog;

           # try curl instead of just reading file, so it has less junk and more content

            $curlresult = get_url_data($thisurl);

            $page = false;
            $page = $curlresult[content];
            if ($page === false) {
                	echo "Curl Error on $thisurl:  $curlresult[errmsg]  - Code:  $curl_result[errno]" ;
                	continue;
            }

#echo "content: \n" ,htmlentities($page); exit;
     #echo "loaded contents \n";
        	#$page = mb_convert_encoding($page, 'OLD-ENCODING','UTF-8' ); #convert to utf-8

        	preg_match('/^(.*?)<[^>]*?body[^>]*?>(.*)$/msi',$page,$mp);
        	$header = $mp[1];
        	$buffer = $mp[2];
        	/*
        	$pagesplit = explode('<body',$page,2);
        	#print_r($pagesplit); exit;
        	list ($header,$buffer) = $pagesplit;
           */
          # echo 'head: ' . strlen($header) . ' buff: ' . strlen($buffer);
           $buffer = preg_replace('/<!--.*?-->/ms', '', $buffer); #remove html comments
#echo "<code>" . htmlentities($buffer) . "</code>";
	/*	if ($matches = preg_match_all("/(?=(.{1,180}))<img .*?src[ =\'\"]+(.*?)[\'\"].*?>(?=(.{1,180}))/msi", $buffer, $m,PREG_PATTERN_ORDER)){
		#print_r ($m);
*/


    #find selected extension INSIDE tags and replace the tag with [**] >



    $buffer1 =preg_replace('/\<[^>]+?(\/?[\w\.\-\:\/\%]+)\.(' . $extensions . ').*?\>/msi',"[##$1.$2##]",$buffer,-1,$rcount);

    if (!$rcount){continue;}
     echo "$rcount hits in $filename<br>";
 #  echo $buffer1; exit;

    #now remove all the html tags
   $buffer2 = strip_tags($buffer1);
   $buffer2 = preg_replace('/\s\s+/',' ',$buffer2);
	$buffer2 = preg_replace('/[\n\r]+/',' ',$buffer2);
  # echo '</pre>',$buffer2; exit;



   #now pull out the tags and the surrounding text
   if ($matches =
    preg_match_all("/(.{0,80}?)\[##(.*?)##\](?=(.{0,80}))/msi",$buffer2, $m,PREG_PATTERN_ORDER)) {

        #process if matches found
		#echo "Found match tags: $matches<br>" . print_r($m) . '<br>';

		    $dups = 0;
			for ($i=0;$i<$matches;++$i){
				$ilink = $m[2][$i];
				#$ilink = preg_replace("|http://(www\.)?flamesite.org|","$thisdir",$ilink);
                #$ilink = preg_replace("|http://(www\.)?amdflames.org|",'',$ilink);
                 $ilink =  html_entity_decode($ilink);
                $ilink = urldecode($ilink);


				#if (substr($ilink,0,9) == '/graphics'){continue;}
				#if (substr($ilink,0,7) == '/assets'){continue;}
				if (substr($ilink,-15) == 'logo-FLAMEs.gif'){continue;}


				if (substr($ilink,0,4) == 'http'){$ref = $ilink;}
				elseif (substr($ilink,0,2) == './'){$ref = "$thisdir/" . substr($ilink,2);}
				elseif (substr($ilink,0,1) == '/'){$ref = "$ilink";}



				else {$ref = "$thisdir/$ilink";}

    		    $linkext = pathinfo($ref,PATHINFO_EXTENSION);


				if (in_array($ref,$refhistory)){++$dups;continue;} #repeat entry
				 $refhistory[] = $ref;


#		echo "ilink: $ilink -> ref: $ref .. ";
                $test=$error='';
                if ($ref == '/newsp/'){continue;}
				if (substr($ref,0,1)=='/'){
				    if ($ilink == '/'){continue;}
				    if ( file_exists("$GLOBALS[sitepath]$ref")){ $test = "      found $ref";}
				    else {
				        #try adding a newsp to it
				        if (file_exists("$GLOBALS[sitepath]/newsp/$ref")){
				            $oldref = $ref;
				            $ref = "/newsp" .$ref;
				            $test = "*** R    Replaced $oldref with $ref";
				        }
				        else {$test = "*** **** <span style='color:red'>Cannot find local $ref</span>";}
				    }

				}
				else{
				    $ref2 = preg_replace("|http://(www\.)?flamesite.org|",'/newsp',$ref,-1,$m);
				    if ($m>0){
				    #echo "ref2 $ref2 <br>\n";
                        if (file_exists("$GLOBALS[sitepath]$ref2")){
                            $oldref = $ref;
                            $ref = $ref2;
                            $test = "*** R  Replaced $oldref with $ref";
                        }
                        else {$test = "*** **** <span style='color:red'>Cannot find after removing flamesite ref $ref</span>";}
				    }
                    else {$test = "    ignored external href";}
                }
                $error = '';
				if (substr($test,0,3)=='***'){
				    $error = $test;
				    ++$error_count;
				}

            #now see if it's already in the database
            $refenc = mysqli_real_escape_string($GLOBALS['DB_link'],$ref);
            $sql2 = "Select id from assets where url = '$refenc';";

             $dupres = mysqli_query($GLOBALS['DB_link'],$sql2);
            if (mysqli_num_rows($dupres) != 0){
                $duprow = mysqli_fetch_assoc($dupres);
                $dupid = $duprow['id'];
                ++$already_in;
                echo "       already in database: id $dupid for $ref<br>";
                continue;
            }
				#$ilink = preg_replace('|/\.?/|','/',$ilink); #remove extra slashes/dots
    #   echo "ilink: $ilink\n";
    /* was context section here, capturing tex before and after
        ythe image link, but it can overlap with another image,
        so dropping it.  Wasn't very helpful anyway.
*/
				$c1 = $m[1][$i];
					#$c1 = hte(strip_tags(trim($c1)));
					$c1trim = max(80,strlen($c1));
					$c1 = substr($c1,-$c1trim); #last 250 chars
		#echo "c1: $c1<br>";

				$c2 = $m[3][$i];
					#$c2 = hte(strip_tags(trim($c2)));

#echo "c2: $c2<br>";
				$context = $c1 . ' <span style="color:green;">[link]</span> ' . $c2;




				$image = "<img src='$ref'>";

				$record = sprintf ("$format", $tag_count,$thispath,$ref,$sql_date,$context,$error);
				echo $record;
				echo "<span style='color:$mmlink;'>$test</span>\n";$test='';
				if (empty($error)){fwrite($logfh,$record);++$tag_count;}

			} #end of recording matche
			if ($dups>0){echo "       (Plus $dups duplicate entries)\n";}

    } #end of finding matches

} #end while file
	echo "<p>$tag_count tags found; $checkedcount of $filecount possible files checked. $already_in files already in db. $error_count error entries not written to output.</p>\n";

} #end of function

function show_form(){
    echo <<<EOT
        <form method='POST'>
        Enter Year to search in:
        <input type='text' name='year' id='year' onfocus='form.all.checked = false;'><br>
        <input type=checkbox name='all_years' id='all' onclick='form.year.value="";'>All Years<br>
        <p>Search for: <input type=radio name='ext' value='doc'>documents
            <input type=radio name='ext' value='img'>graphics
            <input type=radio name='ext' value='mmm'>audio/video
            <input type=radio name='ext' value='all'>All
            </p>
        <input type=submit>
        </form>
EOT;
}
    function process_form(){
        $params = $_POST;
        // <input type='text' name='year'><br>
//         <input type=checkbox name='all_years'>All Years<br>
//         <p>Search for: <input type=radio name='ext' value='doc'>documents
//             <input type=radio name='ext' value='img'>graphics
//             <input type=radio name='ext' value='mmm'>audio/video
//             <input type=radio name='ext' value='all' checked>All

        return $params;
    }
?>


