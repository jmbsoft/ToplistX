{assign var=$page_title value="Account Login"}
{include filename="global-header.tpl"}

<br />
<br />

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Account Login</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>


<form method="POST" action="{$config.install_url}/accounts.php">

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
{* Display any errors encountered during the login process *}
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


{if $logged_out}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="notice">
You have been successfully logged out of your account
</div>
</td>
</tr>
{/if}

<tr>
<td colspan="2">
Enter your account username and password to login.  Through this interface you will be able to 
view your stats, get banners and links to use, and edit your account.
</td>
</tr>

<tr>
<td width="35%" align="right">
<b>Username</b>
</td>
<td>
<input type="text" size="30" name="login_username" value="{$login.login_username|htmlspecialchars}" />
</td>
</tr>

<tr>
<td width="35%" align="right">
<b>Password</b>
</td>
<td>
<input type="password" size="30" name="login_password" value="" />
</td>
</tr>

<tr>
<td align="center" colspan="2">
<a href="{$config.install_url}/accounts.php?r=pwreset" class="small">Forgot Your Password?</a>
<br /><br />
<button type="submit">Login</button>
</td>
</tr>
</table>
</div>

<input type="hidden" name="r" value="overview">
</form>


{include filename="global-footer.tpl"}