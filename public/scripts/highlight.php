<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
/*
Show selected document with search string highlighted.
 e.g. showhigh.php?file=news/news-041126.html&sstr=Bob Harris
 script also adds a base tag to maintain relative links

*/

// Get parameters
 $reloc = $_GET['file'];
//t $reloc = "/archive/news-071102.html";
$fpath = "$GLOBALS[sitepath]/$reloc";
preg_match('/(\d{6})/',$fpath,$m);
$fdtag = $m[1];

 $term = $_GET['sstr'];

 $term = preg_replace('/\s+/',"\s+",$term); // Dodge LF's in the target string.
# $srch  // Add syntax marks for search pattern


$buffer = file_get_contents($fpath);

#echo $fpath, strlen($buffer);
// Format a regex search string

 $srch = "/(\b|&nbsp;)($term)(\b|&nbsp;)/i"; // String must follow a > or a space or a &nbsp;.
$repl = "$1<span style='background-color:yellow;font-weight:bold;'>$2</span>$3";
// Highlight search strings in document
#  $hl = "\$1<font style=\"background-color: yellow;\">" .$sstr. "</font>";

  $buffer = preg_replace($srch, $repl, $buffer);

// Modify the base so all the links work
	$buffer = str_replace('<head>',"<head>\n<base href='$reloc'",$buffer);
	$buffer = str_replace('<?=$assets_folder?>',"/newsp/Assets-$fdtag",$buffer);
// Modify the src statement to show images from the news folder
#   $buf = preg_replace('/src=\"/',"src=\"newsp/",$buf);

// Display highlighted document
  echo $buffer
  #echo  `php "$buf"`;


?>
