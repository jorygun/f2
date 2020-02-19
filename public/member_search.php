<?php

namespace digitalmx\flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\FileDefs;



if ($login->checkLogin(4)){
   $page_title = 'Flames Search';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START

	$admin = new MemberAdmin();

if (isset($_POST['search'])){
   if ($_POST['search'] == 'Search DB'){

	$mdata = $admin->listMembers ($_POST);
	$data = [
		'mdata' => $mdata,
		'info' => 'Found ' . count ($mdata)
	];
#u\echor($data,'data found');
	echo $templates->render('user_list',$data);

	}
	elseif ($_POST['search']== 'Search Assets'){
		$asset = new Asset();
		$name = u\safe_like($_POST['name']);
		$alist = $asset->getAssetsByName($name);
		#u\echor($alist,'assets');
		foreach ($alist as $id){
			echo $asset->getGalleryAsset($id);
		}



	}
	elseif ($_POST['search'] == 'Search News'){
	   $term = $_POST['news_name']??'';
	   $back = $_POST['back']??'';
      echo search_news($term,$back);
	}
	else {echo "Invalid Search";}

   echo "<hr><a href='/member_search.php'>New Search </a>\n";
   exit;
}



elseif (isset($_GET['uid']) && $uid = $_GET['uid']){
echo "Getting profile";
    $profile_data = $ma->getProfileData($uid);
	echo  $templates->render('profile', $profile_data);
	exit;
}

###########################################

function search_news($term,$back) {

   static $months = array(
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

	$this_year = date('Y');
	$limit_year = $this_year - $back;
	$found = 0;
	if (empty($term)){return "Invalid Search";}
	echo "Search for '$term' in newsletters published in $limit_year or later<br>";

// Open the news folder to array $files[] (only news-*.htm(l) files)
#echo file_get_contents(FileDefs::news_index_json);

 $file_list = json_decode(file_get_contents(FileDefs::news_index_json),true);



	$term = trim($term);
	$sterm = preg_quote($term,'/'); #escape regex specials
	$sterm = preg_replace('/\s+/','\s+',$sterm); // Dodge LF's in the target string. // Dodge LF's in the target string.
	$rx = '(.{0,60})\b'.$sterm.'\b(?=(.{0,60}))';

	#echo "looking for /$rx/im <br>";
   $out = '<ul>';
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
				$filenames[] = str_replace('index.php','news_amd.html',$filename);
				$filenames[] = str_replace('index.php','news_govt.html',$filename);
				$filenames[] = str_replace('index.php','news_people.html',$filename);
				$filenames[] = str_replace('index.php','news_technology.html',$filename);
				$filenames[] = str_replace('index.php','news_remember.html',$filename);

				$filenames[] = str_replace('index.php','news_modern.html',$filename);
            $filenames[] = str_replace('index.php','news_news.html',$filename);
            $filenames[] = str_replace('index.php','news_flames.html',$filename);;
            $filenames[] = str_replace('index.php','news_sad.html',$filename);;
        $filenames[] = str_replace('index.php','news_know.html',$filename);;
            $filenames[] = str_replace('index.php','news_mail.html',$filename);;
            $filenames[] = str_replace('index.php','news_fun.html',$filename);;
        }

        $found_some = 0;
   		$this_issue = '';
        foreach ($filenames as $testfile ){
            $reloc = "/newsp/$testfile";
            if (! file_exists(SITE_PATH . "/newsp/$testfile")){continue;}
            else {
             #echo "getting $show_date $testfile<br>\n";
            }

             $get_file = "/newsp/$testfile";

                $buffer = file_get_contents(SITE_PATH . "/newsp/$testfile");
               # echo "..$testfile buffered..";

               #$buffer = stristr($buffer,'<body'); #clip the buffer head
               # $buffer = preg_replace('/<!--.*?-->/', '', $buffer); #remove html comments
                $buffer = preg_replace ('/[\w\.\-]+\@[\w\.\-]+/','',$buffer); #remove emails
                $buffer = strip_tags($buffer); #remove all html tags


            // Match all occurences of the target string, plus 20 characters before and after for context
                    if ($found_count = preg_match_all("/$rx/im", $buffer, $m)){
                        $found_some = 1;

                        for($i=0;$i<$found_count;++$i){
                            $string = $m[1][$i] . "<span class='red'>$sterm</span>" . $m[2][$i];
                            $this_issue .= "...$string...<br>";
                        }
                    }



            }

		if ($found_some){
			$out .= "<li><a href='/newsp/$filename'>newsletter $show_date</a><br>
			$this_issue<br></li>";

			++$found;
	// Note if any of the matches are followed by a Picture Mark (<!--P-->)

		 }


    }
	$out .= "</ul>";

	if ($found){return "$found newsletters had '$term' in them.<br> " . $out;}
	else {return "Nothing Found.<br>";}
 }
#show search screen
?>


<hr>
<p><b>Locate a Member in the Member Database</b></p>
To find a member enter name or email address.  Partials work. Not case sensitive. Limited to 100 found.
<form  method = 'POST'>
<table >
<tr><th>Find by name: </th><th>Find by email:</th></tr>
<tr>

    <td> <input type='text' name = 'name' ></td>
    <td><input type='text' name='email'></td>
 </table>
<input type=submit name='search' value='Search DB'>
</form>

<p><b>Locate a Member in the Photo/Asset Library</b></p>
<form  method = 'POST'>
<table >
<tr><th>Find by name: </th></tr>
<tr>
    <td> <input type='text' name = 'name' ></td>
 </table>
<input type=submit name='search' value='Search Assets'>
</form>

<p><b>Locate references to a member (or any term) in past newsletters</b></p>

<form  method = 'POST'>
Enter the text you're looking for (NOT case-sensitive) and the range
of years to search in.
<table>
<tr><td>Search for<br>
 <input type="text" name="news_name" ></td>
<td>
Search In<br>
<select name='back'>
<option value=0>This year</option>
<option value="1">1 year back</option>
<option value="2">2 years back</option>
<option value="3">3 years back</option>
<option value="4">4 years back</option>
<option value="5">5 years back</option>
<option value="20">For All Time</option>
</select>
</td></tr>
</table>
<input type=submit name='search' value='Search News'>
</form>
</form>
