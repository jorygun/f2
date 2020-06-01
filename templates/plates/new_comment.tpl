
<!-- comment form -->
	<br><hr>
	<div style='float:left;'>
	 <?=$admin_note?>

	<p class='content'><b>Add a comment from <?=$username?></b>
	 <div id='Standards'  style="display:none; border:2px solid green; padding:2px;"  >
<b>About Comments</b>
<p>Comments can be applied to most newsletter articles on this site.<br>
Comments will automatically include your name and will be emailed to the article's author and any previous commenters.</p>
<p>You can use the "tinymce" editor bar to format your comment.  You can edit/modify html code by clicking the right-most icon on the toolbar.   </p>

<p>You can include a graphic/photo that is already in the site's assets. Just type <code>[asset <i>nn</i>]</code>, where nn is the asset id.  The thumbnail (200 x 200 pixels) of the asset will be inserted. <br>

Use the <a href='/asset_search.php' target='assets'>Search Graphics</a> menu item to find assets.  You can also upload a new graphic from there.
 </p>

 <p>Inappropriate comments will be removed.<br>
 <i>Inappropriate</i> means:
libelous, defamatory, or degrading to other AMDers,  obscene, pornographic, sexually explicit, or vulgar,
predatory, hateful, or intended to intimidate or harass, or contains derogatory name-calling.<br>
Please don't.
</p>

<p>Thanks for participating and keeping our site a friendly place for all of us.</p>


</div>
	<button type='button' onClick="showDiv('Standards');return false;">Comment Help</button></p>


<form method='post' >
<input type='hidden' name='on_id' value='<?=$on_id?>' >

<p>Use the tools in the toolbar below to format your comments with HTML</p>
	<textarea name='comment' id='comment' class='useredit' rows='4' cols='60' onkeyup='stoppedTyping()'></textarea>
	<p><b>Assets</b></p>
	Create or assign an asset (e.g., photo) to be displayed with your comment. You can find existing assets or create a new one here. ID is number over 1000. Only one allowed.<br>
	Your asset id: <input type=text id='asset_list' name='asset_list' size = 6 pattern = "^\d+$" value='' >
	<button type = 'button' onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >Create a new asset</button>

	<button type='button' onclick="window.open('/asset_search.php?mode=j' ,'assets','width=1100,left=160');">Search Assets</button>
<br>



	<p>If other people comment on this thread, you will receive their comment by email, UNLESS you...<br>
	<input type=checkbox name="no_email" value='1'> check here to block email from other commentors.</p>
	<button type='submit' id='submit_button'   >Submit Comment </button>
</form>
	</div>
	<br style="clear:both" />
