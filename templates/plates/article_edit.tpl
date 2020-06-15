
<h3>Create/Edit Article News Article</h3>



<form  method="POST"  onsubmit="return check_form(['title','topic']);">

<div style='background-color:#ccc;border:1px solid black;'>
(Data below cannot be changed)<br>
ID: <input type='text' name = 'id' value='<?=$id?>' READONLY><br>
Entered: <?=$date_entered?><br>
Status: <?=$status_name?> <?=$date_published?><br>

</div>

<hr>
<h4>Article Title and Content</h4> <button type='button' style='background:#cfc' name='ShowAbout' onclick="showDiv('AboutArticles')">Help</button>


 <div id='AboutArticles'  style="display:none; border:2px solid green;"  >
        <p>"News Articles" are the stories that will be published in a FLAMEs newsletter.  You create an article with a title and some content and optional link to some website.  You can simultaneously add a graphic to the asset database, and link it to your article.</p>
        <p>All articles are reviewed by the editor prior to being published, so if you make a mistake, don't worry; just let the editor know what you want.
        </p>
        <p><b>Create Your Article Content</b></p>
        <table>
        <tr><td>Title</td><td><i> The Item Title is the headline on the news article</i></td></tr>
        <tr><td>Topic</td><td><i> The Topic determines what section of the newsletter the article will be in.</i></td></tr>
        <tr><td>Source (optional)</td><td><i>If this is from some other publication or source, enter the publication or event or other source and its date. (Date is unformatted text and just for information, so it doesn't have to be in any particular format.)</i></td></tr>
        <tr><td>link title and url (optional)</td><td><i>If there is a link to another site, enter its title and url here.  It will show as a link below your article. If you leave out a title,
it will be shown as "Read More...". Link URL must start with 'http://' or 'https://'</i></td></tr>
        <tr><td>Content</td><td><i>The content of the article, typically a few lines from a magazine article, or whatever you want to say. Content should normally be filled in, but you CAN publish an article without any content, but just title and a graphic. <br> Carriage returns are converted to line breaks. URLs are linked IF they are preceeded by a space; otherwise they display whatever html you type in.</i></td></tr>
        <tr><td>Comment</td><td><i>Your comment on the story; appears in green below the story</i></td></tr>
        <tr><td colspan=2><hr /><b>Adding a Graphic, File, or other Asset</b>
        <br>
            Assets are photos, movies or pdf files catalogued in the searchable "Asset" database on the site.  One or more assets can displayed along with your article. <br>
            Assets are displayed as a "thumbnail" in a 200px wide box to the left of your article text.  Clicking on the thumbnail will bring up a full size display of the asset and the url if someone wants to download it.
            <br><br>
            Every asset has an id number. Assets can be a file stored on the
            site, or can just be a pointer to some url somewhere else.  You add a graphic that's already on the site by referencing its existing ID, or you can create a new asset by uploading a file or a url to a file on the web.<br></td></tr>

            <tr><td colspan='2'><b>Create New Asset</b></td></tr>
            <tr><td></td><td><i>If you have a new graphic to display with your article, you can enter it here.
             It will be assigned an ID number, entered into the Asset catalog, and displayed with your article.
            <tr><td>Asset Type</td><td><i>
                Assets can be images, documents (e.g., PDF), multimedia (mp3, mp4), or web pages (urls to some other place.)
            </i></td></tr>

            <tr><td>Title</td><td><i>
                Short title.  Searched, but not usually displayed.
            </i></td></tr>

            <tr><td>Source and Year</td><td><i>
                Source is basically attribution, like magazine or photographer.
                Year is called "vintage" sometimes.  It is the best guess to the
                year the graphic was created.  You can search for assets by year.
            </i></td></tr>

            <tr><td>Caption</td><td><i>
               Usually displayed under the graphic.  This field is searched, so
               be sure to include the names of people in the photo.
            </i></td></tr>

<tr><td>Thumb Source</td><td><i>The "thumb source" is the file used to create
        the image used inthe asset thumbnail.  Often, this is also the file the
        thumbnail should point to.  You can either specify a url or, most
        commonly, just upload a file</i></td></tr>
<tr><td>Choose a File</td><td><i>
                Press this to open a file dialog box on your computer, from which you can select a file to upload.  Try to keep file
                size minimal (say below 2MB) but larger files and videos can be
                uploaded as well.
            </i></td></tr>

<tr><td>URL</td><td><i>
               You can simply point to a url on another site (hopefully it is a
               reasonably permanent url). If it's a graphic, then a local thumbnail will be created too. URLs to files on amdflames.org should start with a /.  External files should start with "http://".
            </i></td></tr>
<tr><td>Link Thumb Elsewhere</td><td><i>If you want the asset to point to
    something other than the image used to create the thumbnail, then enter
    that url here.  Use this when the asset is not simply the image used for
    the thumbnail, like a video or audio file. </i></td></tr>

 <tr><td><b>Or use existing asset(s)</b></td></tr>
 <tr><td></td>Asset IDs<td><i>
            If the graphic you want is already in the asset database, enter its id number here. You can search assets here: <button type='button' onclick="window.open('/scripts/assets.php' ,'assets','width=1100,left=160');">Search</button>. You can add multiple assets. They will line up left to right. </i>
            </td></tr>
        </table>
        <button type='button' style='background:#cfc' name='ShowAbout' onclick="showDiv('AboutArticles')">Close</button>

</div>

<table>

<tr><td width='160'>Topic: (required)</td><td><select name='topic' id='topic' class='input required'> <?=$topic_options?></select></td></tr>


<tr><td >Item Title (required)</td><td><input type='text' size='60' name='title' class='input required' id='title' value="<?=$title?>"></td></tr>

<tr><td>Status</td><td><select name = 'status'><?=$status_options?></select></td></tr>
<tr><td>FLAME contributor:</td><td><input type='text' name='contributor' value='<?=$contributor?>' onfocus="form.contributor_id.value='';"
    style = '$cont_style'> id: <input type='text' name='contributor_id' id='contributor_id' value='<?=$contributor_id?>' ><br><?=$Aliastext?></td></tr>



<tr><td >Source</td><td><input type='text' name='source' value="<?=$source?>" size="30"> date: <input type='text' name='source_date' value = "<?=$source_date?>" size="15"></td></tr>

<tr><td>url for more info</td><td><input type='text' name='link' value = "<?=$link?>" size="60"></td></tr>


<tr><td >title for above url</td><td><input type='text' size='60' name='link_title' value="<?=$link_title?>"></td></tr>


<tr><td style="vertical-align:top;">Content</td><td><textarea cols=60 rows=10 name='content' class='useredit' ><?=$content?></textarea></td><tr>

<tr><td>Contributor's Comment</td><td><textarea cols=60 rows=3 name='ed_comment'><?=$ed_comment?></textarea></td><tr>

<tr><td><b>Assets</b></td>
	<td>Assign asset ids to surround the article content, and/or assign 1 asset to occupy the full width of the article.  You can find existing assets or create a new one here.<br>
	<button type = 'button' onClick = 'window.open("/aq.php","quick_asset","width=600,height=400,left=300,top=100,resizable,scrollbars");' >Create a new asset</button>

	<button type='button' onclick="window.open('/asset_search.php?mode=j' ,'assets','width=1100,left=160');">Search Assets</button>
</td></tr>

<tr><td>Surrounding asset ids (left/top)</td>
   <td> First two will be on left of article; remaining ones across the top.</br><input type=text name='asset_list' id='asset_list' size = 40 value='<?=$asset_list?>'>
   </td></tr>

<tr><td>Central asset id (in body):</td>
	<td><input type=text name='asset_main' id=size=8 value='<?=$asset_main?>'>
	</td></tr>


<tr><td colspan='2'>Allow Comments? <input type='checkbox' value='1' name='take_comments' <?= $comments_checked ?>> &bull;
Allow Votes? <input type='checkbox' value='1' name='take_votes' <?= $votes_checked ?>></td></tr>



<?php if ( $_SESSION['level'] > 4): ?>
   <tr> <td colspan='2'>
    Queue for next <select name='queue'><?=$queue_options?></select>

    </td></tr>
<?php endif; ?>

</table>
<input type='submit' value='Submit Article' style='background:#CFC;'>
<button type='button' style='background:#CFC;' onClick ='window.open("/get-article.php?<?=$id?>")'>View Article</button>

</form>
</div>

