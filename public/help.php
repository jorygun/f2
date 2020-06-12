<?php

namespace DigitalMx\Flames;

#ini_set('display_errors', 1);

//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\Flames\DocPage;




$login->checkLevel(0);

$page_title = 'AMD Flames Help';
$page_options=[]; #ajax, votes, tiny

$page = new DocPage($page_title);
echo $page -> startHead($page_options);
# other heading code here

echo $page->startBody();


//END START


?>

<h1>Help</h1>
<p>The FLAMEsite is for previous employees and associates (like reps and contractors) of Advanced Micro Devices.
</p>
<p>Access is limited to members and guests who have registered and been accepted for membership</p>
<p>If you have any difficulties with the site, please let the administrator know so he/she can fix the
problems.  It's only software; there's always something wrong.</p>
<p>Email the administrator at <a href="mailto:admin@amdflames.org" target="_blank">admin@amdflames.org</a></p>

<hr>
<div  class="indent" >
<h3><a id="logininfo">Login Help</a> </h3>
<p>To log into this site, you must use a link that looks like this: <code>https://amdflames.org/?s=xxxxxxxxxxx</code>.  The xxx part is your unique login code, supplied by the site when you signed up. Once logged in, you will remain logged in until you close your browser, log out, or time out after around 20 minutes of inactivity. </p>
   <ul>
   <li>If you have never signup up for the site, please use the <a href="/signup.php">sign up</a> link.
   <li>If you have lost your login link, enter your email address below, and your login link will be sent to you if your email is in the member list.
   <li>If all else fails, please <a href="mailto:admin@amdflames.org">contact the administrator</a>.  </ul>


<p>If you are member and need to retrieve your login, enter your email here: <br>Email: <input type="text" id='em' name="email" size="40"> <button type='button' onClick = "var em = getElementById('em').value; takeAction('sendLogin',em,'','resp');">Send Logins</button></p>


<h3 name='touchscreen'>Touchscreen Users</h3>
Touchscreens behave a bit differently than mouse-driven screens.  If menus that you open on a touch devices don't close until you touch another menu, use thethere is  "X" button at the right side of the menu bar.  Click on that and any open menu will go away on a touchscreen device like a phone or tablet. Maybe.

</div>


</div>
