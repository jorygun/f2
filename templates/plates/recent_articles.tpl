<div style='margin-left:2em;float:left'>
<style>
	table.article_list td{text-align:center;}
</style>
   <h4>Recent Article Activity</h4>
<?php if (!empty ($articles)) : ?>
   <table class='alternate article_list'>

        <tr><th>Article</th><th>Published</th>
        <th>Comments</th><th>Link Clicks</th><th>Interesting?</th></tr>

    <?php foreach ($articles as $row) : ?>

       <tr><td style='text-align:left;'>
       <a href='/get-article.php?id=<?=$row['id']?>&mode=d' target='discussion'>
        <?=$row['title'] ?></a> </td>

      <td><?= $row['pubdate'] ?> </td>
      <td><?= $row['comment_count'] ?> </td>

      <td><?= $row['clicks'] ?></td>
      <td ><?= $row['votes'] ?></td>

      </tr>
      <?php endforeach; ?>
		</table>
<?php else: ?>
	Nothing to Report
<?php endif; ?>
		<p><small>Run <?= $run_date?></small></p>
</div>

