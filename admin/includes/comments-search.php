<?php
if( !defined('ToplistX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function() { Search.search(true); });

function deleteSelected(id)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one comment to delete');
        return false;
    }

    if( confirm('Are you sure you want to delete ' + (selected.length > 1 ? 'the selected comments?' : 'this comment?')) )
    {
        infoBarAjax({data: 'r=tlxCommentDelete&' + selected.serialize()});
    }

    return false;
}

function approveSelected(id)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one comment to approve');
        return false;
    }

    if( confirm('Are you sure you want to approve ' + (selected.length > 1 ? 'the selected comments?' : 'this comment?')) )
    {
        infoBarAjax({data: 'r=tlxCommentApprove&' + selected.serialize()});
    }

    return false;
}

function rejectSelected(id)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one comment to reject');
        return false;
    }

    if( confirm('Are you sure you want to reject ' + (selected.length > 1 ? 'the selected comments?' : 'this comment?')) )
    {
        infoBarAjax({data: 'r=tlxCommentReject&' + selected.serialize()});
    }

    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/comments.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Account Comments
    </div>



    <form action="ajax.php" name="search" id="search" method="POST">

    <table align="center" cellpadding="3" cellspacing="0" class="margin-top" border="0">
      <tr>
      <td align="right">
      <b>Status:</b>
      </td>
      <td colspan="2">
      <input type="checkbox" class="checkbox" name="status[]" value="pending" id="s_pending"<?php if( $_REQUEST['pending'] ) echo ' checked="checked"'; ?>> <label for="s_pending" class="plain-label lite">Pending</label>
      <input type="checkbox" class="checkbox" name="status[]" value="approved" id="s_approved" style="margin-left: 12px;"> <label for="s_approved" class="plain-label lite">Approved</label>
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Search:</b>
      </td>
      <td colspan="2">
      <select name="field">
        <option value="comment">Comment</option>
        <option value="username"<?php if( $_REQUEST['username'] ) echo ' selected="selected"'; ?>>Username</option>
        <option value="date_submitted">Date Submitted</option>
        <option value="ip_address">IP Address</option>
        <option value="email">E-mail Address</option>
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
      <input type="text" name="search" size="40" onkeypress="return Search.onenter(event)" value="<?php echo htmlspecialchars($_REQUEST['username']); ?>" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Sort:</b>
      </td>
      <td>
      <select name="order" id="order">
        <option value="date_submitted">Date Submitted</option>
        <option value="username">Username</option>
        <option value="ip_address">IP Address</option>
        <option value="email">E-mail Address</option>
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

    <input type="hidden" name="config_id" value="<?php echo htmlspecialchars($_REQUEST['config_id']); ?>">
    <input type="hidden" name="r" value="tlxCommentsSearch">
    <input type="hidden" name="page" id="page" value="1">
    </form>

    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Comments <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
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
            Comment Data
          </td>
          <td style="width: 110px;">
            Date Submitted
          </td>
          <td class="last" style="width: 100px; text-align: right">
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
            No comments matched your search or no search term entered
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
      <button type="button" onclick="approveSelected()">Approve</button>
      &nbsp;
      <button type="button" onclick="rejectSelected()">Reject</button>
    </div>

    <br />

    <table align="center" border="0" cellspacing="3">
      <tr>
        <td align="center" class="pending" width="75" style="border: 1px solid #AAA">
        Pending
        </td>
        <td align="center" class="approved" width="75" style="border: 1px solid #AAA">
        Approved
        </td>
      </tr>
    </table>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>