<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <script type="text/javascript" src="{$config.install_url}/includes/jquery.js"></script>
  <script type="text/javascript" src="{$config.install_url}/includes/interface.js"></script>
  <link rel="stylesheet" type="text/css" href="{$config.install_url}/templates/style.css" />
  <title>Toplist - Site Comments</title>
  <style type="text/css">
  html {
    height: auto;
  }
  
  body {
    background-color: #efefe6;
    margin-top: 10px;
  }
  </style>
</head>
<body>

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Site Comments {$pagination.start|htmlspecialchars} - {$pagination.end|htmlspecialchars} of {$pagination.total|htmlspecialchars}</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>


<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%">

{foreach var=$comment from=$comments counter=$counter}
<tr>
<td class="{cycle values=row-color-a,row-color-b}">
<span class="small">Posted by <b>{$comment.name|htmlspecialchars}</b> @ <b>{$comment.date|tdatetime}</b></span>
<div style="padding-top: 10px; padding-left: 20px;">{$comment.comment|htmlspecialchars|nl2br}</div>
</td>
</tr>
{/foreach}

</table>
</div>

<br />

<table align="center" width="100%" cellpadding="5" cellspacing="0">
<tr>
<td>
{if $pagination.prev}
<a href="{$config.install_url}/comments.php?id={$account.username|urlencode}&p={$pagination.prev_page|urlencode}" class="link" style="text-decoration: none;">
<img src="{$config.install_url}/images/prev.png" border="0" alt="" style="position: relative; top: 5px;"> <b>Previous</b></a>
&nbsp;
{/if}
</td>
<td align="right">
{if $pagination.next}
&nbsp;
<a href="{$config.install_url}/comments.php?id={$account.username|urlencode}&p={$pagination.next_page|urlencode}" class="link" style="text-decoration: none;">
<b>Next</b> <img src="{$config.install_url}/images/next.png" border="0" alt="" style="position: relative; top: 5px;"></a>
{/if}
</td>
</tr>
</table>

<br />
<br />

</body>
</html>