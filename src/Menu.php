<?php
namespace digitalmx\flames;
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

// routine to build menus and return them as html text.
// also includes some javascript to close the open
//  menus on mobile, because they stick open.
// get the menus by
// 	require nav.class.php
//		$nav = new navBar($header);
//				header = 1, add header graphic
//		echo build_menus (extra)
//			extra is text to add below the menu (like another link back)

//

class Menu {

	private $thisMenu;
	private $t;
	private $menulist = array();
	private $level, $text;
	public $header;
	private $login = array();
	private $css;

#these people get experiemtnal nav header. see show_exp_menu;
	private $expnames = array(
		'FLAMES editor',
		'Mark McClain',
		'FLAMES admin',
		'Rick Marz',
	);
	
		
	public function __construct ($log_info,$header='') {
		 $this->login = $log_info;
			$this->header = $header;
			$this->css = $this->choose_css($this->expnames);
	 
	}

	public function get_option () {
		return $this->header;
	}

	private function if_level($level,$text){
		 if ($this->login['seclevel'] >= $level){return "$text";}
		 return '';
	}

	private function count_opps(){
		return 0;
		
		 $pdo = digitalmx\MyPDO::instance();
		 $sql = "SELECT count(*) FROM opportunities WHERE
					expired = '0000-00-00' OR
					expired > NOW();";

		 $opp_rows = $pdo -> query($sql) -> fetchColumn();
		 return $opp_rows;

	}


	private function addMenu ($level,$id,$title=''){
		$text='';
		if ($this->login['seclevel'] >= $level){
			if (empty($title)){$title = $id;}
			$text = <<<EOT
		
		<li id='$id'><a href='#' >$title</a>
		<ul id='${id}_child'>
EOT;
    # removed this line from above:
    # <script>//addMenuList('$id');</script>

		}
		return "$text";
	}

	private function get_version() {
		
		$vroot = basename(REPO_PATH); 
		$vfile = REPO_PATH . "/config/version.txt";
		if (! file_exists($vfile)){throw new Exception ("No version file");}
		
		$vrel = date('d M Y H:i',filemtime($vfile));
		
		#$vnum = $init->getVersion();
		$vfh = fopen($vfile,'r');
		while (($line = fgets($vfh)) !== false) {
   		 if (!empty($line)) {
   		 	$vlatest = $line;
   		 }
   	}
		
		#gets last line
		
		#echo "vl $vfile $vlatest";
		
		list($vnum,$vdesc) = preg_split("/\s+/",$vlatest);
		
		$vname = "<div class='vbox'>[$vroot] $vnum <br>($vrel)</div>";
		
		return $vname;
	}
	
	private function closeLine ($level,$thisMenu){
		// add a closing menu item and end the ul.
		// decided to NOT add the last menu item.
		$t='';
		if ($this->login['seclevel'] >= $level) {
			$t = <<<EOT
		<li><a href="#" onClick="closeMenu('${thisMenu}')" >Close Menu</a></li>
		</ul>
EOT;
		$t = "</ul>
		"; #don't add the menu item
		}
		return $t;
	}


	private function choose_css($experimental_users){

		if (in_array ($this->login['username'],$experimental_users)){
			$stylelink =  '/css/navbar2.css' ;
		} else {
			$stylelink =  '/css/navbar2.css' ;
		}
		return $stylelink;
	}

	public function getMenuBar ($extra='') 
	{
		$version = $this->get_version();
		
		if (empty($this->login)){
			throw new Exception ("No data for NavBar user"); 
		}
		
		$username = $this->login['username'];
		$usertype = $this->login['status_name'];
		$userlevel = $this->login['seclevel'];
		$userlinkedin = $this->login['linkedin'] ?? '';

		$css_file = $this->css;
	
		$t = <<<EOT
		<link rel='stylesheet' href='$css_file'>
		<script>
		function closeMenu(close_list){
			for (var i=0;i < close_list.length;i++){
				document.getElementById(close_list[i]).blur();
				document.getElementById(close_list[i] +'_child').blur();
				//alert ("Closing item " + close_list[i]);
			}
				return false;
		}

		</script>

		<nav id='nav'>
EOT;
	#$t .= "nav header: " . $this->header;
	if ($this->header == 1){ $t .=
	"<p><img src='/graphics/logo69x89.png' ><span style='font-size:1.5em;'>AMD Flames - The AMD Alumni Site </span> </p>";
	}
	
	$t .=  "<ul>\n";


	$thisMenu = 'Admin';

		$t .= self::addMenu(7,$thisMenu);
		if ($userlevel >= 7){$menulist[] = $thisMenu;}

		$t .= self::if_level(8,"<li><a href='/member_admin.php' target='_blank'>User Admin</a>");
		  $t .= self::if_level (7,"<li><a href='/level7.php'>News Admin</a>");
		 $t .= self::if_level(8,"<li><a href='/info.php' target='_blank'>Site Info</a>");
		 $t .=  self::if_level(7,"<li><a href='/views.php' target='data'>Count of Views by Issue</a>");
		  $t .=   self::if_level(7,"<li><a href='/scripts/view_links.php'  target='data'>Link Activity</a>");

		  $t .=    self::if_level (7, "<li><li><a href='/scripts/news_items.php' target='newsitems'>Review Articles</a>");
		  $t .=   self::if_level (7, "<li><a href='/news/news_next/' target='preview'>Preview</a>");
		  $t .=    self::if_level (7, "<li><a href='/scripts/assets.php' target='assets'>Asset Manager</a>");
		  $t .=    self::if_level (7, "<li><a href='/scripts/gallery_edit.php' target='galleries'>Edit Gallery</a>");
		  $t .=   self::if_level (7, "<li><a href='/WWW/amdflames.org.html' target='_blank'>Web Stats</a>");

	$t .=  self::closeLine(7,$thisMenu);

	$thisMenu = 'Authoring';
	if ($userlevel >= 6){$menulist[] = $thisMenu;}
	$t .=  self::addMenu (6,$thisMenu);
		$t .= self::if_level(6,"<li><a href='/level6.php'>Add/View Pending Articles</a> ");
		 $t .=  self::if_level(6,"<li><a href='/scripts/assets.php' target='assets'>Add/Find Graphics</a> ");
		 $t .=  self::if_level(6,"<li><a href='/views.php' target='data'>Count of Views by Issue</a> ");
		 $t .=  self::if_level(6,"<li><a href='/scripts/view_links.php'  target='data'>Link Activity</a> ");

	$t .=  self::closeLine(6, $thisMenu) ;

	$thisMenu = 'Opportunities';
	$menulist[] = $thisMenu;
	$opp_rows = self::count_opps();
	$t .= self::addMenu (0,$thisMenu);
	$t .=  "<li><a href='/opportunitiesE.php' target='_blank'> $opp_rows Listed</a>";
	$t .=  self::closeLine(0, $thisMenu) ;


	$thisMenu = 'Search';
	$t .=  self::addMenu(4,$thisMenu);
	if ($userlevel >= 4){$menulist[] = $thisMenu;}
	$t .= self::if_level(4,"
		<li><a href='/scripts/search_news.php' target='_blank'>Search Newsletters</a>
		<li><a href='/scripts/search_member.php' target='_blank'>Search For a Member</a>
		<li><a href='/scripts/assets.php' target='assets'>Search Graphics/Video</a>
		<li><a href='http://www.linkedin.com/groups?gid=117629&trk=myg_ugrp_ovr' target='_blank'>AMD Alumni on LinkedIn</a>
	");
	$t .=  self::closeLine(4,$thisMenu) ;


	$thisMenu = 'Dig In';
	if ($userlevel >= 2){$menulist[] = $thisMenu;}
	$t .=  self::addMenu(2,$thisMenu);
	$t .= self::if_level(2,"
		 <li><a href='/news/' target='newsletter'>Latest Newsletter</a>
		 <li><a href='/newsp/' target='_blank'>Newsletter Index</a>
		<li><a href='/galleries.php' target='gallery'>Photo Galleries</a>
		<li>--- special pages ---
		<li><a href='/spec/spirit.php' target='spirit'>The Spirit of AMD</a>
		<li><a href='/spec/Upward.php' target='upward'>Flames who later became CEOs</a>
		<li><a href='/spec/hbwjs80.php' target='upward'>Members on Jerry Sanders' 80th Birthday</a>
		<li><a href='/spec/anixter.php' target='upward'>Tributes to Ben Anixter</a>
		<li><a href='/spec/McKean.html' target='upward'>Tribute to John McKean</a>
		 <li><a href='/special.php' target='special'>Other Special Pages</a>
		 ");
	$t .=  self::closeLine(2, $thisMenu);

	
	$thisMenu = 'Member';
	#if ($this->login['seclevel'] >= 0){$menulist[] = $thisMenu;}
	$t .=  self::addMenu (0,$thisMenu,$username);
	$t .= self::if_level(1,"
		 <li >$username <br> &nbsp;&nbsp;<i>$usertype</i><hr style='height:2px;margin:1px;'>
		<li><a href='/'>Home</a>
		 <li><a href='/scripts/profile_view.php' target='profile'>View/Edit My Profile</a>
		 ");
	$t .= ($userlevel > 1 and !empty ($userlinkedin))? 
		"<li><a href='$userlinkedin' target='_blank'>My LinkedIn Page</a>" : '';
	
	$t .= self::if_level(1,"
		<li><a href='/?s=-1'>Log Out</a>
		");
	$t .= self::if_level(0,"
		<li>-------------
		<li><a href='/help.html'>Help</a>
		<li><a href='/scripts/signup.php'>New User Signup</a>
		 <li><a href='/about.php' target='about'>About AMD Flames</a>
		 <li><a href='mailto:admin@amdflames.org'>When all else fails, email the admin</a>
	");
		
	$t .= " <li>.
		<li>$version";
	
	
	$t .=  self::closeLine(0, $thisMenu);

	$js_menulist = json_encode ($menulist);

	$t .= "
	<li><button type='button' onClick='closeMenu($js_menulist)' title='Close open menus (for touchscreen users)' class='xbutton'> X </button>
	<ul><li>Touchscreen users <a href='#' onClick='closeMenu($js_menulist)' style='text-decoration:underline;'>click</a> to close open menus </ul>
";	 

	$t .=  "	</ul>\n";

	if (! empty($extra)){ $t .= $extra;}
	
	$t .= "</nav>\n";
	
	return "$t";
	}


}

