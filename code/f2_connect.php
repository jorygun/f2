<?php

/* db params must already be set s constants */


function Connect_DB(){
 #pair:
 	 $DB_link = mysqli_connect(DB_SERVER,DB_USER, DB_PASSWORD, DB_NAME) or die("Error " . mysqli_error($DB_link)); 

	if (!mysqli_set_charset($DB_link, "utf8")) {
		die ("Error: could not set charst to utf8");
	}
	mysqli_query($DB_link,'set names utf8mb4');
	
 	 return $DB_link;
 }
 
