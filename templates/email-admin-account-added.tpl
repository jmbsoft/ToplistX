=>[subject]
New Account Created
=>[plain]
A new account has been created at your top sites list.

Username: {$account.username}
Site URL: {$account.site_url}
Title: {$account.title}
Description: {$account.description}

If you are reviewing new accounts, you will need to visit your
ToplistX control panel to approve or reject this account.

{$config.install_url}/admin/index.php?r=tlxShAccountSearch&new=true
=>[html]
A new account has been created at your top sites list.<br />
<br />
Username: {$account.username}<br />
Site URL: <a href="{$account.site_url}">{$account.site_url}</a><br />
Title: {$account.title}<br />
Description: {$account.description}<br />
<br />
If you are reviewing new accounts, you will need to visit your<br />
ToplistX control panel to approve or reject this account.<br />
<br />
<a href="{$config.install_url}/admin/index.php?r=tlxShAccountSearch&new=true">{$config.install_url}/admin/index.php?r=tlxShAccountSearch&new=true</a>
