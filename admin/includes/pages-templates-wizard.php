<?php
if( !defined('ToplistX') ) die("Access denied");

$categories =& $DB->FetchAll('SELECT * FROM `tlx_categories` ORDER BY `name`');

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#inrows').bind('change', function()
                                  {
                                      if( $(this).val() == 'yes' )
                                          $('#perrow_span').show();
                                      else
                                          $('#perrow_span').hide();
                                  });

      $('#agetype').bind('change', function()
                                  {
                                      if( $(this).val() == 'between' )
                                          $('#betweenage').show();
                                      else
                                          $('#betweenage').hide();

                                      if( $(this).val() == '' )
                                          $('#age').hide();
                                      else
                                          $('#age').show();
                                  });

      $('#perm_agetype').bind('change', function()
                                  {
                                      if( $(this).val() == 'between' )
                                          $('#perm_betweenage').show();
                                      else
                                          $('#perm_betweenage').hide();

                                      if( $(this).val() == '' )
                                          $('#perm_age').hide();
                                      else
                                          $('#perm_age').show();
                                  });

      $('#agetype').trigger('change');
      $('#inrows').trigger('change');
  });

</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/templates-wizard.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Use this interface to generate {accounts} template code for your ranking pages
    </div>

        <?php if( $GLOBALS['message'] ): ?>
        <div class="notice margin-bottom">
          <?php echo $GLOBALS['message']; ?>
        </div>
        <?php endif; ?>

        <fieldset>
          <legend>General Options</legend>

          <div class="fieldgroup">
            <label for="ranks_start">Ranks To Display:</label>
            <input type="text" name="ranks_start" id="ranks_start" value="1" size="5"> through <input type="text" name="ranks_end" id="ranks_end" value="50" size="5">
          </div>

          <div class="fieldgroup">
            <label for="display">Display As:</label>
            <select name="display" id="display">
              <option value="traditional">Traditional text link and banner</option>
              <option value="friends">Friends style page</option>
              <option value="rss_text">RSS Feed</option>
              <option value="rss_banner">RSS Feed With Banners</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="categories">Categories:</label>
            <div id="category_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="categories[]">
              <option value="MIXED">ANY CATEGORY</option>
              <?php
              echo OptionTagsAdv($categories, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="categories">Exclude Categories:</label>
            <div id="exclude_category_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="exclude_categories[]">
              <option value="">NONE</option>
              <?php
              echo OptionTagsAdv($categories, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#exclude_category_selects')" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#exclude_category_selects')" class="click-image" alt="Remove Category">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="order">Sort Accounts By:</label>

            <?php

            $fields = array('raw_in_', 'unique_in_', 'raw_out_', 'unique_out_', 'clicks_');
            $periods = array_merge(array('this_hour', 'last_hour'),
                                   array_map(create_function('$i', 'return "last_$i"."_hours";'), range(2,24)),
                                   array_map(create_function('$i', 'return "last_$i"."_days";'), array(2,3,4,5,6,7,10,14,30,60,90,120,150,180,365)));

            ?>
            <select name="order" id="order">
              <option value="average_rating">Average Rating</option>
              <option value="ratings">Number of Ratings</option>
              <option value="date_added">Date Added</option>
              <option value="date_activated">Date Activated</option>

              <?php
              foreach( $fields as $field ):
                  foreach( $periods as $period ):
              ?>
              <option value="<?php echo "$field$period"; ?>"><?php echo ucwords(str_replace('_', ' ', "$field$period")); ?></option>
              <?php
                  endforeach;
              endforeach;
              ?>
            </select>

            <select name="direction" id="direction">
              <option value="DESC">Descending</option>
              <option value="">Ascending</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="minhits">Minimum Sort Value:</label>
            <input type="text" name="minhits" id="minhits" value="0" size="5">
          </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit">Generate Template</button>
    </div>

    <input type="hidden" name="r" value="tlxPageTemplateWizard">
    </form>
</div>



</body>
</html>
