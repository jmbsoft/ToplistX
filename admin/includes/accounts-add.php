<?php
if( !defined('ToplistX') ) die("Access denied");

$defaults = array('status' => 'active',
                  'date_added' => gmdate(DF_DATETIME, TimeWithTz()),
                  'ratings' => 0,
                  'ratings_total' => 0,
                  'return_percent' => $C['return_percent']);

if( !$editing )
{
    $_REQUEST = array_merge($defaults, $_REQUEST);
}

$categories =& $DB->FetchAll('SELECT `name`,`category_id` FROM `tlx_categories` ORDER BY `name`');
$icons =& $DB->FetchAll('SELECT * FROM `tlx_icons` ORDER BY `identifier`');

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#description').bind('keyup', function() { $('#description_charcount').html($(this).val().length); });
      $('#description').trigger('keyup');

      $('#title').bind('keyup', function() { $('#title_charcount').html($(this).val().length); });
      $('#title').trigger('keyup');

      updateRating();
  });

function updateRating()
{
    var ratings = parseInt($('#ratings').val());
    var total = parseInt($('#ratings_total').val());

    if( ratings > 0 )
        $('#rating_avg').html(Math.round((total/ratings)*100)/100);
    else
        $('#rating_avg').html('0');
}

<?PHP if( $GLOBALS['added'] && empty($_REQUEST['nosearch']) ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>


<div style="padding: 10px;">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/accounts.html#add" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this account by making changes to the information below
      <?php else: ?>
      Add a new account by filling out the information below
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

    <?php if( is_array($GLOBALS['warn']) ): ?>
    <div class="warn margin-bottom">
      <?php echo join('<br />', $GLOBALS['warn']); ?>
    </div>
    <?php endif; ?>

    <form action="index.php" method="POST" id="form">
      <fieldset>
        <legend>General Information</legend>

        <div class="fieldgroup">
            <label for="username">Username:</label>
            <?php if( $editing ): ?>
            <div style="margin-top: 3px;">
            <?php echo $_REQUEST['username']; ?>
            </div>
            <?php else: ?>
            <input type="text" name="username" id="username" size="30" value="<?php echo $_REQUEST['username']; ?>" />
            <?php endif; ?>
        </div>

        <div class="fieldgroup">
            <label for="password">Password:</label>
            <input type="text" name="password" id="password" size="30" value="<?php echo $_REQUEST['password']; ?>" />
            <?php if( $editing ): ?>Only fill this in if you want to change the account password<?php endif; ?>
        </div>

        <div class="fieldgroup">
            <label for="email">E-mail Address:</label>
            <input type="text" name="email" id="email" size="45" value="<?php echo $_REQUEST['email']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="site_url">Site URL:</label>
            <input type="text" name="site_url" id="site_url" size="90" value="<?php echo $_REQUEST['site_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="title">Site Title:</label>
            <input type="text" name="title" id="title" size="100" value="<?php echo $_REQUEST['title']; ?>" />
            <span id="title_charcount" style="padding-left: 5px;">0</span>
        </div>

        <div class="fieldgroup">
            <label for="description">Description:</label>
            <input type="text" name="description" id="description" size="100" value="<?php echo $_REQUEST['description']; ?>" />
            <span id="description_charcount" style="padding-left: 5px;">0</span>
        </div>

        <div class="fieldgroup">
            <label for="keywords">Keywords:</label>
            <input type="text" name="keywords" id="keywords" size="80" value="<?php echo $_REQUEST['keywords']; ?>" />
        </div>

        <?php if( count($categories) ): ?>
        <div class="fieldgroup">
            <label for="category_id">Category:</label>
            <select name="category_id">
            <?php
            echo OptionTagsAdv($categories, $_REQUEST['category_id'], 'category_id', 'name', 50);
            ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="fieldgroup">
            <label for="banner_url">Banner URL:</label>
            <input type="text" name="banner_url" id="banner_url" size="90" value="<?php echo $_REQUEST['banner_url']; ?>" />
            <input type="checkbox" name="download_banner" id="download_banner" value="1" class="checkbox" /> <label for="download_banner" class="cblabel inline">Download</label>
        </div>

        <div class="fieldgroup">
            <label for="banner_width">Banner Size:</label>
            <input type="text" name="banner_width" id="banner_width" size="4" value="<?php echo $_REQUEST['banner_width']; ?>" />
            x
            <input type="text" name="banner_height" id="banner_height" size="4" value="<?php echo $_REQUEST['banner_height']; ?>" />
            &nbsp;
            WIDTH x HEIGHT
        </div>

        <div class="fieldgroup">
            <label for="ratings">Ratings/Total:</label>
            <input type="text" name="ratings" id="ratings" size="10" value="<?php echo $_REQUEST['ratings']; ?>" onkeyup="updateRating()" />
            /
            <input type="text" name="ratings_total" id="ratings_total" size="10" value="<?php echo $_REQUEST['ratings_total']; ?>" onkeyup="updateRating()" /> &nbsp;
            Average Rating: <span id="rating_avg"></span>
        </div>

        <div class="fieldgroup">
            <label for="date_added">Date Added:</label>
            <input type="text" name="date_added" id="date_added" size="20" value="<?php echo $_REQUEST['date_added']; ?>" class="calendarSelectDate" />
        </div>

        <div class="fieldgroup">
            <label for="status">Status:</label>
            <select name="status" id="status">
            <?php
            $statuses = array('pending' => 'Pending',
                              'active' => 'Active');

            echo OptionTags($statuses, $_REQUEST['status']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="admin_comments">Admin Comments:</label>
            <textarea name="admin_comments" id="admin_comments" rows="3" cols="90"><?php echo $_REQUEST['admin_comments']; ?></textarea>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="locked" class="cblabel inline">
            <?php echo CheckBox('locked', 'checkbox', 1, $_REQUEST['locked']); ?> Locked for editing</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="disabled" class="cblabel inline">
            <?php echo CheckBox('disabled', 'checkbox', 1, $_REQUEST['disabled']); ?> Disabled</label>
        </div>

        <?php foreach($icons as $icon): ?>
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="icons[<?php echo $icon['icon_id']; ?>]" class="cblabel inline">
            <?php echo CheckBox("icons[{$icon['icon_id']}]", 'checkbox', $icon['icon_id'], $_REQUEST['icons'][$icon['icon_id']]) . " " . $icon['icon_html']; ?></label>
          </div>
        <?php endforeach; ?>
      </fieldset>


      <fieldset>
        <legend>Click Tracking Data</legend>

        <div class="fieldgroup">
            <label for="return_percent">Return Percent:</label>
            <input type="text" name="return_percent" id="return_percent" size="10" value="<?php echo ($_REQUEST['return_percent'] * 100); ?>" />
        </div>

        <hr />

        <div class="fieldgroup">
            <label>Raw Incoming:</label>
            <table width="500">
              <tr>
              <?php foreach( range(0,23) as $hour ): ?>
                <td align="center">
                  <?php printf("%02d:00", $hour); ?><br />
                  <input type="text" name="raw_in_<?php echo $hour; ?>" size="4" value="<?php printf('%d', $_REQUEST["raw_in_$hour"]); ?>" />
                </td>
                <?php if( ($hour + 1) % 8 == 0 ): ?>
              </tr>
              <tr>
                <?php endif; ?>
              <?php endforeach; ?>
              </tr>
            </table>
        </div>

        <hr />

        <div class="fieldgroup">
            <label>Unique Incoming:</label>
            <table width="500">
              <tr>
              <?php foreach( range(0,23) as $hour ): ?>
                <td align="center">
                  <?php printf("%02d:00", $hour); ?><br />
                  <input type="text" name="unique_in_<?php echo $hour; ?>" size="4" value="<?php printf('%d', $_REQUEST["unique_in_$hour"]); ?>" />
                </td>
                <?php if( ($hour + 1) % 8 == 0 ): ?>
              </tr>
              <tr>
                <?php endif; ?>
              <?php endforeach; ?>
              </tr>
            </table>
        </div>

        <hr />

        <div class="fieldgroup">
            <label>Raw Outgoing:</label>
            <table width="500">
              <tr>
              <?php foreach( range(0,23) as $hour ): ?>
                <td align="center">
                  <?php printf("%02d:00", $hour); ?><br />
                  <input type="text" name="raw_out_<?php echo $hour; ?>" size="4" value="<?php printf('%d', $_REQUEST["raw_out_$hour"]); ?>" />
                </td>
                <?php if( ($hour + 1) % 8 == 0 ): ?>
              </tr>
              <tr>
                <?php endif; ?>
              <?php endforeach; ?>
              </tr>
            </table>
        </div>

        <hr />

        <div class="fieldgroup">
            <label>Unique Outgoing:</label>
            <table width="500">
              <tr>
              <?php foreach( range(0,23) as $hour ): ?>
                <td align="center">
                  <?php printf("%02d:00", $hour); ?><br />
                  <input type="text" name="unique_out_<?php echo $hour; ?>" size="4" value="<?php printf('%d', $_REQUEST["unique_out_$hour"]); ?>" />
                </td>
                <?php if( ($hour + 1) % 8 == 0 ): ?>
              </tr>
              <tr>
                <?php endif; ?>
              <?php endforeach; ?>
              </tr>
            </table>
        </div>

        <hr />

        <div class="fieldgroup">
            <label>Clicks:</label>
            <table width="500">
              <tr>
              <?php foreach( range(0,23) as $hour ): ?>
                <td align="center">
                  <?php printf("%02d:00", $hour); ?><br />
                  <input type="text" name="clicks_<?php echo $hour; ?>" size="4" value="<?php printf('%d', $_REQUEST["clicks_$hour"]); ?>" />
                </td>
                <?php if( ($hour + 1) % 8 == 0 ): ?>
              </tr>
              <tr>
                <?php endif; ?>
              <?php endforeach; ?>
              </tr>
            </table>
        </div>
      </fieldset>

      <?php
      $result = $DB->Query('SELECT * FROM `tlx_account_field_defs` ORDER BY `field_id`');
      ?>
      <fieldset<?php if( $DB->NumRows($result) < 1 ) echo ' style="display: none;"'; ?>>
        <legend>User Defined Fields</legend>

        <?php
        while( $field = $DB->NextRow($result) ):
            ArrayHSC($field);
            AdminFormField($field);
        ?>

        <div class="fieldgroup">
            <?php if( $field['type'] != FT_CHECKBOX ): ?>
              <label for="<?php echo $field['name']; ?>"><?php echo $field['label']; ?>:</label>
              <?php echo FormField($field, $_REQUEST[$field['name']]); ?>
            <?php else: ?>
              <label style="height: 1px; font-size: 1px;"></label>
              <label for="<?php echo $field['name']; ?>" class="cblabel inline">
              <?php echo FormField($field, $_REQUEST[$field['name']]); ?> <?php echo $field['label']; ?></label>
            <?php endif; ?>
        </div>

        <?php
        endwhile;
        $DB->Free($result);
        ?>
      </fieldset>

      <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Account</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'tlxAccountEdit' : 'tlxAccountAdd'); ?>">
    <input type="hidden" name="nosearch" value="<?php echo $_REQUEST['nosearch']; ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>

</body>
</html>