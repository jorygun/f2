"<div style='margin-left:2em;float:left'>";
   <h4>Recent Article Activity</h4>

   <table class='alternate article_list'>

        <tr><th>Article</th><th>Contributor</th><th>Published</th>
        <th>Comments</th><th>Link Clicks</th><th>Interesting?</th></tr>

    <?php foreach ($data as $row) :
    	$article_id = $row['id'];
    	 ?>

       <tr><td> <a href='/get-article.php?id=<?=$article_id ?>&mode='d' target='discussion'>" .
        <?=$row['title'] ?></a> </td>
      <td><?=$row['contributor']?> </td>
      <td><?=$row['pubdate']
      <td><?= $row['comments']?></td>





        $votes = ($row['take_votes'])? $row['net_votes'] : '-';

		$clicks = $link_counts[$article_id] ?? '-';

            $report .=  <<<EOT
            <tr >
            <td>$link</td>
            <td>$contributor</td>
             <td style='text-align:center'>$pub_date</td>
            <td style='text-align:center'>$ccount</td>
            <td style='text-align:center'>$clicks</td>
            <td style='text-align:center'>$votes</td>

            </tr>
EOT;
      }
      $report .=  "</table>\n";
    }
     $report .= "<small>Updated ". date('d M H:i T') . "</small></div>\n\n";
