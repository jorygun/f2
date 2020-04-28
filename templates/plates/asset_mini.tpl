 <table>
       <tr style='border-top:1px solid blue;'> <td style='font-size:.9em;'>
        <?= $id ?> <b><?= $this->e($title) ?></b><br>
        <?= $this->e($caption) ?><br>
        <i>Source url:</i> <?= $url ?> <br>
        <i>Type:</i> <?= $type ?>  <i>Vintage</i> <?= $vintage ?><br>
        <i>Status:</i> <?= $status_label ?> <br>
    
        <i>Entered:</i> <?= $date_entered ?> <i> First Used:</i> <?= $first_link ?><br> 
        <i>Contributor:</i> <?= $contributor ?><br>

        <i>Size</i> <?= $sizekb ?> KB<i> Has:</i> Thumb <?= $show_thumb ?> &middot; Gallery <?= $show_gallery ?> &middot; Toon <?= $show_toon ?><br>
        <br><b>Tags</b> (* archival)<br><?= $tag_display ?><br>

        <?= $edit_panel ?>
        </td><td>
        <a href='imagelink' target='image'><?= $image ?></a>
        </td></tr>
        
</table>
