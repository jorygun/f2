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
	void = catchError ($e, $more)
	
*/

function echop($text){
    echo "<p>$text</p>";
}

function echopre($text){
    echo "<pre>\n$text\n</pre>\n";
}

function echor($var,$title=''){

    echo "<h4>$title:</h4>";
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

function catchError ( $e , $more=[]){
	echo "<p class='red'>Failed " . $e->getFile() . ' at ' . $e->getLine() . ': </p>' . BRNL;
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

            
            $null = null;
            if (empty($val)){ #catches 0, '', and false
            	if ($var == 'asset_id'){ 
            		$val = 0;
            		#echo "setting asset id to 0";
            	} #leave out of list // no, leave in and set to null
   
            }
            
				$db[$var] = htmlspecialchars_decode($val);
				
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

function buildOptions($val_array,$check=''){
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

	#echo "check: $check.  options:", $opt,"<br>";
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



 function full_copy( $source, $target ) {
 	// copies entire directories.. like cp -r
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

function url_exists($url){
   $headers=get_headers($url);
   return stripos($headers[0],"200 OK")?true:false;
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


