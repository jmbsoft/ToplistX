<div id="infobar" class="noticebar"><div id="info"></div></div>

<div style="background-image: url(images/logo-bg.png); clear: left;">
  <div style="float: left"><a href="index.php"><img src="images/logo.png" border="0" width="119" height="28" alt="ToplistX" /></a></div>  
  <div id="logout">
  <a href="index.php?r=tlxLogOut" onclick="return confirm('Are you sure you want to log out?')"><img src="images/logout.png" border="0" alg="Log Out"></a>
  </div>
  <div style="clear: both;"></div>
</div>

<?php
$new_accts = number_format($DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE `status`=?', array('pending')), 0, $C['dec_point'], $C['thousands_sep']);
$edited_accts = number_format($DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE `edited`=?', array(1)), 0, $C['dec_point'], $C['thousands_sep']);
$pending_comments = number_format($DB->Count('SELECT COUNT(*) FROM `tlx_account_comments` WHERE `status`=?', array(STATUS_PENDING)), 0, $C['dec_point'], $C['thousands_sep']);
?>

<div id="menu">
  <span class="topMenu">
    <a class="topMenuItem">Accounts</a>
    <div class="subMenu">
      <a href="index.php?r=tlxShAccountSearch">Search Accounts</a>
      <a href="index.php?r=tlxShAccountScanner">Account Scanner</a>
      <a href="index.php?r=tlxShAccountAdd" class="window {title: 'Add Account'}">Add an Account</a>
      <a href="index.php?r=tlxShAccountMailAll" class="window {title: 'E-mail All Accounts'}">E-mail All Accounts</a>
      <a href="index.php?r=tlxShComments">Account Comments</a>
    </div>
  </span>

  <span class="topMenu" id="_ranking_">
    <a class="topMenuItem">Ranking Pages</a>
    <div class="subMenu">
      <a href="index.php?r=tlxShPages">Manage Pages</a>
      <a href="index.php?r=tlxShPageTemplates">Edit Templates</a>
      <a href="index.php?r=tlxShPagesRecompile" class="window {title: 'Recompile TGP Templates', height: 300}">Recompile Page Templates</a>
      <a href="" onclick="return rebuildPages()">Rebuild Pages</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">Templates</a>
    <div class="subMenu">
      <a href="index.php?r=tlxShScriptTemplates">Script Pages</a>
      <a href="index.php?r=tlxShEmailTemplates">E-mail Messages</a>
      <a href="index.php?r=tlxShRejectionTemplates">Rejection E-mails</a>
      <a href="index.php?r=tlxShLanguageFile">Language File</a>      
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">To Do</a>
    <div class="subMenu">
      <a href="index.php?r=tlxShAccountSearch&new=true">Review New Accounts (<span id="new_b"><?php echo $new_accts; ?></span>)</a>
      <a href="index.php?r=tlxShAccountSearch&edited=true">Review Edited Accounts (<span id="edited_b"><?php echo $edited_accts; ?></span>)</a>
      <a href="index.php?r=tlxShComments&pending=true">Review Pending Comments (<span id="edited_b"><?php echo $pending_comments; ?></span>)</a>
    </div>
  </span>
  
  <span class="topMenu">
    <a class="topMenuItem">Database</a>
    <div class="subMenu">
      <a href="index.php?r=tlxShDatabaseTools">Tools</a>
      <a href="index.php?r=tlxShAccountFields">User Defined Account Fields</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">Settings</a>
    <div class="subMenu">
      <a href="index.php?r=tlxShGeneralSettings" class="window {title: 'General Settings'}" id="_menu_gs">General Settings</a>
      <a href="index.php?r=tlxShBlacklist">Manage Blacklist</a>
      <a href="index.php?r=tlxShCategories">Manage Categories</a>
      <a href="index.php?r=tlxShIcons">Manage Icons</a>
      <!--<a href="index.php?r=tlxShTriggers">Manage Global Triggers</a>-->
      <a href="index.php?r=tlxShAdministrators">Manage Administrators</a>
      <a href="index.php?r=tlxShUrlencode" class="window {title: 'Encode URLs'}">Encode URLs</a>
      <a href="index.php?r=tlxShPhpinfo">phpinfo() Function</a>
    </div>
  </span>
</div>

<?php if( empty($C['from_email']) ): ?>
<script language="JavaScript">
$(function() { $('#_menu_gs').trigger('click'); });
</script>
<?php endif; ?>

<?php if( file_exists("{$GLOBALS['BASE_DIR']}/admin/reset-access.php") ): ?>
<div class="alert centered">
  SECURITY RISK: Please remove the reset-access.php file from the admin directory of your ToplistX installation immediately
</div>
<?php endif; ?>

<?php if( file_exists("{$GLOBALS['BASE_DIR']}/admin/install.php") ): ?>
<div class="alert centered">
  SECURITY RISK: Please remove the install.php file from the admin directory of your ToplistX installation immediately
</div>
<?php endif; ?>

<?php if( file_exists("{$GLOBALS['BASE_DIR']}/admin/mysql-change.php") ): ?>
<div class="alert centered">
  SECURITY RISK: Please remove the mysql-change.php file from the admin directory of your ToplistX installation immediately
</div>
<?php endif; ?>

