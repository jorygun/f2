<?php
ini_set('display_errors', 1);
ini_set('error_reporting',E_ALL);

//BEGIN START
	require_once "init.php";
	#if (f2_security_below(4)){exit;}
    require_once "news_functions.php";


//END START
//echo "Beginning news_files2.php<br>\n";


$news_directory = SITEPATH . '/news';
$next_directory = "$news_directory/news_next";
$advance_directory = "$news_directory/advance";
$test_directory = "$news_directory/news_test";
$itemdb = 'news_items';
$now = sql_now();


if ($_SERVER['QUERY_STRING'] == 'test'){
    build_test($next_directory,'test');
    echo "Copied test stories to news_next";
}

else {
    #echo "Build_next";
    build_next($next_directory);

    #build_advance($advance_directory);
}
#build_test($test_directory);



########################################################
#build news_next

function build_next($directory,$test=false){
    global $itemdb;global $sections;
    $pdo = MyPDO::instance();
    $show_schedule = 0;
    $show_edit = 0;
    $selector = $test?
        " type = 'T' " :
        " use_me > 0 AND type != 'T' ";
    $these_sections = array_keys($sections); #all of them
    $sql = "SELECT i.*,t.section from $itemdb i 
    	INNER JOIN news_topics t ON i.type = t.topic
    	WHERE i.use_me > 0 ORDER BY t.section, i.use_me DESC;";
//echo $sql,"<br>\n";
        
        $stories = build_news_arrays($sql,$show_schedule,$these_sections,$show_edit);
        $story_text = build_news_files($stories);
        if (!empty($story_text)){
            echo save_story_files($directory,$story_text);
        }
}


#build advance
function build_advance($directory){
    global $itemdb; global $sections;
    $show_schedule = 1;
    $show_edit = 0;
    $these_sections = array('news','people','mail','remember','ieee');
    $these_sections = array_keys($sections); #all of them
    $sql= "SELECT * from $itemdb where status != 'P' and status != 'T' ORDER BY use_me ,type;";
    #$sql= "SELECT * from news_items where date_published > '2016-04-10' ORDER BY use_me ,type;";
      
        $stories = build_news_arrays($sql,$show_schedule,$these_sections,$show_edit);
        $story_text = build_news_files($stories);
       if (!empty($story_text)){
            echo save_story_files($directory,$story_text);
        }
}


function build_test($directory){
    global $itemdb;
    global $sections;
    $show_schedule = 0;
    $show_edit = 0;
    #$directory = $directory;
    $these_sections = array_keys($sections); #all of them
    $sql= "SELECT * from $itemdb where status = 'T' ;";
        //echo $sql,"<br>";
        $result = mysqli_query($GLOBALS['DB_link'],$sql);
        //echo "found " . mysqli_num_rows($result);
        $stories = build_news_arrays($sql,$show_schedule,$these_sections,$show_edit);
       # print_r ($stories);
        $story_text = build_news_files($stories);
        echo save_story_files($directory,$story_text);

}

#########################################################



?>
