{assign var=$page_title value="Add An Account"}
{include filename="global-header.tpl"}

<br />
<br />

<script language="JavaScript">
{assign var=$num_categories value=count($categories)}
var category_data = 
[
{foreach var=$c from=$categories counter=$counter}
{ldelim}title_max: '{$c.title_max_length|htmlspecialchars}', title_min: '{$c.title_min_length|htmlspecialchars}', 
desc_max: '{$c.desc_max_length|htmlspecialchars}', desc_min: '{$c.desc_min_length|htmlspecialchars}', 
banner_width: '{$c.banner_max_width|htmlspecialchars}', banner_height: '{$c.banner_max_height|htmlspecialchars}', banner_size: '{$c.banner_max_bytes|tnumber_format}'{rdelim}{if $counter != $num_categories},{/if}
{/foreach}
];

{literal}
// Execute this code when the page is loaded
$(function()
{
    // Handle category changes (to update min and max title, description, and banner information)
    $('#category_id').bind('change', changeCategory);
    $('#category_id').trigger('change');
    
    // Site title character counter
    $('#description').bind('keyup', function() { $('#desc_charcount').html($(this).val().length); });
    $('#description').trigger('keyup');
    
    // Site description character counter
    $('#title').bind('keyup', function() { $('#title_charcount').html($(this).val().length); });
    $('#title').trigger('keyup');
});

// Execute when selected category is changed
function changeCategory()
{
    var index = $('#category_id').get(0).selectedIndex;
    var data = category_data[index];
    
    $('#title_min').html(data.title_min);
    $('#title_max').html(data.title_max);
    $('#desc_max').html(data.desc_max);
    $('#desc_min').html(data.desc_min);
    $('#max_banner_width').html(data.banner_width);
    $('#max_banner_height').html(data.banner_height);
    $('#max_banner_bytes').html(data.banner_size);
}
{/literal}
</script>

<form method="post" action="{$config.install_url}/accounts.php" enctype="multipart/form-data">

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Add Your Account</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">

{* Display any errors encountered during the account submission process *}
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

<tr id="email_tr">
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
<input type="text" size="30" name="email" value="{$account.email|htmlspecialchars}" />
</td>
</tr>

{* Only display if there is at least one category defined *}
{if count($categories) > 0}
<tr>
<td width="150" align="right" valign="top">
<b>Category</b>
</td>
<td>
<select name="category_id" id="category_id">
{* Generate option tags to display each category in the drop-down selection box *}
{options from=$categories key=category_id value=name selected=$account.category_id}
</select>
<br />
<span class="small">Select the category that best fits your site</span>
</td>
</tr>
{/if}

<tr>
<td width="150" align="right">
<b>Site URL</b>
</td>
<td>
<input type="text" size="60" name="site_url" value="{$account.site_url|htmlspecialchars}" />
</td>
</tr>

<tr>
<td width="150" align="right" valign="top">
<b>Site Title</b>
</td>
<td>
<input type="text" size="60" name="title" id="title" value="{$account.title|htmlspecialchars}" /><br />
<span class="small">
Must contain between <span id="title_min">{$config.min_title_length}</span> and <span id="title_max">{$config.max_title_length}</span> characters; 
<span id="title_charcount">0</span> characters currently entered
</span>
</td>
</tr>

<tr>
<td width="150" align="right" valign="top">
<b>Description</b>
</td>
<td>
<textarea name="description" id="description" rows="5" cols="80">{$account.description|htmlspecialchars}</textarea><br />
<span class="small">
Must contain between <span id="desc_min">{$config.min_desc_length}</span> and <span id="desc_max">{$config.max_desc_length}</span> characters; 
<span id="desc_charcount">0</span> characters currently entered
</span>
</td>
</tr>

{* Only display if user is allowed to submit keywords *}
{if $config.allow_keywords}
<tr>
<td width="150" align="right" valign="top">
<b>Keywords</b>
</td>
<td>
<input type="text" size="60" name="keywords" value="{$account.keywords|htmlspecialchars}" /><br />
<span class="small">You may submit up to {$config.max_keywords} keywords; please separate them by spaces, not commas</span>
</td>
</tr>
{/if}

<tr>
<td width="150" align="right" valign="top">
<b>Banner URL</b>
</td>
<td>
<input type="text" size="60" name="banner_url" value="{$account.banner_url|htmlspecialchars}" /><br />
<span class="small">
Your banner can be a maximum of <span id="max_banner_width">{$config.banner_max_width}</span> pixels wide, 
<span id="max_banner_height">{$config.banner_max_height}</span> pixels tall, 
and <span id="max_banner_bytes">{$config.banner_max_bytes|tnumber_format}</span> bytes in size</span>
</td>
</tr>

{* Only display these fields if you are not hosting banners on your server and are not downloading banners to check their size *}
{if !$config.download_banners && !$config.host_banners}
<tr>
<td width="150" align="right">
<b>Banner Width</b>
</td>
<td>
<input type="text" size="4" name="banner_width" value="{$account.banner_width|htmlspecialchars}" />
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Banner Height</b>
</td>
<td>
<input type="text" size="4" name="banner_height" value="{$account.banner_height|htmlspecialchars}" />
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
{field from=$field value=$field.value}
<b><label for="{$field.name|htmlspecialchars}">{$field.label|htmlspecialchars}</label></b> 
</td>
</tr>    
    {else}
<tr>
<td width="150" align="right">
<b>{$field.label|htmlspecialchars}</b> 
</td>
<td>
{field from=$field value=$field.value}
</td>
</tr>
    {/if}
  {/if}
{/foreach}

<tr>
<td width="150" align="right">
<b>Username</b>
</td>
<td>
<input type="text" size="20" name="username" id="username" value="{$account.username|htmlspecialchars}" /><br />
<span class="small">4 to 32 characters, English letters and numbers only</span>
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Password</b>
</td>
<td>
<input type="password" size="20" name="password" id="password" value="" /><br />
<span class="small">4 characters minimum</span>
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Confirm Password</b>
</td>
<td>
<input type="password" size="20" name="confirm_password" id="confirm_password" value="" />
</td>
</tr>

{* Display verification code if required *}
{if $config.account_add_captcha}
<tr id="verification">
<td width="150" align="right">
<b>Verification</b>
</td>
<td>
<div>
<img src="{$config.install_url}/code.php" border="0" style="vertical-align: middle;">
<input type="text" name="captcha" size="20" style="vertical-align: middle;" />
</div>
<span class="small">Copy the characters from the image into the text box for verification</span>
</td>
</tr>
{/if}
<tr>
<td align="center" colspan="2">
<button type="submit">Add Account</button>
</td>
</tr>

</table>
</div>

<input type="hidden" name="r" value="accountadd">
</form>

{include filename="global-footer.tpl"}