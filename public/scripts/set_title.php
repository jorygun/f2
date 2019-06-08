<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;

	// secript to set the title file in next next
	$titlefile = SITEPATH . '/news/news_next/title.txt';

	if (!empty ($_POST['title']) and $_POST['title'] != 'Title Not Set' ){
			$new_title = htmlspecialchars($_POST['title'],ENT_QUOTES);
			file_put_contents($titlefile,$new_title);
			echo "<script> alert('New title saved');</script>";
	}
	
	else {
		if (file_exists($titlefile)){
		unlink($titlefile);
		}
		echo "<script> alert('Title removed');</script>";
	}
	

	
	echo "<script>
		window.location.href = '/level7.php';
		</script>
		
		";

	

