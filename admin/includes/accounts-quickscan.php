<?php
if( !defined('ToplistX') ) die("Access denied");
include_once('includes/header.php');
?>

<style>
#results td {
    height: 20px;
    padding-left: 5px;
}

.changed {
  color: red;
  padding-left: 20px;
}
</style>

<script language="JavaScript">

</script>

<div style="padding: 0px 10px 10px 10px;">

  <form>
  <fieldset>
    <legend>Scan Results</legend>

      <?php
      // Get account information
      $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['username']));

      $http = new Http();
      $success = FALSE;
      if( $http->Get($account['site_url'], $C['allow_redirect']) ):
          $success = TRUE;
          $account['html'] = $http->body;
          $account['headers'] = $http->raw_response_headers;
          $blacklisted = CheckBlacklistAccount($account);
      ?>
      <table id="results" width="100%">
        <tr>
          <td width="235" align="right">
            <b>HTTP Status</b>
          </td>
          <td>
            <?php echo htmlspecialchars($http->response_headers['status']); ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <b>IP Address</b>
          </td>
          <td>
            <?php
            echo GetIpFromUrl($http->end_url);
            ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <img src="images/<?php echo ($blacklisted !== FALSE ? 'x' : 'check'); ?>.png">
          </td>
          <td>
            No blacklisted data found
          </td>
        </tr>
      </table>

      <?php else: // if( $http->Get($account['site_url'], $C['allow_redirect']) ) ?>

      <div class="alert">
        <?php echo htmlspecialchars($http->errstr); ?>
      </div>

      <?php endif; // if( $http->Get($account['site_url'], $C['allow_redirect']) ) ?>

  </fieldset>

  <?php if( $success ): ?>
  <fieldset>
    <legend>HTTP Headers</legend>

    <div style="font-size: 9pt; font-family: monospace, fixed; width: 95%; overflow: auto; background-color: #ececec; padding: 10px;"><?php echo nl2br(htmlspecialchars(trim($http->raw_response_headers))); ?></div>

  </fieldset>

  <fieldset>
    <legend>Page HTML</legend>

    <div style="font-size: 9pt; font-family: monospace, fixed; width: 95%; height: 300px; overflow: auto; background-color: #ececec; padding: 10px;"><?php echo nl2br(htmlspecialchars($http->body)); ?></div>
  </fieldset>
  <?php endif; // if( $success ) ?>

  </form>

</div>

</body>
</html>
