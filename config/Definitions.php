<?php
namespace digitalmx\flames;

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
	
	public static $asset_tags = array(
    'A' => 'Ad *',

    'C' => 'Corp - External *',
    'D' => 'Stories About AMD *',
    'E' => 'Events *',
    'F' => 'Facilities *',
    'G' => 'Gatherings',

    'H' => 'Flames People',
    'I' => 'Corp - Internal *',

    'M' => 'Marketing Pub *',
	

    'O' => 'Historical Electronics *',

    'P' => 'Data sheet/Apps *',
    'R' => '',
    'S' => 'Sales/Mktg Bulletins *',
    'T' => 'Cartoons',
	'U' => 'Interviews *',
    'V' => 'Car stuff',
    'W' => 'Sales Conference *',
    'X' => 'x-Problem',
    'Y' => 'Posters and Symbols ',
    
    

    );
    #tag starting with Z is reserved for special searches, e.g., all archives

	 public static $archival_tags = "ACDEFIMOSWY";

	
	

// code => array(name,next-code,after-days)
private static $ems_codes = array(
		'Y'	=> ['Validated','',7],
		'Q'	=> ['Believed Good','',7],
		'XX'	=> ['To be removed','',7],
		'LA'	=> ['Lost - No Response','',7],
		'LB'	=> ['Lost - Bounced','',7],
		'LO'	=> ['Lost - Other','',7],
		'LN'	=> ['Lost - No Email Address','',7],
		'LE'	=> ['Lost - After email change','',7],
		'LS'    => ['Lost at signup','',7],
		'LD'    => ['Lost - Deceased','',7],
		'B1'	=> ['May be bouncing','B2',7],
		'B2'	=> ['Bounced twice','LB',7],
		'A1'	=> ['Being revalidated','A2',7],
		'A2'	=> ['Being revalidated (2nd attempt)','A3',7],
		'A3'	=> ['Being revalidated (3rd attempt)','A4',7],
		'A4'	=> ['Being revalidated (Final attempt)','LA',7],
		'E1'	=> ['Email change being validated','E2',7],
		'E2'	=> ['Email change being validated (2nd)','LE',7],
		'N1'	=> ['New Signup','N2',2],
		'N2'	=> ['New Signup (2nd)','XX',3],
		'D'     => ['Lost but logging in. (Deferred lost)','',7],

		);
	

		
	

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
	'B'	=>	'Marketing/Sales/Rep',
	'C'	=>	'Manufacturing',
	'D'	=>	'Finance/HR/Legal',
	'E' =>  'Contractor/Vendor',
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
            'jeff' => 'Jeff Drobman'

        );
        
public static $asset_type_names = array(
    'I' =>'Image' ,
     'C' =>'Cartoon' ,
     'M' =>'Multimedia' ,
     'P' =>'Document' ,
     'A' =>'Album' ,
     'W' =>'Web Page',
     'V' => 'Web Video',
     'O' =>'Other'
    );

public static $asset_status_names = array(
    'T' =>'Temporary Holding' ,
     'X' =>'Deleted' ,
     'U' =>'User Photo' ,
     'A' =>'Ordinary Asset' ,
     
    );

// code => [name, seclevel]

private static $member_codes = array (
	'N' => array ('New Signup', 1),
	
	'G' => array ('Guest', 2),
	'GA'	=> array ('Anonymous Guest', 2),
	'M' => array ('Member', 4),

	'MC' => array ('Contributor', 6),
	'MN' => array ('News Admin', 7),
	'MU' => array ('User Admin', 8),
	'MA' => array ('Admin Admin', 9),
	'MI' => array ('Inactive Member', 2),

	'I' => array ('Inactive', 2),
	'T' => array ('test user (like member)', 4),
	'L' => array ('lost ', 1),
	'D' => array ('deceased', 0),

	'H' => array ('hidden', 0),
	'X' => array ('to be deleted', 0),
	'Y' => array ('Non-member', 0),
	'YA' => array ('Alumni non-member', 0),
	
	'H' => array ('??' ,4),
	);


#these member status codes are considered members
	private static $member_array = array(
	'M', 'MA','MN','MC','MU','R','G'
	);

public static $stale_data_limit = 365;   #days
public static $test = 'you win';

######## Getters
	/* returns the list at teh var */
	public  static function getEmsData($code){
			return self::$ems_codes[$code];
		}
	public  static function getEmsName($code){
			return self::$ems_codes[$code][0];
	}
	public getEmsNameArray() {
		$names=array();
		foreach (self::$ems_codes as $k=>$data){
			$names[$k] = $data[0];
		}
		return $names;
	}
	
	public  static function getSecLevel($code='N'){
		return self::$member_codes[$code][1] ;
	}
	public static function getMemberInList(){
		return self::$member_array;
	}
	public  static function getMemberInSet () {
		#for sql IN clause
		return "'" . implode("','",self::$member_array) . "'";
	}
	public  static function  getMemberDescription($code='N'){
		return self::$member_codes[$code][0];
	}
	
	public  static function getMemberAliasList () {
	// for showing list of available aliases
		$Aliastext = "(Aliases: " . implode(', ',array_keys(self::$user_aliases)) . ")";
		return $Aliastext;
	}
	public static function getAssetArchivalTags() {
		return self::$archival_tags;
	}
	public static function getAssetTypeName($type) {
		return self::$asset_type_names($type);
	}
	public static function getAssetTagArray () {
		return self::$asset_tags;
	}
	
}



	
	







