<style>
table {border:1px solid black; }
.thumbtable td {text-align:center;width:10em;size:1em;}
.assettable td {size:1em;}
</style>


<h2>Bulk Asset Editor</h2>
<p>Use this to create a series of assets from a directory of  files
that have been uploaded).
</p><p>If the directory is in /assets/uploads, then when asset is created, files are moved
from the upload directory to the /assets/files directory (and renamed
with the asset id, like 1013.jpg).  If the files are in any other directory, they
are NOT moved or renamed. </p>
<p>  The directory must also include a file called "titles.txt". This file needs to have 3 tab-deimited fields: <br>
<code>filename	caption	title</code><br>
These will be used when the assets are created.</p>
<p><b>CAUTION: </b> 
<ul>
<li>Be sure tabs are not converted to spaces when you save the file.</p>
<li>The first record MUST have all 3 fields: filename, caption, and title. THose will become the default caption and title to be used for any files that don't have their own.
</ul>

<hr>
<form  method="POST"  style="border:1px solid black;padding:6px;">


<table>
<tr><td>Thumb Images</td><td>
		<table class='thumbtable'>
		<tr><th>Form</th><th>Create/Recreate</th></tr>
		<tr ><td>Thumb </td><td><input type='checkbox' name='thumbs' checked></td></tr>
		<tr><td>Gallery </td><td><input type='checkbox' name='galleries'></td></tr>
		<tr><td>Toon </td><td><input type='checkbox' name='toons'></td></tr>
		</table>
</td></tr>


<tr><td>Source directory<br>
(from web root)</td><td><input type='text' name='dir' value='<?=$dir?>' size='100'></td></tr>


<tr><td>Contributor:</td><td><input type='text' name='contributor' value='<?=$contributor?>'  ><input type='hidden' name='contributor_id' id='contributor_id' value=0><br><?=$Aliastext?></td></tr>


<tr><td>From</td><td>vintage (year): <input type='text' name='vintage'  size="6"> Attribution <input type='text' name='source'  size="40"> </td></tr>



</table>
<input type="submit" value='Submit'>

</form>
