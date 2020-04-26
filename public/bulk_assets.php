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
	use digitalmx\flames\AssetAdmin;
	use digitalmx\flames\Asset;
	


if ($login->checkLogin(0)){
   $page_title = 'Bulk Asset Editor';
	$page_options=[]; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	
	echo $page->startBody();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
	
	#u\echor ($asset_data);
	$itemdata = array(); #store data to display
    $itemdata['date_entered'] = sql_now('date');
    $itemdata['contributor'] = $_SESSION['login']['username'];
    $itemdata['contributor_id'] = $_SESSION['login']['user_id'];
    $itemdata['id']=0;
    $itemdata['dir'] = "/assets/uploads";
    
	echo $templates->render('bulk_assets',$itemdata);
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (!empty($_POST['submit'])) {
		$_SESSION['bulk_asset_post'] = $_POST;
	} elseif (!empty($_POST['Continue'])){
		$_POST = $_SESSION['bulk_asset_post'];
		$_POST['Continue'] = 'Continue';
	}
	
	
	build_file_list ($_POST['dir']);
}
	
		
	function build_file_list($dir) {
		 $dirpath = SITE_PATH . $dir; #upload directory typically

		$titles=array();
		$captions = array();
		$title_file = "$dirpath/titles.txt";
	echo "reading titles from $title_file" . BRNL;
		 if (! file_exists($title_file)){
			 die ("No titles.txt file found in folder.");
			}
		  $fh = fopen($title_file,'r');
		  $default_caption = $default_title = '';
	  
		  #read title file 
		  $caution_found = false;
		  while (($line = fgets($fh))!==false){
		  	if (preg_match('/^\s*$/',$line)){ continue;}
			if (substr($line,0,1) == '#') {continue;}
			if (empty( $params = explode("\t",$line ) )){ continue;}
			if ( empty($params[0]) ){continue;}
			
			if (empty($default_title) ){
				$default_title = $params[2];
				$default_caption = $params[1];
				echo "Set default title, caption: $default_title; $default_caption" . BRNL;
				if (empty($default_title)) {
				 throw new Exception ("Title not set in first record of titles.txt");
				}
			}
			
			$gfile = $params[0];
			if (empty($gfile)){continue;}
			
			$caption = $params[1] ?? $default_caption;
			$title = $params[2] ?? $default_title;
			$caution = '';
			if (!file_exists("$dirpath/$gfile") ){
				$caution = "FILE NOT FOUND!";
				$caution_found = true;
			}
			$file_list[$gfile] = [$title,$caption,$caution];
		
		}
	
		fclose($fh);
		
		if (!$caution_found && !empty($_POST['Continue'])) { process_files($file_list); exit;}
		else {
			echo "<h3>Titles and Captions</h3>";
			echo "<table>";
			echo "<tr><td>FileName</td><td>Title</td><td>Caption</td></tr>\n";
			foreach ($file_list as $f=>$data){
				echo "<tr><td>$f</td><td>$data[0]</td><td>$data[1]</td><td>$data[2] </td></tr>\n" ;
			}
			echo "</table>\n";
	
			
		}
		if ($caution_found)  {
				echo "<p>Please Fix Errors noted above.</p>" . NL;
		}
		
		echo <<<EOF
		<form method='POST'>
		<input type = 'submit' name='Continue' value='Continue'>
		</form>
EOF;
		exit;
		
	}
	
	function process_files ($file_list) { 
	 echo "Processing files" . BRNL;
	 $dir = $_SESSION['bulk_asset_post']['dir'];
    $new_ids = [];
    foreach ($file_list as $fname=>$data){
			list ($fcaption,$ftitle,$fcaution) = $data;
			if (!empty ($fcaution)){
				die ("File $fname is missing.");
			}
    	
        #build the post array, starting with stuff from form
        #make the chosen file look like it was an upload from asset form
        
        $post_array = $_SESSION['bulk_asset_post'];
        $post_array['id'] = 0;

         $post_array['caption'] = $fcaption; 
        	$post_array['title'] = $ftitle;
         $post_array['notes'] = "Bulk upload from $fname.\n";
        
         //  Need: $_FILES[type]['error'] UPLOAD_ERR_OK.
// 		   Need: $_FILES[type]['tmp_name'] location of file
// 		     Need: $_FILES[type]['name'] orig file name
        $_FILES['uuploads']['tmp_name'] = SITE_PATH . "/$dir/$fname";
        $_FILES['uuploads']['error'] = 'UPLOAD_ER_OK';
        $_FILES['uuploads']['name'] = $fname;
        $_FILES['uuploads']['type'] = mime_content_type (SITE_PATH . "/$dir/$fname");
        #recho ($_FILES,'from asset_generator');
       # recho ($post_array,'post array from asset gen');
		$aa = new AssetAdmin(new Asset() );
       $id =  $aa->postAssetFromForm($post_array);
      # unlink (SITE_PATH . $dir . '/' . $fname) ;
       $new_ids[] = $id;
	}
    echo "<p>New Ids created: "
        . array_shift($new_ids)
        . ' - '
        . array_pop ($new_ids)
        . "</p>";

    #check to make sure directory is empty
    #files are removed by renaming in the accept_file function
    
    unset ($_SESSION['bulk_asset_post']);
    if ( 0){
        unlink ("$dirpath/*");
        rmdir ("$dirpath");
        echo "Directory $dir removed from server." . BRNL;
    }
    else {echo "Directory $dir retained because not empty." . BRNL;}


    echo "done." . BRNL;


}
