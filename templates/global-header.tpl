<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <script type="text/javascript" src="{$config.install_url}/includes/jquery.js"></script>
  <script type="text/javascript" src="{$config.install_url}/includes/interface.js"></script>
  <link rel="stylesheet" type="text/css" href="{$config.install_url}/templates/style.css" />
  {$head_items}
  <title>Toplist - {$page_title|htmlspecialchars}</title>
  <script language="JavaScript" type="text/javascript">
  var new_search = false;
  function searchSubmit()
  {
      if( new_search )
      {
          $('#p').val('1');
      }
  }
  </script>
</head>
<body>

<div id="background">
<div id="content">

<table id="logo" width="100%">
<tr>
<td>
<img src="{$config.install_url}/images/logo.png" border="0" width="191" height="70" alt="Topsites" />
</td>
<td valign="bottom">
<form action="{$config.install_url}/search.php" method="post" id="search_form" onsubmit="searchSubmit()">
<input type="text" name="s" size="30" value="{$search_term|htmlspecialchars}" onchange="new_search = true;" />
{if !is_array($categories)}
{categories var=$categories}
{/if}
{if count($categories)}
<select name="c">
  <option value="0">All Categories</option>
{foreach var=$category from=$categories}
  <option value="{$category.category_id|htmlspecialchars}">{$category.name|htmlspecialchars}</option>
{/foreach}
</select>
{/if}
<input type="hidden" name="p" id="p" value="{$page|htmlspecialchars}" />
<input type="hidden" name="pp" value="{$per_page|htmlspecialchars}" />
<input type="image" src="{$config.install_url}/images/search-button.png" style="position: relative; top: 7px; margin: 0; padding: 0;" />
</form>
</td>
<td valign="bottom" align="center">
<a href="{$config.install_url}/accounts.php"><img src="{$config.install_url}/images/add-button.png" border="0" alt="Add Your Site" style="display: block; margin-bottom: 8px;" /></a>
<a href="{$config.install_url}/accounts.php?r=login"><img src="{$config.install_url}/images/login-button.png" border="0" alt="Login" /></a>
</td>
</tr>
</table>

<br />