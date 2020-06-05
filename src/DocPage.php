<?php
namespace DigitalMx\Flames;



   /**
    *  Start new html page
    *
    *  startHead starts the page
    *  startBody ends the head and starts the body
    *  usage:
    *  if ($login->checkLogin(4)) { // sets the min security level
    *  .  $page_title = 'News Article';
    *  .  $page_options=['votes','tiny']; #ajax, votes, tiny
    *
    *  .   $page = new DocPage($page_title);
    *  .  echo $page -> startHead($page_options);
    *  .  # echo other heading code here, like style or script
    *
    *  .  echo $page->startBody(style);
    *  .  // style 0 for no graph, 1 for flames news, 2 for all other pages,
    *  .  // 3 for home page, 4 for collapsible list (news index)
    *  }
    *
    *  No dependencies
    *
    */



class DocPage
{
    private $title;
    public function __construct($title = 'Someone forgot to put in the title')
    {
        // add the repo name to the title if its not live
        if (REPO != 'live') {
            $title .= " (" . REPO . ")";
        }
        $this->title = $title;
        $this->menubar = $_SESSION['menubar'];
    }

    public function startHead($options = [])
    {
      /* options:
         'tiny' = include tinymce
         'ajax' = include jquery, ajax
         'votes' = iinclude voting script/css
      */
        $title = $this->title;
        if (! is_array($options)) {
            throw new RuntimeException("start head options not an array");
        }




        $t =  <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />

   <title>$title</title>
   <link rel='stylesheet' href = '/css/news4.css' />

   <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js'></script>
	<script src='/js/f2js.js'></script>
	<script src='/js/ajax.js'></script>

EOT;
        if (!empty($options) && in_array('tiny', $options)) {
            $t .= "
            <script src='https://cdn.tiny.cloud/1/5rh2pclin81y4q8k7r0ddqfz2gcow6nvxqk1yxv4zci54rpx/tinymce/5/tinymce.min.js'></script>

         	<script src='/js/tiny_init.js'></script>
         ";
        }
//  <script src='/jsmx/tinymce/tinymce.min.js'></script>

        if (!empty($options) && in_array('help', $options)) {
            $t .= "
        <script src='/js/help.js'></script>
        ";
        }



        return $t;
    }



    public function startBody($style = 2, $subtitle = '', $preview=false)
    {
     //style 0 for no graph, 1 for flames news, 2 for all other pages, 3 for home page, 4 for collapsible list
        $title = $this->title;

        if ($style == 4) {
            $t = '<script type="text/javascript" src="/js/collapsibleLists.js"></script>';
            $t .= "\n</head>\n<body onload='CollapsibleLists.apply();' >\n";
        } else {
               $t = "\n</head>\n<body>\n";
        }

        $t .= "<div class='page_head'>\n";

     #choose a style by number
        switch ($style) {
            case 3: #for home page
            case 'hp':
                $t .= <<<EOT
<div style="color: #009900; font-family: helvetica,arial,sans-serif; font-size: 24pt; font-weight:bold; ">
<div style="position:relative;float:left;vertical-align:bottom;margin-left:100px;">
   <div style=" float:left;"><img alt="" src="/graphics/logo-FLAMEs.gif"></div>
   <div style= 'position:absolute; bottom:0;margin-left:100px;width:750px;'>FLAMES - The Official AMD Alumni Site </div>
</div>
<p style="font-size:14pt;clear:both;text-align:center;width:750px;margin-left:100px;">
        Keeping thousands of ex-AMDers connected since 1997<br>
    <span style="font-size:12pt;color:#030;font-style:italic;">AMD was probably the best place any of us ever worked.</span>
</p>

</div>
$this->menubar
EOT;
                break;
            case 1: #for newsletter
            case 'nl':
                $t .= "
         <img class='left' alt='AMD Flames' src='/graphics/logo-FLAMEs.gif'>
         <p class='title'>$title<br>
         <span style='font-size:0.5em;'>$subtitle</span>
         </p>";
			if ($preview) {
					$t .= "<p class='preview'><b>Preview</b> " . date('M j, Y') . '</p>';
				}

         $t .= $this->menubar;
                break;

            case 2: #other pages
            case 4:
            case 'small':
                $t .= <<<EOT
         <img class='left' alt='AMD Flames' src='/graphics/logo69x89.png'>
         <p class='title'>$title<br>
         <span style='font-size:0.5em;'>$subtitle</span>
         </p>
          $this->menubar
EOT;
                break;
            case 0: #nothing at top of page
                $t .= <<<EOT
          <img class='left' alt='AMD Flames' src='/graphics/logo69x89.png'>
         <p class='title'>$title</p>

EOT;
                break;

            default:
                $t .= '';
        }

        $t .= "<hr style='width: 100%; height: 2px;clear:both;'>";

        $t .= "</div>\n";

        return $t;
    }
}
