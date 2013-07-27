<tr id="<?php echo $item['username']; ?>" class="<?php echo $item['status']; ?>">
  <td valign="top">
    <input type="checkbox" class="checkbox autocb" name="username[]" value="<?php echo $item['username']; ?>">
  </td>
  <td>
    <div style="float: right;"><b>Sorter:</b> <?php echo StringChopTooltip($item['sorter'], 50); ?></div>
    <b style="color: #f9a239; font-size: 10pt;"><?php echo $item['username']; ?></b>
  
    <div class="fieldgroup">
      <label class="lesspad">Site URL:</label>  <a href="<?php echo $item['site_url']; ?>" target="_blank"><?php echo StringChopTooltip($item['site_url'], 90); ?></a>
    </div>
    
    <div class="fieldgroup">
      <label class="lesspad">E-mail:</label>  <a href="mailto:<?php echo $item['email']; ?>" target="_blank"><?php echo StringChopTooltip($item['email'], 90); ?></a>
    </div>
    
    <div class="fieldgroup">
      <label class="lesspad">Title:</label>  <?php echo StringChopTooltip($item['title'], 90); ?>
    </div>
    
    <div class="fieldgroup">
      <label class="lesspad">Description:</label>  <?php echo StringChopTooltip($item['description'], 110); ?>
    </div>
    
    <?php if( $item['category_id'] ): ?>
    <div class="fieldgroup">
      <label class="lesspad">Category:</label>  <?php echo StringChopTooltip($GLOBALS['_categories_'][$item['category_id']]['name'], 90); ?>
    </div>
    <?php endif; ?>
    
    <div class="fieldgroup">
      <label class="lesspad">Return Percent:</label>  <?php echo $item['return_percent']; ?>%
      
      <b style="padding: 0px 3px 0px 35px;">Added:</b> <?php echo $item['date_added']; ?>
      
      <b style="padding: 0px 3px 0px 35px;">Activated:</b> <?php echo $item['date_activated']; ?>
    </div>
    
    <div class="fieldgroup">
      <label class="lesspad">Raw In:</label>  <?php echo number_format($item['raw_in_total'], 0, $C['dec_point'], $C['thousands_sep']); ?>
      <b style="padding: 0px 3px 0px 25px;">Unique In:</b>  <?php echo number_format($item['unique_in_total'], 0, $C['dec_point'], $C['thousands_sep']); ?>
      <b style="padding: 0px 3px 0px 25px;">Raw Out:</b>  <?php echo number_format($item['raw_out_total'], 0, $C['dec_point'], $C['thousands_sep']); ?>
      <b style="padding: 0px 3px 0px 25px;">Unique Out:</b>  <?php echo number_format($item['unique_out_total'], 0, $C['dec_point'], $C['thousands_sep']); ?>
      <b style="padding: 0px 3px 0px 25px;">Clicks:</b>  <?php echo number_format($item['clicks_total'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    </div>
    
    <div class="fieldgroup">
      <label class="lesspad">Rating:</label>  <?php echo ($item['ratings'] ? sprintf('%0.2f', $item['ratings_total']/$item['ratings']) : '-'); ?>
    </div>
    
    <?php if( $item['admin_comments'] ): ?>
    <div class="fieldgroup">
      <label class="lesspad">Comments:</label>  <?php echo StringChopTooltip($item['admin_comments'], 90); ?>
    </div>
    <?php endif; ?>


    <?php
    if( $item['edited'] ): 
        $edited = unserialize(base64_decode($item['edit_data']));
        unset($edited['banner_data']);
        unset($edited['banner_url_local']);
        ArrayHSC($edited);
    ?>
    <div style="border: 1px solid #ffe7cb; background-color: #FFFFC8; padding: 3px; margin-left: 20px; margin-top: 8px;" class="edited_span">
    <div style="float: right;">
    <img src="images/check.png" border="0" width="12" height="12" alt="Approve" title="Click to approve" class="click" onclick="return processEditSelected('<?php echo $item['username']; ?>', 'approve')">    
    <img src="images/x.png" border="0" width="12" height="12" alt="Reject" title="Click to reject" class="function click" onclick="return processEditSelected('<?php echo $item['username']; ?>', 'reject')">
    </div>
    <b style="color: #ff9112;">Edited Data</b><br />
    <?php 
    foreach( $edited as $name => $value ):
        if( $value != $item[$name] ):
            if( $name == 'category_id' ) $value = $GLOBALS['_categories_'][$value]['name'];
            echo "<div class=\"fieldgroup\"><label class=\"lesspad\">" . (isset($GLOBALS['_fields_'][$name]) ? $GLOBALS['_fields_'][$name] : ucwords(str_replace('_', ' ', $name))) . ":</label> " .
                 (preg_match('~^http://~', $value) ? '<a href="'.$value.'" target="_blank">'.$value.'</a>' : $value) . "</div>\n";
    ?>
    <?php 
        endif;
    endforeach;
    ?>
    <div class="clear"></div>
    </div>
    <?php endif; ?>
  </td>
  <td style="text-align: right;" class="last"  valign="top">
    <?php 
    if( $item['banner_url'] ): 
        $item['banner_url'] = $item['banner_url_local'] ? $item['banner_url_local'] : $item['banner_url'];
    ?>
    <img src="images/banner.png" width="11" height="12" alt="Banner" title="Click to view banner" class="function click" onclick="showBanner(this,'<?php echo $item['banner_url']; ?>','<?php echo "{$item['banner_width']}x{$item['banner_height']}"; ?>')">
    <?php endif; ?>
    
    <?php if( $item['comments'] ): ?>
    <a href="index.php?r=tlxShComments&username=<?php echo urlencode($item['username']); ?>" class="function">
    <img src="images/comments.png" width="12" height="12" alt="Comments" title="Click to view comments"></a>
    <?php endif; if( $item['disabled'] ): ?>
    <img src="images/disabled.png" width="12" height="12" alt="Unlocked" title="Click to enable account" onclick="return doToSelected('<?php echo $item['username']; ?>', 'enable')" class="function click">
    <?php else: ?>
    <img src="images/enabled.png" width="12" height="12" alt="Unlocked" title="Click to disable account" onclick="return doToSelected('<?php echo $item['username']; ?>', 'disable')" class="function click">
    <?php endif; ?>
    
    <?php if( $item['locked'] ): ?>
    <img src="images/locked.png" width="12" height="12" alt="Locked" title="Click to unlock account" class="function click" onclick="return doToSelected('<?php echo $item['username']; ?>', 'unlock')">
    <?php else: ?>
    <img src="images/unlocked.png" width="12" height="12" alt="Unlocked" title="Click to lock account" onclick="return doToSelected('<?php echo $item['username']; ?>', 'lock')" class="function click">
    <?php endif; ?>
    
    <img src="images/stats.png" width="12" height="12" alt="Stats" title="Stats" class="click-image function" onclick="openStats('<?php echo $item['username']; ?>')">
    <img src="images/search.png" width="12" height="12" alt="Scan" title="Scan" class="click-image function" onclick="openScan('<?php echo $item['username']; ?>')">
    <a href="index.php?r=tlxShAccountEdit&username=<?php echo urlencode($item['username']); ?>" class="window function {title: 'Edit Account'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="index.php?r=tlxShAccountMail&username[]=<?php echo urlencode($item['username']); ?>" class="window function {title: 'E-mail Account'}">
    <img src="images/mail.png" width="12" height="12" alt="E-mail" title="E-mail"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['username']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
    
    <?php if( $item['status'] == STATUS_PENDING ): ?>
    <span class="reject_span" style="padding-top: 10px;">
    <br />
    <br />
    
    <img src="images/check.png" border="0" width="12" height="12" alt="Approve" title="Click to approve" class="click" onclick="return processSelected('<?php echo $item['username']; ?>', 'approve')">    
    <img src="images/x.png" border="0" width="12" height="12" alt="Reject" title="Click to reject" class="function click" onclick="return processSelected('<?php echo $item['username']; ?>', 'reject')">

    <select name="reject[<?php echo $item['username']; ?>]" id="reject_<?php echo $item['username']; ?>" class="reject" style="margin-left: 5px;">
      <option value="">None</option>
      <?php if( $GLOBALS['_rejects_'] ): ?>
      <?php foreach( $GLOBALS['_rejects_'] as $reject ): ?>
      <option value="<?php echo $reject['email_id']; ?>"><?php echo StringChop($reject['identifier'], 20); ?></option>
      <?php endforeach; ?>
      <?php endif; ?>
    </select>    
    </span>
    <?php endif; ?>
  </td>
</tr>