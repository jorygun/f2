<?php
namespace DigitalMx\Flames;
// ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);

/* routine to build menus and return them as html text.
// also includes some javascript to close the open
//  menus on mobile, because they stick open.
	run this at login to set the menu in the session file.

*/
use DigitalMx\MyPDO;
use DigitalMx\Flames\Opportunities;
use DigitalMx\Flames\FileDefs;


class Menu {

	private $menubar;

	private $menulist = array();
	private $userlevel, $text;
	public $header;
	private $login = array();
	private $opp;

	private $opp_count;

#these people get experiemtnal nav header. see show_exp_menu;
	private $expnames = array(
		'FLAMES editor',
		'Mark McClain',
		'FLAMES admin',
		'Rick Marz',
	);


	public function __construct ($login='') {

		$this->opp = new Opportunities();
		if (empty($login)){
			$login = $_SESSION['login'];
		}

		$this->menubar = $this->setMenuBar($login);

	}

	public function getMenuBar ()
	{
		return $this->menubar;
	}

	public function get_option () {
		return $this->header;
	}

	private function if_level($level,$text){
		 if ($this->userlevel >= $level){return "$text";}
		 return '';
	}




	private function addMenu ($level,$id,$title=''){
		$text='';
		if ($this->userlevel >= $level){
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
		$vdate = REPO_PATH . "/var/commit_timestamp"; #touched after evry commit in master
		if (! file_exists($vfile) || ! file_exists($vdate) ){throw new Exception ("No version file");}

		$vrel =  trim(file_get_contents($vdate));

		#$vnum = $init->getVersion();
		$vfh = fopen($vfile,'r');
		$ln = 0;
		while (!feof($vfh)) {
			$line = fgets($vfh);
			++$ln;
   		 if (strlen($line) > 6) {
   		 	$vlatest = $line;

   		 }
   	}

		#gets last line

		list($vnum,$vdesc) = preg_split("/\s+/",$vlatest);
		$sec = $_SESSION['login']['seclevel'];
		$vname = "<div class='vbox'>V $vnum [$vroot]<br>B $vrel<br>L $sec </div>";

		return $vname;
	}

	private function closeLine ($level,$thisMenu){
		// add a closing menu item and end the ul.
		// decided to NOT add the last menu item.
		$t='';
		if ($this->userlevel >= $level) {
			$t = <<<EOT
		<li><a href="#" onClick="closeMenu('${thisMenu}')" >Close Menu</a></li>
		</ul>
EOT;
		$t = "</ul>
		"; #don't add the menu item
		}
		return $t;
	}


	private function choose_css($username, $experimental_users){

		if (in_array ($username, $experimental_users)){
			$stylelink =  '/css/navbar2.css' ;
		} else {
			$stylelink =  '/css/navbar2.css' ;
		}
		return $stylelink;
	}


	public function setMenuBar($login)
	{
$version = $this->get_version();

		if (empty($login)){
			throw new Exception ("No data for NavBar user");
		}

		$username = $login['username'];
		$usertype = $login['status_name'];
		$userlevel = $login['seclevel'];
		$this->userlevel = $userlevel;
		$userlinkedin = $login['linkedin'] ?? '';

		$css_file = $this->choose_css($username, $this->expnames);

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
		$t .=    self::if_level (7, "<li><li><a href='/article_list.php' target='article_manager'>List Pending Articles</a>");

		$t .= self::if_level(8,"<li><a href='/member_admin.php' target='_blank'>User Admin</a>");
		  $t .= self::if_level (7,"<li><a href='/news_admin.php'>News Admin</a>");
		  $t .= self::if_level (7,"<li><a href='/calendar_admin.php'>Calendar Admin</a>");

		 $t .= self::if_level(8,"<li><a href='/varinfo.php?v' target='_blank'>Var Info</a>");

		 $t .=  self::if_level(7,"<li><a href='/views.php' target='data'>Count of Views by Issue</a>");
		  $t .=   self::if_level(7,"<li><a href='/scripts/view_links.php'  target='data'>Link Activity</a>");


		  $t .=   self::if_level (7, "<li><a href='/news/next/' target='preview'>Preview</a>");
		  $t .=    self::if_level (7, "<li><a href='asset_search.php' target='assets'>Asset Manager</a>");
		  $t .=    self::if_level (7, "<li><a href='/scripts/gallery_edit.php' target='galleries'>Edit Gallery</a>");
		  $t .=   self::if_level (7, "<li><a href='/WWW/amdflames.org.html' target='_blank'>Web Stats</a>");


	$t .=  self::closeLine(7,$thisMenu);

	$thisMenu = 'Authoring';
	if ($userlevel >= 6){$menulist[] = $thisMenu;}
	$t .=  self::addMenu (6,$thisMenu);
		$t .= self::if_level(6,"<li><a href='/article_manager.php'>List Pending Articles</a> ");
		 $t .=  self::if_level(6,"<li><a href='/asset_search.php' target='assets'>Add/Find Graphics</a> ");
		 $t .=  self::if_level(6,"<li><a href='/views.php' target='data'>Count of Views by Issue</a> ");


	$t .=  self::closeLine(6, $thisMenu) ;

	$opp_rows = $this->opp->getOppCount();
	$thisMenu = "Opportunities ($opp_rows)";
	$menulist[] = $thisMenu;
	$opp_list = $this->opp->linkOppList();
	$t .= self::addMenu (0,$thisMenu);

	foreach ($opp_list as $line){
		$t .=  "<li>$line";
	}

	$t .=  self::closeLine(0, $thisMenu) ;


	$thisMenu = 'Search';
	$t .=  self::addMenu(4,$thisMenu);
	if ($userlevel >= 4){$menulist[] = $thisMenu;}
	$t .= self::if_level(4,"
		<li><a href='/search.php' target='_blank'>Search For a Member or Topic</a>

		<li><a href='http://www.linkedin.com/groups?gid=117629&trk=myg_ugrp_ovr' target='_blank'>AMD Alumni on LinkedIn</a>
	");
	$t .=  self::closeLine(4,$thisMenu) ;


	$thisMenu = 'Dig In';
	if ($userlevel >= 2){$menulist[] = $thisMenu;}
	$t .=  self::addMenu(2,$thisMenu);
	$t .= self::if_level(2,"
		 <li><a href='/news/current/' target='newsletter'>Latest Newsletter</a>
		 <li><a href='/news/' target='_blank'>Newsletter Index</a>
		<li><a href='/galleries.php' target='gallery'>Photo Galleries</a>

		 <li><a href='/special/' target='special'>Special Tributes</a>
		 <li><a href='/galleries.php/?4547' target='_blank'>Our Great Ads</a>
		<li><a href='/special/McKean.html' target='upward'>Tribute to John McKean</a>

		 ");
	$t .=  self::closeLine(2, $thisMenu);


	$thisMenu = 'Member';
	$menulist[] = $thisMenu;
	$t .=  self::addMenu (0,$thisMenu,$username);
	$t .= self::if_level(1,"
		 <li >$username <br> &nbsp;&nbsp;<i>$usertype</i><hr style='height:2px;margin:1px;'>
		<li><a href='/'>Home</a>
		 <li><a href='/profile.php' target='profile'>View/Edit My Profile</a>
		 ");
	$t .= ($userlevel > 1 and !empty ($userlinkedin))?
		"<li><a href='$userlinkedin' target='_blank'>My LinkedIn Page</a>" : '';

	$t .= self::if_level(1,"
		<li><a href='/?s=logout'>Log Out</a>
		");
	$t .= self::if_level(0,"
		<li>-------------
		<li><a href='/help.php'>Help</a>
		<li><a href='/signup.php'>New User Signup</a>
		 <li><a href='/about.php' target='about'>About AMD Flames</a>
		 <li><a href='mailto:admin@amdflames.org'>When all else fails, email the admin</a>
	");

	$t .= " <li>-----------
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

