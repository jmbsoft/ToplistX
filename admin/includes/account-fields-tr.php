<tr id="<?PHP echo $item['field_id']; ?>">
  <td>
    <input class="checkbox autocb" name="field_id[]" value="<?PHP echo $item['field_id']; ?>" type="checkbox">
  </td>
  <td>
    <?PHP echo $item['name']; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['label'], 70); ?>
  </td>
  <td>
    <?PHP echo $item['type']; ?>
  </td>
  <td style="text-align: center;">
    <?php if( $item['on_create'] ): ?>
    <img src="images/<?PHP echo $item['required_create'] ? 'check-required' : 'check'; ?>.png">
    <?php else: ?>
    <img src="images/x.png">
    <?php endif; ?>
  </td>
  <td style="text-align: center;">
    <?php if( $item['on_edit'] ): ?>
    <img src="images/<?PHP echo $item['required_edit'] ? 'check-required' : 'check'; ?>.png">
    <?php else: ?>
    <img src="images/x.png">
    <?php endif; ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=tlxShAccountFieldEdit&field_id=<?php echo urlencode($item['field_id']); ?>" class="window function {title: 'Edit Account Field'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['field_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>