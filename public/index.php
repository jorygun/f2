<?php
 #ini_set('display_errors', 1);
// ini_set('error_reporting', E_ALL);


//BEGIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
	use DigitalMx as u;
	use DigitalMx\Flames as f;
	use DigitalMx\Flames\Definitions as Defs;
	use DigitalMx\MyPDO; #if need to get more $pdo
	use DigitalMx\Flames\DocPage;
	use DigitalMx\Flames\FileDefs;



   if ($_SESSION['level'] > 0){
    	header('location:/news/current');

}

	$page_title = 'AMD Flames';
	$page_options = ['ajax','no-cache']; # ajax, votes, tiny


	$page = new DocPage($page_title);
	echo $page -> startHead($page_options);
		echo "<meta name='google-site-verification' content='VIIA7KGTqXjwzC6nZip4pvYXtFVLx7Th7VpWNGpWzpo' />";

	echo $page->startBody(3);




#set breaking news

 $breaking = FileDefs::breaking_news;
 if (file_exists($breaking)){
       echo file_get_contents($breaking);
   }

#set notice
 if (file_exists("index_notice.html")){
		        $notice =
			"<div id='block2' style='border:0px solid black; padding:5px;' >"
			. file_get_contents('index_notice.html')
			.	"</div>";

}


if ($_SESSION['login']['status']=='I'){
		echo <<< EOT
		<div style='border:1px solid #360;padding:5px;background-color:#cfc;'>
		<h3>Welcome Back, $username</h3>
		<p>You have requested an "Inactive" status, which limits the
		information you can retrieve from the site.</p>
		<p>If you would like to restore your membership,
		please <a href="mailto:admin@amdflames.org">contact the admin</a> and have your status reset.  You can still opt
		out of any regular emails from the site.
		</p>
		</div>
EOT;
exit;
		}


	echo <<< EOT
	<div style='border:1px solid #360;padding:5px;background-color:#cfc;'>
	<h3>Welcome AMD Alumni and Friends</h3>
	<p>This site is for former employees and associates of Advanced Micro Devices.</p>
	<p>You must access the site with your FLAMES-supplied link to enter the site. <br>
	If you are already a member and have lost your login link, retrieve it <a href="/help.php">here</a>.<br>
	If you are not a member but would like to be,choose the sign-up option under the menu above.</p>
	</div>
EOT;







echo <<<EOT

</div>
<p style='text-align:center;clear:both'></p>
</div>
</body></html>
EOT;

exit;



