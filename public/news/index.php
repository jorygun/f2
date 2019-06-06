<?php
$current = file_get_contents('latest_pointer.txt');
header("Location: $current");
