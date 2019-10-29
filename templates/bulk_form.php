<?php
echo <<<EOT
<p><b>Create An Email</b></p>


<p>You may use the following placeholders:</p>
<table><tr><td><ul>
<li>::link::  User's personal link to log in
<li>::scode:: users_code
<li>::newslink:: link to latest newsletter for this user
<li>::name::  User's name in db
<li>::profile_date:: Date user's profile last updated <br>

<li>::teaser::  Combination of highlights from current newsletter
</ul></td><td><ul>
<li>::pointer:: URL specified below
<li>::verify:: URL to verify email
<li>::uemail:: User's email address
<li>::no_bulk:: Notice to users not subscribing to weekly email
<li> [image nnn] replaced by image link to thumb file nnn.jpg
<li> ::edition:: Edition name
</ul>
</td></tr></table>
<div id='in_bulk_queue'>$jobs_in_queue</div>

<p>
<button  onclick="getMessage('bm-std-html');">News Ready (html)</button>
<button onclick="getMessage('bm-lost');">Periodic Lost</button>

</p>
<form  method="post" name='sendchoices'>
<hr>

<p>Subject <input type="text" name="subject" size="100" id='msubject'  ></p>

Message Body<br>
<textarea name="body" rows="15" cols="78" id='mcontent'>

</textarea>
<br><br>
<p>Pointer (for substition) <input type=text name='pointer' value=$pointer></p>
<p>Send to: (times calculated for 600 msgs/hour) <br>
<p><input type="radio" name="sendto" value="test" checked>Test (test_status not empty, count ${counts['test']}) </p>

<p class="greenlight"><input type="radio" name="sendto" value="bulk" > Only those with no_bulk = FALSE (count:${counts['bulk']}; time: $time_bulk)</p>

<p>
<input type="radio" name="sendto" value="nobulk">Users set to No Bulk (count: ${counts['nobulk']}; time: $time_nobulk) </p>

<p><input type="radio" name="sendto" value="all">All Valid Emails (count: ${counts['active']}; time: $time_all) </p>

<p><input type="radio" name="sendto" value="atag">Only the admin statuses below:
<br>Set admin statuses (single chars): <input type="text" name='tag'></p>

<p><input type="radio" name="sendto" value="contributors">News contributors</p>

<p><input type="radio" name="sendto" value="aged_out">Lost - Aged Out (count: ${counts['aged']}; time: $time_aged)
</p>


<br>
<!-- <p>Send Rate: <input type=text name='sendrate' value='360' size='6'> messages per hour</p> -->
<p>Send first cron after: <input type='text' name='start' value='$now'> (e(If no timezone specified, will be pacific time. )</p>


<input type="submit" name="go" value="Schedule" class="greenlight">
<input type="submit" name="go" value="Run Now">
<input type="submit" name="go" value="Setup Only">


</form>


</body>
</html>
EOT;
