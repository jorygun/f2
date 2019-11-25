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
	use digitalmx\flames\MemberAdmin;
	


if ($login->checkLogin(2)){
   $page_title = 'Member Profile';
	$page_options=['tiny','ajax']; #ajax, votes, tiny 
	
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	# other heading code here
	echo <<<EOT
	<script type="text/javascript" >
    	function validate_profile(theForm) {
		 var s = '';
		 var v = '';
			//alert ('in script');
			v = document.getElementById('email').value;
			//s=v;

 		  if (! validateEmail(v)){
 		  	s += " Email address is not valid. "; }


			  v = document.getElementById('affiliation').value;
			if (v.length <5) {s += 'Affilation not filled in. ';}

 			  v = document.getElementById('location').value;
 			 if (v.length < 5){s += "Location field incomplete.";}

			  if (s != ''){alert (s); return false;}
 			 return true;

		}
   </script>
EOT;
	
	echo $page->startBody();
}
	
//END START
	 $ma = new MemberAdmin();
	 
	if ($_SERVER['REQUEST_METHOD'] == 'POST'){
		$uid = $_POST['user_id'];
		$ma->saveProfileData($_POST);
		#echo "<script>window.location.assign('/profile.php/?uid=$uid&s=relogin');</script>";
	
		exit;
   } 
   
   if (!empty ($uid = $_GET['edit'] ?? '' )){ 
   // deliver the edit form
   	 $profile_data = $ma->getProfileData($uid);
   	 echo  $templates->render('profile-edit', $profile_data);
   	 exit;
   	 
   } elseif (!empty($uid = $_GET['confirmed'] ?? '')){

   	$ma->confirmProfile($uid);
   	
   	$profile_data = $ma->getProfileData($uid);
   	
		echo  $templates->render('profile', $profile_data);
		exit;
		
	} elseif (!empty($uid = $_GET['uid'] ?? '' )){
			$profile_data = $ma->getProfileData($uid);
			echo  $templates->render('profile', $profile_data);
			echo "Site: " . SITE . BRNL;
			exit;
			
	}  elseif ($uid = $_SESSION['login']['user_id']){
		$profile_data = $ma->getProfileData($uid);
	#	u\echor($profile_data,'profile data'); 
			echo  $templates->render('profile', $profile_data);
			exit;
	
 	}else {
 		echo "No profile requested"; exit;
 	}
 		
 	
    	
###############
    
