=>[subject]
Account Login Information
=>[plain]
Greetings,

Someone has recently requested that your account login information be reset.  Your current username and new password are listed below.

Username: {$account.username}
Password: {$account.password}

Cheers,
Toplist Administrator
{$config.install_url}/accounts.php?r=login
=>[html]
Greetings,<br />
<br />
Someone has recently requested that your account login information be reset.  Your current username and new password are listed below.<br />
<br />
Username: {$account.username}<br />
Password: {$account.password}<br />
<br />
Cheers,<br />
Toplist Administrator<br />
<a href="{$config.install_url}/accounts.php?r=login">{$config.install_url}/accounts.php?r=login</a>
