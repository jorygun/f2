<?php
namespace digitalmx\flames;

/*
   Start new html page

*/


class DocPage {

   public function __construct ()

      {

   }
   public function getHead($title, $min = 0, $options=[]){
      /* options:
         'tiny' = include tinymce
         'ajax' = include jquery, ajax
         'votes' = iinclude voting script/css
      */
      if ($_SESSION['login']['seclevel'] < $min){
         $header = "HTTP/1.1 403 Forbidden" ;
         #echo $header;
        header($header);

      }

      if (REPO != 'live'){ $title .= " (" . REPO . ")";}

     $t =  <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>$title</title>
   <link rel='stylesheet' href = '/css/news3.css' />
   <script src='/js/f2js.js'></script>
EOT;
      if (in_array('tiny',$options)){
         $t .= "
         <script src='/jsmx/tinymce/tinymce.min.js'></script>
         <script src='/jsmx/tiny_init.js'></script>
         ";
      }
      if (in_array('ajax',$options)){
         $t .= "
         <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js'></script>
         <script src='/js/ajax.js'></script>
         ";
      }
      if (in_array('votes',$options)) {
         $t .= "
         <link rel='stylesheet' href='/css/votes.css' />
         <script src='/js/voting3.js'></script>
         ";
      }

      return $t;
   }

   public function startBody($title,$graphic=1,$subtitle='') {
      #choose a graphic by number
      switch ($graphic) {
      case 1:
         $gsource = "/graphics/logo-FLAMEs.gif";
         break;
      default:
         $gsource = '';

   }

      $t = "<div class='page_head'>\n";

	if (!empty($gsource)){
	   $t .= "<img class='left' alt='AMD Flames' src='$gsource'>";
	}
	$t .= <<<EOT
	<p class='title'>FLAME<i>news</i><br>
	<span style='font-size:0.5em;'>$subtitle</span>
	</p>
EOT;
   $t .= $_SESSION['menu'];
   $t .= "<hr style='width: 100%; height: 2px;clear:both;'>";

   $t .= "</div>\n";


      return $t;
   }

}
