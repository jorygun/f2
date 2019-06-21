<?php
#$current = file_get_contents('latest_pointer.txt');
#header("Location: $current");
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
if (f2_security_below(2)){exit;}
echo "<script>window.location.replace ('/news/news_latest/') </script>";


