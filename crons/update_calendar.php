<?php
namespace digitalmx\flames;

/*
	cron script to update the calendar files
*/



$script = basename(__FILE__);
$dir=dirname(__FILE__);

if (! @defined ('INIT')) {
	include "$dir/cron-ini.php";
}

if (! @defined ('INIT')) { throw new Exception ("Init did not load"); }

use digitalmx\flames\Calendar;

$c = new Calendar();
$c -> build_calendar();


