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
    public function __construct($title = 'AMD Flames')
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
         'no-cache' = send headers to prevent caching
      */

        if (! is_array($options)) {
            throw new RuntimeException("start head options not an array");
        }

	$this->nc = 'Off';
	if (!empty($options) && in_array('no-cache', $options) ) {
		header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
		header("Pragma: no-cache"); // HTTP 1.0.
		header("Expires: 0"); // Proxies.
		$this->nc = 'No-cache On';
	}

        $t =  <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />

   <title>$this->title</title>
   <link rel='stylesheet' href = '/css/news4.css' />
   <link rel='stylesheet' href = '/css/navbar2.css' />

   <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js'></script>
	<script src='/js/f2js.js'></script>
	<script src='/js/ajax.js'></script>
	<script src='/js/help.js'></script>
	<script src='/js/navbar.js'></script>

EOT;
        if (!empty($options) && in_array('tiny', $options)) {
            $t .= "
            <script src='https://cdn.tiny.cloud/1/5rh2pclin81y4q8k7r0ddqfz2gcow6nvxqk1yxv4zci54rpx/tinymce/5/tinymce.min.js'></script>

         	<script src='/js/tiny_init.js'></script>
         ";
        }

        return $t;
    }



    public function startBody($style = 5, $subtitle = '', $sub2title='')
    {

    		$preview_text = ($style == 6 ) ? 'Preview' : '';
     //style 0 for no graph, 1 for flames news, 2 for all other pages, 3 for home page, 4 for collapsible list
     // 5 is new style, 6 is preview new style
        $title = $this->title;

		$warning_file = REPO_PATH . "/var/repo_warning.txt";
		$repo_warning = '';
		if (file_exists($warning_file)){
			$repo_warning = file_get_contents($warning_file);
			if (!empty($repo_warning)){
				$repo_warning =
					"<p style='color:orange;'>"
					. $repo_warning
					. '</p>'
					. NL;
			}
		}
			$t= '' ; // build head here

			// before body tag
		switch ($style) {
			case 4:
			case 'cl':
            $t .= <<<EOT
            <script type="text/javascript" src="/js/collapsibleLists.js"></script>
            </head>
           <body onload='CollapsibleLists.apply()'>
EOT;
				break;
			default:
				$t .= <<<EOT
				</head>
				<body>
EOT;
			break;

        }

      // after body tag, build page head

     #choose a style by number
        switch ($style) {
           //  case 3: #for home page
//             case 'hp':
//                 $t .= <<<EOT
//                 <div class='page_head'>
// <div style="color: #090; font-family: helvetica,arial,sans-serif; font-size: 24pt; font-weight:bold; ">
// <div style="position:relative;float:left;vertical-align:bottom;margin-left:100px;">
//    <div style=" float:left;"><img alt="" src="/assets/graphics/logo-FLAMEs.gif"></div>
//    <div style= 'position:absolute; bottom:0;margin-left:100px;width:750px;'>FLAMES - The Official AMD Alumni Site </div>
// </div>
// <p style="font-size:14pt;clear:both;text-align:center;width:750px;margin-left:100px;">
//         Keeping thousands of ex-AMDers connected since 1997<br>
//     <span style="font-size:12pt;color:#030;font-style:italic;">AMD was probably the best place any of us ever worked.</span>
// </p>
//
// </div>
// 			$this->menubar
//          <hr style='width: 100%; height: 2px;clear:both;'>
// 			</div>
//
// EOT;
//                 break;
//             case 1: #for newsletter
//             case 'nl':
//                 $t .= "
//                 <div class='page_head'>
//          <img class='left' alt='AMD Flames' src='/assets/graphics/logo-FLAMEs.gif'>
//          <p class='title'>$title<br>
//          <span style='font-size:0.5em;'>$subtitle</span>
//          </p>";
// 			if ($preview) {
// 					$t .= "<p class='preview'><b>Preview</b> " . date('M j, Y') . '</p>';
// 				}
// 			$t .= <<<EOT
// 			$this->menubar
// 			<hr style='width: 100%; height: 2px;clear:both;'>
// 			</div>
// EOT;
//                 break;
//
//             case 2: #other pages
//             case 4:
//             case 'small':
//                 $t .= <<<EOT
//                 <div class='page_head'>
//          <img class='left' alt='AMD Flames' src='/assets/graphics/logo69x89.png'>
//          <p class='title'>$title<br>
//          <span style='font-size:0.5em;'>$subtitle</span>
//          </p>
//           $this->menubar
//          <hr style='width: 100%; height: 2px;clear:both;'>
// 			</div>
//
// EOT;
//                 break;
//
//
//             case 0: #nothing at top of page
//                 $t .= <<<EOT
//                 <div class='page_head'>
//           <img class='left' alt='AMD Flames' src='/assets/graphics/logo69x89.png'>
//          <p class='title'>$title</p>
//          <hr style='width: 100%; height: 2px;clear:both;'>
// 			</div>
//
// EOT;
//                 break;


				case 6:
				$title = "(PREVIEW) " . $title;
				case 2:
				case 4:
				case 3:
				case 0:
				case 1:
            case 5: #new style


           	$t .= <<<EOT
<div class='page'>
<div class='page_head'>
	<div style='text-align:right;'>
		<div class='page_top'>
		$repo_warning
			<p >AMD Flames</p>
			$this->menubar
		</div>
		<img  class='logo' src='/assets/graphics/amdheart-grn72.png' >
	</div>

	<hr style='width: 100%; height: 2px;clear:both;'>
	<p class='head'>$title</p>
	<p class='subhead'>$subtitle</p>
</div>
EOT;
					break;

				default:
				// nothiing

       }

		$t .= $this->nc;
        return $t;
    }
}
