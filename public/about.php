<?php
//BEGIN START
	require_once "init.php";

	use digitalmx\flames\DocPage;
	$pdo = MyPDO::instance();

	$page = new DocPage;
	echo $page->startHead("About AMD Flames", 0);
	echo $page->startBody("About AMD Flames",2);

?>
<p>"FLAME" stands for "<b>F</b>ormer <b>L</b>oyal <b>AM</b>D <b>E</b>mployee"</p>
<p>The FLAME website was created in 1997 by John McKean, an AMD FAE from Toronto, as a way for former and current AMDers to stay connected. After compiling the suggestions that were sent to him each week, and adding his own commentary, in italics, signed/jm. He published his weekly FLAME newsletter to more than 2,000 readers, around the world, over the past 17 years.</p><p>
John passed away suddenly in September, 2014.  John will be remembered by many for his contributions to AMD as an integral part of the Toronto field sales office from 1981-1996.</p><p>
Read our <a href="/spec/McKean.html" target="_blank">tribute to John here</a>.</p>


<h3>Editor</h3>
<ul >
	<li>John Springer, tech dude, admin, and temporary editor

</ul>

<h3>Financial Support</h3>
<p >
The cost of operating this site is borne by these members.
</p>


<ul>

 <?
 $q="SELECT username,user_email,contributed FROM `members_f2` WHERE contributed IS NOT NULL and contributed > CURDATE() - INTERVAL 24 month;";
 $result = $pdo -> query($q);
  while ($row = $result->fetch()){
    echo "<li>${row['username']}</li>";
    }
 ?>

</ul>

<div  style="width:100%;text-align:center;border:1px solid black;display:block;" id="adv" >

<p>Contributions to the site operations can be made through paypal, using
the link <a href="http://paypal.me/amdflames">paypal.me/amdflames</a>.  </p>
</div>

<h3>Authors</h3>
These members can write articles and upload graphics to the site. If you&rsquot;d like to do have that ability,
<a href='mailto:editor@amdflames.org'>contact the editor</a>.
<ul>
 <?
 $q="SELECT username,user_email,contributed FROM `members_f2` WHERE status = 'MC' ";
 $result = $pdo -> query($q);
  while ($row = $result->fetch()){
    echo "<li>${row['username']}</li>";
    }
 ?>
</ul>

<h3>How it Works</h3>
<p>The site is hosted by <a href='http://www.pair.com'>pair.com</a>.  I highly recommend them.</p>
<p>The site is written in PHP.  Over the years, I keep learning better code-writing skills, so the site is kind of constantly evolving, from spaghetti to structured to objects.  Anyone who wants to help is welcome, as I'm still totally an amateur.
</p>
<p>Virtually all the pages on the site are php so I can at least check for a login before they display.  The admin is pretty much all through web forms and php.  I rarely have to manually upload a file or diddle with any html code or data.
</p>
<p>The site uses mysql tables to store:<ul>
    <li>all the member data
    <li>all the news articles (since 2014)
    <li>a table of assets (files, title, caption, thumbnail, contributor)
    <li>a list of links, so I can record what links people click on: (<a href='https://amdflames.org/scripts/view_links.php' target='links'>links</a>).
    <li>a table of comments. ( I tried using Disqus for comments for a while, but it was awkward.  Much better to just role my own.  Comments can be attached to an article or graphic.  Users can make multiple comments on an article or be restricted to one.  Other people can get notified when a comment is posted.  Seems to work OK now. )
    </ul>


    </p>
<p>Newsletters date back to 1998.  The older ones are self-contained files;
the newer ones are each in a directory containing a newsletter template and a
bunch of text files that contain the content.  This lets me modify newsletter
structure without messing up old newsletters.</p>

<p>I generally update the articles several times a week, with the last update on Thursday.  Then I run a script that creates a list of all the updates to member data made since the last newsletter, which just drops into the newsletter folder.  Preview the newsletter, and if it's OK, publish it , which is mostly just moving the directory.  Then build the bulk email.  The email can be queued to go out overnight, or if necessary, can go out in real time.  Each member gets an individualized newsletter, and they are paced to avoid triggering spam blockers.</p>

<p>There a "sweep" script that runs every night to look for members who have not logged in or verified their email for a year.  It sends them an email asking to verify their address.  A week later it sends another one.  Then a third.  Then it notifies me and I try manually sending them something.  Then it marks them as Lost.  The newly Lost members get listed in the weekly newsletter.  A similar sequence is followed when people change their email address, or when I get a vague bounce.
</p>

<p>Login is via a unique code for each user, which is basically your userid + password, both machine-generated.  Not very secure, but much simpler than trying to have a proper password system. I just include your login in every email you get. </p>
<p>The newsletter is saved as a directory with a php template and a series of text files with the content that get included when the template is run.  I've thought about emailing out the whole newsletter as a html file, but the way it is now encourages people to log in, where they can make comments and get reminded to update their profiles from time to time.  So nae.
</p>
<p>I do all my site admin on my Mac, using the web forms.  I do all my development on my Mac, using BBEdit to write the code, and Transmit to keep local folders in sync with folders on the server.  PHP and Mysql both run on my Mac, but I don't usually run stuff there because there's too many differences between the local environment and the server. (php.ini for example.)  So occasionally I edit something live on the site, and regret it 🙄.


</body>

</html>
