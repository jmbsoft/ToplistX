<tr id="<?php echo $item['username']; ?>">
  <td>
    <?php if( $item['action'] != 'Deleted' && $item['action'] != 'Blacklisted' ): ?>
    <input type="checkbox" class="checkbox autocb" name="username[]" value="<?php echo $item['username']; ?>">
    <?php endif; ?>
  </td>
  <td valign="top">
    <a href="<?php echo $item['site_url']; ?>" target="_blank"><?php echo StringChopTooltip($item['site_url'], 90, TRUE); ?></a><br />
    <?php echo $item['message']; ?>
  </td>
  <td valign="top" class="r-<?php echo strtolower($item['action']); ?>">
    <?php echo $item['action']; ?>
  </td>
  <td valign="top">
    <?php echo date(DF_SHORT, strtotime($item['date_scanned'])); ?>
  </td>
  <td style="text-align: right;" class="last" valign="top">
    <?php if( $item['action'] != 'Deleted' && $item['action'] != 'Blacklisted' ): 
    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($item['username']));    
    ?>
    <img src="images/search.png" width="12" height="12" alt="Scan" title="Scan" class="click-image function" onclick="openScan('<?php echo $item['username']; ?>')">
    
    <?php if( $account['disabled'] ): ?>
    <a href="" onclick="return doToSelected('<?php echo $item['username']; ?>', 'enable')" class="function">
    <img src="images/disabled.png" width="12" height="12" alt="Unlocked" title="Click to enable account"></a>
    <?php else: ?>
    <a href="" onclick="return doToSelected('<?php echo $item['username']; ?>', 'disable')" class="function">
    <img src="images/enabled.png" width="12" height="12" alt="Unlocked" title="Click to disable account"></a>
    <?php endif; ?>
    
    <?php if( $account['locked'] ): ?>
    <a href="" onclick="return doToSelected('<?php echo $item['username']; ?>', 'unlock')" class="function">
    <img src="images/locked.png" width="12" height="12" alt="Locked" title="Click to unlock account"></a>
    <?php else: ?>
    <a href="" onclick="return doToSelected('<?php echo $item['username']; ?>', 'lock')" class="function">
    <img src="images/unlocked.png" width="12" height="12" alt="Unlocked" title="Click to lock account"></a>
    <?php endif; ?>
    
    <a href="index.php?r=tlxShAccountEdit&username=<?php echo urlencode($item['username']); ?>" class="window function {title: 'Edit Account'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="index.php?r=tlxShAccountMail&username[]=<?php echo urlencode($item['username']); ?>" class="window function {title: 'E-mail Account'}">
    <img src="images/mail.png" width="12" height="12" alt="E-mail" title="E-mail"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['username']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
    <?php endif; ?>
  </td>
</tr>