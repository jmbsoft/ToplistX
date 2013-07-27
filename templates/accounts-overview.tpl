{assign var=$page_title value="Account Overview"}
{include filename="global-header.tpl"}

<div style="font-weight: bold; text-align: center;">
<a href="{$config.install_url}/accounts.php?r=edit">Edit Account</a> : 
<a href="{$config.install_url}/accounts.php?r=links">Get Links and Banners</a> : 
<a href="mailto:{$config.from_email}">E-mail Administrator</a> :
<a href="{$config.install_url}/accounts.php?r=logout">Log Out</a>
</div>

<br />

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Account Overview</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
{if $updated}
{* Display a message confirming that the account has been updated *}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="notice">
{if $config.review_edited_accounts}
Your new account information has been recorded and will be reviewed by an administrator
before it goes live on our site.  Please allow 48 hours for the new information to appear
in the ranking lists.
{else}
Your account information has been succesfully updated
{/if}
</div>
</td>
</tr>
{/if}
<tr>
<td width="100">
<b>Date Created</b>
</td>
<td>
{$account.date_added|tdatetime}
</td>
</tr>
<tr>
<td>
<b>Site Title</b>
</td>
<td>
{$account.title|htmlspecialchars}
</td>
</tr>
<tr>
<td>
<b>Site URL</b>
</td>
<td>
<a href="{$account.site_url|htmlspecialchars}" target="_blank">{$account.site_url|htmlspecialchars}</a>
</td>
</tr>
</table>
</div>

<br />

<div style="width: 70%; margin-left: auto; margin-right: auto">
<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Statistics</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
<tr>
<td width="150">
<b>In Last 24 Hours</b>
</td>
<td>
{$stats.unique_in_total|tnumber_format}
</td>
</tr>
<tr>
<td width="100">
<b>Out Last 24 Hours</b>
</td>
<td>
{$stats.unique_out_total|tnumber_format}
</td>
</tr>
<tr>
<td width="100">
<b>Clicks Last 24 Hours</b>
</td>
<td>
{$stats.clicks_total|tnumber_format}
</td>
</tr>
</table>
</div>
</div>

{include filename="global-footer.tpl"}