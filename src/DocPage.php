<?php
namespace digitalmx\flames;

/*
   Start new html page
   startHead starts the page
   startBody ends the head and starts the body
*/

use digitalmx as u;

class DocPage {

   public function __construct ()
   {

   }

    public function startHead ($title, $options = []){
      /* options:
         'tiny' = include tinymce
         'ajax' = include jquery, ajax
         'votes' = iinclude voting script/css
      */
      if (! is_array($options)){
      	throw new RuntimeException ("start head options not an array");
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
      if (!empty($options) && in_array('tiny',$options)){
         $t .= "
         <script src='/jsmx/tinymce/tinymce.min.js'></script>
         <script src='/jsmx/tiny_init.js'></script>
         ";
      }
      if (!empty($options) && in_array('ajax',$options)){
         $t .= "
         <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js'></script>
         <script src='/js/ajax.js'></script>
         ";
      }
      if (!empty($options) && in_array('votes',$options)) {
         $t .= "
         <link rel='stylesheet' href='/css/votes.css' />
         <script src='/js/voting3.js'></script>
         ";
      }

      return $t;
   }



   public function startBody($title,$style=2,$subtitle='') {
	//style 0 for no graph, 1 for flames news, 2 for all other pages, 3 for home page
      $t = "\n</head>\n<body>\n";
      $t .= "<div class='page_head'>\n";

    #choose a style by number
      switch ($style) {
      case 3: #for home page
         $t .= <<<EOT
<div style="color: #009900; font-family: helvetica,arial,sans-serif; font-size: 24pt; font-weight:bold; ">
<div style="position:relative;float:left;vertical-align:bottom;margin-left:100px;">
   <div style=" float:left;"><img alt="" src="graphics/logo-FLAMEs.gif"></div>
   <div style= 'position:absolute; bottom:0;margin-left:100px;width:750px;'>FLAMES - The Official AMD Alumni Site </div>
</div>
<p style="font-size:14pt;clear:both;text-align:center;width:750px;margin-left:100px;">
		Keeping thousands of ex-AMDers connected since 1997<br>
	<span style="font-size:12pt;color:#030;font-style:italic;">AMD was probably the best place any of us ever worked.</span>
</p>
</div>
EOT;
         break;
      case 1:
         $t .= <<<EOT
         <img class='left' alt='AMD Flames' src='/graphics/logo-FLAMEs.gif'>
         <p class='title'>FLAME<i>news</i><br>
         <span style='font-size:0.5em;'>$subtitle</span>
         </p>
EOT;
         break;
         case 2:
         $t .= <<<EOT
         <img class='left' alt='AMD Flames' src='/graphics/logo69x89.png'>
         <p class='title'>$title<br>
         <span style='font-size:0.5em;'>$subtitle</span>
         </p>
EOT;
         break;
               case 0:
         $t .= <<<EOT
         
         
EOT;
         break;


      default:

   }

  ( $t .= $_SESSION['menu']) || ($t .= 'Menu not created') ;
   $t .= "<hr style='width: 100%; height: 2px;clear:both;'>";

   $t .= "</div>\n";




      return $t;
   }

}
