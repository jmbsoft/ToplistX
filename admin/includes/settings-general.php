<?php
if( !defined('ToplistX') ) die("Access denied");

$defaults = array('document_root' => $_SERVER['DOCUMENT_ROOT'],
                  'install_url' => "http://{$_SERVER['HTTP_HOST']}" . preg_replace('~/admin/index\.php.*~', '', $_SERVER['REQUEST_URI']),
                  'forward_url' => "http://{$_SERVER['HTTP_HOST']}/",
                  'alternate_out_url' => "http://{$_SERVER['HTTP_HOST']}/",
                  'cookie_domain' => preg_replace('~www\.~', '', $_SERVER['HTTP_HOST']),
                  'date_format' => 'm-d-Y',
                  'time_format' => 'h:i:s',
                  'dec_point' => '.',
                  'thousands_sep' => ',',
                  'secret_key' => sha1(uniqid(rand(), true)),
                  'redirect_code' => 301,
                  'max_rating' => 5,
                  'min_comment_length' => 10,
                  'max_comment_length' => 500,
                  'comment_interval' => 120,
                  'min_title_length' => 10,
                  'max_title_length' => 100,
                  'min_desc_length' => 10,
                  'max_desc_length' => 500,
                  'max_keywords' => 8,
                  'return_percent' => 1.0,
                  'banner_max_width' => 468,
                  'banner_max_height' => 60,
                  'banner_max_bytes' => 20480,
                  'font_dir' => "{$GLOBALS['BASE_DIR']}/fonts",
                  'min_code_length' => 4,
                  'max_code_length' => 6,
                  'page_permissions' => '666',
                  'confirm_approve_process' => 1,
                  'confirm_account_delete' => 1,
                  'confirm_account_lock' => 1,
                  'confirm_edit_process' => 1,
                  'rebuild_interval' => 3600,
                  'ranking_images' => 10,
                  'ranking_images_extension' => 'png');

$defaults['in_url'] = "{$defaults['install_url']}/in.php";
$defaults['banner_url'] = "{$defaults['install_url']}/banners";
$defaults['ranking_images_url'] = "{$defaults['install_url']}/images";

if( !isset($C['from_email']) )
{
    $C = array_merge($C, $defaults);
}

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#cron_rebuilds').bind('change', function()
                                         {
                                             if( this.checked )
                                             {
                                                 $('#rebuild_interval_div').slideUp();
                                             }
                                             else
                                             {
                                                 $('#rebuild_interval_div').slideDown();
                                             }
                                         });

      $('#form').bind('submit', function()
                                {
                                    $('input[@type=checkbox]').each(function()
                                                       {
                                                           if( !this.checked )
                                                           {
                                                               $('#form').append('<input type="hidden" name="'+this.name+'" value="0">');
                                                           }
                                                       });
                                });

      $('#generate-secret').bind('click', function() { $('#secret_key').val(hex_sha1(Math.random().toString())); return false; });
  });

var hexcase = 0;
var b64pad  = "";
var chrsz   = 8;

function hex_sha1(s){return binb2hex(core_sha1(str2binb(s),s.length * chrsz));}

function sha1_vm_test()
{
  return hex_sha1("abc") == "a9993e364706816aba3e25717850c26c9cd0d89d";
}

function core_sha1(x, len)
{

  x[len >> 5] |= 0x80 << (24 - len % 32);
  x[((len + 64 >> 9) << 4) + 15] = len;

  var w = Array(80);
  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;
  var e = -1009589776;

  for(var i = 0; i < x.length; i += 16)
  {
    var olda = a;
    var oldb = b;
    var oldc = c;
    var oldd = d;
    var olde = e;

    for(var j = 0; j < 80; j++)
    {
      if(j < 16) w[j] = x[i + j];
      else w[j] = rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
      var t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)),
                       safe_add(safe_add(e, w[j]), sha1_kt(j)));
      e = d;
      d = c;
      c = rol(b, 30);
      b = a;
      a = t;
    }

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
    e = safe_add(e, olde);
  }
  return Array(a, b, c, d, e);

}

function sha1_ft(t, b, c, d)
{
  if(t < 20) return (b & c) | ((~b) & d);
  if(t < 40) return b ^ c ^ d;
  if(t < 60) return (b & c) | (b & d) | (c & d);
  return b ^ c ^ d;
}

function sha1_kt(t)
{
  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
         (t < 60) ? -1894007588 : -899497514;
}

function core_hmac_sha1(key, data)
{
  var bkey = str2binb(key);
  if(bkey.length > 16) bkey = core_sha1(bkey, key.length * chrsz);

  var ipad = Array(16), opad = Array(16);
  for(var i = 0; i < 16; i++)
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = core_sha1(ipad.concat(str2binb(data)), 512 + data.length * chrsz);
  return core_sha1(opad.concat(hash), 512 + 160);
}

function safe_add(x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

function rol(num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}

function str2binb(str)
{
  var bin = Array();
  var mask = (1 << chrsz) - 1;
  for(var i = 0; i < str.length * chrsz; i += chrsz)
    bin[i>>5] |= (str.charCodeAt(i / chrsz) & mask) << (32 - chrsz - i%32);
  return bin;
}

function binb2hex(binarray)
{
  var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
  var str = "";
  for(var i = 0; i < binarray.length * 4; i++)
  {
    str += hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8+4)) & 0xF) +
           hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8  )) & 0xF);
  }
  return str;
}

</script>
<style>
.fieldgroup label {
  width: 190px;
}
</style>

<?php if( isset($GLOBALS['no_access_list']) ): ?>
<div class="warn centered">
  ENHANCED SECURITY: You have not yet setup an access list, which will add increased security to your control panel.
  <a href="docs/access-list.html" target="_blank"><img src="images/help-small.png" border="0" width="12" height="12" style="position: relative; top: 1px; left: 10px;"></a>
</div>
<?php endif; ?>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">

    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/settings.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Use this page to adjust the software's general settings
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
        <legend>Basic Settings</legend>
        <div class="fieldgroup">
            <label for="document_root">Document Root:</label>
            <input type="text" name="document_root" id="document_root" size="70" value="<?PHP echo $C['document_root']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="install_url">ToplistX URL:</label>
            <input type="text" name="install_url" id="install_url" size="70" value="<?PHP echo $C['install_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="banner_url">Banner  URL:</label>
            <input type="text" name="banner_url" id="banner_url" size="70" value="<?PHP echo $C['banner_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="cookie_domain">Cookie Domain:</label>
            <input type="text" name="cookie_domain" id="cookie_domain" size="30" value="<?PHP echo $C['cookie_domain']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="page_permissions">Page Permissions:</label>
            <input type="text" name="page_permissions" id="page_permissions" size="5" value="<?PHP echo $C['page_permissions']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="from_email">E-mail Address:</label>
            <input type="text" name="from_email" id="from_email" size="40" value="<?PHP echo $C['from_email']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="from_email_name">E-mail Name:</label>
            <input type="text" name="from_email_name" id="from_email_name" size="40" value="<?PHP echo $C['from_email_name']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="email_type">E-mail Sender:</label>
            <select name="email_type">
              <?php
              $email_types = array(MT_PHP => 'PHP mail() function',
                                   MT_SENDMAIL => 'Sendmail',
                                   MT_SMTP => 'SMTP Server');
              echo OptionTags($email_types, $C['email_type']);
              ?>
            </select>
            <input type="text" name="mailer" id="mailer" size="40" value="<?PHP echo $C['mailer']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="date_format">Date Format:</label>
            <input type="text" name="date_format" id="date_format" size="20" value="<?PHP echo $C['date_format']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="time_format">Time Format:</label>
            <input type="text" name="time_format" id="time_format" size="20" value="<?PHP echo $C['time_format']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="timezone">Your Timezone:</label>
            <select name="timezone" id="timezone">
            <?PHP
            $zones = array('-12' => 'GMT -12 Hours',
                           '-11' => 'GMT -11 Hours',
                           '-10' => 'GMT -10 Hours',
                           '-9' => 'GMT -9 Hours',
                           '-8' => 'GMT -8 Hours',
                           '-7' => 'GMT -7 Hours',
                           '-6' => 'GMT -6 Hours',
                           '-5' => 'GMT -5 Hours',
                           '-4' => 'GMT -4 Hours',
                           '-3.5' => 'GMT -3.5 Hours',
                           '-3' => 'GMT -3 Hours',
                           '-2' => 'GMT -2 Hours',
                           '-1' => 'GMT -1 Hour',
                           '0' => 'GMT',
                           '1' => 'GMT +1 Hour',
                           '2' => 'GMT +2 Hours',
                           '3' => 'GMT +3 Hours',
                           '3.5' => 'GMT +3.5 Hours',
                           '4' => 'GMT +4 Hours',
                           '4.5' => 'GMT +4.5 Hours',
                           '5' => 'GMT +5 Hours',
                           '5.5' => 'GMT +5.5 Hours',
                           '6' => 'GMT +6 Hours',
                           '6.5' => 'GMT +6.5 Hours',
                           '7' => 'GMT +7 Hours',
                           '8' => 'GMT +8 Hours',
                           '9' => 'GMT +9 Hours',
                           '9.5' => 'GMT +9.5 Hours',
                           '10' => 'GMT +10 Hours',
                           '11' => 'GMT +11 Hours',
                           '12' => 'GMT +12 Hours');

            echo OptionTags($zones, $C['timezone']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="dec_point">Decimal Point:</label>
            <input type="text" name="dec_point" id="dec_point" size="10" value="<?PHP echo $C['dec_point']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="thousands_sep">Thousands Separator:</label>
            <input type="text" name="thousands_sep" id="thousands_sep" size="10" value="<?PHP echo $C['thousands_sep']; ?>" />
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="using_cron" class="cblabel inline"><?php echo CheckBox('using_cron', 'checkbox', 1, $C['using_cron']); ?>
            Using cron to automate page building, hourly stats, and daily stats</label>
        </div>

        <div class="fieldgroup" id="rebuild_interval_div"<?php if( $C['cron_rebuilds'] ): ?> style="display: none;"<?php endif; ?>>
            <label for="rebuild_interval">Rebuild Interval:</label>
            <input type="text" name="rebuild_interval" id="rebuild_interval" size="10" value="<?PHP echo $C['rebuild_interval']; ?>" />
        </div>
      </fieldset>


      <fieldset>
        <legend>Ranking Images Settings</legend>
        <div class="fieldgroup">
            <label for="ranking_images_url">Ranking Images URL:</label>
            <input type="text" name="ranking_images_url" id="ranking_images_url" size="70" value="<?PHP echo $C['ranking_images_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="ranking_images">Images To Show:</label>
            <input type="text" name="ranking_images" id="ranking_images" size="5" value="<?PHP echo $C['ranking_images']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="ranking_images_extension">File Extension:</label>
            <input type="text" name="ranking_images_extension" id="ranking_images_extension" size="5" value="<?PHP echo $C['ranking_images_extension']; ?>" />
        </div>
      </fieldset>


      <fieldset>
        <legend>Click Tracking &amp; Rating Settings</legend>
        <div class="fieldgroup">
            <label for="tracking_mode">Tracking Mode:</label>
            <select name="tracking_mode" id="tracking_mode">
            <?PHP
            $tracking_modes = array('embedded' => 'Accounts send surfers directly to your site',
                                    'unique_link' => 'Unique link for each account');

            echo OptionTags($tracking_modes, $C['tracking_mode']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="secret_key">Secret Key:</label>
            <input type="text" name="secret_key" id="secret_key" size="45" value="<?PHP echo $C['secret_key']; ?>" /> <a href="" id="generate-secret">Generate</a>
        </div>

        <div class="fieldgroup">
            <label for="in_url">Tracking URL:</label>
            <input type="text" name="in_url" id="in_url" size="70" value="<?PHP echo $C['in_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="forward_url">Default Forward URL:</label>
            <input type="text" name="forward_url" id="forward_url" size="70" value="<?PHP echo $C['forward_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="alternate_out_url">Alternate Out URL:</label>
            <input type="text" name="alternate_out_url" id="alternate_out_url" size="70" value="<?PHP echo $C['alternate_out_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="redirect_code">Redirect Status Code:</label>
            <input type="text" name="redirect_code" id="redirect_code" size="5" value="<?PHP echo $C['redirect_code']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="max_rating">Maximum Site Rating:</label>
            <input type="text" name="max_rating" id="max_rating" size="5" value="<?PHP echo $C['max_rating']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="min_comment_length">Comment Length:</label>
            <input type="text" name="min_comment_length" id="min_comment_length" size="5" value="<?PHP echo $C['min_comment_length']; ?>" /> to
            <input type="text" name="max_comment_length" id="max_comment_length" size="5" value="<?PHP echo $C['max_comment_length']; ?>" /> characters
        </div>

        <div class="fieldgroup">
            <label for="comment_interval">Comment Interval:</label>
            <input type="text" name="comment_interval" id="comment_interval" size="10" value="<?PHP echo $C['comment_interval']; ?>" />
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="review_comments" class="cblabel inline"><?php echo CheckBox('review_comments', 'checkbox', 1, $C['review_comments']); ?>
            Review new surfer comments before displaying them on your site</label>
        </div>
      </fieldset>


      <fieldset>
        <legend>Account Submission Settings</legend>
        <div class="fieldgroup">
            <label for="accounts_status">Account Submissions:</label>
            <select name="accounts_status" id="accounts_status">
            <?PHP
            $accounts_statuses = array('open' => 'Open - accepting new accounts',
                                       'closed' => 'Closed - not accepting new accounts');

            echo OptionTags($accounts_statuses, $C['accounts_status']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="review_new_accounts" class="cblabel inline"><?php echo CheckBox('review_new_accounts', 'checkbox', 1, $C['review_new_accounts']); ?>
            Review new accounts before listing them</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="review_edited_accounts" class="cblabel inline"><?php echo CheckBox('review_edited_accounts', 'checkbox', 1, $C['review_edited_accounts']); ?>
            Review account editing before listing the changes</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="confirm_accounts" class="cblabel inline"><?php echo CheckBox('confirm_accounts', 'checkbox', 1, $C['confirm_accounts']); ?>
            New accounts must be confirmed through the submitter's e-mail address</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="email_new_accounts" class="cblabel inline"><?php echo CheckBox('email_new_accounts', 'checkbox', 1, $C['email_new_accounts']); ?>
            Send confirmation e-mail message to new accounts when created</label>
        </div>

        <div class="fieldgroup">
            <label for="min_desc_length">Site Title Length:</label>
            <input type="text" name="min_title_length" id="min_title_length" size="5" value="<?PHP echo $C['min_title_length']; ?>" /> to
            <input type="text" name="max_title_length" id="max_title_length" size="5" value="<?PHP echo $C['max_title_length']; ?>" /> characters
        </div>

        <div class="fieldgroup">
            <label for="min_desc_length">Description Length:</label>
            <input type="text" name="min_desc_length" id="min_desc_length" size="5" value="<?PHP echo $C['min_desc_length']; ?>" /> to
            <input type="text" name="max_desc_length" id="max_desc_length" size="5" value="<?PHP echo $C['max_desc_length']; ?>" /> characters
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_keywords" class="cblabel inline"><?php echo CheckBox('allow_keywords', 'checkbox', 1, $C['allow_keywords']); ?>
            Allow user to submit keywords with their site information</label>
        </div>

        <div class="fieldgroup">
            <label for="max_keywords">Keywords Allowed:</label>
            <input type="text" name="max_keywords" id="max_keywords" size="5" value="<?PHP echo $C['max_keywords']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="return_percent">Default Return Percent:</label>
            <input type="text" name="return_percent" id="return_percent" size="5" value="<?PHP echo ($C['return_percent'] * 100); ?>" />
        </div>

        <div class="fieldgroup">
            <label for="banner_max_width">Maximum Banner Width:</label>
            <input type="text" name="banner_max_width" id="banner_max_width" size="5" value="<?php echo $C['banner_max_width']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="banner_max_height">Maximum Banner Height:</label>
            <input type="text" name="banner_max_height" id="banner_max_height" size="5" value="<?php echo $C['banner_max_height']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="banner_max_bytes">Maximum Banner Filesize:</label>
            <input type="text" name="banner_max_bytes" id="banner_max_bytes" size="10" value="<?php echo $C['banner_max_bytes']; ?>" />
        </div>

        <div class="fieldgroup">
            <label></label>
            <label for="banner_force_size" class="cblabel inline">
            <?php echo CheckBox('banner_force_size', 'checkbox', 1, $C['banner_force_size']); ?> Force all banners to the height and width entered above
            </label>
        </div>

        <div class="fieldgroup">
            <label></label>
            <label for="download_banners" class="cblabel inline">
            <?php echo CheckBox('download_banners', 'checkbox', 1, $C['download_banners']); ?> Download banners to check height, width, and filesize
            </label>
        </div>

        <div class="fieldgroup">
            <label></label>
            <label for="host_banners" class="cblabel inline">
            <?php echo CheckBox('host_banners', 'checkbox', 1, $C['host_banners']); ?> Host member account banners from your server
            </label>
        </div>

        <div class="fieldgroup">
            <label></label>
            <label for="allow_redirect" class="cblabel inline">
            <?php echo CheckBox('allow_redirect', 'checkbox', 1, $C['allow_redirect']); ?> Allow redirecting site URLs to be submitted (300 level HTTP status codes)
            </label>
        </div>
      </fieldset>

      <fieldset>
        <legend>Verification Code Settings</legend>

        <div class="fieldgroup">
            <label for="font_dir">Font Directory:</label>
            <input type="text" name="font_dir" id="font_dir" size="60" value="<?PHP echo $C['font_dir']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="min_code_length">Code Length:</label>
            <input type="text" name="min_code_length" id="min_code_length" size="5" value="<?PHP echo $C['min_code_length']; ?>" /> to
            <input type="text" name="max_code_length" id="max_code_length" size="5" value="<?PHP echo $C['max_code_length']; ?>" /> characters
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="use_words" class="cblabel inline"><?php echo CheckBox('use_words', 'checkbox', 1, $C['use_words']); ?>
            Use words file for verification codes</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="account_add_captcha" class="cblabel inline"><?php echo CheckBox('account_add_captcha', 'checkbox', 1, $C['account_add_captcha']); ?>
            Require verification code on account creation interface</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="rate_captcha" class="cblabel inline"><?php echo CheckBox('rate_captcha', 'checkbox', 1, $C['rate_captcha']); ?>
            Require verification code on account rating/comment interface</label>
        </div>
      </fieldset>

      <fieldset>
        <legend>Confirmation Message Settings</legend>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="confirm_approve_process" class="cblabel inline"><?php echo CheckBox('confirm_approve_process', 'checkbox', 1, $C['confirm_approve_process']); ?>
            Approving/rejecting an account</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="confirm_account_delete" class="cblabel inline"><?php echo CheckBox('confirm_account_delete', 'checkbox', 1, $C['confirm_account_delete']); ?>
            Deleting an account</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="confirm_account_lock" class="cblabel inline"><?php echo CheckBox('confirm_account_lock', 'checkbox', 1, $C['confirm_account_lock']); ?>
            Disabling/enabling &amp; Locking/unlocking an account</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="confirm_edit_process" class="cblabel inline"><?php echo CheckBox('confirm_edit_process', 'checkbox', 1, $C['confirm_edit_process']); ?>
            Approving/rejecting an account edit</label>
        </div>
      </fieldset>

    <div class="centered margin-top">
      <button type="submit">Save Settings</button>
    </div>

    <input type="hidden" name="r" value="tlxGeneralSettingsSave">
    </form>
</div>


</body>
</html>
