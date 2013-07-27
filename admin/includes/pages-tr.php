<tr id="<?php echo $item['page_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="page_id[]" value="<?php echo $item['page_id']; ?>">
  </td>
  <td>
    <?php echo $item['page_id']; ?>
  </td>
  <td>
    <a href="<?php echo "http://{$_SERVER['HTTP_HOST']}/{$item['filename']}"; ?>" target="_blank">
    <?php echo StringChopTooltip("http://{$_SERVER['HTTP_HOST']}/{$item['filename']}", 75); ?>
    </a>
  </td>
  <td>
    <?php echo $item['build_order']; ?>
  </td>
  <td>
    <?php if( empty($item['category_id']) ): ?>
      MIXED
    <?php
    else:
    $category_name = htmlspecialchars($GLOBALS['categories'][$item['category_id']]['name']);
    echo StringChopTooltip($category_name, 25);
    endif; ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=tlxPageTemplateLoad&page_id=<?php echo urlencode($item['page_id']); ?>" class="function">
    <img src="images/html-small.png" width="12" height="12" alt="Edit Template" title="Edit Template"></a>
    <a href="index.php?r=tlxShPageEdit&page_id=<?php echo urlencode($item['page_id']); ?>" class="window function {title: 'Edit Ranking Page', height: 375}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['page_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>