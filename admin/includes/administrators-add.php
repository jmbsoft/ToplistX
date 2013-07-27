<?php
if( !defined('ToplistX') ) die("Access denied");

$type_options = array(ACCOUNT_ADMINISTRATOR => 'Administrator',
                      ACCOUNT_EDITOR => 'Editor');

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#type').bind('change', function()
                                {
                                    if( this.value == 'administrator' )
                                        $('#privileges').BlindUp(500);
                                    else
                                        $('#privileges').BlindDown(500);
                                });
  });
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/administrators.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this administrator account by making changes to the information below
      <?php else: ?>
      Add a new administrator account by filling out the information below
      <?php endif; ?>
    </div>

        <?php if( $GLOBALS['message'] ): ?>
        <div class="notice margin-bottom">
          <?php echo $GLOBALS['message']; ?>
        </div>
        <?php endif; ?>

        <?php if( $GLOBALS['errstr'] ): ?>
        <div class="alert margin-bottom">
          <?php echo $GLOBALS['errstr']; ?>
        </div>
        <?php endif; ?>

        <fieldset>
          <legend>General Settings</legend>

            <div class="fieldgroup">
                <label for="username">Username:</label>
                <?php if( $editing ): ?>
                <div style="padding: 3px 0px 0px 0px; margin: 0;"><?php echo $_REQUEST['username']; ?></div>
                <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>" />
                <?php else: ?>
                <input type="text" name="username" id="username" size="20" value="<?php echo $_REQUEST['username']; ?>" />
                <?php endif; ?>
            </div>

            <div class="fieldgroup">
                <label for="password">Password:</label>
                <input type="text" name="password" id="password" size="20" value="<?php echo $_REQUEST['password']; ?>" />
                <?php if( $editing ): ?>
                <br /> Leave blank unless you want to change this account's password
                <?php endif; ?>
            </div>

            <div class="fieldgroup">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" size="30" value="<?php echo $_REQUEST['name']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="email">E-mail Address:</label>
                <input type="text" name="email" id="email" size="40" value="<?php echo $_REQUEST['email']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="type">Account Type:</label>
                <select name="type" id="type">
                  <?php echo OptionTags($type_options, $_REQUEST['type']); ?>
                </select>
            </div>
        </fieldset>

        <div id="privileges" style="width: 100%<?php if( $_REQUEST['type'] != ACCOUNT_EDITOR ) echo "; display: none;"; ?>">
        <fieldset>
          <legend>Privileges</legend>

          <div class="fieldgroup">
            <label class="lesspad">Categories:</label>
            <label for="p_cat_a" class="cblabel inline">
            <?php echo CheckBox('p_cat_a', 'checkbox', P_CATEGORY_ADD, $_REQUEST['p_cat_a'], $_REQUEST['rights']); ?> Add/Approve &nbsp;</label>
            <label for="p_cat_m" class="cblabel inline">
            <?php echo CheckBox('p_cat_m', 'checkbox', P_CATEGORY_MODIFY, $_REQUEST['p_cat_m'], $_REQUEST['rights']); ?> Modify &nbsp;</label>
            <label for="p_cat_r" class="cblabel inline">
            <?php echo CheckBox('p_cat_r', 'checkbox', P_CATEGORY_REMOVE, $_REQUEST['p_cat_r'], $_REQUEST['rights']); ?> Remove &nbsp;</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad">Accounts:</label>
            <label for="p_account_a" class="cblabel inline">
            <?php echo CheckBox('p_account_a', 'checkbox', P_ACCOUNT_ADD, $_REQUEST['p_account_a'], $_REQUEST['rights']); ?> Add/Approve &nbsp;</label>
            <label for="p_account_m" class="cblabel inline">
            <?php echo CheckBox('p_account_m', 'checkbox', P_ACCOUNT_MODIFY, $_REQUEST['p_account_m'], $_REQUEST['rights']); ?> Modify &nbsp;</label>
            <label for="p_account_r" class="cblabel inline">
            <?php echo CheckBox('p_account_r', 'checkbox', P_ACCOUNT_REMOVE, $_REQUEST['p_account_r'], $_REQUEST['rights']); ?> Remove &nbsp;</label>
          </div>
        </fieldset>
        </div>


        <fieldset>
          <legend>E-mail Settings</legend>
            <div class="fieldgroup">
              <label></label>
              <label for="e_account_added" class="cblabel inline">
              <?php echo CheckBox('e_account_added', 'checkbox', E_ACCOUNT_ADDED, $_REQUEST['e_account_added'], $_REQUEST['notifications']); ?> Send e-mail when new account is created</label>
            </div>

            <div class="fieldgroup">
              <label></label>
              <label for="e_account_edited" class="cblabel inline">
              <?php echo CheckBox('e_account_edited', 'checkbox', E_ACCOUNT_EDITED, $_REQUEST['e_account_edited'], $_REQUEST['notifications']); ?> Send e-mail when an account is edited</label>
            </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Account</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'tlxAdministratorEdit' : 'tlxAdministratorAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
