<?php
if( !defined('ToplistX') ) die("Access denied");

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');

include_once('includes/header.php');
?>

<script language="JavaScript">
<?PHP if( $GLOBALS['edited'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/comments.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Update this comment by making changes to the information below
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
                <label for="comment">Comment:</label>
                <textarea name="comment" id="comment" rows="8" cols="90"><?php echo $_REQUEST['comment']; ?></textarea>
            </div>

            <div class="fieldgroup">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" size="40" value="<?php echo $_REQUEST['name']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="email">E-mail Address:</label>
                <input type="text" name="email" id="email" size="40" value="<?php echo $_REQUEST['email']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="ip_address">IP Address:</label>
                <input type="text" name="ip_address" id="ip_address" size="20" value="<?php echo $_REQUEST['ip_address']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="date_submitted">Date Submitted:</label>
                <input type="text" name="date_submitted" id="date_submitted" size="20" value="<?php echo $_REQUEST['date_submitted']; ?>" class="calendarSelectDate" />
                </div>

            <div class="fieldgroup">
                <label for="status">Status:</label>
                <select name="status" id="status">
                  <?php
                  $status_options = array(STATUS_PENDING => 'Pending', STATUS_APPROVED => 'Approved');
                  echo OptionTags($status_options, $_REQUEST['status']); ?>
                </select>
            </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit">Update Comment</button>
    </div>

    <input type="hidden" name="r" value="tlxCommentEdit">
    <input type="hidden" name="comment_id" value="<?php echo $_REQUEST['comment_id']; ?>">
    </form>
</div>



</body>
</html>
