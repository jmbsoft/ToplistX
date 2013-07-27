{assign var=$page_title value="Account Password Reset"}
{include filename="global-header.tpl"}

<br />
<br />

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Account Password Reset</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
<tr>
<td>
{if $error}
<div class="error">
{$error|htmlspecialchars}
</div>
{else}
Confirmation has been completed and your account login information has been e-mailed to {$account.email|htmlspecialchars}
{/if}
</td>
</tr>
</table>
</div>

{include filename="global-footer.tpl"}