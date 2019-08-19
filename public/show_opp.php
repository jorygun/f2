<?php
#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\MyPDO; #if need to get more $pdo
	use digitalmx\flames\DocPage;

	
  
	

if ($login->checkLogin(0)){
	$page_title = "Current Opportunities";
	$page_options = ['tiny']; # ['ajax','tiny','votes']
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	echo <<<EOT
<script>
function setToNow(id) {
	document.getElementById(id).value = new Date().toISOString().slice(0, 10);
	
}
</script>
EOT;
	echo $page->startBody();
}
   
 
	
//END START
	use digitalmx\flames\Opportunities;
	$opps = new Opportunities();
	$opp_id = $_GET['id'] ?? 0;
	/* set type as user, admin or public
		user can edittheir own
		admin can edit any
		public cannot edit any
	*/
			
	if (isset($_POST['id'])){
		#save data
		$id = $opps->postOpp($_POST);
		$opp_row = $opps->getOpp($id);
		show_data($opp_row);
		exit;
	}
	
	if (isset($_GET['edit'])){

			$opp_row = $opps->getOpp($opp_id); #may be 0
			show_edit($opp_row);
	}
	elseif ($opp_id){ #asked for a specific opp
		#show the opp data
		if (! $opp_row = $opps->getOpp($opp_id) ){
			throw new Exception ("Opp id $opp_id does not exist");
		}
		show_data($opp_row);
	}
	
	else {#display opp list for edits
		if (!$opp_list = $opps->linkOppList() ){
			throw new Exception ("No opp list");
		}
		#u\echor ($opp_list,'opps');
		
		echo "<ul>\n";
		foreach ($opp_list as $line){
			echo "<li>$line\n";
		}
		echo "</ul>";
	}

	function show_data($row) {
		$description = $row['description'] ?? '';
		$status = $row['active']? 'Inactive':'Active';
		$id = $row['id'];
			echo <<<EOT
	<h3>{$row['title']}</h3>
		Contact: ${row['owner']}; Contact: <a href=mailto:${row['owner_email']}>${row['owner_email']}</a><br>
		 Post ${row['created']}<br>
		 Expires: ${row['expired']}<br>
		 Description: <hr>$description<hr>
		 More Info: <a href='${row['link']}' target='_blank'>Link</a><br>
		 <i>Status: $status ($id)</i>
EOT;
}

	function show_edit ($opp_row){
		
		$id = $opp_row['id'];
		echo <<<EOT
		<h3>Edit/Create Opportunity</h3>
		(yellow fields are required)
		<form method='post'>
		
		<table style='width:100%'>
		<tr><td colspan = 2>
		<input type=text name='id' value='$id' READONLY>
		</td></tr>
		
		<tr><td>
			Title
			</td><td>
			<input type=text name='title' value='${opp_row['title']}' size=60 class='required'>
			</td></tr>
		<tr><td>
			Location
			</td><td>
			<input type=text name='location' value='${opp_row['location']}' size=60  class='required'>
			</td></tr>
		<tr><td>
			Contact
			</td><td>
			<input type=text name='owner' value='${opp_row['owner']}'  class='required'> 
			Email:  <input type=email name='owner_email' value='${opp_row['owner_email']}'  class='required'>
			</td></tr>
	<tr><td>
			Link to URL
			</td><td>
			<input type=text name='link' value='${opp_row['link']}'size=60>
			</td></tr>
			
			<tr><td>
			Expiration
			</td><td>
			<input type=text name='expired' id='expired' value='${opp_row['expired']}'  class='required'> (max 90 days)
			<button type='button' onClick = setToNow('expired')>Now</button>
			</td></tr>
	<tr><td>Description</td><td>
	<textarea rows=50 cols=60 name = 'description' class=useredit>${opp_row['description']}</textarea>
	</td></tr>
	<tr><td><input type='submit'></td></tr>
	</table>
	
	</form>
	
EOT;
}


