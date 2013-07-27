<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <link rel="stylesheet" type="text/css" href="{$config.install_url}/templates/style.css" />
  <script type="text/javascript" src="{$config.install_url}/includes/jquery.js"></script>
  <title>Top Sites List</title>
  <script language="JavaScript" type="text/javascript">
  var install_url = '{$config.install_url}';
  {literal}
  $(function()
  {
      $('.rating').bind('click', function() { window.open(install_url+'/rate.php?id='+this.id, '_blank', 'menubar=no,height=600,width=620,scrollbars=yes,top=200,left=200'); });
      $('.comments').bind('click', function() { window.open(install_url+'/comments.php?id='+this.id, '_blank', 'menubar=no,height=600,width=620,scrollbars=yes,top=200,left=200');});
  });
  {/literal}
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
<form action="{$config.install_url}/search.php" method="post">
<input type="text" name="s" size="30" value="" />
{categories var=$categories}
{if count($categories)}
<select name="c">
  <option value="0">All Categories</option>
{foreach var=$category from=$categories}
  <option value="{$category.category_id|htmlspecialchars}">{$category.name|htmlspecialchars}</option>
{/foreach}
</select>
{/if}
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

{featured var=$featured ranks=1-50 category=MIXED}
{if $featured}
<div style="width: 500px; margin-left: auto; margin-right: auto">
<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
Featured Site
</td>
<td class="table-header-r">
</td>
</tr>
</table>

<div class="table-border">
<table cellspacing="1" cellpadding="0" width="100%">
<tr>
<td class="row-color-a" style="text-align: center; padding: 6px;">
{if $featured.banner_url}
<a href="{$config.install_url}/out.php?id={$featured.username|urlencode}" target="_blank">
<img src="{$featured.banner_url|htmlspecialchars}" border="0" alt="{$featured.title|htmlspecialchars}" class="banner" />
</a><br />
{/if}
<a href="{$config.install_url}/out.php?id={$featured.username|urlencode}" target="_blank"><b>{$featured.title|htmlspecialchars}</b></a> 
{if $featured.timestamp_activated > TIME_NOW - 259200}<img src="{$config.install_url}/images/new.png" alt="New" />{/if}
{if $featured.icons}{foreach var=$icon from=$featured.icons}{$icon}&nbsp;{/foreach}{/if}
<br />
{$featured.description|htmlspecialchars}
</td>
</tr>
</table>
</div>
</div>

<br />
{/if}

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td style="width: 5em;">
<span style="position: relative; left: -1px;">Rank</span>
</td>
<td>
Site Information
</td>
<td style="width: 5em;">
In
</td>
<td style="width: 5em;">
Out
</td>
<td class="table-header-r">
</td>
</tr>
</table>

{accounts
var=$accounts
ranks=1-5
storeranks=true
category=MIXED
minhits=0
stats=unique_out_last_24_hours
order=unique_in_last_24_hours DESC}

<div class="table-border">
<table cellspacing="1" cellpadding="0" width="100%">
{foreach var=$account from=$accounts}
{cycle values=row-color-a,row-color-b var=$background}
<tr>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.rank|htmlspecialchars}.
</td>
<td class="{$background|htmlspecialchars}" style="padding: 6px;">
<div style="float: right">
{if $account.comments}
<img src="{$config.install_url}/images/comments.png" alt="Comments" border="0" class="click comments" style="padding-right: 10px; position: relative; top: 2px;" id="{$account.username|htmlspecialchars}"/>
{/if}
{if $account.ratings}
<img src="{$config.install_url}/images/{$account.average_rating|tnearest_half}.gif" alt="Rating: {$account.average_rating|tnearest_half}" border="0" class="click rating" id="{$account.username|htmlspecialchars}" />
{else}
<img src="{$config.install_url}/images/0.gif" alt="Not Yet Rated" border="0" class="click rating" id="{$account.username|htmlspecialchars}" />
{/if}
</div>
{if $account.banner_url}
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank">
<img src="{$account.banner_url|htmlspecialchars}" border="0" alt="{$account.title|htmlspecialchars}" class="banner" />
</a><br />
{/if}
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank"><b>{$account.title|htmlspecialchars}</b></a> 
{if $account.timestamp_activated > TIME_NOW - 259200}<img src="{$config.install_url}/images/new.png" alt="New" />{/if}
{if $account.icons}{foreach var=$icon from=$account.icons}{$icon}&nbsp;{/foreach}{/if}
<br />
{$account.description|htmlspecialchars}
</td>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.sorter|tnumber_format}
</td>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.unique_out_last_24_hours|tnumber_format}
</td>
</tr>
{/foreach}

{if $fillranks}
{range start=$fillranks.start end=$fillranks.end counter=$rank}
{cycle values=row-color-a,row-color-b var=$background}
<tr>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$rank|htmlspecialchars}.
</td>
<td class="{$background|htmlspecialchars}" style="padding: 6px;  text-align: center;" colspan="3">
<a href="{$config.install_url}/accounts.php">Add Your Site</a>
</td>
</tr>
{/range}
{/if}

</table>
</div>

<div style="margin-top: 15px; margin-bottom: 15px; text-align: center; font: bold  1.25em verdana">
ADVERTISEMENT HERE!!
</div>

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td style="width: 5em;">
<span style="position: relative; left: -1px;">Rank</span>
</td>
<td>
Site Information
</td>
<td style="width: 5em;">
In
</td>
<td style="width: 5em;">
Out
</td>
<td class="table-header-r">
</td>
</tr>
</table>

{accounts
var=$accounts
ranks=6-50
storeranks=true
category=MIXED
minhits=0
stats=unique_out_last_24_hours
order=unique_in_last_24_hours DESC}

<div class="table-border">
<table cellspacing="1" cellpadding="0" width="100%">
{foreach var=$account from=$accounts}
{cycle values=row-color-a,row-color-b var=$background}
<tr>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.rank|htmlspecialchars}.
</td>
<td class="{$background|htmlspecialchars}" style="padding: 6px;">
<div style="float: right">
{if $account.comments}
<img src="{$config.install_url}/images/comments.png" alt="Comments" border="0" class="click comments" style="padding-right: 10px; position: relative; top: 2px;" id="{$account.username|htmlspecialchars}"/>
{/if}
{if $account.ratings}
<img src="{$config.install_url}/images/{$account.average_rating|tnearest_half}.gif" alt="Rating: {$account.average_rating|tnearest_half}" border="0" class="click rating" id="{$account.username|htmlspecialchars}" />
{else}
<img src="{$config.install_url}/images/0.gif" alt="Not Yet Rated" border="0" class="click rating" id="{$account.username|htmlspecialchars}" />
{/if}
</div>
{if $account.banner_url}
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank">
<img src="{$account.banner_url|htmlspecialchars}" border="0" alt="{$account.title|htmlspecialchars}" class="banner" />
</a><br />
{/if}
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank"><b>{$account.title|htmlspecialchars}</b></a> 
{if $account.timestamp_activated > TIME_NOW - 259200}<img src="{$config.install_url}/images/new.png" alt="New" />{/if}
{if $account.icons}{foreach var=$icon from=$account.icons}{$icon}&nbsp;{/foreach}{/if}
<br />
{$account.description|htmlspecialchars}
</td>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.sorter|tnumber_format}
</td>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.unique_out_last_24_hours|tnumber_format}
</td>
</tr>
{/foreach}

{if $fillranks}
{range start=$fillranks.start end=$fillranks.end counter=$rank}
{cycle values=row-color-a,row-color-b var=$background}
<tr>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$rank|htmlspecialchars}.
</td>
<td class="{$background|htmlspecialchars}" style="padding: 6px;  text-align: center;" colspan="3">
<a href="{$config.install_url}/accounts.php">Add Your Site</a>
</td>
</tr>
{/range}
{/if}
</table>
</div>

<div style="text-align: center; margin-top: 20px">
{$total_accounts|tnumber_format} Site{if $total_accounts != 1}s{/if} In Our Database<br />
Last Update: {date value=now format='m-d-Y h:ia'}

<br />
<br />

<a href="{$config.install_url}/accounts.php">Create Account</a> | <a href="{$config.install_url}/accounts.php?r=login">Maintain Account</a>

<br />
<br />

%%Powered_By%%
</div>

</div>
</div>

</body>
</html>