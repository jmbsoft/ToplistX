{assign var=$page_title value="Banners And Links"}
{include filename="global-header.tpl"}

<div style="font-weight: bold; text-align: center;">
<a href="{$config.install_url}/accounts.php?r=edit">Edit Account</a> : 
<a href="{$config.install_url}/accounts.php?r=overview">Overview</a> : 
<a href="mailto:{$config.from_email}">E-mail Administrator</a> :
<a href="{$config.install_url}/accounts.php?r=logout">Log Out</a>
</div>

<br />

<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td>
<b>Banners and Links</b>
</td>
<td class="table-header-r">
</td>
</tr>
</table>


<form method="POST" action="{$config.install_url}/accounts.php">

<div class="table-border">
<table cellspacing="1" cellpadding="5" width="100%" class="rows">
<tr>
<td>
</td>
</tr>
</table>
</div>

{*
////////////////////////////                         \\\\\\\\\\\\\\\\\\\\\\\\\\\\
//////////////////////////// SITE OWNERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\ 
////////////////////////////                         \\\\\\\\\\\\\\\\\\\\\\\\\\\\

PLACE YOUR BANNERS AND LINKS ON THIS PAGE

USE THE {$tracking_url|htmlspecialchars} TEMPLATE VALUE TO INSERT THE URL
YOUR MEMBERS SHOULD SEND VISITORS TO.

THIS EXAMPLE WILL SHOW THE MEMBER WHAT THE LINK WILL LOOK
LIKE FOLLOWED BY A TEXT BOX THAT WILL ALLOW THEM TO SEE
AND COPY THE HTML CODE FOR THIS LINK:

<ul>
This is how it will appear on your site:<br />
<a href="{$tracking_url|htmlspecialchars}"><b>Visit NAME OF YOUR SITE</b></a>

<br />
<br />

Copy and paste the code from this box to use this link<br />
<textarea rows="5" cols="70"><a href="{$tracking_url|htmlspecialchars}">Visit NAME OF YOUR SITE</a></textarea>
</ul>
*}

{include filename="global-footer.tpl"}