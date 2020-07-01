<?php

namespace DigitalMx\Flames;
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLevel(4)){
   $page_title = 'Flames Search';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

//END START

	$admin = $container['membera'];
	$assets = $container['assets'];
	$asseta = $container['asseta'];
	$pdo = $container['pdo'];
	$thumbs = $container['assetv'];


if (isset($_POST['search'])){
   if ($_POST['search'] == 'Search DB'){

	$mdata = $admin->listMembers ($_POST);
	$data = [
		'mdata' => $mdata,
		'info' => 'Found ' . count ($mdata)
	];
#u\echor($data,'data found');
	echo $container['templates']->render('user_list',$data);

	}
	elseif ($_POST['search']== 'Search Assets'){

		$name = $_POST['name'];
		$alist = $assets->getAssetListByName($name);
		echo "<h4>Assets with '$name'  </h4>";;
		echo "<div class='asset-row'>";
		#u\echor($alist,'assets');
		foreach ($alist as $id){
			echo $thumbs->getAssetBlock($id,'small',false);
		}
		echo "</div><div class='clear'></div>";


	}
	elseif ($_POST['search'] == 'Search News'){
	   $term = $_POST['news_name']??'';
	   $back = $_POST['back']??'';
      echo search_news($term,$back,$pdo);
	}
	else {echo "Invalid Search";}

   echo "<hr><a href='/search.php'>New Search </a>\n";
   exit;
}



elseif (isset($_GET['uid']) && $uid = $_GET['uid']){
echo "Getting profile";
    $profile_data = $ma->getProfileData($uid);
	echo  $container['templates']->render('profile', $profile_data);
	exit;
}

###########################################

function search_news($term,$back,$pdo) {

	$this_year = date('Y');
	$limit_year = $this_year - $back;
	$found = 0;
	if (empty($term)){return "No search term";}
	echo "<h3>Search for '$term' in newsletters published in $limit_year or later</h3>";

// get the urls for the newsletters to search
	$sql = "SELECT issue,url,DATE_FORMAT(pubdate,'%M %d, %Y') as pdate from pubs WHERE pubdate > '${limit_year}-01-01' ";

 	if(! $issuest = $pdo->query($sql)->fetchAll() ) {
 		die ("No issues found");
 	}

 // set up search term.  will use grep
	$sterm = trim($term);
	//$sterm = preg_quote($term,'/'); #escape regex specials
	$issue_count = 0;
	$last_issue = '';
	$hits = [];

	foreach ($issuest as $issued) {
		// set vars
		$issue = $issued['issue'];
		if (!empty($hits) && $last_issue && $last_issue != $issue){
			// get context
			echo "<a href='$url' target='news'> Issue $last_issue ($hdate) </a>: " . BR;
			foreach ($hits as $hit){
				$context = shell_exec("grep  -hi '$sterm' $hit"); // get context, multiline
				echo nl2br(strip_tags($context)) . BR ;
			}
			echo BRNL;
			++$issue_count;
			$hits = [];
		}

		foreach (['url','pdate'] as $var){
			$$var = $issued[$var];
		}

		$hdate = date('M d, Y',strtotime($pdate));

		$search_path = FileDefs::shared_dir . $url; // file or  folder

		//echo "Searching in $search_path" . BR;

/*
		$files =  exec "grep -Ril $sterm $search"
			foreach $files as $file {
				print file info
				grep -C1 $sterm $file
				print results
			}
		}
*/
		$exec = "grep -iRl --include '*.html'  '$sterm' $search_path ";
		//$exec = "grep -iRl --include '*.html'  'springer' .* ";
		//grep -iRl --include "*.html" 'springer' .*
		//echo $exec . BRNL;
		$hit = exec($exec); // file with matching term
		if (!empty($hit)) {$hits[]=$hit;}
		$last_issue = $issue;

// 		  if ( $files = exec($exec ) ) {
// 		 	$filesall[$url][] = explode("\n",$files);
// 		 }




	}
/*
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
    */


	if ($issue_count){return "$issue_count newsletters had '$term' in them.";}
	else {return "Nothing Found.<br>";}
 }
#show search screen
?>


<hr>
<h3>Search For Members or Topics </h3>

<h4>Locate a Member in the Member Database</h4>
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
<hr>
<h4>Locate a Member in the Photo/Asset Library</h4>
<form  method = 'POST'>
<table >
<tr><th>Find by name: </th></tr>
<tr>
    <td> <input type='text' name = 'name' ></td>
 </table>
<input type=submit name='search' value='Search Assets'>
</form>
<hr>
<h4>Locate references to a member (or any term) in past newsletters</h4>

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
