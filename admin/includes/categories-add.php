<?php
if( !defined('ToplistX') ) die("Access denied");

$defaults = array('forward_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/',
                  'title_min_length' => 10,
                  'title_max_length' => 100,
                  'desc_min_length' => 10,
                  'desc_max_length' => 500,
                  'banner_max_width' => 468,
                  'banner_max_height' => 60,
                  'banner_max_bytes' => 20480);
                  
if( !$editing && $_SERVER['REQUEST_METHOD'] == 'GET' )
{
    $_REQUEST = array_merge($defaults, $_REQUEST);
}

include_once('includes/header.php');
?>



<script language="JavaScript">
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>

$(function() 
  {
      $('#apply_all').bind('click', function() { if( this.checked ) { $('#apply_matched').attr({checked: false}); } } );
      $('#apply_matched').bind('click', function() { if( this.checked ) { $('#apply_all').attr({checked: false}); } } );
      
      $('#form').bind('submit', function() 
                                {
                                    if( $('#apply_matched').attr('checked') )
                                    {
                                        $('#apply_matched').val(window.parent.$('#search').formSerialize());
                                    }
                                });
  });
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/categories.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this category by making changes to the information below
      <?php else: ?>
      Add a new category by filling out the information below
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
                <?php if( $editing ): ?>
                <label for="name">Category Name:</label>
                <input type="text" name="name" id="name" size="60" value="<?php echo $_REQUEST['name']; ?>" />
                <?php else: ?>
                <label for="name">Category Name(s):</label>
                <textarea name="name" id="name" rows="3" cols="80" wrap="off"><?php echo $_REQUEST['name']; ?></textarea>
                <?php endif; ?>
            </div>
            
            <div class="fieldgroup">
                <label for="forward_url">Forward URL:</label>
                <input type="text" name="forward_url" id="forward_url" size="70" value="<?php echo $_REQUEST['forward_url']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="page_url">Page URL:</label>
                <input type="text" name="page_url" id="page_url" size="70" value="<?php echo $_REQUEST['page_url']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <label for="hidden" class="cblabel inline"><?php echo CheckBox('hidden', 'checkbox', 1, $_REQUEST['hidden']); ?> Make this category hidden</label>
            </div>
        </fieldset>
        
        <fieldset>
          <legend>Account Settings</legend>
          
            <div class="fieldgroup">
                <label for="title_min_length">Site Title Length:</label>
                <input type="text" name="title_min_length" id="title_min_length" size="5" value="<?php echo $_REQUEST['title_min_length']; ?>" />
                to
                <input type="text" name="title_max_length" id="title_max_length" size="5" value="<?php echo $_REQUEST['title_max_length']; ?>" />
                characters
            </div>
          
            <div class="fieldgroup">
                <label for="desc_min_length">Description Length:</label>
                <input type="text" name="desc_min_length" id="desc_min_length" size="5" value="<?php echo $_REQUEST['desc_min_length']; ?>" />
                to
                <input type="text" name="desc_max_length" id="desc_max_length" size="5" value="<?php echo $_REQUEST['desc_max_length']; ?>" />
                characters
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <label for="allow_redirect" class="cblabel inline">
                <?php echo CheckBox('allow_redirect', 'checkbox', 1, $_REQUEST['allow_redirect']); ?> Allow redirecting site URLs to be submitted (300 level HTTP status codes)
                </label>
            </div>
        </fieldset>
        
        <fieldset>
          <legend>Account Banner Settings</legend>
                        
            <div class="fieldgroup">
                <label for="banner_max_width">Max Banner Width:</label>
                <input type="text" name="banner_max_width" id="banner_max_width" size="5" value="<?php echo $_REQUEST['banner_max_width']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="banner_max_height">Max Banner Height:</label>
                <input type="text" name="banner_max_height" id="banner_max_height" size="5" value="<?php echo $_REQUEST['banner_max_height']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="banner_max_bytes">Max Banner Filesize:</label>
                <input type="text" name="banner_max_bytes" id="banner_max_bytes" size="10" value="<?php echo $_REQUEST['banner_max_bytes']; ?>" />
            </div>           
            
            <div class="fieldgroup">
                <label></label>
                <label for="banner_force_size" class="cblabel inline">
                <?php echo CheckBox('banner_force_size', 'checkbox', 1, $_REQUEST['banner_force_size']); ?> Force all banners to the height and width entered above
                </label>
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <label for="download_banners" class="cblabel inline">
                <?php echo CheckBox('download_banners', 'checkbox', 1, $_REQUEST['download_banners']); ?> Download banners to check height, width, and filesize
                </label>
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <label for="host_banners" class="cblabel inline">
                <?php echo CheckBox('host_banners', 'checkbox', 1, $_REQUEST['host_banners']); ?> Host member account banners from your server
                </label>
            </div>
        </fieldset>
        
        
        <?php if( $editing ): ?>
        <fieldset>
          <legend>Bulk Edit Options</legend>          
            <div class="fieldgroup">
              <label></label>
              <input type="checkbox" class="checkbox" name="apply_all" id="apply_all" value="1"> 
              <label for="apply_all" class="cblabel inline">Apply these settings to all categories</label><br />
            </div>
            
            <div class="fieldgroup">
              <label></label>
              <input type="checkbox" class="checkbox" name="apply_matched" id="apply_matched" value="1">
              <label for="apply_matched" class="cblabel inline">Apply these settings to matched categories</label><br />
            </div>
        </fieldset>
        <?php endif; ?>
    
    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Category</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'tlxCategoryEdit' : 'tlxCategoryAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <input type="hidden" name="category_id" value="<?php echo $_REQUEST['category_id']; ?>">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
