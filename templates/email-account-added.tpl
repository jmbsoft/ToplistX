=>[subject]
Account Added
=>[plain]
Greetings,

Your account has been created and is ready to begin tracking
the visitors you send to our site.

You can begin sending visitors to our site at any time. Use the
following URL for your links:
{if $config.tracking_mode == 'unique_link'}
{$config.in_url}?id={$account.username|urlencode}
{else}
{$config.in_url}
{/if}

You can get additional linking information, edit your account, and
view your stats at our account maintenance page.
{$config.install_url}/accounts.php?r=login

Use the following information to login:

Username: {$account.username}
Password: {$account.password}

Make sure you write down your username and password! If you have any
questions, please let us know.

Cheers,
Top List Webmaster
{$config.from_email}
=>[html]
Greetings,<br />
<br />
Your account has been created and is ready to begin tracking<br />
the visitors you send to our site.<br />
<br />
You can begin sending visitors to our site at any time. Use the<br />
following URL for your links:<br />
{if $config.tracking_mode == 'unique_link'}
<a href="{$config.in_url}?id={$account.username|urlencode}">{$config.in_url}?id={$account.username|urlencode}</a><br />
{else}
<a href="{$config.in_url}">{$config.in_url}</a><br />
{/if}
<br />
You can get additional linking information, edit your account, and<br />
view your stats at our account maintenance page.<br />
<a href="{$config.install_url}/accounts.php?r=login">{$config.install_url}/accounts.php?r=login</a><br />
<br />
Use the following information to login:<br />
<br />
Username: {$account.username}<br />
Password: {$account.password}<br />
<br />
Make sure you write down your username and password! If you have any<br />
questions, please let us know.<br />
<br />
Cheers,<br />
Top List Webmaster<br />
<a href="mailto:{$config.from_email}">{$config.from_email}</a>
