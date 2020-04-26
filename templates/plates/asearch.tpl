

    <hr>
    <h3>Search Assets </h3>
    <input type='button' name='ShowAbout' onclick="showDiv('AboutAssets')" value="What Are Assets?" />
  


<div id='AboutAssets'  style="display:none;"  >
        <p>"Assets" are the various pictures, documents, and audio/video items that
have been uploaded to the site over the years.  The assets have been (mostly)
indexed in a database for easy search and retrieval.  Use this page to
find and retrieve an asset.</p>
<p>Graphic assets are stored in the original resolution and a 200 pixel wide version (called "thumb" and sometimes others.</p>

<p>Assets have various attributes, including a title, caption, and keywords.
Searching for a word (including multi-word phrases) will return assets matching
the search term in any of the three fields mentioned.</p>
<p>Assets also have a "vintage" which is the year the item was produced.  
You can search for a range of years.  For many items, the vintages are guesses.
Items also have a first-use, generally when it was first published in a newsletter.</p>

<p>Assets may also have "tags" - checkboxes for a series of archival classifications, like "Marketing bulletin", "Facility", "Corporate".  These can be used in searching assets.  Assets that aren't AMD archives (like photos that go with off-topic articles) don't have any tags.</p>

<p>Enjoy!</p>
<hr>
</div>

    <form method='post' name='select_assets' id='select_assets'>
    <p> Click to create a new asset <button type = "button" onclick = "window.open('/scripts/asset_edit.php?id=0','asset_edit');">New Asset</button>
    OR 
    choose any of the parameters below to find photos, videos, and audio files.</p>
    <hr>
    <table>
    <tr><td width='25%'></td><td ><button type='button' onclick="clearForm(this.form);">Clear Form</button></td></tr>
    <tr><td>Search Terms</td><td><input type='text' name='searchon' value = '<?=$searchon_hte?>'></td></tr>
    <tr><td>&nbsp;</td><td><small>Search is not case sensitive. Search terms can include spaces (like 'John East'). </small></td></tr>
    <tr><td>Vintage (year)</td><td><input type='text' name='vintage' size=8 value='<?=$vintage?>'> +/- years: <input type=text name='plusminus'  size=3 value='<?=$plusminus?>'></td></tr>
    <tr><td>Asset Type</td><td><select name='type'><?=$type_options?></select></td></tr>

    <tr><td>Tags (* tags are 'archival')</td><td><?=$tag_options?></td></tr>

     <tr><td>Asset id or range</td><td><input type=text name='id_range' value='<?=$id_range?>'></td></tr>

    <tr><td>Status</td><td><input type='checkbox' name='all_active' id='all_active' value=1 <?=$all_active_checked?> >All Active .. or .. <select name='status' onChange = 'check_select_all(this)' ><?=$status_options?></select></td></tr>

 <tr $hideme><td>Contributor<br>
    </td><td><input type='text' name='contributor' id='contributor' value='<?=$contributor?>'
    
   </td></tr>

   <tr><td>First Use</td><td><select name='relative'> <?=$use_options?> </select> <input type='text' name='searchuse' value='<?=$searchuse?>'></td></tr>


<?php
 //    if ($_SESSION['level'] > 7){
//     $output .= "<tr class='grayback'><td colspan='3'>Admin Functions</td></tr>\n";
//      $output .= "<tr class='grayback'><td>source URL</td><td><input type='text' name='url' size='100' value='${pdata['url']}'></td></tr>\n";
//      $output .= "<tr class='grayback'><td>addt'l sql WHERE clause</td><td>AND <input type='TEXT' name='sqlspec' value='${pdata['sqlspec']}' size=100></td></tr>";
//     
// 
 //   }
?>
<tr><td><button type='submit' name='submit' id='submit' value=true >Submit</button></td> <td></td></tr>
<tr><td colspan='3'><hr></td></tr>
    </table>

</form>

