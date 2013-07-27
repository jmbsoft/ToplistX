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
Your account has been located and a confirmation e-mail message has been sent to {$account.email|htmlspecialchars} with
instructions on how to reset your account password.  This confirmation e-mail should arrive within a few minutes and will
be valid for 24 hours.
</td>
</tr>
</table>
</div>


{include filename="global-footer.tpl"}