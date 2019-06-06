<?php

class NewsDefinitions {

	#article types available for individual entries
public static $ptypes = array(

	'amd_news'	=>	'AMD News',
	'tech_news'	=>	'Technology',
	'mfg' => 'Manufacturing',
	'cars'	=>	'Down at the Car Lot',
	'notable' => 'Notable',
	'biz'	=>	'Business News',

	'nostalgia'	=>	'Nostalgia',
	'gatherings'	=>	'Gatherings',
	'flames'	=>	'News About Flames',
	'sad'	=>	'Sad News',
	'wot'	=>	'WOT?',

	'ieee' => 'From the IEEE',
	'badgov' => 'Government Gone Bad',
	'goodgov' => 'Thanks for Good Government',
	'hot' => 'Might Be Controversial'

);

#article types available for admin entry
public statis $atypes = array (
	'mailbox'	=>	'In the Mailbox',
	'apology' => 'Apologia',
    'flamesite' => 'Site Update',
	'gatherings'	=>	'Gatherings',
	'cellar' => "Z's Wine Cellar",
	'spec' => "Special Topic",
    'toon' => 'Opening Cartoon',

);

#deprecated types
public_static $dtypes = array (
    'thread'	=>	'Conversations',
    'people' => 'The people win',
    'swamp' => 'From the Swamp'
);

public_static $itypes = array_merge($ptypes, $atypes,$dtypes);



public static $sections = array(
    'amd' => array('amd_news'),
    'news' =>  array ('biz','mfg','nostalgia'),
    'technology' => array ('ieee','tech_news'),
    'know' => array ('cars','wot','cellar','notable'),
    'people' => array ('gatherings','flames'),
    'opener' => array('toon'),
    'site' => array('apology','flamesite','spec'),
    'mail' => array('mailbox'),
    'sad' => array('sad'),
    'govt' => array('swamp','goodgov','badgov','people','hot'),


);

public_static $section_names = array (
    'amd' => 'AMD News',
    'news' =>  'The News',
    'remember' => 'From the Past',
    'people' => 'Friends',
    'know' => 'Off Topic',
    'opener' => 'Opener',
    'site' => 'Site News',
    'mail' => 'In The Mailbox',
    'ieee' => 'From IEEE',
    'technology' => 'Engineering Dept.',
    'sad' => 'Sad News',
    'govt' => 'Government and Politics'

);


}
