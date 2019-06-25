<?php
// script to copy model news to news_next without doing anything else.
//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(6)){exit;}
//END START
?>

<html><head><title>Copy Model to Next</title></head>
<body>
<?php
		$news_dir = SITE_PATH . "/news";
		$nextnews_dir = $news_dir . '/news_next';
	


	deleteDir ($nextnews_dir);
	mkdir ($nextnews_dir);
	copy ("$news_dir/model-index.php","$nextnews_dir/index.php");
	
	full_copy ($modelnews_dir,$nextnews_dir);
	echo "<script>
	    alert('Model news copied to next news');
	    window.close();
	    </script>";


?>
</body></html>
