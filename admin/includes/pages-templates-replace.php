<?php
if( !defined('ToplistX') ) die("Access denied");

include_once('includes/header.php');
?>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/templates-ranking.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Use this interface to perform a search and replace in the selected ranking page templates
    </div>

        <?php if( $GLOBALS['message'] ): ?>
        <div class="notice margin-bottom">
          <?php echo $GLOBALS['message']; ?>
        </div>
        <?php endif; ?>

        <fieldset>
          <legend>Search and Replace Settings</legend>

          <div class="fieldgroup">
            <label for="pages">Pages:</label>
            <select name="pages[]" id="pages" multiple="multiple" size="10">
              <?php
              $pages =& $DB->FetchAll('SELECT `page_id`,`filename`,CONCAT(?, `filename`) AS `page_url` FROM `tlx_pages` ORDER BY `filename`', array("http://{$_SERVER['HTTP_HOST']}/"));
              echo OptionTagsAdv($pages, '', 'page_id', 'page_url', 70);
              ?>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="search">Search For:</label>
            <textarea name="search" id="search" rows="7" cols="100" wrap="off"></textarea>
          </div>

          <div class="fieldgroup">
            <label for="replace">Replace With:</label>
            <textarea name="replace" id="replace" rows="7" cols="100" wrap="off"></textarea>
          </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit">Search and Replace</button>
    </div>

    <input type="hidden" name="r" value="tlxPageTemplatesReplace">
    </form>
</div>



</body>
</html>
