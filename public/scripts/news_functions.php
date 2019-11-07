<?php
	namespace digitalmx\flames;
	
	use digitalmx\MyPDO;
	use digitalmx as u;
	use digitalmx\flames as f;
	use digitalmx\flames\Definitions as Defs;
$itemdb = 'news_items';

require_once 'asset_functions.php';

//shortcuts for contributor names


#article types available for individual entries
$ptypes = array(

	'amd_news'	=>	'AMD News',
	'tech_news'	=>	'Technology',
	'mfg' => 'Manufacturing',
	'cars'	=>	'Down at the Car Lot',
	'notable' => 'Notable',
	'biz'	=>	'Business News',

	'nostalgia'	=>	'History',
	'gatherings'	=>	'Gatherings',
	'flames'	=>	'News About Flames',
	'sad'	=>	'Sad News',
	'wot'	=>	'WOT?',
    'inv' => 'Investments',

	'notpc' => 'Might Have To Apologize Later',

	'ieee' => 'From the IEEE',
	'badgov' => 'Foul!',
	'goodgov' => 'Technology',
	'hot' => 'Might Be Controversial',
	'mod' => 'Modern Living',


);

#article types  for admin entry
$atypes = array (
	'mailbox'	=>	'In the Mailbox',
	'apology' => 'Mia Culpa',
    'flamesite' => 'Site News',
	'gatherings'	=>	'Gatherings',
	'cellar' => "Z's Wine Cellar",
	'spec' => "Special Topic",
    'toon' => 'Opening Cartoon',
    'feedback' => 'Site Feedback',
    'spirit' => 'The Spirit of AMD',

);

#deprecated types
$dtypes = array (
    'thread'	=>	'Conversations',
    'people' => 'The people win',
    'swamp' => 'From the Swamp'
);

$itypes = array_merge($ptypes, $atypes);



$sections = array(
    'amd' => array('amd_news','spirit'),
    'news' =>  array ('biz','mfg'),
    'remember' => array ('nostalgia'),
    'technology' => array ('ieee','tech_news'),
    'know' => array ('cars','wot','cellar','notable','notpc'),
    'people' => array ('gatherings','flames'),
    'opener' => array('toon'),
    'site' => array('apology','flamesite','spec','feedback'),
    'mail' => array('mailbox'),
    'sad' => array('sad'),
    'govt' => array('goodgov','badgov','hot'),
    'deprecated' => array('swamp','people','thread'),
    'opps' => array ('inv'),
    'modern' => array ('mod'),


);

$section_names = array (
    'amd' => 'AMD',
    'news' =>  'Industry',
    'remember' => 'From the Past',
    'people' => 'Friends',
    'know' => 'Off Topic',
    'opener' => 'Opener',
    'site' => 'Site News',
    'mail' => 'In The Mailbox',
    'ieee' => 'From IEEE',
    'technology' => 'Engineering Dept.',
    'sad' => 'Sad News',
    'govt' => 'Government and Politics',
    'deprecated' => 'Deprecated Topic',
    'opps' => 'Opportunities',
    'modern' => 'Modern Living',

);

function get_section($me){
    global $sections;
    foreach (array_keys($sections) as $section){
        foreach ($sections[$section] as $type){
            if ($type == $me){return $section;}
        }

    }
    return 'No Section';
}

$istatus = array (
	'N'	=> 'New',
	'R'	=> 'Ready',
	'P'	=> 'Published',
	'E'	=>	'Needs Work',
	'X'	=>	'To Delete',
	'T' => 'Test Article'
);

$ifields = array(
	'title',
	'source',
	'source_date',
	'link_title',
	'url',
	'type',
	'status',
	'content',
	'contributor',
	'contributor_id',
	'ed_comment',


	'asset_id',
	'asset_list',
	'take_comments',
	'take_votes',
	'use_me',
);

#require_once 'voting_function.php';
#adds code to genereate vote buttons

#Funtion to generate name linked to email from user id





function show_edit($id,$val='Edit'){
    $params = "width=800,scrollbars=yes,resizeable=yes,toolbar=yes";
    $url = "/scripts/news_item_edit.php?id=$id";
    $name = "itemedit";

    $button = "<br><input type='button' value='$val' style='background-color:#ccf;' onclick=\"ewin=window.open('$url','$name','$params');\">";
    return $button;
}



function build_news_arrays($sql,$show_schedule,$these_sections='',$show_edit=false,$show_discuss=true){
	
	if (empty($these_sections)){
		global $sections;
		$these_sections = array_keys($sections);
		}#all of them
	
        #show_edit and show_schedule are flags to show/hide edit button adn schedule
        #echo "these sections: ",print_r ($these_sections),'<br>';
       #$rows = mysqli_num_rows($result); echo "rows: $rows.";
       #echo var_dump($result);
       $story_array = array();
       $pdo = MyPDO::instance();
       $result = $pdo->query($sql);
       foreach ($result as $row ){
        	#print_r ($row);
            $rowdata = $row;
#echo "${row['type']},${row['section']},${row['use_me']},${row['title']},tc: ${row['take_comments']}" . BRNL;

	list($section,$teaser,$story) = build_story($rowdata,$show_schedule,$show_edit,$show_discuss);
            #echo "<hr>built story:section $section<br> story : $story <hr>";
            #only include these sections
            if (in_array($section,$these_sections)){
                #echo "<br>preparing story array section $section<br>";
                $story_array[$section]['story'][] = $story;
                $story_array[$section]['teaser'][] = $teaser;
            }
        }
       # echo "<pre>story_array:\n",print_r ($story_array,true), "</pre>\n";
    return ($story_array);
}

function build_news_files($story_array){
    #stag indicates whether to show status on article
    global $itypes;
    global $sections;
    global $section_names;
    $story_text = array();

    #sections[topics][story|teaser][]=content


    $story_count = 0;
    // first build stories into array.  then build the teasers.
    if (!empty($story_array)){
        foreach (array_keys($story_array) as $section){
            #echo "Building stories in section $section: $section_names[$section]. <br>\n";
            $contents = '';

            foreach ($story_array[$section]['story'] as $story){
                $contents .= $story;
                ++$story_count;
            }

            $story_text[$section] = $contents;
        }
        // now build g
        $teaser = "News Stories\n--------------------------\n";
         foreach (array_keys($story_array) as $section){
            #save teaser text
            if ($section == 'opener'){continue;}  #don;t put opener into teaser

            if (! in_array($section , ['sad','mail'])){
                foreach ($story_array[$section]['teaser'] as $t){
                    if (! empty($t)){$teaser .= "     $t\n";}
                }
            }
        }
        foreach (['sad','mail'] as $section ){
            if (!empty ($story_array[$section])){
                $teaser .= "\n$section_names[$section]\n----------------------\n";
                 foreach ($story_array[$section]['teaser'] as $t){
                    if (! empty($t)){$teaser .= "    $t\n";}
                }
            }
        }
        $teaser .= "\n";
        $story_text['teaser'] = $teaser;
    }

    echo "$story_count Stories generated...\n";

    return $story_text;

}

function save_story_files($directory,$story_text){
    global $sections;
    global $section_names;

    #build array of sections file names
     foreach (array_keys($sections) as $section){
            $section_files[$section] = "/news_${section}.html";
            $thisfile = "$directory/$section_files[$section]";
            if (file_exists($thisfile)) {unlink ($thisfile);}

    }

   # echo print_r($story_text, true);
    foreach (array_keys($story_text) as $section){
       # echo "Got section $section for directory $directory<br>";
        if ($section == 'teaser'){
             $myfile  = "$directory/tease_news.txt";
        }
        else{
            $myfile = "$directory/$section_files[$section]";
        }

        #save files
       file_put_contents($myfile,$story_text[$section]);
    }

     return "saved to $directory.<br><br>\n";

}


function build_story($row,$stag=0,$etag=0,$dtag=true){
    #stag is whether or not to show Scheduled status in story
    #etag is whether or not to show Edit button
    #dtag is whether or not to show the "discuss" and voting sections 


    global $itypes;
    require_once 'asset_functions.php';
   # if (function_exists('digitalmx\flames\get_asset_by_id')) {echo "have it";} else {echo 'nope';}

    #convert line breaks and add entities, except for protected area in content
    $webready = $row;
    $articleid = $row['id'];



    foreach (array('content','title','link_title','ed_comment','graphic_caption') as $f){
    	#don't do htmlspecialchars.  Leave html alone
        $t = trim($row[$f]);

        // above line was thmtl instead of nl2br.  Included htmlentities.
        // 3/3/17: went to tbreak (with the entities)
        // if ($f == 'content' || $f == 'ed_comment'){# restore html between braces
//             $t = preg_replace_callback('/{(.*?)}/',
//             function($matches){return htmlspecialchars_decode($matches['1'],ENT_QUOTES);},
//             $t
//             );
//         }
        // convert web addresses to links
        // deleted because you can't put regular hrefs into text.
        // included because you shouldn't put hrefs into text.
   #$t = preg_replace(URL_REGEX, '<a href="$0" target="_blank" title="$0">$0</a>', $t);
    $t = make_links($t);
        $webready[$f] = $t;
    }
    /* detect if story is already html.  If not, do nl2br.
    // otherwise use as is. Non-tiny content will only have
    <p class=quoted" paragraphs. 
    */
    
	if (strpos($webready['content'],'<p>') === false ){
		$webready['content'] = nl2br($webready['content']);
	}
	$webready['ed_comment'] = nl2br($webready['ed_comment']);
	

    if (! $stag){$status_display='';}
    else {  if($row['use_me']){$status_display = "<span style='background-color:#9F9;width:15em;'>Queued for Next Newsletter</span>";}
            else {$status_display="<span style='background-color:#F99;width:15em;'>Not Queued</span";}
    }

    $topic = $row['type'];
     $section = get_section($topic);

   //  if ($gurl = $row['graphic_url']){
//         if (!$row['graphic_size']){$row['graphic_size'] = 240; }
//     }

	#now build story

	 #echo "Building story: ${webready['title']} topic $topic section $section <br>\n";

    $this_scheduled = '';
	$story = $teaser = '';
	switch ($topic) {

    case 'toon':
        $story = "<div><div class='toon'>";
         if ($row['asset_id']){
            $story .= f\get_asset_by_id($row['asset_id'],'toon');
        }
	
       $story .= "<div class='content'><p>${webready['content']}</p></div>\n";
        $story .=   add_source($webready);


        $story .= add_contributor($webready);
        $story .= add_ed($webready);
        $story .= "<hr>";
    break;

	case 'mailbox':
        $teaser  =     $row['title'] . ' (' . $row['contributor'] . ')';
        $this_scheduled = $status_display;
		$story = <<<EOT
		<div class='article'>

	    <p class='headline'>${webready['title']}</p>

EOT;
        $story .= add_contributor($webready,'pre');
        #adds linked "source name" instead of contributor

        if ($row['asset_id']){
            $story .= f\get_asset_by_id($row['asset_id'],'thumb');
        }
        if (!empty($row['asset_list'])){
            $ids = preg_split('/\s+/ms',$row['asset_list']);
            $ids = array_filter($ids, 'is_numeric');
            foreach($ids as $n){
                $story .= f\get_asset_by_id($n,'thumb');
            }
        }

        $story .= "<div class='body'>
        <div class='content'>${webready['content']}</div>\n";


        $story .= add_more($webready);
        $story .= add_ed($webready);

	break;
	case 'apology':
	$this_scheduled = '';
	    $story = <<<EOT

		<div class='apology'>
		<p class='comment'>Oops...</p>
EOT;

        
         if ($row['asset_id']){
            $story .= f\get_asset_by_id($row['asset_id']);
        }

        $story .= " <div class='left'>
        <p class='content'>${webready['content']}</p>\n";

       $story .= add_more($webready);

        $story .= add_ed($webready);



	break;


	default: #all other stories
       $teaser  =      $row['title'] ; 
       if (strcmp($row['contributor'],'FLAMES editor') !== 0) {
       	$teaser .= '(' . $row['contributor'] . ')';
       	}
       	$this_scheduled = $status_display;
        $story = <<<EOT

    <div class='article'>

    <p class='head'><span class='type'>$itypes[$topic]</span><br />
    <span class='headline'>${webready['title']}</span></p>

EOT;


         // if ($row['asset_id']){
//             $story .= get_asset_by_id($row['asset_id'],'thumb');
//         }
//          if (!empty($row['asset_list'])){
//             $ids = preg_split('/\s+/ms',$row['asset_list']);
//             $ids = array_filter($ids, 'is_numeric');
//             foreach($ids as $n){
//                 $story .= get_asset_by_id($n,'thumb');
//             }
//             $story .= "<div class='clear'></div>";
//         }

        $story .= "<div class='body' >\n";
        if ($row['asset_id']){
            $story .= f\get_asset_by_id($row['asset_id'],'thumb');
        }
         if (!empty($row['asset_list'])){
            $ids = preg_split('/\s+/ms',$row['asset_list']);
            $ids = array_filter($ids, 'is_numeric');
            foreach($ids as $n){
                $story .= f\get_asset_by_id($n,'thumb');
            }
            $story .= "<div class='clear'></div>";
        }
        $story .= "<p class='content'>${webready['content']}</p>\n";

        $story .=  add_more($webready);
        $story .=   add_source($webready);

        $story .= add_contributor($webready);

        $story .= add_ed($webready);




	} #end of switch

	#common to all stories
#echo "story ${row['id']}: ts ${row['take_comments']}, tv: ${row['take_votes']} " . BRNL;
	if ($dtag == true and ($row['take_comments'] == 1 or $row['take_votes'] == 1)) {
		
		$story .=
           " <div class='story_comment_box clear'>
           <table class='voting'><tr>";
           
        if ($row['take_comments'] == 1 ){
            $story .= "<td ><!-- comment $articleid --></td>";
           
        }
        # add voting buttons
        if ($row['take_votes']== 1 && $_SESSION['level'] > 6){
        	$story .= "<td ><!-- vote $articleid --></td>";
        }
        
        $story .= '</tr></table></div>';
    }
        

    $edit_button = $etag? show_edit($row['id']) : '';
    if ($etag || $stag){
	    $story .= "<p>$edit_button $this_scheduled</p>\n";
	}
	$story .= "</div>
	<p class='clear' style='margin-top:0px;margin-bottom:0px;'>&nbsp;</p>\n";
    $story .= "</div>\n";


  return array ($section,$teaser,$story);
}

function add_source($row){
        if (empty($source = $row['source'])){return '';}
        if (empty($row['source_date'])){$sdate = "Undated";}
        else {$sdate = $row['source_date'];}
        return "<br><span class='source'>Source: ${row['source']} ($sdate)</span>\n";
 }

function add_more($row){
    if( empty($url = $row['url']) ){return '';}

    if ( empty($link_title = $row['link_title']) ){
        $link_title = "Read more ...";
     }
    if (substr($url,0,4)=='http'){
        $more =
            "<a href= '/links.php?url="
            . urlencode($url)
            . '&aid='
            . $row['id']
            .  "' target = 'offsite'>"
            . $link_title
            . '</a>'
            ;
    }
     else {
        $more =
            "<a href= '" .
            $url        .
            "' target = '_blank'>"  .
            $link_title             .
            '</a>'
            ;
    }
    return $more . "\n";
 }

function add_ed ($row){
    // adds editor comment
        if (!empty($comment = $row['ed_comment'])){
            return " <div class='ed_comment'>
            <p > $comment</p>
            </div>";}
        else {return '';}
    }

function add_contributor($row,$pos='post'){
    // can have contributor as text, or, if it's a member, will
    // havbe the contributor id filled in.  Members can be linked.

    
    if ($cid = $row['contributor_id']){
        $contributor_linked = get_linked_contact($cid );
    }
    else {throw new Exception ("No contributor id on article {$row['id']}");}

    if ($pos == 'pre'){
    #use source name instead of contributor name.
       # $source = add_source($row);
        #return " <p class='presource'>From: $contributor_linked $source </p>";
        #$source_linked = link_contact($row['source']);
        #return "<p class='presource'>From: $source_linked</p>";
        // changed back to using contributor because aliases are already handled
        return "<p class='presource'>From: $contributor_linked</p>";
    }
    #otherwise
    return "<p class= 'contributor'>-- Contributed by $contributor_linked</p>\n";
}


function get_linked_contact ($uid) {
	$pdo = MyPDO::instance();
	$sql="SELECT username,user_email FROM members_f2
			WHERE user_id = $uid;";
	
   
   if (! $urow = $pdo->query($sql)->fetch() ){
   	throw new Exception ("Contributor id $uid not found in database");
   	}
   	
	$email = $urow['user_email'];
	$name = $urow['username'];

    if (! empty($email)){
        $linked = "<a href=\"mailto:$email\">$name</a>";
	}
	else {$linked = "$name (no email)"; }

    return $linked;
}




// function get_article_comments ($article){
//     /* gets comments associated with an article and returns them
//     as an html block to show undeer article; Adds a add_comment button
//     */
//     if (! is_integer($article)){return ; }
//
//     $sql = "SELECT * from comments where article_id = '$article'
//         ORDER BY date;
//         ";
//     $result = query($sql);
//     $rcount = mysqli_num_rows($result);
//      $block=''; #will accumulate text here
//     if ($rcount > 0){
//         while ($row = mysqli_fetch_assoc($result)){
//             $user = $row['user_id'];
//             $userrow = get_member_by_uid($user);
//             $userlink = "<a href='mailto:${userrow['user_email']}'>
//             ${userrow['username']}</a>";
//             $comment_time = date('M d, Y h:i',strtotime ($row['date']));
//             $htcomment = hte($row['comment']);
//
//             $text = <<<EOF
//             <div class='comment_block'>
//             <p>$userlink <i>$comment_time</i></p>
//             <p>$htcomment</p>
//             </div>
//
// EOF;
//              $block .= $text;
//         }
//     }
//     $block .= "<p>
//     <button onclick=\'window.open(\'add_comment.php?article=$article\')\'>
//     Add Comment
//     </button></p>";
//     return $block;
//
// }

// function add_article_comment($article,$user_id,$comment){
//     $escaped_comment = json_encode($comment);
//     $sql = "INSERT INTO comments SET
//         article_id = '$article',
//         user_id = '$user_id',
//         date = NOW(),
//         comment = '$escaped_comment'
//         ;
//         ";
//         echo $sql;
//     $result = query($sql);
// }

?>
