<?php
//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(3)){exit;}
//END START



/*
File from old site, mostly
Called by Archive.htm with target = text to search for.
 If target is empty, list all .htm files in news folder.
 If target is valid, list .htm files that contain the target text.
 (Text found after the "Revisions" marker is ignored because
  of repeats.)
*/

$months = array(
	'01' => 'Jan',
	'02' => 'Feb',
	'03' => 'Mar',
	'04' => 'Apr',
	'05' => 'May',
	'06' => 'Jun',
	'07' => 'Jul',
	'08' => 'Aug',
	'09' => 'Sep',
	'10' => 'Oct',
	'11' => 'Nov',
	'12' => 'Dec',
	);


 ?>
 <!DOCTYPE html>
 <html>
 <head>
 <title>Search Newsletters</title>
 <link rel='stylesheet' href='/css/flames2.css'>
 <style type='text/css'>
 .red {color:red;}
 </style>
 </head>
 <body>


<?php
$nav = new navBar(false);
$navbar = $nav -> build_menu();

echo $navbar;

$term=''; $back='';
if ( isset ($_GET['term']) && $term = urldecode($_GET['term'])){ #gprocess
	$back = $_GET['back'];

	$this_year = date('Y');

	$limit_year = $this_year - $back;

	$found = 0;
	echo "Search for '$term' in newsletters published in $limit_year or later<br>";


// Open the news folder to array $files[] (only news-*.htm(l) files)

 $file_list = json_decode(file_get_contents(SITE_PATH . "/newsp/index.json"));


	$term = trim($term);
	$sterm = preg_quote($term,'/'); #escape regex specials
	$sterm = preg_replace('/\s+/','\s+',$sterm); // Dodge LF's in the target string. // Dodge LF's in the target string.
	$rx = '(.{0,60})\b'.$sterm.'\b(?=(.{0,60}))';

	echo "looking for /$rx/im <br>";



	 foreach ($file_list as $dt => $filename) {
		if (!$filename){continue;}

		$year = substr($dt,0,4);
		$month = substr($dt,4,2);
		$month_name = $months[$month];
		$day = substr($dt,6,2);

		if ($year < $limit_year){continue;}

		$show_date = "$month_name $day, $year";

        $filenames = array($filename); #array of files to look in
        if (stripos($filename,'index.php')>0){
            $testfile = $filename;
            $filenames[]=$filename;
            $filenames[] = str_replace('index.php','updates.html',$filename);

            $filenames[] = str_replace('index.php','news_news.html',$filename);
            $filenames[] = str_replace('index.php','news_flames.html',$filename);;
            $filenames[] = str_replace('index.php','news_sad.html',$filename);;
        $filenames[] = str_replace('index.php','news_know.html',$filename);;
            $filenames[] = str_replace('index.php','news_mail.html',$filename);;
            $filenames[] = str_replace('index.php','news_site.html',$filename);;
        }

        $found_some = 0;
       echo "<ul>\n";
        foreach ($filenames as $testfile ){
            $reloc = "/newsp/$testfile";
            if (! file_exists(SITE_PATH . "/newsp/$testfile")){continue;}
            else {
             #echo "getting $testfile<br>";
            }

             $get_file = "/newsp/$testfile?s=Q21kr1";

                #echo "curling for $this_url<br>";
            // try curl instead of just reading file, so it has less junk and more content
            // 	curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1,CURLOPT_URL => $this_url));
            //
            // 	if(!$buffer = curl_exec($curl)){
            //     	die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
            // 	}


                $buffer = file_get_contents(SITE_PATH . "/newsp/$testfile");
               # echo "..$testfile buffered..";

               #$buffer = stristr($buffer,'<body'); #clip the buffer head
               # $buffer = preg_replace('/<!--.*?-->/', '', $buffer); #remove html comments
                $buffer = preg_replace ('/[\w\.\-]+\@[\w\.\-]+/','',$buffer); #remove emails
                $buffer = strip_tags($buffer); #remove all html tags


            // Match all occurences of the target string, plus 20 characters before and after for context
                    if ($found_count = preg_match_all("/$rx/im", $buffer, $m)){
                        $found_some = 1;
                       # print_r ($match); echo "<br>\n";
                       // # $match0 = $match[0];
//                         foreach ($match0 as $matchphrase){
//                             echo "...$matchphrase...<br>";
//                         }
                        for($i=0;$i<$found_count;++$i){
                            $string = $m[1][$i] . "<span class='red'>$sterm</span>" . $m[2][$i];
                            echo "...$string...<br>";
                        }
                    }

                # $found = preg_match_all("/$sterm/", $buffer, $match);


            }

		if ($found_some){

			echo "<li><a href='/newsp/$filename'>newsletter $show_date</a><br><br></li>";
			++$found;
	// Note if any of the matches are followed by a Picture Mark (<!--P-->)

		 }
			echo "</ul>";

    }


	if ($found){echo "$found newsletters had '$term' in them.<br>";}
	else {echo "Nothing Found.<br>";}
 }



?>
<hr>
<h3>Search for a term in old newsletters. </h3>
<p>Note: this script literally opens every newsletter and scans it for the text<br>
you're looking for.  So you might want to top off your beer while you're waiting.</p>

<form method="GET" >
<table><tr><td>
Enter the text you're looking for:
(NOT case-sensitive)</td><td> <input type="text" name="term" value=<?=$term?>></td></tr>
<tr><td>To reduce the number of CPU seconds <br>
on our trustworthy IBM 360, <br>
you might limit the years<br>
you're interested in:</td><td>
Search <select name='back'>
<option value=0>This year</option>
<option value="1">1 year back</option>
<option value="2">2 years back</option>
<option value="3">3 years back</option>
<option value="4">4 years back</option>
<option value="5">5 years back</option>
<option value="20">For All Time</option>
</select>
</td></tr>
<tr><td></td><td>
<input type=submit></td></tr></table>
</form>

</div></body></html>

