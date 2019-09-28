<?php
$pointer = file_get_contents('./pointer.txt');
header('location:' . $pointer);
