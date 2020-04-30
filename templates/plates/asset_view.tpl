
        <h3><?=$title?></h3>
<p>(Note: display size on this page is limited to 1024px wide. Use URL below to retrieve raw file.)<br>
	
       Link to source:  <?=$urllinked?><br>
   
        (Note: some source files cannot be displayed in the iframe below.  Use source link above to view.)
        </p>

    <?=$asset_display?>
    <p class='caption'><?=$caption?></p>
    <hr>
        <table>
        <tr><td>
        Asset id: <?=$id?><br>
        Type: <?=$mime?> -> <?=$type?> <br>
        Entered on <?=$date_entered?>  <br>
        Source: <?=$credit?>  <br>
        First use: <?=$first_use ?><br>
        Size: <?=$sizekb?> kB;  <br>
			Raw url: <?=$asset_url?><br> 
			encoded url:<?=$url_enc?><br>
        </td></tr>
        </table>

