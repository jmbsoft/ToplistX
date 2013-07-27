{assign var=$page_title value="Account Submitted"}
{include filename="global-header.tpl"}

<br />
<br />

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Account Submitted</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
<tr>
<td colspan="2" style="padding-bottom: 5px;">
Your account has been submitted with the information listed below.

{if $account.status == 'unconfirmed'}
{* Display this section if accounts must be confirmed through e-mail *}
An e-mail message has been dispatched to {$account.email|htmlspecialchars}.  In that e-mail message you will
find a link that you need to visit in order to confirm your new account.  Further instructions will be provided 
once your account has been confirmed.
{elseif $account.status == 'pending'}
{* Display this section if reviewing new accounts *}
To ensure the quality of our member accounts we review them all before they can be listed on our site.
Within the next 1-2 days we will review your account. If it is determined to be acceptable, your account
will be activated and it will then be eligible for display on our site.  You will receive a confirmation
e-mail once your account has been reviewed.
{else}
{* Display this section if NOT reviewing new accounts *}
You can create a link from your site to the following URL so that the visitors you send to our site are tracked:
<br />
<span style="color: blue;">{$tracking_url|htmlspecialchars}</span>

<br />
<br />

For more in-depth linking instructions, please visit the <a href="{$config.install_url}/accounts.php?r=login">account maintenance page</a> 
where you can enter your username and select to view the links you can use for this program.
{/if}
</td>
</tr>

{* Uncomment this section to add your banners and or links
<tr>
<td colspan="2" style="padding-bottom: 5px;">

ADD BANNERS AND/OR LINKS HERE THAT YOU WANT YOUR MEMBERS TO USE

</td>
</tr>
*}

<tr>
<td width="150" align="right">
<b>Username</b>
</td>
<td>
{$account.username|htmlspecialchars}
</td>
</tr>

<tr id="email_tr">
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
{$account.email|htmlspecialchars}
</td>
</tr>

{* Only display if a category was submitted *}
{if $account.category}
<tr>
<td width="150" align="right" valign="top">
<b>Category</b>
</td>
<td>
{$account.category|htmlspecialchars}
</td>
</tr>
{/if}

<tr>
<td width="150" align="right">
<b>Site URL</b>
</td>
<td>
{$account.site_url|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right" valign="top">
<b>Site Title</b>
</td>
<td>
{$account.title|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right" valign="top">
<b>Description</b>
</td>
<td>
{$account.description|htmlspecialchars}
</td>
</tr>

{* Only display if user is allowed to submit keywords *}
{if $account.keywords}
<tr>
<td width="150" align="right" valign="top">
<b>Keywords</b>
</td>
<td>
{$account.keywords|htmlspecialchars}
</td>
</tr>
{/if}

{if $account.banner_url}
<tr>
<td width="150" align="right">
<b>Banner</b>
</td>
<td>
<img src="{$account.banner_url|htmlspecialchars}" border="0" height="{$account.banner_height|htmlspecialchars}" width="{$account.banner_width|htmlspecialchars}" />
</td>
</tr>
{/if}


{* Show the user defined fields *}
{foreach var=$field from=$user_fields}
  {if $field.on_create}
    {if $field.type == FT_CHECKBOX}
<tr>
<td width="150" align="right">
&nbsp;
</td>
<td>
{if $field.value}
<img src="{$config.install_url}/images/check.png" border="0" />
{else}
<img src="{$config.install_url}/images/uncheck.png" border="0" />
{/if}
<b>{$field.label|htmlspecialchars}</b> 
</td>
</tr>    
    {else}
<tr>
<td width="150" align="right">
<b>{$field.label|htmlspecialchars}</b> 
</td>
<td>
{$field.value|htmlspecialchars}
</td>
</tr>
    {/if}
  {/if}
{/foreach}
</table>
</div>


{include filename="global-footer.tpl"}