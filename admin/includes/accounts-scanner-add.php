<?php
if( !defined('ToplistX') ) die("Access denied");               

$categories =& $DB->FetchAll('SELECT * FROM `tlx_categories` ORDER BY `name`');
array_unshift($categories, array('category_id' => '', 'name' => 'ALL CATEGORIES'));

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#categories').bind('focus', function() 
                                     {
                                         $('#categories')
                                         .css({position: 'absolute'})
                                         .attr({size: 30})
                                         .bind('blur', function() 
                                                       { 
                                                           $('#categories').css({position: 'static'}).attr({size: 2}).unbind('blur'); 
                                                       });
                                     });
  });

<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/accounts-scanner.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this scanner configuration by making changes to the information below
      <?php else: ?>
      Add a scanner configuration by filling out the information below
      <?php endif; ?>
    </div>
       
        <?php if( $GLOBALS['message'] ): ?>
        <div class="notice margin-bottom">
          <?php echo $GLOBALS['message']; ?>
        </div>        
        <?php endif; ?>
        
        <?php if( $GLOBALS['errstr'] ): ?>
        <div class="alert margin-bottom">
          <?php echo $GLOBALS['errstr']; ?>
        </div>        
        <?php endif; ?>

        <fieldset>
          <legend>General Settings</legend>
          
        <div class="fieldgroup">
            <label for="identifier">Identifier:</label>
            <input type="text" name="identifier" id="identifier" size="60" value="<?php echo $_REQUEST['identifier']; ?>" />
        </div>
                
        </fieldset>
        
        <fieldset>
          <legend>Accounts To Scan</legend>
          
          <div class="fieldgroup">
            <label>Status:</label>
            <div style="padding-top: 3px">
            <?php echo CheckBox('status[unconfirmed]', 'checkbox', 1, $_REQUEST['status']['unconfirmed']); ?> <label class="cblabel inline" for="status[unconfirmed]">Unconfirmed</label> &nbsp;
            <?php echo CheckBox('status[pending]', 'checkbox', 1, $_REQUEST['status']['pending']); ?> <label class="cblabel inline" for="status[pending]">Pending</label> &nbsp;
            <?php echo CheckBox('status[active]', 'checkbox', 1, $_REQUEST['status']['active']); ?> <label class="cblabel inline" for="status[active]">Active</label>
            </div>
          </div>
          
          <div class="fieldgroup">
            <label for="date_added_start">Date Added Range:</label>
            <input type="text" name="date_added_start" id="date_added_start" size="20" value="<?php echo $_REQUEST['date_added_start']; ?>" class="calendarSelectDate" /> through
            <input type="text" name="date_added_end" id="date_added_end" size="20" value="<?php echo $_REQUEST['date_added_end']; ?>" class="calendarSelectDate" />
          </div>
          
          <div class="fieldgroup">
            <label for="date_scanned_start">Date Scanned Range:</label>
            <input type="text" name="date_scanned_start" id="date_scanned_start" size="20" value="<?php echo $_REQUEST['date_scanned_start']; ?>" class="calendarSelectDate" /> through
            <input type="text" name="date_scanned_end" id="date_scanned_end" size="20" value="<?php echo $_REQUEST['date_scanned_end']; ?>" class="calendarSelectDate" />
          </div>
          
          <div class="fieldgroup">
            <label for="categories[]">Categories:</label>
            <div id="category_selects" style="float: left;">
            <?php 
            
            if( is_array($_REQUEST['categories']) ):                        
                foreach( $_REQUEST['categories'] as $category ):
            ?>
            
            <div style="margin-bottom: 3px;">
            <select name="categories[]">
            <?php
            echo OptionTagsAdv($categories, $category, 'category_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
            
            <?php
                endforeach;
            else: 
            ?>
            <div style="margin-bottom: 3px;">
            <select name="categories[]">
            <?php            
            echo OptionTagsAdv($categories, null, 'category_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
            <?php endif; ?>            
            </div>
        </div>
          
        </fieldset>
        
        
        <fieldset>
          <legend>Processing Options</legend>
                    
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="enable_disabled" class="cblabel inline">
            <?php echo CheckBox('enable_disabled', 'checkbox', 1, $_REQUEST['enable_disabled']); ?> Re-enable suspended accounts that no longer have exceptions</label>
          </div>
          
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_rebuild" class="cblabel inline">
            <?php echo CheckBox('process_rebuild', 'checkbox', 1, $_REQUEST['process_rebuild']); ?> Rebuild the ranking pages when the scanner is completed</label>
          </div>
          
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_emailadmin" class="cblabel inline">
            <?php echo CheckBox('process_emailadmin', 'checkbox', 1, $_REQUEST['process_emailadmin']); ?> Send an e-mail to administrators when the scanner is completed</label>
          </div>
            
        </fieldset>  
        
        <fieldset>
          <legend>Actions</legend>
          
          <div class="fieldgroup">
          <label style="width: 250px;">Connection errors:</label>
          <select name="action_connect">
          <?php
          $actions = array('0x00000000' => 'Ignore',
                           '0x00000001' => 'Display in report only',
                           '0x00000002' => 'Suspend the account',
                           '0x00000004' => 'Delete account from database',
                           '0x00000008' => 'Delete account and blacklist');
                           
          echo OptionTags($actions, $_REQUEST['action_connect']);
          ?>
          </select>
          </div>       
          
          <div class="fieldgroup">
          <label style="width: 250px;">Broken URLs:</label>
          <select name="action_broken">
          <?php
            echo OptionTags($actions, $_REQUEST['action_broken']);
          ?>
          </select>
          </div>  
          
          <div class="fieldgroup">
          <label style="width: 250px;">Forwarding URLs:</label>
          <select name="action_forward">
          <?php
            echo OptionTags($actions, $_REQUEST['action_forward']);
          ?>
          </select>
          </div>
          
          <div class="fieldgroup">
          <label style="width: 250px;">Blacklisted data:</label>
          <select name="action_blacklist">
          <?php
            echo OptionTags($actions, $_REQUEST['action_blacklist']);
          ?>
          </select>
          </div>
          
        </fieldset>
    
    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Scanner Configuration</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'tlxScannerConfigEdit' : 'tlxScannerConfigAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="config_id" value="<?php echo $_REQUEST['config_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
