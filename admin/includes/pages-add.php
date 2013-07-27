<?php
if( !defined('ToplistX') ) die("Access denied");

$categories =& $DB->FetchAll('SELECT `name`,`category_id` FROM `tlx_categories` ORDER BY `name`');

if( !isset($_REQUEST['build_order']) )
{
    $_REQUEST['build_order'] = $DB->Count('SELECT MAX(build_order) FROM `tlx_pages`') + 1;
}

include_once('includes/header.php');
?>

<script language="JavaScript">
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>

function checkFilename()
{
    var path = $('#filename').val();
    var lastslash = path.lastIndexOf('/');
    var filename = path.substr(lastslash + 1);

    if( filename.match("[^a-zA-Z0-9\-\._]") )
    {
        alert('The page filename may only contain letters, numbers, dots, dashes, and underscores');
        return false;
    }

    if( filename.indexOf('.') == -1 )
    {
        return confirm("WARNING\r\n" +
                       "Adding pages without a file extension may cause\r\n" +
                       "the page to display incorrectly in your browser.\r\n" +
                       "Are you sure you want to add this page without a\r\n" +
                       "file extension?");
    }

    return true;
}
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form" onsubmit="return checkFilename()">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/pages-manage.html#add" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this ranking page by making changes to the information below
      <?php else: ?>
      Add a new ranking page by filling out the information below
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

        <?php if( $GLOBALS['warn'] ): ?>
        <div class="warn margin-bottom">
          <?php
          foreach( $GLOBALS['warn'] as $warning ):
              echo "$warning<br />";
          endforeach;
          ?>
        </div>
        <?php endif; ?>

        <fieldset>
          <legend>General Information</legend>

          <div class="fieldgroup">
            <label for="filename">Page URL:</label>
            <?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/'; ?><input type="text" name="filename" id="filename" size="50" value="<?php echo $_REQUEST['filename']; ?>" />
          </div>

          <div class="fieldgroup">
            <label for="category_id">Category:</label>
            <select name="category_id">
              <option value="">MIXED</option>
            <?php
            echo OptionTagsAdv($categories, $_REQUEST['category_id'], 'category_id', 'name', 50);
            ?>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="build_order">Build Order:</label>
            <input type="text" name="build_order" id="build_order" size="5" value="<?php echo $_REQUEST['build_order']; ?>" />
          </div>

          <div class="fieldgroup">
            <label for="tags">Tags:</label>
            <input type="text" name="tags" id="tags" size="80" value="<?php echo $_REQUEST['tags']; ?>" />
          </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Ranking Page</button>
    </div>

    <input type="hidden" name="page_id" value="<?php echo $_REQUEST['page_id']; ?>" />
    <input type="hidden" name="r" value="<?php echo ($editing ? 'tlxPageEdit' : 'tlxPageAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
