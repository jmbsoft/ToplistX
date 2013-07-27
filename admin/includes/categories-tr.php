<tr id="<?php echo $item['category_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="category_id[]" value="<?php echo $item['category_id']; ?>">
  </td>
  <td>
    <?php echo StringChopTooltip($item['name'], 40); ?>
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['hidden'] ? 'check' : 'x'; ?>.png">
  </td>
  <td>
    <?php
    $accounts = $DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE `category_id`=?', array($item['category_id']));
    echo number_format($accounts, 0, $C['dec_point'], $C['thousands_sep']);
    ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item[$_REQUEST['order']], 20); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=tlxShAccountSearch&category_id=<?php echo urlencode($item['category_id']); ?>" class="function">
    <img src="images/go.png" alt="View Galleries" title="View Galleries"></a>
    <a href="index.php?r=tlxShCategoryEdit&category_id=<?php echo urlencode($item['category_id']); ?>" class="window function {title: 'Edit Category'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['category_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>