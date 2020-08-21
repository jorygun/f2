<?php
namespace DigitalMx\Flames;
use \Exception as Exception;


/* This file contains tables of names, lists, etc
	used throughout the site and grouped into
	  Definitions (db tables, )
	  Definitions_Email (status codes, )
	  Definitions_News (sections, types,
	  Definitions_Member (status, aliases, security levels)
	Most are public static vars, so call like
	  Definitions::$dbTable

*/
class Definitions {

	// days before warnings start on profile or email

	public static $profile_warning = 720;
		#days since update; warning on login and bulk email
	public static $inactivity_limit = 366;
		#days since last activity; triggers lost test

	public static $asset_status = array(

    'T' => 'temp holding',
    'E' => 'Missing Graphic or bad URL',
    'X' => 'Deleted',


    'U' => 'Updated. Needs Tag Review',

    'W' => 'Warning but usable', // automatic
    'O' => 'OK',
    'K' => 'OK, override asset url error',  //use for inacessible asset url

);


public static $local_site = 'f2.local';


	public static $editor_id = 13145;
	// userId for editor account.
		public static $admin_id = 13146;
	// userId for admin account.

	public static $news_status = array(
		'N'	=> 'New',
		'Q'	=> 'Queued Next',
		'P'	=> 'Published',
		'X'	=>	'To Delete',

	);

	 public static $thumb_width = array(
						 'thumbs' => 200,
						 'galleries' => 330,
						 'toons' => 800,
						 'small' => 200,
						 'medium' => 330,
						 'large' => 800
						 );

	// private static $accepted_mime_types = array(
// 					'jpg' => 'image/jpeg',
// 					'jpeg' => 'image/jpeg',
// 					'png' => 'image/png',
// 					'gif' => 'image/gif',
// 					'pdf' => 'application/pdf',
// 					'mp4' => 'video/mp4',
// 					'mov' => 'video/quicktime',
// 					'mp3' => 'audio/mpeg',
// 					'm4a' => 'audio/mp4',
// 					'tif' => 'image/tiff',
// 					'tiff' => 'image/tiff',
// 					'doc' => 'application/msword',
// 					'docx' => 'application/msword',
// 					'html' => 'text/html',
// 					'' => 'video/x-youtube',
//
// 			  );
		// all accept mime types for assets and their group
	public static $asset_types = array (

		'application/msword' => 'Document',
		'application/pdf' => 'Document',
		'audio/mp4' => 'Audio',
		'audio/m4a'	=>	'Audio',
		'audio/mp3'	=>	'Audio',
		'audio/mpeg' => 'Audio',
		'image/gif' => 'Image',
		'image/jpeg' => 'Image',
		'image/png' => 'Image',
		'image/tiff' => 'Image',
		'text/html' => 'Web Page',
		'video/mp4' => 'Video',
		'video/quicktime' => 'Video',
		'video/x-youtube' => 'Video',
		'video/x-m4v' => 'Video',
		'audio/x-m4a' => 'Audio',
		'text/plain' => 'Document',
	);

	private static $mime_icons = array(
		'application/msword' 	=>	'doc.jpg',
		'application/pdf' 	=>	'pdf.jpg',
		'audio/m4a'	=>	'm4a.jpg',
		'audio/mp3'	=>	'mp3.jpg',
		'audio/mpeg' => 'm4a.jpg',
		'image/gif'	=>	'image.jpg',
		'image/jpeg'	=>	'image.jpg',
		'image/png'	=>	'image.jpg',
		'image/tiff'	=>	'image.jpg',
		'text/html'	=>	'web.jpg',
		'video/mp4'	=>	'mp4.jpg',
		'video/quicktime'	=>	'mov.jpg',
		'video/x-youtube' => 'mov.jpg',
		'video/x-m4v' => 'mp4.jpg',
		'audio/x-m4a' => 'm4a.jpg',
		'text/plain' => 'text.png',
	);


// public static $asset_types = array(
// 	'Image' ,
// 	'Cartoon' ,
//  	'Multimedia' ,
// 	'Document' ,
// 	'Album' ,
// 	'Web Page',
// 	 'Web Video',
// 	'Other',
//    'Member Photo'
//     );


	// tag codes.  * in description means archival
	public static $asset_tags = array(
    'A' => 'Ad',

    'C' => 'Corp - External',
    'D' => 'Stories About AMD',
    'E' => 'Events',
    'F' => 'Facilities',

    'I' => 'Corp - Internal',

    'M' => 'Marketing Pub',
    'P' => 'Data sheet/Apps ',

    'S' => 'Sales/Mktg Bulletins',

	'U' => 'Interviews',

    'W' => 'Sales Conference',

    'Y' => 'Posters and Symbols ',



    );
    #tag starting with Z is reserved for special searches, e.g., all archives

	 public static $archival_tags = "ACDEFIMPSUWY";

//id to use youtube api
public static $ytapikey = 'AIzaSyAU30eOK0Xbqe4Yj0OMG9fUj3A9C_K_edU';


public static $gallery_status = array(
    'G' => 'Good.  Publish',
    'D' => 'Delete',
    'N' => 'New'
    );


// code => array(name,next-code,after-days)
public static $ems_codes = array(
		'Y'	=> ['Validated','',0],
		'Q'	=> ['Believed Good','',7],
		'XX'	=> ['To be removed','',7],
		'LA'	=> ['Lost - No Response to several inquiries','',0],
		'LB'	=> ['Lost - Bounced','',0],
		'LO'	=> ['Lost - Other Reason','',0],
		'LN'	=> ['Lost - No Email Address','',0],
		'LE'	=> ['Lost - After changing Email address','',0],
		'LS'    => ['Lost at signup','',0],
		'LD'    => ['Lost - Deceased','',0],
		'B1'	=> ['May be bouncing','LB',3],
		'B2'	=> ['Bounced twice','LB',3],
		'A1'	=> ['Revalidation requested','A2',3],
		'A2'	=> ['Revalidation requested (2nd attempt)','A3',7],
		'A3'	=> ['Revalidation requested (Final attempt)','LA',3],
		'A4'	=> ['Revalidation requested (Final attempt)','LA',3],
		'E1'	=> ['Changed Email not yet validated','LE',3],
		'E2'	=> ['Changed Email not yet validated (2nd)','LE',3],
		'N1'	=> ['New Signup','XN',2],
		'D'  => ['Status D','D',1],

		);

// these emails are ok to send to from local_site (dev site)
// all others will be blocked, to prevent embarrassing mistakes

public static $safe_emails = [
'john@digitalmx.com',
'editor@amdflames.org',
'johnmx@me.com',
'springerj@yahoo.com',
'jorygun@gmail.com',
'johnscottspringer@gmail.com',
'john.scott.springer@gmail.com',

];


public static $decades = array(
	'A'	=>	'1960s',
	'B'	=>	'1970s',
	'C'	=>	'1980s',
	'D'	=>	'1990s',
	'E'	=>	'2000s',
	'F'	=>	'2010s',
);

public static $locations = array(
	'A'	=>	'Sunnyvale',
	'B'	=>	'Austin',
	'C'	=>	'San Antonio',
	'U'	=>	'US Field',
	'V'	=>	'Europe',
	'W'	=>	'Asia',
	'X' =>	'Other'
);

public static $departments = array (
	'A'	=>	'Engineering',
	'B'	=>	'Marketing/Sales',
	'C'	=>	'Manufacturing',
	'D'	=>	'Finance/HR/Legal',
	'E' =>  'Contractor/Vendor',
	'F' =>  'Executive',
	'X' =>	'Other'
);

public static $user_aliases = array (
            'z' => 'Steve Zelencik',
            'bob' => 'Bob McConnell',
            'js' => 'John Springer',
            'editor' => 'Flames Editor',
            'admin' => 'Flames Admin',
            'rick' => 'Rick Marz',
            'dave' => 'David Laws',
            'es' => 'Elliott Sopkin',
            'jm' => 'John McKean',
            'glen' => 'Glen Balzer',
            'jeff' => 'Jeff Drobman',
            'amdc' => 'AMD Communications',
            'kc' => 'K.C. Murphy',

        );


// code => [name, seclevel]

public static $member_codes = array (
	'N' => array ('New Signup', 1),

	'G' => array ('Guest', 2),

	'M' => array ('Member', 4),
	'MC' => array ('Contributor', 6),
	'MN' => array ('News Admin', 7),
	'MU' => array ('User Admin', 8),
	'MA' => array ('Admin Admin', 9),


	'I' => array ('Inactive', 2),
	'T' => array ('test user (like member)', 4),
	'L' => array ('lost ', 1),
	'D' => array ('deceased', 0),


	'X' => array ('to be deleted', 0),
	'Y' => array ('Non-member', 0),
	'YA' => array ('Alumni non-member', 0),

	);

#these member status codes are considered members
	public static $member_array = array(
	'M', 'MA','MN','MC','MU','G'
	);


	public static $signup_status_names = array(

			'A' => 'Validated Email',
			'R' => 'Needs Review',
			'U' => 'New Unvalidated',
			'X' => 'To Be Deleted',
			'Z' => 'Other',
		);




#####################
public static function getMimeGroup($mime) {
	$group = self::$asset_types[$mime] ?? '';
	return $group;

}

public static function getIconForMime($mime) {
	return self::$mime_icons[$mime] ?? 'default.jpg';
}

public static function getAssetType($mime) {
	$type = self::$asset_types[$mime] ?? 'Other';
	return $type;

}
public static function getAssetTypes() {
	return array_keys(array_flip(self::$asset_types));
}

######## Getters
	/* returns the list at teh var */
	public  static function getEmsData($code){
		if (empty($code)){
			return "n/a";
		}
			return self::$ems_codes[$code];
	}
	public  static function getEmsName($code){
		if (empty($code)) return "n/a";

		return self::$ems_codes[$code][0];
	}
	public static function getEmsNameArray() {
		$names=array();
		foreach (self::$ems_codes as $k=>$data){
			$names[$k] = $data[0];
		}
		return $names;
	}
	public static function getStatusOptions() {
		// returns the array for the buildOptions routine
		$opt = array();
		foreach (self::$member_codes as $code=>$defs){
			$opt[$code] = $defs[0];
		}
		return $opt;
	}
	public  static function getSecLevel($code='Y'){
		if ( $s = self::$member_codes[$code][1] ) {
			return $s;
		}
		return 0;
	}

	public static function getMemberInList(){
		return self::$member_array;
	}
	public  static function getMemberInSet () {
		#for sql IN clause
		return "'" . implode("','",self::$member_array) . "'";
	}
	public  static function  getMemberDescription($code){
		if ($s = self::$member_codes[$code][0] ){
			return $s;
		}
		return "?";
	}

	public  static function getMemberAliasList () {
	// for showing list of available aliases
		$Aliastext = "(Aliases: " . implode(', ',array_keys(self::$user_aliases)) . ")";
		return $Aliastext;
	}

	public static function replaceAlias ($alias){
    // looks for maybe in alias list and replaces with alias name if any
    $alias = strtolower($alias) ; #all aliases are lower case
    if (preg_match('/^\w+$/',$alias)){ # match alias format
        if (in_array($alias,array_keys(Definitions::$user_aliases))){
            $lookup = Definitions::$user_aliases[$alias];
            return $lookup;
        }
    }
    return $alias;
 }

	public static function getArchivalTagList()  {
		// returns an sql formatted list of aarchival tags for "in ( ) statement: 'A','B' etc.
		// $archival_tags = [];
// 		foreach (self::$asset_tags as $tag=>$label){
// 			if (strpos($label,'*') !== false){
// 				$archive_tags[] = "'$tag'";
// 			}
// 		}
		return "'" . implode("','",array_keys(self::$asset_tags)) . "'" ;
	}

	public static function getAssetArchivalTags() {
		return self::$archival_tags;
	}

	public static function getAssetTagArray () {
		return self::$asset_tags;
	}

	public static function getThumbTypes() {
		return array_keys(self::$thumb_width);
	}

	public static function getAcceptedMime() {
		return array_keys(self::$asset_types);
	}



}












