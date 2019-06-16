<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';


#get latest news directory
$latest_pointer=trim(file_get_contents(SITE_PATH . "/news/latest_pointer.txt"));

$now = gmdate('M d H:i T');


$bnews = "<div style='border:2px solid black;padding:1em;'>"
    . "<p style='color:red;'><b>Update posted at " .$now . "</b></p>\n"
	. thtml($_POST['bnews'])
	. "</div>\n";

file_put_contents(SITE_PATH . "/$latest_pointer/breaking.html",$bnews);
file_put_contents(SITE_PATH . '/news/news_latest/breaking.html',$bnews);



echo <<<EOT
	<script type='text/javascript'>
	window.alert('Posted to $latest_pointer/breaking.html');

	window.location.assign("/news");
</script>
EOT;





