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
	use DigitalMx\Flames\MemberAdmin;



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
	 $ma = $container['membera'];

	if ($_SERVER['REQUEST_METHOD'] == 'POST'){
		$uid = $_POST['user_id'];
		$ma->saveProfileData($_POST);
		echo "<script>window.location.assign('/profile.php/?uid=$uid&s=relogin');</script>";

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

	} elseif (
		!empty($uid = $_GET['uid'] ?? $_SESSION['login']['user_id']) ){
			$profile_data = $ma->getProfileData($uid);
			 // scan for images or links in the about box
		 $profile_data['user_about_linked'] = u\link_assets($profile_data['user_about']);

			#u\echor($profile_data,'profile data'); exit;
			echo  $templates->render('profile', $profile_data);
			#u\echoAlert ("MA Site: " . SITE);

			exit;


 	}else {
 		echo "No profile requested"; exit;
 	}



###############

