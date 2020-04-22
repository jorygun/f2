<h4>Send an email to a Flames Member</h4>
<p>This allows you to send an email to another Flames user,
<?=$username?>, who has hidden their email.  

The user will see your name and email.
</p>

<form method='post'>
<input type='hidden' name='to_id' value='<?=$user_id?>' >
Subject: <input type='text' size='40' name='subject'><br />
Message: <br />
<textarea name='message' rows = '10' cols='60'></textarea> <br />
<input type = 'submit' value='Send'>
</form>

