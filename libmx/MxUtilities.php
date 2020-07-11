<?php
namespace Digitalmx;

use \Exception as Exception;

/* miscellaneous utility scripts
	echopre(string) / echos <pre>string</pre>
	echor(array,title) / echos tiel and print_r array
	echop(string) / echos <p>string</p>

	string = spchar($string) /replaces spec chars
	string = spchard($string / restores spec chars

	bool = delete_dir(path) /removes files, subs, and dir
	bool = isValidEmail (email) /checks with filter
	array = get_url(url) /uses curl, content is in [content=>xxx]
	string = detab_text(string) / replaces tabs with spaces
	array = list_recent_files ($path,number) / list of file names in path
	string = sqldate(format,when) /date or time, now or a date
	string = safelike($string) / excapes _ and %

	array = pdoPrep(post,accept,key) / complicated.  data for a pdo insert or update
	array = stripslashes_deep(array) / removes slashes
	string = buildCheckBoxSet(var_name, def_array,checkedlist) / builds set of checkbox
	bool = full_copy(source,dest) / copies source dir to dest dir (like cp -r)
	string = decompress (keys,defs) / lists values of defs for items in keys
	string = charListToString(list) / implodes list
	string = makelinks(strings) / replaces urls with links
	d = days_ago(date) /days since date
	void = catchError ($emsg, $e, $more)

*/

function echop($text){
    echo "<p>$text</p>";
}

function echopre($text){
    echo "<pre>\n$text\n</pre>\n";
}

function echoc($text,$title=''){
// echo block of code
    echo "<div class='code'>";
    if ($title) echo "<u>$title:</u><br><br>";
    echo nl2br($text) ;
    echo "</div>" . NL;
}

function getref() {
	// remove any refs to this file
	$me = basename(__FILE__);
	$bt = debug_backtrace();
	$caller['file'] = $me;
  	while (strpos($caller['file'], $me) !== false ) {$caller = array_shift($bt);}
	$title = basename($caller['file']) . '(' . $caller['line'] . ')';
	return $title;
}

function echor($var,$title=''){
	$ref = getref();
	$title = "$title ($ref)";

	// if (empty($title)) {
// 		$bt = debug_backtrace();
//   		$caller = array_shift($bt);
// 		$title = basename($caller['file']) . '(' . $caller['line'] . ')';
// 		}
    echo "<h4>$title</h4>";
    echo "<pre>" .  print_r($var,true) . "</pre>\n";
}
function txt2html($text){
	// returns text coverting line feeds to <br>s and entities
	$text = htmlspecialchars($text,ENT_QUOTES);
	$text = nl2br($text);
	return $text;
}

function special($var){
    #convert < > " & , but not ' (default ENT_COMPAT)
	return htmlspecialchars($var,ENT_QUOTES);
}
function despecial($var) {
	return htmlspecialchars_decode($var);
}

function catchError ( $emsg, $e , $more=[]){
	echo "<p class='red'>Error $emsg</p> " . NL;
	echo $e->getFile() . ' (' . $e->getLine() . ') </p>' . BRNL;
	echo $e->getMessage() . BRNL;
	if ($more) {
		echo "------------" .BRNL ;
		foreach ($more as $var => $val){
			if (is_array($val)){
				echor($val,$var);
				continue;
			}
			echo "<b>$var: </b><br> $val" . BRNL;
		}
	}

	echo "<hr>\n";
}
function get_youtube_id($url) {
	return youtube_id_from_url($url);
}
function youtube_id_from_url($url) {
	return is_youtube($url);
}

function is_youtube($url) {

             $pattern =
				'%#match any youtube url
                (?:https?://)?  # Optional scheme. Either http or https
                (?:www\.)?      # Optional www subdomain
                (?:             # Group host alternatives
                  youtu\.be/    # Either youtu.be,
                | youtube\.com/
                )				# or youtube.com
                (?:          # Group path alternatives
                    embed/     # Either /embed/
                  | v/         # or /v/
                  | watch\?.*?v=  # or /watch\?xxxxx&v=
                ) ?            # or nothing# End path alternatives.
                               # End host alternatives.
                ([\w-]+)  # Allow 10-12 for 11 char youtube id.
                %x'
                ;
            $result = preg_match($pattern, $url, $matches);
            if (array_key_exists(1,$matches)){
            	$vid = $matches[1] ;
           	 	#echo "Matched youtube $matches[0] to video id $vid " . BRNL;
           		return $vid;
           	}
            else {
            	#echo "No youtube id in $url" . BRNL;
            	return false;
            }
 }

 function get_url($url) {
     $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => false,     // follow redirects
        CURLOPT_ENCODING       => "",      // handle all encodings
        CURLOPT_USERAGENT      => "MxUtilities", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 10,      // timeout on connect
        CURLOPT_TIMEOUT        => 10,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
         CURLOPT_REFERER		=> 'https://amdflames.org',
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


    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $data;
    return $header;
}


function humanSecs($esecs){
    // express time in secs in human readable
    if (! $esecs = intval($esecs) ) {
		die ("$esecs is not an integer.");
	}

    $t = '';
    $edays = intval ($esecs/ 86400);
    if ($edays > 0){
        $esecs %= 86400;
        $t .= "$edays days, ";
    }
    $ehrs = intval ($esecs / 3600);
    if ($ehrs > 0) {
        $esecs %=  3600;
        $t .= "$ehrs hours, ";
    }
    $emins = intval ($esecs / 60);
    if ($emins > 0) {
        $esecs %= 60;
        $t .= "$emins minutes,  ";
    }

    $t .= "$esecs seconds.";

    return $t;
}
function validateDate($date, $format = 'Y-m-d')
{
    $d = \DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}


function deleteDir($src, $keep=false) {
	// deletes all the contents of $path
	// if $keep=false, also deletes the dir at path.
    if (!is_dir($src)) {
        throw new \InvalidArgumentException("$src is not a directory");
    }

     if (file_exists($src)) {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    deleteDir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        if ($keep){echo "Keeping dir $dir" . BRNL;}
        elseif (! rmdir($src) ){ echo "Cannot delete $src" . BRNL;}
        return true;
    }


}
function emptyDir ($path) {

      try{
        $iterator = new \DirectoryIterator($path);
        foreach ( $iterator as $fileinfo ) {
          if($fileinfo->isDot())continue;
          if($fileinfo->isDir()){
            if(deleteContent($fileinfo->getPathname()))
              @rmdir($fileinfo->getPathname());
          }
          if($fileinfo->isFile()){
            @unlink($fileinfo->getPathname());
          }
        }
      } catch ( \Exception $e ){
         echo "Error: " . $e->getMessage();
         return false;
      }
      return true;

}

function isValidEmail($email){
	return is_valid_email($email);
}
function is_valid_email($email){
	return (filter_var($email, FILTER_VALIDATE_EMAIL)) ? 1 : 0;
}

function echoAlert($text) {
	#$text=addslashes($text);

	echo '<script>alert("' .$text . '")</script>';
	return ;
}


function detab_text($message){
   return  preg_replace('/\t/',"   ",$message);
}

function email_std ($message){
	$message = str_replace("\t",'    ',$message);
	$message = preg_replace('/\r?\n/',"\r\n",$message);

  $array = explode("\r\n", $message);
  $message = "";
  foreach($array as $line) {
  	$newline = wordwrap($line, 70, "\r\n", true);
  	if (strcmp($newline, $line) != 0) {
  		echo "warning: long email line shortened: " . BRNL
  		. $line . BRNL;
  	}
   $message .= $newline;
   $message .= "\r\n";
  }
  return $message;
}

function list_recent_files($number,$path){
/**
	#returns a list of n most recent files of type in directory.

**/
	$latest_ctime = 0;
	$mods = array(); $fnames=array(); $files=array();

	if (is_dir($path) == false){return [];}

    foreach (glob($path . '/*') as $f) {
        $mtimes[filemtime($f)] = $f;
    }
    krsort($mtimes);
    $files = array_values(array_slice($mtimes, 0, $number, true));
    $fnames = array_filter($files,function($f){return basename($f);});
    return $fnames;
}
function makeDate($when, $form='human',$type = 'date') {
	return  make_date ($when, $form,$type);
}

function sqlnow() {
	return make_date('now','sql','datetime');
}
function make_date ($when, $form='human',$type = 'date'){
	/* returns formated date or datetime or 'never'
		@ when is either text date/time or unix timestamp or 'now' or empty (= never)
		@ form is human, sql, rfc or ts (time-stamp)
		@ type is date or datetime

	// when is either timestampe text data/time
	*/

	if (empty($when)){
		$ts = 1; #Jan 1 1970
	} elseif ($when == 'now'){
		$ts = time(); #now
	} elseif ( is_integer($when)){
		$ts =  (int)$when;
	} else {
		$ts = (int)strtotime($when);
	}

	if ($ts <= 1){return 'Never';}

	$dt = new \DateTime();
	$dt->setTimestamp($ts);

	switch ($form){
		case 'sql' :
			$format = ($type == 'datetime')?
		'Y-m-d H:i:s' : 'Y-m-d';
			break;
		case 'human' :
			$format = ($type=='datetime')?
		'd M Y H:i' : 'd M Y';
			break;
		case 'rfc' :
			$format = 'c';

			break;
		case 'ts' :
			return $ts;
			break;
		default :
			throw new Exception ("unknown format $form for make_date");
	}


	if (! $dt ){
		echo "Cannot set date from $when in make_date";
		return '??';
	}
	return $dt->format($format);
}


function safe_like ($text){
	#escapes special chars in sql LIKE data
	$safe = preg_replace('/%/','\%',$text);
	$safe = preg_replace('/_/','\_',$safe);
	return $safe;
}

function makeButton($type='open',$label,$href) {
	// types open=window.open, loc = window.location
	if ($type=='open'){$wf = "window.open('$href');";}
	elseif ($type ='loc'){$wf = "location='$href';";}
	else {die ("unrecognized type '$type' for makeButton");}

	$b = <<<EOT
	<button type='button' onClick = $wf >$label</button>
EOT;

	return $b;
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


   $prep = u\pdoPrep($post_data,array_keys($model),'id');

	$sql = "INSERT into `Table` ( ${prep['ifields']} )
			VALUES ( ${prep['ivals']} );

		$stmt = $this->pdo->prepare($sql)->execute($prep['data']);
		$new_id = $pdo->lastInsertId();

    $sql = "INSERT into `Table` ( ${prep['ifields']} )
    		VALUES ( ${prep['ivals']} )
    		ON DUPLICATE KEY UPDATE ${prep['update']};
    		";
       $stmt = $this->pdo->prepare($sql)->execute($prep['udata']);
       $new_id = $pdo->lastInsertId();

    $sql = "UPDATE `Table` SET ${prep['update']} WHERE id = :pdokey ;";
       $stmt = $this->pdo->prepare($sql)->execute($prep['udata']);


  **/
         $db =  $ufields = $ifields = $ivalues = $dbu =  array ();


        #transfer fields from arr to db

        foreach ($data as $var => $val){

         // ignore any fields not listed in valid fields


            //find key field which is returned separately
            if (! empty($key) && $var === $key){
                $prepared['key'] = $val;
         	}

				if ( !empty($include) and ! in_array($var,$include) ){ continue; }

				//$db[$var] = htmlspecialchars_decode($val);
				$db[$var] = $val;


			// leave key out of update fields, but add value back in as pdokey
				if ($var !== $key) {
					$uvar = 'u'.$var;
					$udb[":$uvar"] = $val;
					$ufieldsu[] = "`$var` = :$uvar"; #new way
            	$ufields[] = "`$var` = :$var"; #old way
            }
            else {
            	$udb[':pdokey'] = $val;
            }

            $ifields[] = "`$var`";
            $ivalues[] = ":$var";



        }

			if (! empty($key) && empty($prepared['key'])) {
				throw new Exception ("Key $key specified but did not find key in data");
			}

        $prepared['data'] = $db; #all data for insert
        $prepared['update'] = implode(', ',$ufields);
        $prepared['updateu'] = implode(', ',$ufieldsu);
        $prepared['ifields'] = implode(', ',$ifields);
        $prepared['ivals'] = implode(', ',$ivalues);
			$prepared['udata'] = $udb; #all values for an insert


        return $prepared;
    }

function prepPDO($datain,$include=[], $keyfield=''){
$type = 'UI'; // do both
 /**
  *   Rewrite of pdoPrep
  *  to prepare fields for a pdo execute.
  *
  *  $datain = data array (var=>val),
  *  $include = list of vars in $data to insert/update
  *    ( all vars included if include is empty; )
  *  if $keyfield is specified, it will be removed from update data
  *     and its value is returned in the data array as ':pdokey',
  *		so "sql = ... WHERE id = :pdokey"

  *
  *  returns array of arrays:
      'data' = array of placeholder=>val,
        	(datain with :var => val, but only with fields in include_vars)

      'uset' = text string for update SET assignment, like
            email=:email,status=:status

      'ifields' text like email,status,... for use in insert fields.
      'ivals' text like :email,:status,... for use in insert values .


   	if type == IU, (insert ON DUP KEY update) then the data includes additional
   	rows :uvar = val for use in the update portion (cannot re-use names )

/**
including key field removes that field from udata and adds value to ukey
PREP:
   $prep = u\prepPDO ($post_data,allowed_list,'key_field_name');

INSERT:
		$sql = "INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']})";

UPDATE:
		$sql = "UPDATE `Table` SET ${prep['uset']} WHERE id = $prep['ukey'];";

INSERT ON DUP UPDATE:
   	$sql = INSERT into `Table` ( ${prep['ifields']} ) VALUES ( ${prep['ivalues']} )
    			ON DUPLICATE KEY UPDATE ${prep['uset']};
    		";

THEN:
       $sth = $pdo->prepare($sql);
THEN:
		$sth->execute($prep['idata']); // for insert, or udata for update or merge for both
       $new_id = $pdo->lastInsertId();
**/

  	if (! in_array($type,['U','I','UI'])) {
  		throw new Exception ("Illegal prepPDO type: $type");
  	}
  	if (!is_array($datain)){
  		throw new Exception ("prepPDO called with no data array");
  	}
  	if (!is_array($include)){
  		throw new Exception ("prepPDO called with no include array");
  	}
  	if ( strpos($type,'U') !== false ) {
  		// if update, then the sql needs a WHERE clause.
  		// if user defines key field here, it will be replaced in data array
  		// with :pdokey=val so user says WHERE field = :pdokey,
  		// if its an update and no key is specified, then all datain will
  		// be in update set var=val and user manually does the WHEre clause.
  		//
  		if (!empty($keyfield) && ! isset ($datain[$keyfield]) ){
  			throw new Exception ("prepPDO update missing key field in data");
  		}
  		// if (strpos($type,'I') !== false  && empty($keyfield)) { // is insert on DUP
//   				throw new Exception ("prepPDO has UI transaction with no key field defined");
//   		}

  	}

	$data = $if_array = $ival_array = $uset_array = array();
	$uset = $pdokey = $ifields = $ivalues =  $ukey = '';


   #transfer fields from datain to dataout

	foreach ($datain as $var => $val){
		//echo "$var - $val" . BRNL;
   	 // ignore any fields not listed in valid fields
		if ( !empty($include) and ! in_array($var,$include) ){ continue; }

		$ivar = ":i$var";
		$uvar = ":u$var";
		if (strpos($type,'I') !== false) { // insert needed
     		$if_array[] = $var;
     		$ival_array[] = $ivar;
     		$idata[$ivar] = $val;
     	}
     	if (strpos($type,'U') !== false) {
     		if ($var == $keyfield) {
     			// remove from data, but set $key
     			$ukey = $val;
     		} else {
     			$uset_array[] = "$var = $uvar";
				$udata[$uvar] = $val;
			}

      }

	}

	  $prepared['idata'] = $idata; #all data for insert
	  $prepared['udata'] = $udata; #all data for insert

	  $prepared['ifields'] = implode(', ',$if_array);
	  $prepared['ivalues'] = implode(', ',$ival_array);
		$prepared['ukey'] = $ukey;
	  $prepared['uset'] = implode(', ',$uset_array);

	  return $prepared;
   }

function stripslashes_deep ($value){
    $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);
    return $value;
}

function buildOptions($val_array,$check='',$choose = true){
	// val array is keys and values, check is current selection,
	// choose is true to include the Choose One... line
	$opt = '';
	if ($choose) {
		$opt = "<option value=''>Choose One...</option>";
	}


	if( isAssociative($val_array)){
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

	#echo "check: $check.  options:", $opt,"<br>";
	return $opt;
}
function isAssociative($arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
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

          $checkme = (strstr($check, (string)$k))?"checked":'';
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

function is_local ($url) {
// returns false if not a local format
// returns '' if local, but no such file
// if local and file exists, returns mime type
	static $finfo;

	if (substr($url,0,1) != '/') return false;
	$path = SITE_PATH . $url;
	if (! file_exists($path)) return '';
	$mime = mime_content_type($path);
	return $mime;

}


function is_http ($url) {
	if (!substr($url,0,4) == 'http') {return false;}
	// valid url an it exists.  returns mime type
	// if(filter_var($url, FILTER_VALIDATE_URL) == FALSE)  {return false;}
	 $cinfo =  get_info_from_curl($url);
//echor($cinfo);
	if ($cinfo === false ) {return '';}
	 if (empty($cinfo)){ return 'text/html';}
	 $mime = $cinfo['mime'];
//	 echo "mime from curl $mime" . BRNL;
 	return $mime;
}

function is_valid_url($url) {
	if(filter_var($url, FILTER_VALIDATE_URL) !== FALSE) return true;
	return false;
}

 function full_copy( $source, $target ) {
 	// copies entire directories.. like cp -r

	if ( is_dir( $source ) ) {
			if (!is_dir($target)){
				if(!mkdir($target,0775,true)){
					die ("cant make target $target.");
				}
			}

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
	/**
	Converts string of chars into string of defs, comma sep

	@data  character string, like ABCD
	@defs array of defs, like B=>'letter B'
	@returns comma separated list of defs for strings in data

**/
		$choices = [];

		// step through the codes and values in the defining array
		foreach ($defs as $k=>$v){  # D => '60s'
			if (strchr($data,$k)){$choices[] = $v;}
		}
		return implode(', ',$choices);
}

function charListToString ($clist){

   /**
     * converts array of char  to string abc                                   *
     * @clist ['a','b','c']                                                    *
     * @return str abc                                                         *
   **/


   if (is_string($clist)) {return $clist;}
	return implode ('',$clist);
}

function linkHref($url,$label='',$target='' ){
	if (isValidEmail($url)){
		return "<a href='mailto:$url'>$url</a>";
	}
	else {
		if (! empty ($target)){$target = " target = '$target' ";}
		if (empty($label)){$label = $url;}
		return "<a href='$url' $target >$label</a>" ;
	}
}
function list_to_inlist($list) {
	return make_inlist_from_list($list);
}
function make_inlist_from_list($list){
	/* takes php list ['a','b','c']
		returns list  for sql IN() : 'a','b','c',
	*/
	if (empty($list) )return false;
	$qlist = array_map(function ($c) {return "'$c'";},$list);

	$inlist = join(',',$qlist);
	return $inlist;
}

function makeLinks($input) {
	return make_links($input);
}
function make_links($input){
    // replaces http:... with a link
   // replaces emailaddr with link


    // first find urls
    if ($n = preg_match_all(URL_REGEX,$input,$m)){
        $urls = array_unique($m[0]);
        foreach ($urls as $u){
            $u = trim($u);
           $input = str_replace($u,"<a href='$u' target='_blank' title='$u'>$u</a>",$input); //<a href='$u' target='_blank' title='$u'>$u</a>"
        }
    }
    #also look for emails

    $n = preg_match_all(EMAIL_REGEX,$input,$m);
    #echo "input" . " n=$n";
    	if ($n){
        $urls = array_unique($m[0]);
        foreach ($urls as $u){
            $u = trim($u);
           $input = str_replace($u,"<a href='mailto:$u'>$u</a>",$input); //<a href='$u' target='_blank' title='$u'>$u</a>"
        }
    }


    return $input;
}

function link_assets($input) { return $input; //disable this
// links [asset n] to thumbnail of asset id n
  if ($n = preg_match_all ('/\[asset (\d+)\]/i',$input,$m)) {

         for ($i=0;$i<$n;++$i){
            $assetlink = $m[0][$i];
            $thisid = $m[1][$i];
            if (! $assetcode = \DigitalMx\Flames\get_asset_by_id ($thisid) ){
                $asset_code = "[ Could not get asset  $thisid ]";
            }
            $input = str_replace($assetlink,"$assetcode",$input);
        }
    }
   return $input;
}



function range_to_list($text) {
	return number_range($text);
}
function number_range ($text){
		/* accepts a string of numbers separated by anything
			AND ALSO expansion of pairs of numbers separated by a -
			and returns a php array of numbers

		*/
		$number_list = [];

		#look for \d - \d
		if (preg_match_all('/(\d+)\s*\-\s*(\d+)/',$text,$m)){#number range
			#print_r($m);
			#count instances of n - m
			$jc = count($m[0]); #echo "ranges = $jc\n";
				for ($j = 0; $j < $jc; ++$j){
					for ($i=$m[1][$j];$i<=$m[2][$j];++$i){
						 $number_list[] = $i;
					}
					#now remove the pair from the string
				  $text = str_replace ($m[0][$j],' ',$text);
			 }
		}

		#npw add in the rest of the numbers in the string
		if (preg_match_all('/(\d+)/',$text,$m)){
			$jc = count($m[0]); #echo "numbers = $jc\n";
			for ($j = 0; $j < $jc; ++$j){
				$number_list[] = $m[1][$j];
			}

		 }

		return $number_list;
}



function days_ago ($date_str = '1') {
	//takes a date and returns the age from today in days
	// date_str can be normal string or timestamp.
	// routine converts to timestamp and returns days from now.


	$dt = new \DateTime();
	; #may change
	if (is_numeric($date_str)){
		$t = $date_str;
	} elseif (! $t = strtotime($date_str) ){
		#echo "u\days_ago cannot understand date $date_str";
		$t = 0;
	}

	#is unix time
	$dt->setTimeStamp($t);

	$dtnow = new \DateTime();

	$diff = $dt -> diff($dtnow);
	$diff_str = $diff->format('%a');


	return $diff_str;
}

function url_exists($url)
	{
	return get_mime_from_url($url);
	}

function get_mime_from_url($url) {
	//returns mime type for all 3 sources (local, youtube, web) ,
	// or false if invalid url

	$mime = false;
	//echo "starting url exists on $url ... " ;
	$mime=is_local($url);
	if ($mime !== false)  {
		#ok, but may be empty
		//echo "is local  mime $mime" . BRNL;
	}

	if ($mime === false) {
		$vid = get_youtube_id($url);
		if ($vid) {
			$mime = 'video/x-youtube';
			//echo "is yt  mime $mime" . BRNL;
		}
	}

	if ($mime === false) {
		$mime = is_http($url) ;
		if ($mime === false){echo "failed http filter";}
		//echo "<br>is http  mime '$mime'" . BRNL;
		// ok
	}
	if ($mime === false) {
		echo "cannot determine local/yt/http" . BRNL;
	}
	return $mime;
}



function get_info_from_curl ($url) {
	#reutrns array of info about this url
	// also checks if url exists
	 $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => 1,    //  return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",      // handle all encodings
        CURLOPT_USERAGENT      => "http:/amdflames.org", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 10,      // timeout on connect
        CURLOPT_TIMEOUT        => 10,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_NOBODY			=> true,		// don't retrieve the body of the url
    );
	$ch = curl_init($url);
	curl_setopt_array( $ch, $options );
	if (!curl_exec($ch) ) return false;
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($code >= 400) return false;
	$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE)  ;
	if ($semipos = strpos($mime,';')  ){
		$mime = substr($mime,0,$semipos);
	}
	$size = curl_getinfo($ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD) ;
	$result = array (
		'mime' => $mime,
		'size' => $size,
	);
	return $result;

}


function array_filter_keys($arr,$allowed){
		//creates new array from arr containing only keys in allowed list.
		return array_intersect_key($arr, array_flip($allowed));
}
function array_filter_remove($arr,$remove) {
		//creates new array from arr without $remove
		if ( ($key = array_search($remove, $arr) ) !== false) {
    			unset ($arr[$key]);
    	}
    	return $arr;
}

function isInteger($input){
	//returns true if every character in input is a digit
	if (is_array($input)) {
		return false;
	}
    return(ctype_digit(strval($input)));
}

function goBack() {
	echo "<script>window.history.go(-1);</script>";
}

function age_and_date($date_str) {
	//takes a date and returns the age from today in days and a formatted version of date
	// note if date is from a db timestamp field, db will return a date string.
	// was "age" in old utilities.

	if (!$date_str){ #blank or NULL??
		return array('99999','no_date');
	}
	$DT_now = new \DateTime();
	$vd = new \DateTime($date_str);
	$diff = $vd -> diff($DT_now);
	$diff_str = $diff->format('%a');
	$last_val = $vd->format ('M j, Y');
	#echo "$date_str, $diff_str, $last_val<br>\n";
	return array ($diff_str,$last_val);
}

function extract_email ($text){
	preg_match('/^(.\s+)?.*?([\w\.\-]+@[\w\.\-]+)/',$text,$m);
	$email = $m[2];
	return $email;
}


function linkEmail($em,$name){
	return "<a href='mailto:$em'>$name</a>";
}


