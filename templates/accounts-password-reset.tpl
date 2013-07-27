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


<form method="POST" action="{$config.install_url}/accounts.php">

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
<tr>
<td colspan="2">
Enter your e-mail address below to locate your account.  An e-mail message will be sent to this address with a link you will need to visit
in order to reset your account password.
</td>
</tr>

{* Display any errors encountered during the submission process *}
{if $errors}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="error">
Please fix the following errors:<br />
<ol style="margin: 2px; padding-left: 23px; margin-top: 5px;">
{foreach var=$error from=$errors}
<li> {$error|htmlspecialchars}<br />
{/foreach}
</ol>
</div>
</td>
</tr>
{/if}

<tr>
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
<input type="text" size="50" name="email" value="{$account.email|htmlspecialchars}" />
</td>
</tr>
<tr>
<td align="center" colspan="2">
<button type="submit">Submit</button>
</td>
</tr>
</table>
</div>

<input type="hidden" name="r" value="pwresetsend">
</form>


{include filename="global-footer.tpl"}