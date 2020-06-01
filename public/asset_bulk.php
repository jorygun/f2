<?php
namespace DigitalMx\Flames;
ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



if ($login->checkLevel(0)){
   $page_title = 'Bulk Asset Uploader';
	$page_options=[]; #ajax, votes, tiny

	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here

	echo $page->startBody();
}

$asseta = $container['asseta'];
	$templates = $container['templates'];

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $dir = $_POST['dir'];
        $dirpath = SITE_PATH . $dir;
        $title_file = 'titles.txt';
        $title_path = "$dirpath/$title_file";

        if (empty($filelist = get_files($dirpath,[$title_file]))) {
        	die ("No files in $dir: ");
		}

        $titles = get_titles($title_path);

        $new_ids = save_assets($dirpath,$filelist,$titles);

    	echo list_report($new_ids);

    #check to make sure directory is empty
    #files are removed by renaming in the accept_file function
    $remain = 0;
    if (!empty($filelist = get_files($dirpath,[$title_file]) ) ){
    	foreach ($filelist as $file){
            echo "File remaining: $file" . BRNL;
            ++$remain;
   		 }
   		echo "Directory $dir retained because not empty." . BRNL;
   	}
   	else {
     unlink (SITE_PATH . $dir . "/" . $title_file);
     rmdir ("$dirpath");
     echo "Directory $dir removed from server." . BRNL;
    }

   echo "Done." . BRNL;
}


else {#new item
    $itemdata = array(); #store data to display
    $itemdata['date_entered'] = u\sqlnow();
    $itemdata['contributor'] = $_SESSION['login']['username'];
    $itemdata['contributor_id'] = $_SESSION['login']['user_id'];
    $itemdata['id']=0;
    $itemdata['dir'] = "/assets/uploads";




	$itemdata['Aliastext'] = Defs::getMemberAliasList();

    echo $templates->render('asset_bulk',$itemdata);
}

######################################################################


function get_files ($dirpath,$skip=[]) {
	// returns files (not dirs) in dirpath,
	// excluding .files and anythinig listed in skip array

	$skip_list = ['.','..','.DS_Store'];
	if (! is_array($skip) ) die ("non-array passed to get_files");
	if (!empty($skip) ) $skip_list = array_merge($skip_list, $skip);
	if (! is_dir($dirpath)) die ("Directory $dirpath does not exist.");

    if (!$filelist = array_diff(scandir("$dirpath"),$skip_list)){
       return 0;
    }
    $filelista =
    	array_filter ($filelist,function($f)use($dirpath){return is_file("$dirpath/$f");});

    return $filelista;
}

function get_titles($title_path) {

	$titles=array();
	$captions = array();
	$default_title = '';
    $default_caption = '';
	$titles = $captions = [];
	echo "<h4>Titles and Captions</h4>";
	if (! file_exists($title_path)) die ("No title file in $title_path");
	$fh = fopen($title_path,'r') ;

        #got caption file

	while (($line = fgets($fh))!==false){
		if (substr($line,0,1) == '#') continue;
		if (preg_match('/^\s*$/',$line)) continue;
		$params = explode("\t",$line );
		if ( empty($params[0]) ) continue;


		 if (empty($default_title) ){
			$default_title = $params[2];
			$default_caption = $params[1];
			echo "Default title, caption: $default_title; $default_caption" . BRNL;
			if (empty($default_title))
				die ("Title not set in first record of titles.txt");
		 }

		 $gfile = $params[0];
		 $caption = $params[1] ?? $default_caption;
		 $title = $params[2] ?? $default_title;

		 if (!empty($gfile)){
			$titles[$gfile] = [$title,$caption];
		}
	}
	fclose ($fh);

	foreach ($titles as $f => $t){
	 echo "$f: " , join(',' ,$t) , BRNL;
	}
	echo BRNL;
	return $titles;
}

function save_assets($dirpath,$filelist,$titles) {
	echo "<h4>Saving Assets</h4>";
	global $container;

	$finfomime = new \finfo(\FILEINFO_MIME_TYPE);

	foreach ($filelist as $this_file){
        if (empty($this_file)) continue;
     	 echo "<b>Processing file $this_file</b>". BRNL;
		$post = $_POST;
        #build the post array, starting with stuff from form
        #make the chosen file look like it was an upload from asset form
        $post['id'] = 0;
        list($post['title'],$post['caption']) = $titles[$this_file];
        $post['notes'] = "Generated from $this_file.\n";

        $fake_upload = "$dirpath/$this_file";
        $mimetype =  $finfomime->file($fake_upload);
        $_FILES['uuploads']  = array(
            'tmp_name' => $fake_upload,
            'name' => $this_file,
            'error' => 0,
            'size' => filesize($fake_upload),
            'type' => $mimetype,
        );
		if (empty($post['contributor'])) die ("Contributor is required.");
		if (empty($post['title'])) die ("No title");
        // u\echor ($_FILES,'from asset_generator');
//         u\echor ($post_array,'post array from asset gen');

       $ids[] =  $container['asseta']->postAssetFromForm($post);
    }
    return $ids;


}


function list_report($ids) {
	$v =
	"<p><b>>New Ids created: "
	. array_shift($ids)
	. ' - '
	. array_pop ($ids)
	. "</b></p>";
	return $v;
}

