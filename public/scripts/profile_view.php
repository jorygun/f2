<?php
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(2)){exit;}
	use digitalmx\flames\Definitions as Defs;
	use digitalmx\flames\DocPage;
	use digitalmx\flames\Member;
	use digitalmx as u;
	
	
//END START

	
   
	
	

if ($login->checkLogin(3)){
	$page_title = $_SESSION['login']['username'] . " Profile";
	$page_options = []; # ['ajax','tiny','votes']
	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
	echo $page->startBody();
}


$my_sec_level = $_SESSION['level'];
$user_id = $_SESSION['login']['user_id'];

#figure out what profile to view


	if (!empty($_GET['id']) && is_numeric($_GET['id'] ) ){
			$get_uid = $_GET['id'];
	} elseif ( isset ($_POST['id'])){
	    $get_uid = $_POST['id'];
	}
	elseif ( isset ($_GET['uid'])){
	    $get_uid = $_GET['uid'];
	}
	else $get_uid = $user_id;   #current user
	

	


    #if profile requested by user_id instead of record id,
    if (isset($get_uid)){$row = $member->getMemberData($get_uid);}
   
	
	
	
	extract($row['data'],EXTR_PREFIX_ALL,'D');
#u\echor($row['data'],'data');

	$vis_email =  $D_email_public;

	$linkedinlink=  ($D_linkedin)?
         " <p><a href='$D_linkedin' target='_blank'><img src='https://static.licdn.com/scds/common/u/img/webpromo/btn_liprofile_blue_80x15.png' width='80' height='15' border='0' alt='profile on LinkedIn' /><br />$D_linkedin </a></p>":'';
    $member_type = Defs::getMemberDescription($D_status);
	$html_greeting = $D_user_greet;
	$email_status = Defs::getEmsName($D_email_status);
	$message_link ='';
	if ($D_email_hide){
			$message_link = "<a href='send_message.php?n=$D_username&r=$D_id' target='_blank'>Send a Message to me.</a><br>";
		}


	$decade_choices = decompress($D_amd_when,Defs::$decades);
	$location_choices = decompress($D_amd_where,Defs::$locations);
	$department_choices = decompress($D_amd_dept,Defs::$departments);

	$amd_boxes = '';
	$amd_boxes .= (!empty($D_user_amd) ) ?  $D_user_amd : '';
	if (!empty($department_choices) || !empty($location_choices) ||  !empty($decade_choices) ) {
		$amd_boxes .= ". I worked at AMD ";
	}
	$amd_boxes .= (!empty($department_choices)) ? "in " . $department_choices :'';
	$amd_boxes .= (!empty($location_choices)) ? " in " . $location_choices : '';
	$amd_boxes .= (!empty($decade_choices)) ? " during the " . $decade_choices: '' ;
	$last_profile_date = age($D_profile_updated)[1];
	$last_profile_validated = age($D_profile_validated)[1];

	$joindate = age($D_join_date)[1];
	$user_current = $D_user_current ;
	if (!empty($D_user_from)){$user_current .= " ... $D_user_from";}
   $user_web= (!empty($D_user_web))? 
   	"<p><a href='$D_user_web' target='_blank' >Relevant Web Site</a></p>"
   	:
   	'';
   	
    $imagelink='';
    if (!empty($D_image_url )){$imagelink = "<img src='$D_image_url' align='right'  />";}
    $button_text = <<<EOT
		<button onClick = "window.open('/scripts/profile_update.php?id=$D_user_id');">
		Edit My Profile</button>
EOT;
    $edit_button = ($user_id ==  $D_user_id or $_SESSION['level']>7 )?
    	$button_text:'';
	$member_type = Defs::getMemberDescription($D_status);
?>




<h3 ><?=$D_username?>
	<span class='normal'><?=$member_type?>
	<?=$edit_button?>

</h3>

	
<div class='profile_frame current'>

<?=$imagelink?><?=$html_greeting?>
	<h4>Currently</h4> 
	<?=$user_current?>


	<h4>At AMD</h4>
		<?=$amd_boxes?>
		
	<h4>Contact</h4>
	<p>Email: <?=$vis_email?><br>
		<?=$message_link?>
		<em>Email status: <?=$email_status?></em></p>
	<?=$user_web?>
	<?=$linkedinlink?>
	

</div>
<?php

if (! empty($D_user_about)){
	echo "<div class='profile_frame'>\n";
    echo "<h4>More About Me</b></h4>";
	echo nl2br($D_user_about);
	echo "</div>\n";
}


if (! empty($D_user_interests)){
    echo "<div class='profile_frame'>\n";
	echo "<h4>My Other Interests</h4>";
	echo nl2br($D_user_interests);
	echo "</div>\n";
}
if (!empty($D_user_memories)){
	echo "<div class='profile_frame'>\n";
	echo "<h4>Memories of Working at AMD</h4>";
	echo nl2br($D_user_memories);
	echo "</div>\n";
}
?>
<br class='clear'>

<p class='centered'>Joined FLAME site: <?=$joindate?>;
	Profile last updated: <?=$last_profile_date?>;
	Confirmed on <?=$last_profile_validated?>.</p>


   
</body></html>


