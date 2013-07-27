<tr id="<?php echo $item['comment_id']; ?>" class="<?php echo $item['status']; ?>">
  <td valign="top">
    <input type="checkbox" class="checkbox autocb" name="comment_id[]" value="<?php echo $item['comment_id']; ?>">
  </td>
  <td valign="top">
    <table width="100%" cellspacing="0">
      <tr>
        <td align="right">
          <b>Name:</b>
        </td>
        <td>
          <?php echo $item['name']; ?>

          <span style="padding-left: 20px;">
          <b>E-mail:</b>
          <span style="padding-left: 3px;"><a href="mailto:<?php echo $item['email']; ?>"><?php echo $item['email']; ?></a></span>
          </span>
        </td>
      </tr>
      <tr>
        <td align="right">
          <b>IP:</b>
        </td>
        <td>
          <?php echo $item['ip_address']; ?>

          <span style="padding-left: 20px;">
          <b>Username:</b>
          <span style="padding-left: 3px;"><?php echo $item['username']; ?></span>
          </span>
        </td>
      </tr>
      <tr>
        <td align="right" valign="top" width="100">
          <b>Comment:</b>
        </td>
        <td>
        <?php echo nl2br($item['comment']); ?>
        </td>
      </tr>
    </table>
  </td>
  <td valign="top">
    <?php echo date(DF_SHORT, strtotime($item['date_submitted'])); ?>
  </td>
  <td style="text-align: right;" class="last" valign="top">
    <?php if( $item['status'] == STATUS_PENDING ): ?>
    <a href="" onclick="return approveSelected('<?php echo $item['comment_id']; ?>')" class="function">
    <img src="images/check.png" width="12" height="12" alt="Approve" title="Approve"></a>
    <a href="" onclick="return rejectSelected('<?php echo $item['comment_id']; ?>')" class="function">
    <img src="images/x.png" width="12" height="12" alt="Reject" title="Reject"></a>
    <?php endif; ?>
    <a href="index.php?r=tlxShCommentEdit&comment_id=<?php echo urlencode($item['comment_id']); ?>" class="window function {title: 'Edit Comment'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['comment_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>