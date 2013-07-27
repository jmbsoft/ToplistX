<?php
if( !defined('ToplistX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<style>
div.fieldgroup {
  margin: 2px 0px 0px 0px;
  padding: 1px 3px;
}

.lesspad {
  margin: 0;
  width: 90px;
}
</style>

<script language="JavaScript">
$(function() { Search.search(true); });

function mailSelected()
{
    var selected = getSelected();

    if( selected.length < 1 )
    {
        alert('Please select at least one account to e-mail');
        return false;
    }

    return {href: 'index.php?&r=tlxShAccountMail&' + selected.serialize()};
}

function deleteSelected(id)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one account to delete');
        return false;
    }

    if( !<?php echo intval($C['confirm_account_delete']); ?> || confirm('Are you sure you want to delete ' + (selected.length > 1 ? 'the selected accounts?' : 'this account?')) )
    {
        $.ajax({type: 'POST',
                url: 'ajax.php',
                data: 'r=tlxAccountDelete&' + selected.serialize()});

        for( var i = 0; i < selected.length; i++ )
        {
            $('#_end_').html(decrementValue($('#_end_').html()));
            $('#_total_').html(decrementValue($('#_total_').html()));
            $('#'+selected[i].value).remove();
        }
    }

    return false;
}

function processEditSelected(id, what)
{
    if( !<?php echo intval($C['confirm_edit_process']); ?> || confirm('Are you sure you want to ' + what + ' this account edit?') )
    {
        $.ajax({type: 'POST',
                url: 'ajax.php',
                data: 'r=tlxAccountEditProcess&w='+what+'&username='+id});


        $('#'+id+ " .edited_span").remove();
        $('#edited_b').html(decrementValue($('#edited_b').html()));
    }

    return false;
}

function processSelected(id, what)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one account to ' + what);
        return false;
    }

    if( !<?php echo intval($C['confirm_approve_process']); ?> || confirm('Are you sure you want to ' + what + ' ' + (selected.length > 1 ? 'the selected accounts?' : 'this account?')) )
    {
        $.ajax({type: 'POST',
                url: 'ajax.php',
                data: 'r=tlxAccountProcess&w='+what+'&'+selected.serialize()+'&'+$('.reject').serialize()});

        for( var i = 0; i < selected.length; i++ )
        {
            if( what == 'reject' )
            {
                $('#_end_').html(decrementValue($('#_end_').html()));
                $('#_total_').html(decrementValue($('#_total_').html()));
                $('#'+selected[i].value).remove();
            }
            else
            {
                $('#'+selected[i].value+ " .reject_span").remove();
                $('#'+selected[i].value).removeClass().addClass('approved');
            }

            $('#new_b').html(decrementValue($('#new_b').html()));
        }
    }

    return false;
}

function doToSelected(id, what)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one account to ' + what);
        return false;
    }

    if( !<?php echo intval($C['confirm_account_lock']); ?> || confirm('Are you sure you want to ' + what + ' ' + (selected.length > 1 ? 'the selected accounts?' : 'this account?')) )
    {
        infoBarAjax({data: 'r=tlxAccountAction&w='+what+'&'+selected.serialize()});
    }

    return false;
}

function showBanner(icon, url, size)
{
    var pos = $.iUtil.getPositionLite(icon);
    var dims = size.split('x');
    var shift = null;

    if( dims[0] != '' )
    {
        pos.x -= parseInt(dims[0]);
    }
    else
    {
        size = 'Unknown';
        shift = pos.x;
        pos.x -= 468;
    }

    $('#div-float').remove();
    $('body').append('<div style="position: absolute; top: '+pos.y+'px; left: '+pos.x+'px;" id="div-float" class="div-float" onclick="$(\'#div-float\').remove()">' +
                     '<img id="img-float" src="'+url+'" border="0"'+(size == 'Unknown' ? ' onload="bannerLoaded(this, \''+shift+'\')"' : '')+'><br /><span id="img-size-float">' + size +
                     '</span></div>');
}

function bannerLoaded(img, xpos)
{
    xpos = parseInt(xpos) - parseInt(img.width);

    $('#img-size-float').html(img.width + 'x' + img.height);
    $('#div-float').css({left: xpos+'px'});
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=tlxShAccountTasks" class="window {title: 'Quick Tasks'}">
        <img src="images/tasks.png" border="0" alt="Quick Tasks" title="Quick Tasks"></a>
        &nbsp;
        <a href="index.php?r=tlxShAccountAdd" class="window {title: 'Add Account'}">
        <img src="images/add.png" border="0" alt="Add Account" title="Add Account"></a>
        &nbsp;
        <a href="docs/accounts.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Member Accounts
    </div>



    <form action="ajax.php" name="search" id="search" method="POST">

    <table align="center" cellpadding="3" cellspacing="0" class="margin-top" border="0">
      <tr>
      <td align="right">
      <b>Status:</b>
      </td>
      <td colspan="2">
      <input type="checkbox" class="checkbox" name="status[]" value="unconfirmed" id="s_unconfirmed"> <label for="s_unconfirmed" class="plain-label lite">Unconfirmed</label>
      <input type="checkbox" class="checkbox" name="status[]" value="pending" id="s_pending" style="margin-left: 12px;"<?php if( $_REQUEST['new'] ) echo ' checked="checked"'; ?>> <label for="s_pending" class="plain-label lite">Pending</label>
      <input type="checkbox" class="checkbox" name="status[]" value="active" id="s_approved" style="margin-left: 12px;"> <label for="s_approved" class="plain-label lite">Active</label>
      </td>
      </tr>
      <tr>
      <td align="right">
      &nbsp;
      </td>
      <td colspan="2">
      <input type="checkbox" class="checkbox" name="locked" value="1" id="c_locked"> <label for="c_locked" class="plain-label lite">Only locked accounts</label>
      <input type="checkbox" class="checkbox" name="disabled" value="1" id="c_disabled" style="margin-left: 41px;"> <label for="c_disabled" class="plain-label lite">Only disabled accounts</label>
      <input type="checkbox" class="checkbox" name="edited" value="1" id="c_edited" style="margin-left: 41px;"<?php if( $_REQUEST['edited'] ) echo ' checked="checked"'; ?>> <label for="c_edited" class="plain-label lite">Only edited accounts</label>
      </td>
      </tr>
      <?php
      $categories =& $DB->FetchAll('SELECT * FROM `tlx_categories` ORDER BY `name`');

      if( count($categories) ):
      ?>
      <tr>
      <td align="right" valign="top">
      <div style="padding-top: 3px; font-weight: bold;">Categories:</div>
      </td>
      <td colspan="2">

        <div style="float: right;"><input type="checkbox" class="checkbox" name="cat_exclude" id="cat_exclude" value="1"> <label for="cat_exclude" class="plain-label lite">Exclude</label></div>
        <div id="category_selects">
            <div>
            <select name="categories[]">
              <option value="">ALL CATEGORIES</option>
            <?php
            echo OptionTagsAdv($categories, $_REQUEST['category_id'], 'category_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
        </div>
      </td>
      </tr>
      <?php endif; ?>
            <tr>
      <td align="right">
      <b>Search:</b>
      </td>
      <td colspan="2">
      <select name="field">
        <option value="tlx_accounts.username">Username</option>
        <option value="email">E-mail Address</option>
        <option value="site_url">Site URL</option>
        <option value="banner_url">Banner URL</option>
        <option value="banner_height">Banner Height</option>
        <option value="banner_width">Banner Width</option>
        <option value="title,description,keywords">Title, Description &amp; Keywords</option>
        <option value="title">Title</option>
        <option value="description">Description</option>
        <option value="keywords">Keywords</option>
        <option value="date_added">Date Added</option>
        <option value="return_percent">Return Percent</option>
        <option value="avg_rating">Average Rating</option>
        <option value="ratings">Total Ratings</option>
        <option value="inactive">Inactive</option>
        <option value="admin_comments">Admin Comments</option>
        <?php
        $fields =& $DB->FetchAll('SELECT * FROM `tlx_account_field_defs`');
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>
      </select>
      <select name="search_type">
        <option value="matches">Matches</option>
        <option value="contains">Contains</option>
        <option value="starts">Starts With</option>
        <option value="less">Less Than</option>
        <option value="greater">Greater Than</option>
        <option value="between">Between</option>
        <option value="empty">Empty</option>
      </select>
      <input type="text" name="search" size="60" value="" onkeypress="return Search.onenter(event)" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Sort:</b>
      </td>
      <td>
      <select name="order" id="order">
        <option value="username">Username</option>
        <option value="email">E-mail Address</option>
        <option value="site_url">Site URL</option>
        <option value="banner_url">Banner URL</option>
        <option value="banner_height">Banner Height</option>
        <option value="banner_width">Banner Width</option>
        <option value="title">Title</option>
        <option value="description">Description</option>
        <option value="date_added">Date Added</option>
        <option value="date_activated">Date Activated</option>
        <option value="return_percent">Return Percent</option>
        <option value="avg_rating">Average Rating</option>
        <option value="ratings">Total Ratings</option>
        <option value="inactive">Inactive</option>
        <option value="admin_comments">Admin Comments</option>
        <?php
        $fields =& $DB->FetchAll('SELECT * FROM `tlx_account_field_defs`');
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>
        <optgroup label="Last 24 Hours">
        <option value="raw_in_total">Raw In</option>
        <option value="unique_in_total" selected="selected">Unique In</option>
        <option value="raw_out_total">Raw Out</option>
        <option value="unique_out_total">Unique Out</option>
        <option value="clicks_total">Clicks</option>
        </optgroup>
        <optgroup label="Last Hour">
        <option value="raw_in_last_hr">Raw In</option>
        <option value="unique_in_last_hr">Unique In</option>
        <option value="raw_out_last_hr">Raw Out</option>
        <option value="unique_out_last_hr">Unique Out</option>
        <option value="clicks_last_hr">Clicks</option>
        </optgroup>
        <optgroup label="This Hour">
        <option value="raw_in_this_hr">Raw In</option>
        <option value="unique_in_this_hr">Unique In</option>
        <option value="raw_out_this_hr">Raw Out</option>
        <option value="unique_out_this_hr">Unique Out</option>
        <option value="clicks_this_hr">Clicks</option>
        </optgroup>
        <optgroup label="Yesterday">
        <option value="raw_in_days_1">Raw In</option>
        <option value="unique_in_days_1">Unique In</option>
        <option value="raw_out_days_1">Raw Out</option>
        <option value="unique_out_days_1">Unique Out</option>
        <option value="clicks_days_1">Clicks</option>
        </optgroup>
        <optgroup label="Last 7 Days">
        <option value="raw_in_days_7">Raw In</option>
        <option value="unique_in_days_7">Unique In</option>
        <option value="raw_out_days_7">Raw Out</option>
        <option value="unique_out_days_7">Unique Out</option>
        <option value="clicks_days_7">Clicks</option>
        </optgroup>
        <optgroup label="Last 30 Days">
        <option value="raw_in_days_30">Raw In</option>
        <option value="unique_in_days_30">Unique In</option>
        <option value="raw_out_days_30">Raw Out</option>
        <option value="unique_out_days_30">Unique Out</option>
        <option value="clicks_days_30">Clicks</option>
        </optgroup>
        <optgroup label="Last 60 Days">
        <option value="raw_in_days_60">Raw In</option>
        <option value="unique_in_days_60">Unique In</option>
        <option value="raw_out_days_60">Raw Out</option>
        <option value="unique_out_days_60">Unique Out</option>
        <option value="clicks_days_60">Clicks</option>
        </optgroup>
        <optgroup label="Last 90 Days">
        <option value="raw_in_days_90">Raw In</option>
        <option value="unique_in_days_90">Unique In</option>
        <option value="raw_out_days_90">Raw Out</option>
        <option value="unique_out_days_90">Unique Out</option>
        <option value="clicks_days_90">Clicks</option>
        </optgroup>
        <optgroup label="Last 120 Days">
        <option value="raw_in_days_120">Raw In</option>
        <option value="unique_in_days_120">Unique In</option>
        <option value="raw_out_days_120">Raw Out</option>
        <option value="unique_out_days_120">Unique Out</option>
        <option value="clicks_days_120">Clicks</option>
        </optgroup>
        <optgroup label="Last 365 Days">
        <option value="raw_in_days_365">Raw In</option>
        <option value="unique_in_days_365">Unique In</option>
        <option value="raw_out_days_365">Raw Out</option>
        <option value="unique_out_days_365">Unique Out</option>
        <option value="clicks_days_365">Clicks</option>
        </optgroup>
      </select>
      <select name="direction" id="direction">
        <option value="DESC">Descending</option>
        <option value="ASC">Ascending</option>
      </select>

      <b style="padding-left: 30px;">Per Page:</b>
      <input type="text" name="per_page" id="per_page" value="20" size="3">
      </td>
      <td align="right">
      <button type="button" onclick="Search.search(true)">Search</button>
      </td>
      </tr>
    </table>

    <input type="hidden" name="r" value="tlxAccountSearch">
    <input type="hidden" name="page" id="page" value="1">
    </form>

    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Accounts <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
      <div id="_pagelinks_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">
      </div>
      <div class="clear"></div>
    </div>

    <form id="results">

    <table class="tall-list" cellspacing="0">
      <thead>
        <tr>
          <td style="width: 15px;">
            <input type="checkbox" id="_autocb_" class="checkbox">
          </td>
          <td>
            Account Data
          </td>
          <td class="last" style="width: 180px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="7" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="7" class="last warn">
            No accounts matched your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="7" class="last alert">
          </td>
        </tr>
      <tbody id="_tbody_">
      </tbody>
    </table>

    </form>

    <div style="padding: 0px 2px 0px 2px;">
      <div id="_pagelinks_btm_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">
      </div>
      <div class="clear"></div>
    </div>

    <br />

    <div class="centered">
      <button type="button" onclick="deleteSelected()">Delete</button>
      &nbsp;
      <button type="button" class="window {title: 'E-mail Accounts', callback: mailSelected}">E-mail</button>
      &nbsp;
      <button type="button" onclick="processSelected(null, 'approve')">Approve</button>
      &nbsp;
      <button type="button" onclick="processSelected(null, 'reject')">Reject</button>
      &nbsp;
    </div>

    <br />

    <table align="center" border="0" cellspacing="3">
      <tr>
        <td align="center" class="unconfirmed" width="75" style="border: 1px solid #AAA">
        Unconfirmed
        </td>
        <td align="center" class="pending" width="75" style="border: 1px solid #AAA">
        Pending
        </td>
        <td align="center" class="approved" width="75" style="border: 1px solid #AAA">
        Active
        </td>
      </tr>
    </table>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
