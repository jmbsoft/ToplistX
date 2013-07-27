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
        alert('Please select at least one category to delete');
        return false;
    }
    
    var plural = (selected.length > 1 ? 'the selected categories' : 'this category');
    var confirm_msg = 'Are you sure you want to delete ' + plural + "?\r\n" +
                      'All accounts that are in ' + plural + ' will also be deleted!';
    
    if( confirm(confirm_msg) )
    {
        infoBarAjax({data: 'r=tlxCategoryDelete&' + selected.serialize()});
    }
    
    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=tlxShCategoryAdd" class="window {title: 'Add Category'}">
        <img src="images/add.png" border="0" alt="Add Category" title="Add Category"></a>
        &nbsp;
        <a href="docs/categories.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Manage Categories
    </div>
    
    
    
    <form action="ajax.php" name="search" id="search" method="POST">
    
    <table align="center" cellpadding="3" cellspacing="0" class="margin-top" border="0">
      <tr>
      <td align="right">  
      <b>Search:</b>
      </td>
      <td colspan="2">     
      <select name="field">       
        <option value="name">Category Name</option>
        <option value="hidden">Hidden</option>
        <option value="forward_url">Forward URL</option>
        <option value="banner_max_width">Max Banner Width</option>
        <option value="banner_max_height">Max Banner Height</option>
        <option value="banner_max_bytes">Max Banner Filesize</option>
        <option value="title_min_length">Min Title Length</option>
        <option value="title_max_length">Max Title Length</option>
        <option value="desc_min_length">Min Description Length</option>
        <option value="desc_max_length">Max Description Length</option>
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
      <input type="text" name="search" value="" onkeypress="return Search.onenter(event)" size="40" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Sort:</b>
      </td>
      <td> 
      <select name="order" id="order">
        <option value="name">Category Name</option>
        <option value="hidden">Hidden</option>
        <option value="forward_url">Forward URL</option>
        <option value="banner_max_width">Max Banner Width</option>
        <option value="banner_max_height">Max Banner Height</option>
        <option value="banner_max_bytes">Max Banner Filesize</option>
        <option value="title_min_length">Min Title Length</option>
        <option value="title_max_length">Max Title Length</option>
        <option value="desc_min_length">Min Description Length</option>
        <option value="desc_max_length">Max Description Length</option>
      </select>      
      <select name="direction" id="direction">
        <option value="ASC">Ascending</option>
        <option value="DESC">Descending</option>
      </select>
      
      <b style="padding-left: 30px;">Per Page:</b>
      <input type="text" name="per_page" id="per_page" value="20" size="3">
      </td>
      <td align="right">
      <button type="button" onclick="Search.search(true)">Search</button>
      </td>
      </tr>
    </table>
    
    <input type="hidden" name="r" value="tlxCategorySearch">
    
    <input type="hidden" name="page" id="page" value="1">
    </form>
    
    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Categories <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
      <div id="_pagelinks_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
    
    <form id="results">
    
    <table class="list" cellspacing="0">
      <thead>
        <tr>
          <td style="width: 15px;">
            <input type="checkbox" id="_autocb_" class="checkbox">
          </td>
          <td>
            Name
          </td>
          <td style="width: 60px; text-align: center;">
            Hidden
          </td>
          <td style="width: 60px;">
            Accounts
          </td>
          <td style="width: 120px;">
            Sorter
          </td>
          <td class="last" style="width: 80px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="9" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="9" class="last warn">
            No categories matched your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="9" class="last alert">            
          </td>
        </tr>
      <tbody id="_tbody_">        
      </tbody>
    </table>
    
    </form>
    
    <hr>
    
    <div style="padding: 0px 2px 0px 2px;">
      <div id="_pagelinks_btm_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
       
    <div class="centered">
      <button type="button" onclick="deleteSelected()">Delete</button>
    </div>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
