=>[subject]
Password Reset Confirmation
=>[plain]
Greetings,

Someone has recently requested that your account password be reset at our site.  If you did not make this request, you can ignore this e-mail message.

To reset your account password, please visit this confirmation URL:
{$config.install_url}/accounts.php?r=pwresetconfirm&id={$confirm_id}

Cheers,
Toplist Administrator
{$config.install_url}/accounts.php?r=login
=>[html]
Greetings,<br />
<br />
Someone has recently requested that your account password be reset at our site.  If you did not make this request, you can ignore this e-mail message.<br />
<br />
To reset your account password, please visit this confirmation URL:<br />
<a href="{$config.install_url}/accounts.php?r=pwresetconfirm&id={$confirm_id}">{$config.install_url}/accounts.php?r=pwresetconfirm&id={$confirm_id}</a><br />
<br />
Cheers,<br />
Toplist Administrator<br />
<a href="{$config.install_url}/accounts.php?r=login">{$config.install_url}/accounts.php?r=login</a>
