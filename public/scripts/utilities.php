<?php
// miscellaneous utility scripts



$aliases = array (
            'z' => 'Steve Zelencik',
            'bob' => 'Bob McConnell',
            'john' => 'John Springer',
            'editor' => 'Flames Editor',
            'rick' => 'Rick Marz',
            'dave' => 'David Laws',
            'elliott' => 'Elliott Sopkin',
        
            'glen' => 'Glen Balzer',
            'jeff' => 'Jeff Drobman',
            'kc' => 'K.C. Murphy',
            'jp' => 'Jean Pierre Velly',

        );

$Aliastext = "(Aliases: " . implode(', ',array_keys($aliases)) . ")";




function deleteDir($path) {
    if (!is_dir($path)) {
        throw new InvalidArgumentException("$path is not a directory");
    }
    if (substr($path, strlen($path) - 1, 1) != '/') {
        $path .= '/';
    }
    $dotfiles = glob($path . '.*', GLOB_MARK);
    $files = glob($path . '*', GLOB_MARK);
    $files = array_merge($files, $dotfiles);
    foreach ($files as $file) {
        if (basename($file) == '.' || basename($file) == '..') {
            continue;
        } else if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($path);
}

function parse_name($name,$piece){
	preg_match('/[, ]*(jr|sr|II|III)\.? *$/i',$name,$m);
	$suffix = $m[1];
	$tname = preg_replace("/$m[0]/",'',$name);
	preg_match('/ ?(\S+)$/',$tname,$m);
	$last_name = trim($m[0]) ;
	#echo "<hr>How I Parsed the Name<table class='bordered'><tr><th>name</th><th>suffix</th><th>w/o suffix</th><th>last</th></tr><tr><td>$name</td><td>$suffix</td><td>$tname</td><td>$last_name</td></tr></table><hr>\n";
	if ($piece=='last'){return $last_name;}
	return 0; #default
}


function is_valid_email($email){
	return (filter_var($email, FILTER_VALIDATE_EMAIL)) ? 1 : 0;


   /* regex /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
   */

}
function is_valid_id($t){
	#checks for valid id or userlogin in $t.
	$t = trim($t);
	if (is_numeric($t) 
		&& $t < 10000
		){
			
			return true;
		}
		
	
	else { return false;}
}
function is_valid_login($t){
	#checks for valid id or userlogin in $t.
	$t = trim($t);
	if ( preg_match('/^\w{5}\d+$/',$t )
			){
		
		return true;
	}
	else {return false;}
}
function is_valid_uid($t) {
	$t = trim($t);
	if (
		is_numeric($t)
		&& $t >= 10000
			){
			
		return true;
	}
	else {return false;}
}
function send_admin($subject='FLAMEs Admin Notice',$info,$from='admin@amdflames.org'){

	if(empty($info)){$info = "No Reason Provided";}

	$message = <<<EOT
$info

EOT;
	 mail('admin@amdflames.org',$subject,$message,"From: $from\r\n");


}
function get_url_data($url) {
     $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => false,     // follow redirects
        CURLOPT_ENCODING       => "",      // handle all encodings
        CURLOPT_USERAGENT      => "asset_search", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
        CURLOPT_TIMEOUT        => 40,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $data = curl_exec( $ch );


    if (!is_string($data)) return $data;

    unset($charset);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    /* 1: HTTP Content-Type: header */
    preg_match( '@([\w/+]+)(;\s*charset=(\S+))?@i', $content_type, $matches );
    if ( isset( $matches[3] ) )
        $charset = $matches[3];

    /* 2: <meta> element in the page */
    if (!isset($charset)) {
        preg_match( '@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s*charset=([^\s"]+))?@i', $data, $matches );
        if ( isset( $matches[3] ) )
            $charset = $matches[3];
    }

    /* 3: <xml> element in the page */
    if (!isset($charset)) {
        preg_match( '@<\?xml.+encoding="([^\s"]+)@si', $data, $matches );
        if ( isset( $matches[1] ) )
            $charset = $matches[1];
    }

    /* 4: PHP's heuristic detection */
    if (!isset($charset)) {
        $encoding = mb_detect_encoding($data);
        if ($encoding)
            $charset = $encoding;
    }

    /* 5: Default for HTML */
    if (!isset($charset)) {
        if (strstr($content_type, "text/html") === 0)
            $charset = "ISO 8859-1";
    }

    /* Convert it if it is anything but UTF-8 */
    /* You can change "UTF-8"  to "UTF-8//IGNORE" to
       ignore conversion errors and still output something reasonable */
    if (isset($charset) && strtoupper($charset) != "UTF-8")
        $data = iconv($charset, 'UTF-8', $data);

        #echo "Charset: $charset<br>\n";
        #preecho ($data);
// get title
   # preg_match_all('/(....title....)/i',$data,$m);
    #echo "M: <br>";recho ($m);

    #$title = $m[1];
    #echo "Title: $title<br>\n";

// echo $content;
// exit;

    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $data;
    return $header;
}


function send_user ($contact,$subject='Notice from AMD FLAMEs Site',$info){
    #accepts either id or email text
    $contact = trim($contact);
    #echo "Contact in send_user: $contact ... ";
    if (is_numeric($contact)){ #it's the id
        $sql = "SELECT username,user_email FROM members_f2
                WHERE id = '$contact';";
         $result = mysqli_query($GLOBALS['DB_link'],$sql);
            if (mysqli_num_rows($result) == 0){
                die ("No contact found for $contact");
            }

        $row = mysqli_fetch_assoc($result);

        $to = "'${row['username']}' <${row['user_email']}>";
    }
    elseif (is_valid_email($contact)){$to = $contact;}

    else {die("Invalid email in contact: $contact");}


    #echo "send_user Sent email to: " . htmlspecialchars($to) . " ... <br>";

	if(empty($info)){
	    echo "No info provided to email user.";
	    return 0;}

	$message =
     detab_text($info)
     .
    "

------------------------------------------------------------
This is a message from the AMD Flames Alumni site.
If any questions, contact the admin by replying to this email."
    ;

	mail($to,$subject,$message,"From: admin@amdflames.org\r\n");
	return 1;

}




function get_profile_message($row,$type='html'){

        list($profile_days,$profile_date) = age ($row['profile_updated']);
        $login = get_login_from_row($row,'code'); #just the code
        $profile_url = SITEURL . "/scripts/profile_update.php?s=$login";


	    $html =
			"<p>Your profile was lasted updated on $profile_date.
			If you'd like to update it, here's the link:<br>
			<span class='url'><a href='$profile_url'>Update Your Profile</a></span></p>"
			;
		$text =
			"
    Your profile was lasted updated on $profile_date.
    If you'd like to update it.  Here's the link:
        $profile_url
        "
			;
		return ($type=='html')?$html:$text;
 }


function detab_text($message){
    $message = preg_replace('/\t/',"   ",$message);
	 $message = preg_replace('/\r?\n/',"\r\n",$message);
	 return $message;
}

function get_login_from_row($row,$form='code'){
        #returns either login code or full url if form = 'url'
        $logincode = $row['upw'] . $row['user_id'];
        $loginurl = "${GLOBALS['siteurl']}/?s=$logincode";
        if ($form=='url'){return $loginurl;}
        elseif ($form == 'link') {return "<a href='$loginurl'>$loginurl</a>";}
        elseif ($form == 'code'){return $logincode;}
        else {return $logincode;}
 }


function obscure_name($name){
	//returns text with only part of the name showsing

	preg_match('/(...\S*)/',$name,$m);
				$obscure = "$m[0]...";
	return $obscure;
}

function remove_dir($dir) {
    // deletes all the files and subdirectories; then the directory
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") remove_dir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
}
function get_recent_files($number,$path){
	#returns name of n most recent files in directory.
	#returns a string if only 1; otherwise an array
	$latest_ctime = 0;
	$mods = array();
	if (is_dir($path) == false){return "";}
	
    foreach (glob($path . '/*') as $f) {
        $mods[filemtime($f)] = $f;
    }
    krsort($mods);
    $fnames = array_values(array_slice($mods, 0, $number, true));
    $fnames = str_replace("$path/",'',$fnames);
    if ($number == 1){return $fnames[0];} #if only 1, just return it
    return $fnames; #otherwise return the list
}


function obscure_email($email){
	preg_match('/^(..?.?.?).*\@/',$email,$m); #first up to 4 before @
#	echo "first: ";print_r ($m);echo "\n";
	$obscure	= 	"$m[1]---";
	preg_match('/.*?\@(..?.?.?).*(\.\w+)$/',$email,$m); #first up to 4 after @ + domain
#		echo "second: ";print_r ($m);echo "\n";
	$obscure	.=	"@$m[1]---$m[2]";
	return $obscure;
}

function display_email(&$row){

	/* Returns either a linked email address or a message that the email is hidden, or a
		message that it is an invalid address.

	row is 2-dim like from a fectch associative, typ user row
	*/
	if (!$row){return "No row sent to display email";}
	    #print_r ($row) ;

		$addr = trim($row['user_email']);
		$hide = $row['email_hide'];
		$em_status = $row['email_status'];

// use to allow a two-value array instead of full user row
	// if (!$addr){
// 		list ($addr,$hide) = $row;
// 	}
	if (!$addr){return "No email on file";}
	if (! is_valid_email ($addr)){
		echo "Invalid email address ($addr) in display_email.";
		return "Invalid email address";
	}

	if ($hide){
		$v = '(Email hidden)';
	}
	elseif ($em_status == 'LB'){
		$v = "$addr (but it bounces)";
	}
	elseif ($em_status == 'LD'){
		$v = "--";
	}
	elseif (substr($em_status,0,1) == 'L'){
		$v = "$addr (but we can't get a response.)";
	}

	else {
		$v = "<a href='mailto:$addr'>$addr</a>";
	}
	return $v;
}
function pretty_date ($form='sql',$type = 'time',$when='' ){
	/**
		@form sql or human
		@type = date or time
		@when = text string of date
		
		@returns humsn or sql formated date or time, otherwise returns sql date and time
	**/
	
	switch ($form){
		case 'sql' :
			$format = ($type == 'date')?
		'Y-m-d' : 'Y-m-d H:i:s';
			break;
		case 'human' :
			$format = ($type=='date')?
		'M d, Y' : 'M d, Y H:m P';
			break;
		default :
			throw new Exception ("unknown format $form for pretty_date");
	}
	$dt = new DateTime($when);
	#$dt->setTimeZone(new DateTimeZone('America/Los_Angeles'));
	
	
	if (! $dt ){
		echo "Cannot set date from $when in pretty_date";
		return '??';
	}
	return $dt->format($format);  
}

function sql_now($format = 'time'){
	// #returns sql date if format requested, otherwise returns sql date and time
// 	global $DT_now;
// 
// 	if ($format == 'date'){
// 		$t = $DT_now -> format('Y-m-d');
// 	}
// 	else {
// 		$t = $DT_now -> format('Y-m-d H:i:s');
// 	}
	$t = pretty_date('sql',$format,'');
	return $t;
}
function sql_today(){
	// returns current date in sql format
	$t = pretty_date('sql','date','');
	return $t;
}

function remove_slashes($element){
	if (is_string($element)){
		$v=stripslashes($element);
		return $v;
	}
	elseif (is_array($element)){
		$w = array();
		foreach($element as $v){
			$w[] = stripslashes($v);
		}
		return $w;
	}
}
function clear_safe ($Post){
		global $DB_link;
		foreach ($Post as $param => $value){
			if (is_string($value)){
                $clear = remove_slashes($value);
                $clear = trim($clear);

                $safe =  mysqli_real_escape_string($DB_link,$clear);

                $CLEAR[$param] = $clear;
                $SAFE[$param] = $safe;
                #echo "$param -> $value, $clear, $safe <br>\n";
		    }
		}
		return array($CLEAR,$SAFE);
	}
function stripslashes_text ($value){
    $value = is_array($value) ?
                array_map('stripslashes_text', $value) :
                stripslashes(str_replace('\r\n',PHP_EOL,$value));
   return $value;
}

function unslashed($data){
    foreach ($data as $param => $value){
			if (is_string($value)){
                $clear = remove_slashes($value);
                $clear = trim($clear);
            }
        $cleardata[$param] = $value;
    }
    return $cleardata;
}

function safe_like ($text){
	$safe = preg_replace('/%/','\%',$text);
	$safe = preg_replace('/_/','\_',$safe);
	return $safe;
}

function validateMysqlDate( $date )
{
    return preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches )
           && checkdate($matches['month'],$matches['day'],$matches['year']);
}
function thtml($text){
	// returns text coverting line feeds to <br>s and entities
	$text = htmlentities($text,ENT_QUOTES);
	$text = tbreak($text);
	return $text;
}

function tbreak($text){
   	// returns text coverting line feeds spaces to html
	$text = spchar($text);
	$text = nl2br($text);
	$text = str_replace ('/\t/','&nbsp;&nbsp;&nbsp;',$text);
	
	return $text;

}

function set_item_data($itemdata){
	    #turns itemdata array into a string for SET comnd
	    global $DB_link;
        $sqls = array();
        foreach ($itemdata as $k => $v){
		    $v = mysqli_escape_string ($DB_link,trim($v));
			$sqls[]= " $k = '$v' ";
		}
		$sqlset = implode(', ',$sqls);
        return $sqlset;
}

function pdoPrep($data,$include=[], $key=''){

 /**
  *                                          *
  *  to prepare fields for a pdo execute.                                      *
  *  $data = data array (var=>val),
  *  $include = list of vars in $data to insert/update
  *    ( all vars included if include is empty; )
  *  $key is in the WHERE field that will be used, so is removed from data
  *     and its value is returned in the return array as 'key'

  *
  *  returns array of arrays:
        'data' = array of placeholder=>val,
        (Sames as data, but only with fields in include_vars, less key)
        (includes empty fields).
        placeholder is same as var

        'update' = text string for update SET assignment, like
            email=:email,status=:status

        'ifields' text like email,status,... for use in update command.
        'ivals' text like :email,:status,... for use in update command.
        'key' is value of field named in $key, used in WHERE clause


   $prep = pdoPrep($post_data,array_keys($model),'id');

    $sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivals']} );";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = ${prep['key']} ;";
       $stmt = $this->pdo->prepare($sql)->execute($prep['data']);


  **/
         $db =  $ufields = $ifields = $ivalues = array ();

        #transfer fields from arr to db

        foreach ($data as $var => $val){
            // find key field which is returned separately
            if (!isset($result['key'])  and ($var === $key)){
                $prepared['key'] = $val;
                continue; #get next var
            }
            // ignore any fields not listed in valid fields
            if ( !empty($include) and ! in_array($var,$include) ){ continue; }

            $db[$var] = htmlspecialchars_decode($val);
            if (empty($db[$var])){$db[$var] = "";}

            $ufields[] = "$var = :$var";
            $ifields[] = $var;
            $ivalues[] = ":$var";

        }

        $prepared['data'] = $db;
        $prepared['update'] = implode(', ',$ufields);
        $prepared['ifields'] = implode(', ',$ifields);
        $prepared['ivals'] = implode(', ',$ivalues);

        return $prepared;
    }

function stripslashes_deep ($value){
    $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);

    return $value;
}



function get_field_from_db($field,$db,$id){
    #returns array of data for fields specified in db name where id = id
    $sql = "SELECT $field FROM $db where id='$id';";
     $result = mysqli_query($GLOBALS['DB_link'],$sql);
    $row = mysqli_fetch_assoc($result);
    return $row[$field];
}


function get_email_status_name ($status){
	global $G_ems_defs;
	return $G_ems_defs[$status];
}

function get_id_from_name($name){
    $pdo = MyPDO::instance();
        $sql2="SELECT user_id,username FROM `members_f2`
            WHERE username = '$name';";
        if (!$urow = $pdo->query($sql2)->fetch() ){
            return array(0,$name);
        }
        $user_id = $urow['user_id'];
        $username = $urow['username'];
        return array($user_id,$username);

    }

function get_user_data_by_id ($id){
    require_once 'MyPDO.class.php' ;
    $sql="SELECT username,user_email from `members_f2` WHERE user_id=$id;";
    #echo "SQL = $sql<br>\n";

     $dbcon = MyPDO::instance() or die("no connectin");
      $result = $dbcon->query($sql) or die ("no result");
      $row=$result->fetch();
       $username = $row['username'];
        $user_email = $row['user_email'];
        $linked = "<a href='mailto:$user_email'>$username</a>";
    return array($username,$user_email,$linked);
}


function update_record_for_id_pdo ($id,$data){
  $pdo = MyPDO::instance();
    end($data);
  $lastField = key($data);
  $bindString = ' ';
  foreach($data as $field => $val){
    $bindString .= $field . '=:' . $field;
    $bindString .= ($field === $lastField ? ' ' : ',');
  }


$query = "UPDATE `members_f2` SET " . $bindString .
    " WHERE id = $id;" ;

#echo "bind: $bindString<br>";
$stmnt = $pdo->prepare($query);
$stmnt->execute($data);
#echo $stmnt->rowCount() . " rows affected<br>\n";
}

function update_record_for_id ($id,$vars){
	// supply id and array of field => data
	global $GV;


	#make sure id exists
	if($row = get_member_by_id ($id)){
		$q = "UPDATE `members_f2` SET ";
		foreach ($vars as $k => $v){
			$q .= " $k = '$v', ";
		}
		$q = substr($q,0,-2); #chop last 2 chars off to get rid of ,
		$q .= " WHERE id = $id;";

		if (1){
			 $result = mysqli_query($GLOBALS['DB_link'],$q);
			}else {
			echo "SQL NOT RUN:<br>$q<br>";
		}
		return $result;
	}
	else {die ("no id supplied to update_record_for_id");}
}
function build_options($val_array,$check=''){
	$opt = "<option value=''>Choose One...</option>";
	#if 2 dimmensional array
	if( count(array_filter(array_keys($val_array), 'is_string')) > 0){
        foreach ($val_array as $k => $v){
            $checked = ($k == $check)?"selected":'';
            $opt .= "<option value='$k' $checked>$v</option>";
        }
    }
    # or if one-dimensional array
    else {
        foreach ($val_array as $k){
            $checked = ($k == $check)?"selected":'';
            $opt .= "<option value='$k' $checked>$k</option>";
        }
    }

	//echo "check: $check.  options:", h($opt),"<br>";
	return $opt;
}

function buildCheckBoxSet(
    $var_name,
    $val_array,
    $check = '',
    $per_row = 1,
    $show_code = false
) {
    // like building select options, but shows as
    // checkboxes instead (multiples ok)
    // $check is string with multiple characters to match against the val array
    //per_row is how many items to put in a row; 1 is verticle list
        $opt = '';
    $rowcount = 0;
    $tablestyle=false;
    asort($val_array);
    $varcount = count($val_array);
    if ($varcount > $per_row){$tablestyle=true;}
    $opt = '';
    if ($tablestyle){$opt = "<table><tr>";}

    foreach ($val_array as $k => $v) {
    #echo "k=$k,v=$v,check=$check" . BRNL;
        if (empty($v)){continue;}

        $label = $v;
        $label .= ($show_code)? " ($k)" : '';

          $checkme = (strstr($check, $k))?"checked":'';
          if ($tablestyle){ $opt .= "<td>";}
          $opt .= "<span class='nobreak'><input type='checkbox' name='${var_name}[]' value='$k' $checkme>$label</span> ";
            if ($tablestyle){ $opt .= "</td>";}
          ++$rowcount;
        if ($rowcount%$per_row == 0) {
            $opt .= ($tablestyle)? "</tr><tr>" : '<br>';

        }
    }
        if ($tablestyle){ $opt .= "</tr></table>\n";}
      return $opt;
}

function choose_graphic_url($dir,$id){
            /*looks for either a jpeg or png or gif in specified directory
        and returns url to file
        */

         $gfile='';
         $path = SITEPATH . $dir;
        /* try jpg, then png for  file */
        foreach (['jpg','png','gif'] as $ext){
            #echo "testing $path/$id.$ext.. ";
            if (file_exists("$path/$id.$ext")){
                $gfile = "$dir/$id.$ext";
                #echo "Hit on $gfile<br>";
                return $gfile;
            }
         }
         #echo "Album file not found.";
         return false;
}

function get_member_security($code){
	global $G_member_sec;
	$sec = $G_member_sec[$code];

	return $sec;
}

function get_member_description($code){
	global $G_member_desc;
	$desc = $G_member_desc[$code];
	return $desc;
}

function im_here(){
	echo "Utilities are here.";
}

function send_lost_link($this_email){
	global $GV;


	$output = '';
	$msg = "Below is the link for the access to the FLAMEsite attached to $this_email .\n
	There may be more than one.\n\n";

	if (!$this_email){return "No email provided for send_lost_link";}

	$this_email = trim($this_email);
	if (! filter_var($this_email, FILTER_VALIDATE_EMAIL)) {
	  $output .=  "<br/>Bad Address - $this_email - is not a valid email address.</span>";
	  $output .=  "<p><a href='${GLOBALS['siteurl']}'>Return to main page</a></p>";
	  return $output;

	}



	 // Look up this address in DB
		echo "Looking for $this_email<br>\n";
		$q = "SELECT upw, user_id, username, user_email from ${GLOBALS['members_table']} WHERE user_email LIKE '$this_email'
		AND status NOT in('x','d','n');";

	   $result = mysqli_query($GLOBALS['DB_link'],$q);
	   if(mysqli_num_rows($result)<1) // Not there - notify user
	   {
		$output .= "<p>$this_email was not not found in the member file.";
		$output .=  "<p>Please <a href='mailto:admin@amdflames.org'>contact the administrator</a>.</p>";
		$output .=  "<p><a href='${GLOBALS['siteurl']}'>Return to main page</a></p>";
		return $output;

	   }

	 // List each match (some addresses are shared by two or more members)
		  while ($row = mysqli_fetch_assoc($result))
		  {
			$login = $row['upw'] . $row['user_id'];
		   $msg .= "${row['username']}:  ${GLOBALS['siteurl']}/?s=$login\n";

		  }


	 // mail("admin@amdflames.org", $subj, $msg, $hdrs);
	 $hdrs =	$GLOBALS['from_admin'];
	 $hdrs .=	 "cc: ${GLOBALS['admin']}\r\n";

	 mail($this_email,"Your FLAMEsite Login",$msg,$hdrs);

	 	 $output .=  "Your login link has been emailed to &lt;${this_email}&gt;.";

	 return $output;
 }

 function full_copy( $source, $target ) {
 	// copies entire directories
	if ( is_dir( $source ) ) {
			if (!is_dir($target)){mkdir($target);}

			$d = dir( $source );

			while ( FALSE !== ( $entry = $d->read() ) ) {
					if ( $entry == '.' || $entry == '..' ) {
							continue;
					}
					$source_file = $source . '/' . $entry;
					$target_file = $target . '/' . $entry;

					if ( is_dir( $source_file ) ) {
							full_copy( $source_file, $target_file );
							continue;
					}
					#echo "<br>cp $source_file, $target_file";
					if (!copy( $source_file, $target_file )){return FALSE;}

			}

			$d->close();
	}
	else {
			copy( $source, $target );
	}
	return TRUE;
}


function decompress($data,$defs){
	//to turn a string of character codes into a descriptive string.
		$my_choices = '';

		// step through the codes and values in the defining array
		foreach ($defs as $k=>$v){  # D => '60s'
			if (strchr($data,$k)){$my_choices .= "$v, ";}
		}
		// chop off trailing ,\s
		if ($my_choices){$my_choices = substr($my_choices,0,-2);}

		return $my_choices;
}
function charListToString ($clist){
    #recho ($clist,'clist in charListToString');
    $vstring = '';
    if (!empty($clist) && is_array($clist)){
			foreach ($clist as $v){
				$vstring .= $v;
			}
	}
	return $vstring;
}

function make_links($input){
    // replaces http:... with a link
    // replaces [asset nn] with a thumbnail to the asset item.


    // first find urls
    if ($n = preg_match_all(URL_REGEX,$input,$m)){
        $urls = array_unique($m[0]);
        foreach ($urls as $u){
            $u = trim($u);
           $input = str_replace($u,"<a href='$u' target='_blank' title='$u'>$u</a>",$input); //<a href='$u' target='_blank' title='$u'>$u</a>"
        }
    }
    #also look for asset references
     if ($n = preg_match_all ('/\[asset (\d+)\]/',$input,$m)) {
         for ($i=0;$i<$n;++$i){
            $assetlink = $m[0][$i];
            $thisid = $m[1][$i];
            if (! $assetcode = get_asset_by_id ($thisid) ){
                $asset_code = "[ Could not get asset  $thisid ]";
            }
            $input = str_replace($assetlink,"$assetcode",$input);
        }
    }


    return $input;
}
function set_userid($user,$userid){
    if (!empty($user) && empty($userid)){
    #looks up user to see if valid ,checking aliases, and returns
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



function opportunity_count(){
   $pdo = MyPDO::instance();
    $sql = "SELECT count(*) FROM opportunities WHERE
            expired = '0000-00-00' OR
            expired > NOW();";

    $opp_rows = $pdo -> query($sql) -> fetchColumn();
    return $opp_rows;

}


function if_admin ($text) {
    if ($_SESSION ['level'] > 8 ){  return $text;}
    else {return '';}
}

 function send_verify($id,$new_status){
 	
	// Sends notices and updates the email_status for a user.
	// For most statuses, it sends a verification email to user.
	// For lost statuses it sends a notice to admin
	// For unlisted status, it silently updates the status.
	$simulate = 0; #set to 1 to verbosely simulate operation

	global $GV;
	if (! is_numeric($id)){die ("Illegal id requested for send verify");}
	if ($id < 10000){
		$where = "id = $id";
	}else {
		$where = "user_id = $id";
	}
	$pdo = MyPDO::instance();
	$sql = "SELECT * from `members_f2` WHERE $where";
	$row = $pdo->query($sql)->fetch();
	

	$login = $row['upw'] . $row['user_id'];
	$email = $row['user_email'];
	$prior = $row['prior_email'];
	$name = $row['username'];
	$subscriber = $row['no_bulk']?'No':'Yes';
	$uemenc = rawurlencode($email);
	$send_user = 1; #means send email.  will be set to 0 if silent update
	$send_admin = 0;
	$send_to = "$name <$email>";
	$verify_url = SITEURL . "/scripts/verify_email.php?s=$login";
	$profile_url = SITEURL . "/scripts/profile_update.php?s=$login";
	$login_url = SITEURL . "/?s=$login";

	#echo "In send_verify.  id: $id; email: $email<br>\n";
    $bulkwarn = '';
    if ($subscriber == 'No'){$bulkwarn = "

    The FLAMEsite sends out an email whenever a new newsletter is
    published, typically once a week.  YOU ARE NOT CURRENTLY RECEIVING
    THIS.  If you'd like to keep informed about AMD alumni, go to your
    profile using the link below, and UNcheck the box 'No Email Updates'.

    Edit your profile at:
      $profile_url

    ";
    }

	switch ($new_status){
	case 'N1':
			$subj = "AMD Alumni FLAMEs Signup Verification - Action Required!";
	  		$msg = "
	Thanks for signing up for the FLAMEs AMD Alumni News
	site, $name.

	To confirm your signup and receive a temporary password,
	click on the link below:

  	$verify_url

	You must confirm within 3 days to activate this signup.

	After you have clicked the link above, an administrator
	will review your signup and send you an email to confirm
	your membership. This could take a day or two.

	You will receive your personal login and have full access then.
";
		break;

		case 'N2':
			$subj = "AMD Alumni FLAMEs Signup Verification - Second Request";
			$msg = "
	$name, a few days ago we sent you an email asking you
	to confirm this email for your signup on the AMD Alumni
	FLAMEs site. We haven't heard back from you.

	To confirm this is your correct email, please click on the
	link below:

  	$verify_url

	Otherwise, your signup will be cancelled.
";
			break;



	case 'B1':
			$subj = "Your AMD FLAMEs Email Bounced - Action Needed";
			$msg = "
	$name,  The AMD Alumni FLAMES  site recently
	sent you an email at this address that bounced.
	Can you please confirm that this email is correct for you?

	If this email <$email> is correct, please just click on the
	link below to confirm:

  	$verify_url
";
		break;

	case 'B2':
			$subj = "AMD FLAMEs Email Bouncing - Second Request!";
			$msg = "
	$name,  we recently sent you an email advising you that
	email sent to this address <$email>
	from the AMD FLAMEs site was bouncing.

	We didn't hear back from you, we're trying again.

	If this message gets through to you, please click on
	the link below to confirm:

  	$verify_url

	Otherwise we will have to mark you as a Lost Member.
";
			break;

	case 'E1':
			$subj = "AMD Alumni FLAMEs Updated Email  - Please Verify!";
			$msg = "
$name, the email for your membership on the FLAMEs AMD Alumni
site was just updated from $prior to $email.

(If you didn't do this, another FLAME member may have sent us
an updated email for you. We publish the names of a few lost
members each week, and ask for help getting back in touch.)

To confirm this update, click on the link below:
  $verify_url

If you haven't updated your profile for a while, the link below
is your personal login to the site:
  $login_url

";
			break;

	case 'E2':
			$subj = "AMD FLAMEs Updated Email Verification - Second Request!";
			$msg = "
	$name, about two weeks ago, your email
	on the AMD FLAMEs site was changed, and we sent
	you an email asking you to verify the change.

	You didn't responded, so now we're wondering if
	this was a mistake?

	If this is your correct new email, please click on the
	link below:

  	$verify_url

	If you need to change your email or update your profile,
	click here:

  	$profile_url

	If you've already verified your email, or something else seems
	wrong, please contact the admin by just replying
	to this message.
";
			break;


	  case 'A1':

			$subj = "AMD Alumni FLAMEs Site Request  - Please respond.";
			$msg = "
	From time to time we send out emails to confirm that
	the emails we send out to our AMD Alumni members are getting through, and to
	ask them to check their profile information.

	$name, your time has come.

	The site is now located at a new domain: amdflames.org.
	We hope you get this message.  Just click the link below
	to confirm that this is your email:

	$verify_url

	$bulkwarn

";
			break;

		case 'A2':
			$subj = "AMD Alumni FLAMEs Email Verification - Second Request";
			$msg = "
	$name, about a week ago we sent you an email
	asking you to update your profile and confirm that this is
	your correct email.  We haven't heard back from you, so we're
	giving it another shot.

	Please click on the link below to simply verify that this
	email still works for you.

  	$verify_url

  	$bulkwarn
";
			break;

		case 'A3':
			$subj = "AMD Alumni FLAMEs Email Verification - Third Request";
			$msg = "
	$name, about 2 weeks ago we sent you an email asking you to confirm
	that this is your correct email.  We haven't heard back from you.

	To confirm this is your email, please click on the link below:

  	$verify_url

  	$bulkwarn
";
			break;


		case 'A4':

			$subj = "AMD Alumni FLAMEs Email Verification";
			$msg = "
	$name, We have been trying to contact you for several weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we aren't sure you are stilling using this address.

	If this is your correct email, please click on the link below:

  	$verify_url

	If you have any questions or concerns, please contact the administrator.
	We don't want to lose track of you.  If you do not verify,
	your user status will be set to 'Lost' and you won't receive any
	more emails from us.
";
			break;

		#deferered lost because user is loggin in
		case 'D':

			$subj = "AMD Alumni FLAMEs Email Verification";
			$msg = "
	$name, We have been trying to contact you for several weeks
	to confirm that your email address on the AMD Flames Alumni site
	is correct.  This email isn't bouncing, but we haven't heard back
	from you, so we aren't sure you are stilling using this address.

	If this is your correct email, please click on the link below:

  	$verify_url

	If you have any questions or concerns, please contact the administrator.
	We don't want to lose track of you.
";
			break;


	case 'LA':
	case 'LB':
	case 'LO':
	case 'LE':
		// send message to admin, not to user
		if ($new_status == 'LA'){
		     $t = "There has been no response to
	several requests to verify email address";}
		elseif ($new_status == 'LB'){
		    $t = "Email has repeatedly bounced.";}
		elseif  ($new_status == 'LE'){
		    $t = "The user changed email address but
	but has not confirmed receiving
	email at the new address.";}
        else { $t = "";}


$admin_msg = "
Alert to FLAMES administrator:

    Email to FLAMES user $name is apparently not getting through.
	$t
	$name has been set to Lost Status $new_status.

	Please attempt to manually reconnect with this user.
---------------------
   User: $name
   Current email: $email
   Previous email: $prior
   Last login: ${row['last_login']}
   Email address changed on: ${row['email_chg_date']}
   Email last validated: ${row['email_last_validated']}
   Profile last updated: ${row['profile_updated']}
   Profile last validated: ${row['profile_validated']}
   Receiving weekly newsletter: $subscriber

 To update your Profile: $profile_url

 To verify your email: $verify_url

--------------------
		";
		$send_admin = 1;
		$send_user = 0;
		break;

	default:
			// silently update email status
			$send_user=0;
		} #end swithc

		$msg .= "

	If you've already verified your email,  or you think this message
	is in error, please email the admin by replying to this email, so
	I can fix the problem.
	This email was sent by a automated program but your reply will be
	read by a human, namely me.

--
	Regards,
	AMD FLAME site administrator
	admin@amdflames.org

	";

		preg_replace('/\t/',"    ",$msg);
	preg_replace ('/\r?\n/',"\r\n",$msg);

$simulate=0;
		if ($simulate){
			if ($send_user){echo "Preparing to send verify email to $name at ",h($email)," and update to $new_status<br>\n";}
			if ($send_admin){echo "Preparing to send admin notice about $name at ",h($email)," and update to $new_status<br>\n";}
			if (!$send_user && !$send_admin){echo "Preparing to silently update $name at ",h($email)," and update to $new_status<br>\n";}
		}
		else {
		if ($send_admin){mail($GV['admin'],"Lost user - $name",$admin_msg,"${GLOBALS['from_admin']}Reply-to:$send_to");}

		   if ($send_user){
		   	mail($send_to, $subj, $msg, $GLOBALS['from_admin'],"-f postmaster@amdflames.org");
		   	}
		   set_mu_status ($id,$new_status);
	 	 }

	  return 1;
}

function set_email_status($id,$m_status){
    $r = set_mu_status($id,$m_status,'');
    return $r;
}

function set_mu_status($id,$m_status='',$u_status=''){
	//
	$today = sql_today();
	$sqla = array();
	unset ($sqla);
	if (!empty($m_status)){
	    #$sqla[] = " previous_ems = email_status "; #Note: this is in trigger
	    $sqla[] =  " email_status = '$m_status' ";
	    if ($m_status == 'Y'){$sqla[] = " email_last_validated = NOW()";}

	}
	if (!empty ($u_status)){
	    $sqla[] = " status = '$u_status'";
	}

	if (!empty ($sqla)){
    	$sql = "UPDATE `members_f2` SET "
    	. implode(',',$sqla) .
	   " WHERE id=$id;";
	 $result = mysqli_query($GLOBALS['DB_link'],$sql);
	if ($result){return 1;}
    }

	return 0;
}



function h($var){
    #convert < > " & , but not ' (default ENT_COMPAT)
	return htmlspecialchars($var,ENT_QUOTES);
}
function spchar($var){
    #convert < > " & , but not ' (default ENT_COMPAT)
	return htmlspecialchars($var,ENT_QUOTES);
}
function spchard($var){
    #convert < > " & , but not ' (default ENT_COMPAT)
	return htmlspecialchars_decode($var);
}

function hte($var,$row=array()){
    #returns htmlentities, call with value or with name + array
    if (!empty($row)){$val= $row[$var];}
    else {$val = $var;}
    $h = htmlentities($val,ENT_QUOTES);
    return $h;
}
function hted ($var) {
    return html_entity_decode($var);
}

function verify_click_email ($id,$email) {
    $enc_user_email = rawurlencode($email);
	$text = <<< EOT
	<a href="#" onclick="v_window = window.open('$GLOBALS[siteurl]/scripts/verify_email.php?m=$enc_user_email&t=click&r=$id','Verify','height=200,width=200,x=200,y=200');v_window.move_To(200,200);return false;">click here</a>
EOT;
	return $text;
}

function verify_click_profile ($id) {
	global $GV;


	$text = <<< EOT
	<a href="#" onclick="v_window = window.open('$GLOBALS[siteurl]/scripts/verify_profile.php?t=click&r=$id','Verify','height=200,width=200,x=200,y=200');v_window.move_To(200,200); return false;">click here</a>
EOT;
	return $text;
}

function age($date_str) {
	//takes a date and returns the age from today in days and a formatted version of date
	global $DT_zone ; #time zone object
	global $DT_now;
	if (!$date_str){ #blank or NULL??
		return array('99999','no_date');
	}
	$vd = new DateTime($date_str,$DT_zone);
	$diff = $vd -> diff($DT_now);
	$diff_str = $diff->format('%a');
	$last_val = $vd->format ('M j, Y');
	#echo "$date_str, $diff_str, $last_val<br>\n";
	return array ($diff_str,$last_val);
}
function pic_link($loc,$cap){
    #returns the pic with caption hotlinked to it's raw image

    $t = "<div class='photo'><a href='$loc' target='_blank' decoration='none'>
        <img src='$loc' ></a><p class='caption'>$cap<br>
        <small>(Click image to view source)</small></p>
        </div>";
    return $t;
}

function extract_email ($text){
preg_match('/^(.\s+)?.*?([\w\.\-]+@[\w\.\-]+)/',$text,$m);
	$email = $m[2];
	return $email;
}



function pecho($text){
    echo "<p>$text</p>";
}
function preecho($text){
    echo "<pre>\n$text\n</pre>\n";
}
function recho($var,$title=''){
    echo "<h4>$title:</h4>";
    echo "<pre>" .  print_r($var,true) . "</pre>\n";
}

function days_ago ($date_str) {
	//takes a date and returns the age from today in days 

	if (empty($date_str)){ #blank or NULL??
		return 9999;
	}
	$dt = new DateTime();
	$t=$date_str; #may change
	if (!is_numeric($date_str)){
		if (! $t=strtotime($date_str) ){
			echo "Cannot understand date $date_str";
			return 0;
		}

	}#is unix time
	$dt->setTimeStamp($t);
	
	$now = new DateTime();
	
	$diff = $dt -> diff($now);
	$diff_str = $diff->format('%a');
	
	
	return $diff_str;
}

function get_latest_pub_date($form='sql')
{
    /*
     forms are sql: 2017-01-29
        conventional: January 29, 2017
        sqldt: 2017-01-29 07:30
        timestamp: 2023409823408
    */

    #from new publishing script 8/12/16
    $file1 = SITEPATH . '/news/last_published_time.txt';

    if (!file_exists($file1)){
        return "Latest pub date file not found";
    }

    $pdate = file_get_contents($file1);
    $tstamp = strtotime($pdate);

     switch ($form) {
        case 'sql':
            $rdate = date('Y-m-d',$tstamp); break;
        case 'conventional':
            $rdate = date('d M, Y',$tstamp); break;
        case 'sqldt':
            $rdate = date('Y-m-d H:m',$tstamp); break;
        case 'timestamp':
            $rdate = $tstamp;break;
        default:
            $rdate = "Error: date format $form not understood";
    }
    return $rdate;

}


