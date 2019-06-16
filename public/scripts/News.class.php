<?php

class News {

    //shortcuts for contributor names
    public static $aliases = array (
                'z' => 'Steve Zelencik',
                'bob' => 'Bob McConnell',
                'js' => 'John Springer',
                'editor' => 'Flames Editor',
                'admin' => 'Flames Admin',
                'rick' => 'Rick Marz',
                'dave' => 'David Laws',
                'es' => 'Elliott Sopkin',
                'jm' => 'John McKean',
                'glen' => 'Glen Balzer'

            );

    public  $Aliastext = "(Aliases: " . implode(', ',array_keys($aliases)) . ")";



    #article types available for individual entries
    private  $ptypes = array(

        'amd_news'	=>	'AMD News',
        'tech_news'	=>	'Technology',
        'mfg' => 'Manufacturing',
        'cars'	=>	'Down at the Car Lot',
        'notable' => 'Notable',
        'biz'	=>	'Business News',

        'nostalgia'	=>	'Nostalgia',
        'gatherings'	=>	'Gatherings',
        'flames'	=>	'News About Flames',
        'sad'	=>	'Sad News',
        'wot'	=>	'WOT?',

        'ieee' => 'From the IEEE',
        'badgov' => 'Government Gone Bad',
        'goodgov' => 'Thanks for Good Government',
        'hot' => 'Might Be Controversial'

    );

    #article types available for admin entry
    private  $atypes = array (
        'mailbox'	=>	'In the Mailbox',
        'apology' => 'Apologia',
        'flamesite' => 'Site Update',
        'gatherings'	=>	'Gatherings',
        'cellar' => "Z's Wine Cellar",
        'spec' => "Special Topic",
        'toon' => 'Opening Cartoon',

    );

    #deprecated types
    private  $dtypes = array (
        'thread'	=>	'Conversations',
        'people' => 'The people win',
        'swamp' => 'From the Swamp'
    );

    private $itypes = array_merge($ptypes, $atypes,$dtypes);
    public $news_types = $itypes;



    private  $sections = array(
        'amd' => array('amd_news'),
        'news' =>  array ('biz','mfg','nostalgia'),
        'technology' => array ('ieee','tech_news'),
        'know' => array ('cars','wot','cellar','notable'),
        'people' => array ('gatherings','flames'),
        'opener' => array('toon'),
        'site' => array('apology','flamesite','spec'),
        'mail' => array('mailbox'),
        'sad' => array('sad'),
        'govt' => array('swamp','goodgov','badgov','people','hot'),


    );

    private  $section_names = array (
        'amd' => 'AMD News',
        'news' =>  'The News',
        'remember' => 'From the Past',
        'people' => 'Friends',
        'know' => 'Off Topic',
        'opener' => 'Opener',
        'site' => 'Site News',
        'mail' => 'In The Mailbox',
        'ieee' => 'From IEEE',
        'technology' => 'Engineering Dept.',
        'sad' => 'Sad News',
        'govt' => 'Government and Politics'

    );

 private $istatus = array (
        'N'	=> 'New',
        'R'	=> 'Ready',
        'P'	=> 'Published',
        'E'	=>	'Needs Work',
        'X'	=>	'To Delete',
        'T' => 'Test Article'
    );

   private  $ifields = array(
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
        'graphic_url',
        'graphic_caption',
        'graphic_size',
        'asset_id',
        'asset_list',
        'take_comments'
    );

    public function __construct {
        $self::pdo = pdoConnect::GetPDO();
    }


    public function get_section($me){
        global $sections;
        foreach (array_keys($sections) as $section){
            foreach ($sections[$section] as $type){
                if ($type == $me){return $section;}
            }

        }
        return 'No Section';
    }



    #Funtion to generate name linked to email from user id
    // selector is either userid  or username
    public  function link_contact ($selector) {


            if (is_numeric($selector)){
                $sql="SELECT username,user_email FROM members_f2
                    WHERE user_id = $selector;";
                }
            elseif (is_string($selector)){
                $sql="SELECT user_id,user_email,username FROM members_f2
                    WHERE username = '$selector'";
                }
            else {die ("No selector provided to link_contact");}

            if ($result= $self::pdo -> query($sql)) {

                $urow = $result -> fetch_assoc();
                    $email = $urow['user_email'];
                    $name = $urow['username'];

                if (! empty($email)){
                    $linked = "<a href=\"mailto:$email\">$name</a>";
                }
                else {$linked = $name; }

            }
            else {$linked = $selector;}

            return $linked;
        }



    function get_asset_by_id($id,$style=''){

        $sql = "SELECT * from assets WHERE id = $id";
        $stm = $self::pdo -> query($sql);
        $row = mysqli_fetch_assoc($result);
        foreach ($row as $var=>$val){
            $row[$var] = stripslashes(str_replace('\r\n',PHP_EOL,$val));
            #echo "$row['title'] ";
        }

        $data = get_asset_by_row($row,$style);
        return $data;
    }

    function get_asset_by_row($row,$style=''){
        #style = '' or preview, framed image with caption and source
        #style = 'link',link text
        #style = 'album', show floating frames

    #echo "starting get_asset";

        $id = $row['id'];
        $type = $row['type'];
        $url = $row['url'];
        $status = $row['status'];

        $local =  (substr($url,0,1) == '/')? true:false;

    #echo "local $local";

        foreach (array('caption','title','source','contributor') as $f){
                    $hte[$f] = stripslashes($row[$f]);
        }
        $hte['caption'] =  preg_replace(URL_REGEX, '<a href="$0" target="_blank" title="$0">$0</a>', $hte['caption']);
        $hte['caption'] = nl2br($hte['caption'] );

        $source_line = (! empty($row['source'])) ?
            "<p class='source'>${hte['source']} (${row['vintage']})</p>" : '';

        $click_line = "<p class='small'> (Click image to view source)</p>";
        $thumb_url = "/assets/thumbs/${row['thumb_file']}";
        switch ($style){

        case '':
        case 'thumb':
            $out = "<div class='thumb'>";

            if ((!empty($row['thumb_file'])) && file_exists(SITE_PATH . "/$thumb_url")){
                $out .= "
                <a href='/asset_display.php?$id' target='asset' decoration='none'>
                <img src='$thumb_url'></a>
                <p class='caption'>${hte['caption']}</p>
                $source_line
                $click_line
            ";
            }
            else  {
                $urldecode = urldecode($url);
                $out .= "(No thumb image for id $id.  Showing raw data resized.)<br>
                <a href='$url' target='asset' decoration='none'><img src='$url' width='200'></a>
                <p class='caption'>${hte['caption']}</p>
                $source_line
                $click_line

            ";}
            $out .= "</div>";
            break;

        case 'link':
            $out = "<a href='$url' target='_blank'>${hte['title']}</a>";
            break;

       case 'album':
            $out =  "<div class='album'>";

            $gfile = choose_graphic_url('/assets/galleries',$id);



            if ($gfile){
                $out .= "
                <a href='/asset_display.php?$id' target='asset' decoration='none'>
                <img src='$gfile' ></a>
                <p class='caption'>${hte['caption']}</p>
                <p class='contributor'>${hte['contributor']}</p>
                <p class='clear'>[$id]</p>
            ";

            }
            elseif (file_exists(SITE_PATH . "/$thumb_url")){#try using the thumb
                 $out .= "
                <a href='/asset_display.php?$id' target='asset' decoration='none'>
                <img src='$thumb_url' ></a>
                <p class='caption'>${hte['caption']}</p>
                <p class='contributor'>${hte['contributor']}</p>
                <p class='clear'>[$id]</p>
            ";
            }
            else  {$out .= "(No gallery image for id $id)";}
            $out .= "</div>";
            break;

        case 'toon':

             $gfile = choose_graphic_url('/assets/toons',$id);
           if (file_exists(SITE_PATH . "/$gfile" )){
            $out = "
                <img src='$gfile' width='800'>
                ";
            }
             else  {$out .= "(No toon image for id $id)";}
            break;
        default:
            $out = "(prepare image failed;  style  $style not understood)";
        }
        #update the first used if it's blank and not an admin access
        $first_date = $row['first_use_date'];
        if ((empty($first_date) || $first_date == '0000-00-00') && $_SESSION['level']<5){

            $out  .= set_first_use($id);
        }
       # if ($style == 'thumb' || $style=='album'){ $out .= "</div>\n";}
        return $out;
    }

    function choose_graphic_url($dir,$id){
                /*looks for either a jpeg or png in specified directory
                and returns url to file
                */

                 $gfile='';
                 $path = SITE_PATH . $dir;
                /* try jpg, then png for  file */
                foreach (['jpg','png','gif'] as $ext){
                   # echo "testing $path/$id.$ext.. ";
                    if (file_exists("$path/$id.$ext")){
                        $gfile = "$dir/$id.$ext";
                        return $gfile;
                    }
                 }
                 #echo "Album file not found.";
                 return false;
                }


    function set_first_use($id,$asset_status){
                $t='';
                $new_asset_status = ($asset_status == 'N') ? 'G':$asset_status ;
                $sqlnow = sql_now('date');
                $ref = $_SERVER['REQUEST_URI'];
                if (startswith('/scripts/assets.php',$ref)){return null;}

                if ($_SESSION['level'] > 5){return null;} #anythning over member


                $sqld = "UPDATE assets set first_use_date = '$sqlnow', first_use_in = '$ref', status = '$new_asset_status' where id = '$id';";
                 if (true){query ($sqld);}
                else {$t= "<p class='left'>[Debugging: Prepared to set first use to $sqlnow in $ref ]</p>";}
                return $t;
            }

    function piclink($loc,$cap,$width){
            #returns the pic with caption hotlinked to it's raw image
            if (empty($loc)){return "";}
            if (!$width){$width=620;}
            $dwidth=$width+20;
            $t = "<div class='photo' style='width:${dwidth}px'><a href='$loc' target='_blank' decoration='none'>
                <img src='$loc' width=$width></a><p class='caption'>$cap<br>
                <small>(Click image to view source)</small></p>
                </div>";
            return $t;
        }



    function show_edit($id,$val='Edit'){
        $button = "<br><input type='button' value='$val' style='background-color:#ccf;' onclick=\"ewin=window.open('/scripts/news_item_edit.php?id=$id','itemedit','width=800,scrollbars=auto');return false;\">";
        return $button;
    }

    function set_userid($user,$userid){
        if (!empty($user) && empty($userid)){
        #looks up user to see if valid and returns
            global $aliases;
            if (array_key_exists($user,$aliases)){
                    $user = $aliases[$user] ;
            }
            list($cid,$cname) = get_id_from_name($user);
            if ($cid != 0){
                $userid = $cid;
                $user = $cname;
            }
            else {
               $userid = 0;
            }
        }
        return array($user,$userid);
    }



    function build_news_arrays($result,$show_schedule,$these_sections,$show_edit=false,$show_discuss=true){
            #show_edit and show_schedule are flags to show/hide edit button adn schedule
            #echo "these sections: ",print_r ($these_sections),'<br>';
           #$rows = mysqli_num_rows($result); echo "rows: $rows.";
           #echo var_dump($result);
           $story_array = array();
            while ($row = mysqli_fetch_assoc($result)){
                #print_r ($row);
                $rowdata = $row;

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
            // now build teaser
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
                        if (! empty($t)){$teaser .= "     $t\n";}
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
                 $myfile  = "$directory/headlines.txt";
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
        #dtag is whether or not to show the "discuss this story" link


        global $itypes;


        #convert line breaks and add entities, except for protected area in content
        $webready = $row;
        $articleid = $row['id'];



        foreach (array('content','title','link_title','ed_comment','graphic_caption') as $f){
            $t = tbreak(trim($row[$f]));

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
        $t = preg_replace(URL_REGEX, '<a href="$0" target="_blank" title="$0">$0</a>', $t);

            $webready[$f] = $t;
        }


        if (! $stag){$status_display='';}
        else {  if($row['use_me'] > 0){$status_display = "<span style='background-color:#9F9;width:15em;'>Queued for Next Newsletter</span>";}
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
                $story .= get_asset_by_id($row['asset_id'],'toon');
            }


            $story .= "<p style='font-weight:bold;text-align:center;'>${webready['title']}</p>";
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
                $story .= get_asset_by_id($row['asset_id'],'thumb');
            }
            if (!empty($row['asset_list'])){
                $ids = preg_split('/\s+/ms',$row['asset_list']);
                $ids = array_filter($ids, 'is_numeric');
                foreach($ids as $n){
                    $story .= get_asset_by_id($n,'thumb');
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

            $story .= piclink($gurl,$webready['graphic_caption'],$row['graphic_size']) ;
             if ($row['asset_id']){
                $story .= get_asset_by_id($row['asset_id']);
            }

            $story .= " <div class='left'>
            <p class='content'>${webready['content']}</p>\n";

           $story .= add_more($webready);

            $story .= add_ed($webready);



        break;


        default: #all other stories
           $teaser  =     $row['title'] . ' (' . $row['contributor'] . ')';
            $this_scheduled = $status_display;
            $story = <<<EOT

        <div class='article'>

        <p class='head'><span class='type'>$itypes[$topic]</span><br />
        <span class='headline'>${webready['title']}</span></p>

EOT;


             if ($row['asset_id']){
                $story .= get_asset_by_id($row['asset_id'],'thumb');
            }
             if (!empty($row['asset_list'])){
                $ids = preg_split('/\s+/ms',$row['asset_list']);
                $ids = array_filter($ids, 'is_numeric');
                foreach($ids as $n){
                    $story .= get_asset_by_id($n,'thumb');
                }
                $story .= "<div class='clear'></div>";
            }

            $story .= "<div class='body' >
            <p class='content'>${webready['content']}</p>\n";

            $story .=  add_more($webready);
            $story .=   add_source($webready);

            $story .= add_contributor($webready);

            $story .= add_ed($webready);




        } #end of switch

        #common to all stories


            if ($row['take_comments'] && $dtag){

                $story .=
               " <div class='story_comment_box'>
               <? echo get_commenters($articleid) ?>
               <br>
               <a href='/scripts/news_article_c.php?id=$articleid' target='cpage'>Discuss this article</a>
               </div>
               ";

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

        if(empty ($contributor = $row['contributor'])){return '';}
       # else {echo "working on $contributor";}


        if ($cid = $row['contributor_id']){
            $contributor_linked = link_contact($cid );
        }
        else {$contributor_linked = link_contact($contributor);}

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
        return "<p class= 'contributor'>-- Suggested by $contributor_linked</p>\n";
    }

}
