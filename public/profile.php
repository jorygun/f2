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

   <script src='/js/aq.js'></script>
EOT;

	echo $page->startBody();
}

//END START
	 $ma = $container['membera'];
	 $assetv = $container['assetv'];

	 $templates = $container['templates'];

	if ($_SERVER['REQUEST_METHOD'] == 'POST'){
		$uid = $_POST['user_id'];
		$ma->saveProfileData($_POST);
		echo "<script>window.location.assign('/profile.php/?uid=$uid&s=relogin');</script>";

		exit;
   }

// GET PROFILE

	if (!empty($uid = $_GET['confirmed'] ?? '')){
   	$ma->confirmProfile($uid);
   }

   $uid = $_GET['uid'] ?? $_GET['edit'] ?? $_GET['id']??  $_SESSION['login']['user_id'];
//echo "(uid = $uid)" . BRNL;
	if (empty($uid)){
 		echo "No profile requested"; exit;
 	}
    $profile_data = $ma->getProfileData($uid);
    $profile_data['user_about_linked'] = u\link_assets($profile_data['user_about']);

    $assets = u\number_range($profile_data['asset_list']);


	$uid = $_GET['edit'] ??'';
   if (!empty ($uid) ){

		$profile_data['photos'] = [];
			foreach ($assets as $aid){
				 $pdata = $assetv->getUserPhoto($aid,'edit');
				// u\echor($pdata);
				$profile_data['photos'][$aid] = $pdata; //'view' or 'edit'
			}


		//u\echor($profile_data,'profile data'); exit;
   	 echo  $templates->render('profile-edit', $profile_data);
   	 exit;

   } else { // is not an edit
		$profile_data['photos'] = [];
		foreach ($assets as $aid){
			 $pdata = $assetv->getUserPhoto($aid,'view');
			// u\echor($pdata);
			$profile_data['photos'][$aid] = $pdata; //'view' or 'edit'
		}
		if (empty($profile_data['photos'] )){
				// not photos so get a random one
				$random = $container['assetsearch']->getRandomAsset($profile_data['username']);
				if (!empty($random)){
					$pdata = $assetv->getUserPhoto($random,'view');

					$pdata['title'] = "random";
						//u\echor($pdata);
					$profile_data['photos'][$random] = $pdata;
				}
			}
//

		echo  $templates->render('profile', $profile_data);

			exit;

}




###############

