<?php

//BEGIN START
#ini_set('display_errors', 1);
	require_once "init.php";
	if (f2_security_below(4)){exit;}
//END START



 echo "Updating Newsletter Index<br>";
            require_once "NewsIndex.php";
             new NewsIndex(true);
