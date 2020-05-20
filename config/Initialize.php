<?php

namespace DigitalMx\Flames;

/*
    set up paths and params.
    Must work for cron (no _SErVer) as well.
    so avoid env vars
*/


class Initialize
{
    // translate platform into home page
    private static $homes = array(
        'pair' => '/usr/home/digitalm',
        'ayebook' => '/Users/john'
    );
    protected  static $db_ini = '/config/db.ini'; # all the connection params
    protected $platform;

    protected $repo;
    protected $site; #/beta.amdflames.org

    protected $paths;

    public function __construct ()
    {

        $this->setPath();  #add /usr/local/bin
        $this->platform = $this->setPlatform();
        $this->paths = $this->setPaths($this->platform);

        $this->repo  = basename($this->paths ['repo'] ); # live
        $this->site = $this->setSite();
        $this->setIncludes($this->paths['repo'] );
			$this->loadRequires($this->paths['repo']);
        $this->setConstants($this->paths );


    }

    private function setPath()
    {
        // sets env path, not includes
        $path = getenv('PATH') . ':/usr/local/bin';
        $_SERVER['PATH'] = $path;

    }



    private function setPaths($platform)
    {
        $paths = array();

        $paths['repo'] = dirname(__DIR__);  #/usr/home...flames/live
        $paths['proj'] = dirname(__DIR__,2);  #/usr/home...flames
        $paths['home'] = self::$homes[$platform];
        $paths['db_ini'] = $paths['repo'] . self::$db_ini;

        return $paths; //array
    }


    private function setSite()
    {
        $site = $_SERVER['SERVER_NAME'] ?? 'amdflames.org';
        // use main site if run from cron (no _SERVER)
        return $site;
    }

    public function setConstants($paths)
    {

        /* Define site constants

        */
        define ('HOME', $paths['home']);
        define ('PROJ_PATH',$paths['proj']);

        define ('REPO_PATH',$paths['repo']);
        define ('REPO', $this->repo);

        define ('SITE_PATH', REPO_PATH . "/public");

        define ('SITE', $this->site);
        define ('SITE_URL', 'http://' . $this->site);
        define ('PLATFORM',$this->platform);
        define ('DB_INI',$paths['db_ini']);

    }

        private function setPlatform(){
    // using PWD because it seems to alwasy work, even in cron
        $sig = $_SERVER['DOCUMENT_ROOT'];
        $sig2 = getenv('PWD');
        if (
            strpos ($sig,'usr/home/digitalm') !== false
            || strpos ($sig2,'usr/home/digitalm') !== false
            ) {
                $platform = 'pair';
        } elseif (
            strpos ($sig,'Users/john') !== false
            || strpos ($sig2,'Users/john') !== false
            ) {
                $platform = 'ayebook';
        } else {
                throw new Exception( "Init cannot determine platform from ROOT '$sig' or PWD '$sig2'");
        }
        return $platform;
    }

	private function loadRequires($repo) {
		require "MxConstants.php"; #in libmx; in inc
    // BR, NL, BRNL, CRLF, LF, URL_REGEX //
		require_once 'FileDefs.php';
		require_once 'Definitions.php';
		require_once  'MxUtilities.php';
		require_once 'SiteUtilities.php';

		require_once "utilities.php";

	}

    private function setIncludes($repo)
    {
    #initial include path set in .user.ini to include this folder.
    #add other paths here .
    $proj_dir = dirname($repo);
    $current_path = getenv('PATH');
    ini_set('include_path',
          '.'
        . ':' . '/usr/local/lib/php'
        . ':' . '/usr/local/bin'
			. ':' . $repo . '/libmx'
		. ':' . $repo . '/src'
		. ':' . $repo . '/config'
        . ':' . $repo . '/public'
        . ':' . $repo . '/public/scripts'
        . ':' . $current_path

        );
    }
} #end class init

//EOF
