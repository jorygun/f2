<?php
#$current = file_get_contents('latest_pointer.txt');
#header("Location: $current");
if (f2_security_below(2)){exit;}

header("Location: news_latest");

