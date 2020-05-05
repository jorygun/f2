
<h4>Bulk Asset Editor</h4>
<p>Use this to create a series of assets from a directory of  files
that have been uploaded).
</p><p>If the directory is in /assets/uploads, then when asset is created, files are moved
from the upload directory to the /assets/files directory (and renamed
with the asset id, like 1013.jpg).  If the files are in any other directory, they
are NOT moved or renamed. </p>
<p>  The directory must also include a file called "titles.txt". This file needs to have 3 tab-dlimited fields: filename, caption, and title.  These will be used when the assets are created.</p>
<p><b>CAUTION: </b> Be sure tabs are not converted to spaces in the file.</p>
<p>The first record MUST have all 3 fields: filename, caption, and title. THose will become the default caption and title to be used for any files that don't have their own.
</p>

<hr>


<form  method="POST"  style="border:1px solid black;padding:6px;">

<table>

<tr><td>Thumb (200px w):</td><td><input type=checkbox name='need_thumb' checked>b</td></tr>
<tr><td>Toon(800px w):</td><td><input type=checkbox name='need_toon'></td></tr>
<tr><td>Gallery (350px w):</td><td><input type=checkbox name='need_gallery'></td></tr>
</table>

<hr>

<table>

<tr><td>Source directory<br>
(from web root)</td><td><input type='text' name='dir' value='<?=$dir?>' size='100'></td></tr>


<tr><td>Contributor:</td><td><input type='text' name='contributor' value='<?=$contributor?>'><br>$Aliastext</td></tr>


<tr><td>From</td><td>vintage (year): <input type='text' name='vintage'  size="6"> Attribution <input type='text' name='source'  size="40"> </td></tr>



</table>
<input type="submit" name = 'submit' value='Submit'>

</form>
</div>
