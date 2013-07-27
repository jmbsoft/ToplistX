<?PHP
// Copyright 2011 JMB Software, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

if( is_dir('../utilities') )
{
    echo "For security purposes, please remove the utilities directory of your ToplistX installation";
    exit;
}


define('ToplistX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/compiler.class.php");
require_once('includes/functions.php');

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

if( ($error = ValidLogin()) === TRUE )
{
    if( isset($_REQUEST['ref_url']) )
    {
        header("Location: http://{$_SERVER['HTTP_HOST']}{$_REQUEST['ref_url']}");
        exit;
    }

    if( !isset($_REQUEST['r']) )
    {
        include_once('includes/main.php');
    }
    else
    {
        $function = $_REQUEST['r'];

        if( ValidFunction($function) )
        {
            call_user_func($function);
        }
        else
        {
            trigger_error("Function '$function' is not a valid ToplistX function", E_USER_ERROR);
        }
    }
}
else
{
    if( isset($_REQUEST['ref_url']) )
    {
        $_SERVER['QUERY_STRING'] = TRUE;
        $_SERVER['REQUEST_URI'] = $_REQUEST['ref_url'];
    }

    include_once('includes/login.php');
}

$DB->Disconnect();

function tlxShUrlencode()
{
    include_once('includes/urlencode.php');
}

function tlxShComments()
{
    global $DB, $C;

    VerifyPrivileges(P_COMMENT);

    include_once('includes/comments-search.php');
}

function tlxShCommentEdit()
{
    global $DB, $C;

    VerifyPrivileges(P_COMMENT_MODIFY);

    $_REQUEST = $DB->Row('SELECT * FROM `tlx_account_comments` WHERE `comment_id`=?', array($_REQUEST['comment_id']));

    ArrayHSC($_REQUEST);

    include_once('includes/comments-edit.php');
}

function tlxCommentEdit()
{
    global $DB, $C;

    VerifyPrivileges(P_COMMENT_MODIFY);

    $v = new Validator();
    $v->Register($_REQUEST['date_submitted'], V_DATETIME, 'The Date Submitted field must be in YYYY-MM-DD HH:MM:SS format');
    $v->Register($_REQUEST['comment'], V_EMPTY, 'The Comment field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShCommentEdit');
    }

    $DB->Update('UPDATE `tlx_account_comments` SET ' .
                '`date_submitted`=?, ' .
                '`ip_address`=?, ' .
                '`name`=?, ' .
                '`email`=?, ' .
                '`status`=?, ' .
                '`comment`=? ' .
                'WHERE `comment_id`=?',
                array($_REQUEST['date_submitted'],
                      $_REQUEST['ip_address'],
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_REQUEST['status'],
                      $_REQUEST['comment'],
                      $_REQUEST['comment_id']));

    $GLOBALS['edited'] = TRUE;
    $GLOBALS['message'] = 'Comment successfully updated';
    tlxShCommentEdit();
}

function tlxShAccountTasks()
{
    global $DB, $C;

    VerifyPrivileges(P_ACCOUNT);

    include_once('includes/accounts-tasks.php');
}

function tlxShAccountMail()
{
    global $DB, $C;

    VerifyAdministrator(P_ACCOUNT);

    if( is_array($_REQUEST['username']) )
    {
        $_REQUEST['to'] = $DB->Count('SELECT `email` FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['username'][0]));
        $_REQUEST['to_list'] = $_REQUEST['username'][0];
    }

    ArrayHSC($_REQUEST);

    $function = 'tlxAccountMail';
    include_once('includes/email-compose.php');
}

function tlxAccountMail()
{
    global $DB, $C, $t;

    VerifyAdministrator(P_ACCOUNT);

    if( isset($_REQUEST['to']) )
    {
        $result = $DB->Query('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['to']));
    }
    else
    {
        $result = GetWhichAccounts();
    }

    $message = PrepareMessage();
    $t = new Template();
    $t->assign_by_ref('config', $C);

    while( $account = $DB->NextRow($result) )
    {
        $t->assign_by_ref('account', $account);
        SendMail($account['email'], $message, $t, FALSE);
    }

    $DB->Free($result);

    $message = 'The selected accounts have been e-mailed';
    include_once('includes/message.php');
}

function tlxShAccountScan()
{
    global $DB, $C;

    include_once('includes/accounts-quickscan.php');
}

function tlxShAccountStats()
{
    global $DB, $C;

    include_once('includes/accounts-stats.php');
}

function tlxShAccountAdd()
{
    global $C, $DB;

    VerifyPrivileges(P_ACCOUNT_ADD);
    ArrayHSC($_REQUEST);

    include_once('includes/accounts-add.php');
}

function tlxShAccountEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        // Get account data
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_accounts` JOIN `tlx_account_fields` USING (`username`) JOIN `tlx_account_hourly_stats` USING (`username`) WHERE `tlx_accounts`.`username`=?', array($_REQUEST['username']));

        // Load icons
        $_REQUEST['icons'] = array();
        $result = $DB->Query('SELECT * FROM `tlx_account_icons` WHERE `username`=?', array($_REQUEST['username']));
        while( $icon = $DB->NextRow($result) )
        {
            $_REQUEST['icons'][$icon['icon_id']] = $icon['icon_id'];
        }
        $DB->Free($result);

        $_REQUEST['banner_width'] = ($_REQUEST['banner_width'] ? $_REQUEST['banner_width'] : '');
        $_REQUEST['banner_height'] = ($_REQUEST['banner_height'] ? $_REQUEST['banner_height'] : '');
        unset($_REQUEST['password']);
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/accounts-add.php');
}

function tlxAccountAdd()
{
    global $DB, $C, $IMAGE_EXTENSIONS;

    VerifyPrivileges(P_ACCOUNT_ADD);

    $_REQUEST['return_percent'] /= 100;

    // Get domain
    $parsed_url = parse_url($_REQUEST['site_url']);
    $_REQUEST['domain'] = preg_replace('~^www\.~', '', $parsed_url['host']);

    $v = new Validator();
    $v->Register($_REQUEST['username'], V_LENGTH, 'The account username must be between 4 and 32 characters', '4,32');
    $v->Register($_REQUEST['username'], V_ALPHANUM, 'The account username may only contain English letters and numbers');
    $v->Register($_REQUEST['password'], V_LENGTH, 'The account password must be at least 4 characters', '4,9999');
    $v->Register($_REQUEST['email'], V_EMAIL, 'The E-mail Address is not properly formatted');
    $v->Register($_REQUEST['site_url'], V_URL, 'The Site URL is not properly formatted');
    $v->Register($_REQUEST['date_added'], V_DATETIME, 'The Date Added value is not properly formatted');

    if( !IsEmptyString($_REQUEST['banner_url']) )
    {
        $v->Register($_REQUEST['banner_url'], V_URL, sprintf($L['INVALID_URL'], $L['BANNER_URL']));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountAdd');
    }

    // Handling of banner_url_local
    if( $_REQUEST['download_banner'] )
    {
        $http = new Http();

        if( $http->Get($_REQUEST['banner_url'], TRUE, $_REQUEST['site_url']) )
        {
            $banner_file = SafeFilename("{$C['banner_dir']}/{$_REQUEST['username']}.jpg", FALSE);
            FileWrite($banner_file, $http->body);

            $banner_info = @getimagesize($banner_file);

            if( $banner_info !== FALSE )
            {
                $_REQUEST['banner_width'] = $banner_info[0];
                $_REQUEST['banner_height'] = $banner_info[1];
                $banner_ext = strtolower($IMAGE_EXTENSIONS[$banner_info[2]]);

                if( $banner_ext != 'jpg' )
                {
                    $new_file = preg_replace('~\.jpg$~', ".$banner_ext", $banner_file);
                    rename($banner_file, $new_file);
                    $banner_file = $new_file;
                }

                $_REQUEST['banner_url_local'] = "{$C['banner_url']}/{$_REQUEST['username']}.$banner_ext";
            }
            else
            {
                @unlink($banner_file);
                $banner_file = null;
            }
        }
    }

    NullIfEmpty($_REQUEST['banner_url_local']);
    NullIfEmpty($_REQUEST['admin_comments']);

    // Add account data to the database
    $DB->Update('INSERT INTO `tlx_accounts` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array($_REQUEST['username'],
                      $_REQUEST['email'],
                      $_REQUEST['site_url'],
                      $_REQUEST['domain'],
                      $_REQUEST['banner_url'],
                      $_REQUEST['banner_url_local'],
                      $_REQUEST['banner_height'],
                      $_REQUEST['banner_width'],
                      $_REQUEST['title'],
                      $_REQUEST['description'],
                      $_REQUEST['keywords'],
                      $_REQUEST['date_added'],
                      ($_REQUEST['status'] == STATUS_ACTIVE ? MYSQL_NOW : null),
                      null,
                      sha1($_REQUEST['password']),
                      $_REQUEST['return_percent'],
                      $_REQUEST['status'],
                      intval($_REQUEST['locked']),
                      intval($_REQUEST['disabled']),
                      0,
                      $_REQUEST['category_id'],
                      null,
                      null,
                      intval($_REQUEST['ratings']),
                      intval($_REQUEST['ratings_total']),
                      0,
                      null,
                      $_REQUEST['admin_comments']));

    // Add click stats to the database
    $stats = array($_REQUEST['username']);
    $totals = array('raw_in_total' => 0, 'unique_in_total' => 0, 'raw_out_total' => 0, 'unique_out_total' => 0, 'clicks_total' => 0);
    foreach( range(0,23) as $hour )
    {
        $stats[] = $_REQUEST["raw_in_$hour"];
        $stats[] = $_REQUEST["unique_in_$hour"];
        $stats[] = $_REQUEST["raw_out_$hour"];
        $stats[] = $_REQUEST["unique_out_$hour"];
        $stats[] = $_REQUEST["clicks_$hour"];

        $totals['raw_in_total'] += $_REQUEST["raw_in_$hour"];
        $totals['unique_in_total'] += $_REQUEST["unique_in_$hour"];
        $totals['raw_out_total'] += $_REQUEST["raw_out_$hour"];
        $totals['unique_out_total'] += $_REQUEST["unique_out_$hour"];
        $totals['clicks_total'] += $_REQUEST["clicks_$hour"];
    }
    array_push($stats, $totals['raw_in_total'], $totals['unique_in_total'], $totals['raw_out_total'], $totals['unique_out_total'], $totals['clicks_total'], 0, 0);
    $DB->Update('INSERT INTO `tlx_account_hourly_stats` VALUES (' . CreateBindList($stats) . ')', $stats);

    // Add user defined fields
    $query_data = CreateUserInsert('tlx_account_fields', $_REQUEST);
    $DB->Update('INSERT INTO `tlx_account_fields` VALUES ('.$query_data['bind_list'].')', $query_data['binds']);

    // Add icons
    if( is_array($_REQUEST['icons']) )
    {
        foreach( $_REQUEST['icons'] as $icon_id )
        {
            $DB->Update('INSERT INTO `tlx_account_icons` VALUES (?,?)', array($_REQUEST['username'], $icon_id));
        }
    }

    $GLOBALS['message'] = 'New account successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    tlxShAccountAdd();
}

function tlxAccountEdit()
{
    global $DB, $C, $IMAGE_EXTENSIONS;

    VerifyPrivileges(P_ACCOUNT_MODIFY);

    $_REQUEST['return_percent'] /= 100;

    // Get domain
    $parsed_url = parse_url($_REQUEST['site_url']);
    $_REQUEST['domain'] = preg_replace('~^www\.~', '', $parsed_url['host']);

    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, 'The E-mail Address is not properly formatted');
    $v->Register($_REQUEST['site_url'], V_URL, 'The Site URL is not properly formatted');
    $v->Register($_REQUEST['date_added'], V_DATETIME, 'The Date Added value is not properly formatted');

    if( !IsEmptyString($_REQUEST['password']) )
    {
        $v->Register($_REQUEST['password'], V_LENGTH, 'The account password must be at least 4 characters', '4,9999');
    }

    if( !IsEmptyString($_REQUEST['banner_url']) )
    {
        $v->Register($_REQUEST['banner_url'], V_URL, sprintf($L['INVALID_URL'], $L['BANNER_URL']));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountEdit');
    }


    // Setup account password, if changed
    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['username']));
    $_REQUEST['password'] = IsEmptyString($_REQUEST['password']) ? $account['password'] : sha1($_REQUEST['password']);

    // Handling of banner_url_local
    if( $_REQUEST['download_banner'] )
    {
        $http = new Http();

        if( $http->Get($_REQUEST['banner_url'], TRUE, $_REQUEST['site_url']) )
        {
            $banner_file = SafeFilename("{$C['banner_dir']}/{$_REQUEST['username']}.jpg", FALSE);
            FileWrite($banner_file, $http->body);

            $banner_info = @getimagesize($banner_file);

            if( $banner_info !== FALSE )
            {
                $_REQUEST['banner_width'] = $banner_info[0];
                $_REQUEST['banner_height'] = $banner_info[1];
                $banner_ext = strtolower($IMAGE_EXTENSIONS[$banner_info[2]]);

                if( $banner_ext != 'jpg' )
                {
                    $new_file = preg_replace('~\.jpg$~', ".$banner_ext", $banner_file);
                    rename($banner_file, $new_file);
                    $banner_file = $new_file;
                }

                $_REQUEST['banner_url_local'] = "{$C['banner_url']}/{$_REQUEST['username']}.$banner_ext";
            }
            else
            {
                @unlink($banner_file);
                $banner_file = null;
            }
        }
    }
    else
    {
        $_REQUEST['banner_url_local'] = $account['banner_url_local'];
    }

    if( $account['status'] != STATUS_ACTIVE && $_REQUEST['status'] == STATUS_ACTIVE )
    {
        $account['date_activated'] = MYSQL_NOW;
    }


    // Update account data
    $DB->Update('UPDATE `tlx_accounts` SET ' .
                '`email`=?, ' .
                '`site_url`=?, ' .
                '`domain`=?, ' .
                '`banner_url`=?, ' .
                '`banner_url_local`=?, ' .
                '`banner_height`=?, ' .
                '`banner_width`=?, ' .
                '`title`=?, ' .
                '`description`=?, ' .
                '`keywords`=?, ' .
                '`date_added`=?, ' .
                '`date_activated`=?, ' .
                '`password`=?, ' .
                '`return_percent`=?, ' .
                '`status`=?, ' .
                '`locked`=?, ' .
                '`disabled`=?, ' .
                '`category_id`=?, ' .
                '`ratings`=?, ' .
                '`ratings_total`=?, ' .
                '`admin_comments`=? ' .
                'WHERE `username`=?',
                array($_REQUEST['email'],
                      $_REQUEST['site_url'],
                      $_REQUEST['domain'],
                      $_REQUEST['banner_url'],
                      $_REQUEST['banner_url_local'],
                      $_REQUEST['banner_height'],
                      $_REQUEST['banner_width'],
                      $_REQUEST['title'],
                      $_REQUEST['description'],
                      $_REQUEST['keywords'],
                      $_REQUEST['date_added'],
                      $account['date_activated'],
                      $_REQUEST['password'],
                      $_REQUEST['return_percent'],
                      $_REQUEST['status'],
                      intval($_REQUEST['locked']),
                      intval($_REQUEST['disabled']),
                      $_REQUEST['category_id'],
                      intval($_REQUEST['ratings']),
                      intval($_REQUEST['ratings_total']),
                      $_REQUEST['admin_comments'],
                      $_REQUEST['username']));

    // Update stats
    $stats = array();
    $totals = array('raw_in_total' => 0, 'unique_in_total' => 0, 'raw_out_total' => 0, 'unique_out_total' => 0, 'clicks_total' => 0);
    foreach( range(0,23) as $hour )
    {
        $stats[] = "`raw_in_$hour`=" . intval($_REQUEST["raw_in_$hour"]);
        $stats[] = "`unique_in_$hour`=" . intval($_REQUEST["unique_in_$hour"]);
        $stats[] = "`raw_out_$hour`=" . intval($_REQUEST["raw_out_$hour"]);
        $stats[] = "`unique_out_$hour`=" . intval($_REQUEST["unique_out_$hour"]);
        $stats[] = "`clicks_$hour`=" . intval($_REQUEST["clicks_$hour"]);

        $totals['raw_in_total'] += $_REQUEST["raw_in_$hour"];
        $totals['unique_in_total'] += $_REQUEST["unique_in_$hour"];
        $totals['raw_out_total'] += $_REQUEST["raw_out_$hour"];
        $totals['unique_out_total'] += $_REQUEST["unique_out_$hour"];
        $totals['clicks_total'] += $_REQUEST["clicks_$hour"];
    }
    $DB->Update('UPDATE `tlx_account_hourly_stats` SET ' .
                join(', ', $stats) . ', ' .
                '`raw_in_total`=?, ' .
                '`unique_in_total`=?, ' .
                '`raw_out_total`=?, ' .
                '`unique_out_total`=?, ' .
                '`clicks_total`=? ' .
                ' WHERE `username`=?',
                array($totals['raw_in_total'],
                      $totals['unique_in_total'],
                      $totals['raw_out_total'],
                      $totals['unique_out_total'],
                      $totals['clicks_total'],
                      $_REQUEST['username']));


    // Update user defined fields
    UserDefinedUpdate('tlx_account_fields', 'tlx_account_field_defs', 'username', $_REQUEST['username'], $_REQUEST);

    // Update icons
    $DB->Update('DELETE FROM `tlx_account_icons` WHERE `username`=?', array($_REQUEST['username']));
    if( is_array($_REQUEST['icons']) )
    {
        foreach( $_REQUEST['icons'] as $icon_id )
        {
            $DB->Update('INSERT INTO `tlx_account_icons` VALUES (?,?)', array($_REQUEST['username'], $icon_id));
        }
    }


    $GLOBALS['message'] = 'Account successfully updated';
    $GLOBALS['added'] = true;
    tlxShAccountEdit();
}

function tlxShScannerResults()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/accounts-scanner-results.php');
}

function tlxShScannerHistory()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/accounts-scanner-history.php');
}

function tlxShAccountScanner()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/accounts-scanner.php');
}

function tlxShScannerConfigAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/accounts-scanner-add.php');
}

function tlxShScannerConfigEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_scanner_configs` WHERE `config_id`=?', array($_REQUEST['config_id']));
        $_REQUEST = array_merge(unserialize($_REQUEST['configuration']), $_REQUEST);
    }

    ArrayHSC($_REQUEST);

    include_once('includes/accounts-scanner-add.php');
}

function tlxScannerConfigAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShScannerConfigAdd');
    }



    // Add scanner configuration to the database
    $DB->Update('INSERT INTO `tlx_scanner_configs` VALUES (?,?,?,?,?,?,?)',
                array(NULL,
                      $_REQUEST['identifier'],
                      'Not Running',
                      time(),
                      0,
                      null,
                      serialize($_REQUEST)));

    $GLOBALS['message'] = 'New scanner configuration successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    tlxShScannerConfigAdd();
}

function tlxScannerConfigEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( $_REQUEST['pics_preview_size'] == 'custom' )
    {
        $v->Register($_REQUEST['pics_preview_size_custom'], V_REGEX, 'The custom picture preview size must be in WxH format', '~^\d+x\d+$~');
        $_REQUEST['pics_preview_size'] = $_REQUEST['pics_preview_size_custom'];
    }

    if( $_REQUEST['movies_preview_size'] == 'custom' )
    {
        $v->Register($_REQUEST['movies_preview_size_custom'], V_REGEX, 'The custom movies preview size must be in WxH format', '~^\d+x\d+$~');
        $_REQUEST['movies_preview_size'] = $_REQUEST['movies_preview_size_custom'];
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShScannerConfigEdit');
    }

    // Update scanner configuration to the database
    $DB->Update('UPDATE `tlx_scanner_configs` SET ' .
                '`identifier`=?, ' .
                '`configuration`=? ' .
                'WHERE `config_id`=?',
                array($_REQUEST['identifier'],
                      serialize($_REQUEST),
                      $_REQUEST['config_id']));

    $GLOBALS['message'] = 'Scanner configuration successfully updated';
    $GLOBALS['added'] = true;
    tlxShScannerConfigEdit();
}

function tlxShPages()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    include_once('includes/pages.php');
}

function tlxShPageAddBulk()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();
    ArrayHSC($_REQUEST);

    include_once('includes/pages-add-bulk.php');
}

function tlxShPageAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();
    ArrayHSC($_REQUEST);

    include_once('includes/pages-add.php');
}

function tlxShPageEdit()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/pages-add.php');
}

function tlxPageAddBulk()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $v = new Validator();
    $v->Register($_REQUEST['ext'], V_EMPTY, 'The File Extension field must be filled in');
    $v->Register($_REQUEST['num_pages'], V_REGEX, 'The Number of Pages field must be a numeric value', '~^\d+$~');

    if( empty($_REQUEST['category_id']) )
    {
        $v->Register($_REQUEST['prefix'], V_EMPTY, 'The Filename Prefix field must be filled in');
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All page tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    $v->Register($_REQUEST['base_url'], V_CONTAINS, 'For security purposes the Base URL may not contain the .. character sequence', '..');

    $base_dir = ResolvePath($C['document_root'] . '/' . $_REQUEST['base_url']);

    if( !is_dir($base_dir) )
    {
        $v->SetError('The Base URL value must point to an already existing directory');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShPageAddBulk');
    }

    // Get starting build order
    $build_order = $DB->Count('SELECT MAX(`build_order`) FROM `tlx_pages`') + 1;

    // Load default template
    $template = file_get_contents("{$GLOBALS['BASE_DIR']}/templates/default-ranking.tpl");

    NullIfEmpty($_REQUEST['category_id']);

    $pages =& GetBulkAddPages($_REQUEST['base_url']);
    $buffering = @ini_get('output_buffering');

    foreach( $pages as $page )
    {
        $page['filename'] = preg_replace('~^/~', '', $page['filename']);

        if( $DB->Count('SELECT COUNT(*) FROM `tlx_pages` WHERE `filename`=?', array($page['filename'])) < 1 )
        {
            if( $buffering )
            {
                echo '<span style="display: none">'.str_repeat('x', $buffering)."</span>\n";
            }

            $compiled = '';
            $c = new Compiler();
            $c->flags['category_id'] = $page['category_id'];
            $c->compile($template, $compiled);

            // Add page to the database
            $DB->Update('INSERT INTO `tlx_pages` VALUES (?,?,?,?,?,?,?)',
                        array(NULL,
                              $page['filename'],
                              $page['category_id'],
                              $build_order++,
                              $_REQUEST['tags'],
                              $template,
                              $compiled));
        }
    }

    $GLOBALS['message'] = 'New ranking pages successfully added';
    $GLOBALS['added'] = true;

    RenumberBuildOrder();
    UnsetArray($_REQUEST);
    tlxShPageAddBulk();
}

function tlxPageAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $v = new Validator();

    $v->Register($_REQUEST['filename'], V_EMPTY, 'The Page URL field must be filled in');
    $v->Register($_REQUEST['filename'], V_CONTAINS, 'For security purposes the Page URL may not contain the .. character sequence', '..');

    $filename = ResolvePath($C['document_root'] . '/' . $_REQUEST['filename']);

    if( is_dir($filename) )
    {
        $v->SetError('The Page URL value you entered points to a directory');
    }

    // See if the same page already exists
    if( $DB->Count('SELECT COUNT(*) FROM `tlx_pages` WHERE `filename`=?', array($_REQUEST['filename'])) )
    {
        $v->SetError('The ranking page you are trying to add already exists');
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All page tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShPageAdd');
    }

    // Generate build order if not supplied
    if( !is_numeric($_REQUEST['build_order']) )
    {
        $_REQUEST['build_order'] = $DB->Count('SELECT MAX(`build_order`) FROM `tlx_pages`') + 1;
    }

    // Update build orders greater than or equal to the new page's value
    $DB->Update('UPDATE `tlx_pages` SET `build_order`=`build_order`+1 WHERE `build_order`>=?', array($_REQUEST['build_order']));

    // Get template and compile
    $compiled = '';
    $template = file_get_contents("{$GLOBALS['BASE_DIR']}/templates/default-ranking.tpl");
    $c = new Compiler();
    $c->flags['category_id'] = $_REQUEST['category_id'];
    $c->compile($template, $compiled);

    NullIfEmpty($_REQUEST['category_id']);

    // Add page to the database
    $DB->Update('INSERT INTO `tlx_pages` VALUES (?,?,?,?,?,?,?)',
                array(NULL,
                      $_REQUEST['filename'],
                      $_REQUEST['category_id'],
                      $_REQUEST['build_order'],
                      $_REQUEST['tags'],
                      $template,
                      $compiled));

    $GLOBALS['message'] = 'New ranking page successfully added';
    $GLOBALS['added'] = true;

    if( file_exists($filename) )
    {
        $GLOBALS['warn'][] = "The file $filename already exists on your server";

        if( !is_writable($filename) )
        {
            $GLOBALS['warn'][] = 'You will not be able to rebuild your pages until you either remove the existing file or change it\'s permissions to 666';
        }
        else
        {
            $GLOBALS['warn'][] = 'If you rebuild your pages, the old file will be overwritten by the software.  It is recommended that you backup or remove the file before rebuilding your pages';
        }
    }

    RenumberBuildOrder();
    UnsetArray($_REQUEST);
    tlxShPageAdd();
}

function tlxPageEdit()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $v = new Validator();

    $v->Register($_REQUEST['filename'], V_EMPTY, 'The Page URL field must be filled in');
    $v->Register($_REQUEST['filename'], V_CONTAINS, 'For security purposes the Page URL may not contain the .. character sequence', '..');

    $filename = ResolvePath($C['document_root'] . '/' . $_REQUEST['page_url']);

    // See if the same page already exists
    if( $DB->Count('SELECT COUNT(*) FROM `tlx_pages` WHERE `filename`=? AND `page_id`!=?', array($filename, $_REQUEST['page_id'])) )
    {
        $v->SetError('You are changing this ranking page to be the same as an already existing page');
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All page tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShPageEdit');
    }

    $page = $DB->Row('SELECT * FROM `tlx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));

    // Use current build order if not supplied
    if( !is_numeric($_REQUEST['build_order']) )
    {
        $_REQUEST['build_order'] = $page['build_order'];
    }

    NullIfEmpty($_REQUEST['category_id']);


    // Update page settings
    $DB->Update('UPDATE `tlx_pages` SET ' .
                '`filename`=?, ' .
                '`category_id`=?, ' .
                '`build_order`=?, ' .
                '`tags`=? ' .
                'WHERE `page_id`=?',
                array($_REQUEST['filename'],
                      $_REQUEST['category_id'],
                      $_REQUEST['build_order'],
                      $_REQUEST['tags'],
                      $_REQUEST['page_id']));


    // Update build orders greater than or equal to the updated page's value
    if( $_REQUEST['build_order'] < $page['build_order'] )
    {
        $DB->Update('UPDATE `tlx_pages` SET `build_order`=`build_order`+1 WHERE `page_id`!=?', array($_REQUEST['page_id']));
    }
    else if( $_REQUEST['build_order'] > $page['build_order'] )
    {
        $DB->Update('UPDATE `tlx_pages` SET `build_order`=`build_order`-1 WHERE `page_id`!=?', array($_REQUEST['page_id']));
    }


    $GLOBALS['message'] = 'Ranking page successfully updated';
    $GLOBALS['added'] = true;

    RenumberBuildOrder();
    tlxShPageEdit();
}

function tlxShPagesRecompile()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $buffering = @ini_get('output_buffering');

    include_once('includes/header.php');
    include_once('includes/pages-recompile.php');

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
    }

    flush();

    $result = $DB->Query('SELECT * FROM `tlx_pages`');
    while( $page = $DB->NextRow($result) )
    {
        echo "Recompiling http://{$_SERVER['HTTP_HOST']}/" . htmlspecialchars($page['filename']) . "<br />";
        if( $buffering )
        {
            echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
        }
        flush();

        $compiled = '';
        $c = new Compiler();
        $c->flags['category_id'] = $page['category_id'];
        $c->compile($page['template'], $compiled);

        $DB->Update('UPDATE `tlx_pages` SET `compiled`=? WHERE `page_id`=?', array($compiled, $page['page_id']));

        unset($page);
    }
    $DB->Free($result);

    echo "</div>\n<div id=\"done\"></div>\n</body>\n</html>";
}

function tlxShPageTemplateWizard()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/pages-templates-wizard.php');
}

function tlxPageTemplateWizard()
{
    global $DB, $C;

    $template = '';
    $replacements = array('%%RANKS%%' => "{$_REQUEST['ranks_start']}-{$_REQUEST['ranks_end']}", '%%RSSTIMEZONE%%' => RssTimezone());
    IniParse("{$GLOBALS['BASE_DIR']}/admin/includes/pages-templates-wizard-code.php", TRUE, $code);

    if( in_array('MIXED', $_REQUEST['categories']) )
    {
        $categories = array('MIXED');

        foreach( $_REQUEST['exclude_categories'] as $exclude )
        {
            if( empty($exclude) )
                continue;

            $categories[] = "-$exclude";
        }

        $replacements['%%CATEGORY%%'] = join(',', $categories);
    }
    else
    {
        $categories = array();

        foreach( $_REQUEST['categories'] as $category )
        {
            $categories[] = $category;
        }

        $replacements['%%CATEGORY%%'] = join(',', $categories);
    }

    $replacements['%%ORDER%%'] = trim("{$_REQUEST['order']} {$_REQUEST['direction']}");
    $replacements['%%MINHITS%%'] = $_REQUEST['minhits'];

    $template = $code[$_REQUEST['display']];

    $template = str_replace(array_keys($replacements), array_values($replacements), $template);

    include_once('includes/header.php');
    echo "<div style=\"padding: 10px;\">\n";
    echo "Here is your generated template code:<br /><br />\n<textarea rows=\"40\" cols=\"140\" wrap=\"off\">";
    echo htmlspecialchars($template);
    echo "</textarea>\n</div>\n</body>\n</html>";
}

function tlxShPageTemplateReplace()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    include_once('includes/pages-templates-replace.php');
}

function tlxPageTemplatesReplace()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $GLOBALS['_counter'] = 0;

    if( is_array($_REQUEST['pages']) )
    {
        // Prepare data for the search and replace
        UnixFormat($_REQUEST['search']);
        UnixFormat($_REQUEST['replace']);
        $search = preg_quote($_REQUEST['search']);

        foreach( $_REQUEST['pages'] as $page_id )
        {
            $page = $DB->Row('SELECT * FROM `tlx_pages` WHERE `page_id`=?', array($page_id));

            $GLOBALS['_replaced'] = FALSE;

            UnixFormat($page['template']);

            $page['template'] = preg_replace_callback("~$search~i",
                                                      create_function('$matches', '$GLOBALS[\'_counter\']++; $GLOBALS[\'_replaced\'] = TRUE; return $_REQUEST[\'replace\'];'),
                                                      $page['template']);

            // Update and recompile template if replacements were made
            if( $GLOBALS['_replaced'] )
            {
                $compiled = '';
                $c = new Compiler();
                $c->flags['category_id'] = $page['category_id'];
                $c->compile($page['template'], $compiled);

                $DB->Update('UPDATE `tlx_pages` SET `template`=?,`compiled`=? WHERE `page_id`=?',
                            array($page['template'], $compiled, $page_id));
            }
        }
    }

    $GLOBALS['message'] = $GLOBALS['_counter'] == 1 ?
                          "A total of 1 replacement has been made" :
                          "A total of {$GLOBALS['_counter']} replacements have been made";

    tlxShPageTemplateReplace();
}

function tlxShPageTemplates()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    ArrayHSC($_REQUEST);

    include_once('includes/pages-templates.php');
}

function tlxPageTemplateLoad()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $page = $DB->Row('SELECT `page_id`,`filename`,`template` FROM `tlx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));
    $_REQUEST['page'] = $page;
    $_REQUEST['code'] = $page['template'];

    tlxShPageTemplates();
}

function tlxPageTemplateSave()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    // Remove extra whitespace from the template code
    $_REQUEST['code'] = trim($_REQUEST['code']);
    $_REQUEST['page_id'] = explode(',', $_REQUEST['page_id']);


    // Save template for selected pages
    foreach( $_REQUEST['page_id'] as $page_id )
    {
        if( !empty($page_id) )
        {
            $page = $DB->Row('SELECT * FROM `tlx_pages` WHERE `page_id`=?', array($page_id));
            $compiled = '';
            $c = new Compiler();
            $c->flags['category_id'] = $page['category_id'];

            // Attempt to compile code
            if( $c->compile($_REQUEST['code'], $compiled) )
            {
                $DB->Update('UPDATE `tlx_pages` SET `template`=?,`compiled`=? WHERE `page_id`=?',
                            array($_REQUEST['code'],
                                  $compiled,
                                  $page_id));
            }
            else
            {
                $GLOBALS['errstr'] = "Template for {$page['filename']} could not be saved:<br />" . nl2br($c->get_error_string());
            }
        }
    }

    $_REQUEST['page_id'] = $_REQUEST['page_id'][0];
    $GLOBALS['message'] = 'Template has been successully saved';
    $GLOBALS['warnstr'] = CheckTemplateCode($_REQUEST['code']);
    $_REQUEST['page'] = $DB->Row('SELECT `page_id`,`filename` FROM `tlx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));

    tlxShPageTemplates();
}

function tlxShAccountSearch()
{
    global $DB, $C;

    VerifyPrivileges(P_ACCOUNT);

    include_once('includes/accounts-search.php');
}

function tlxShAccountMailAll()
{
    global $DB, $C;

    VerifyAdministrator();

    ArrayHSC($_REQUEST);

    $_REQUEST['to'] = $_REQUEST['to_list'] = 'All Members';

    $function = 'tlxAccountMailAll';
    include_once('includes/email-compose.php');
}

function tlxAccountMailAll()
{
    global $DB, $C, $t;

    VerifyAdministrator();

    $v = new Validator();

    $v->Register($_REQUEST['subject'], V_EMPTY, 'The Subject field must be filled in');
    $v->Register($_REQUEST['plain'], V_EMPTY, 'The Text Body field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountMailAll');
    }

    $message = PrepareMessage();

    StoreValue('bulk_email', $message);

    shell_exec("{$C['php_cli']} cron.php --bulk-mail >/dev/null 2>&1 &");

    $message = 'E-mail message is being sent to all members.  This may take several minutes to complete, but you can continue using the control panel interface normally.';
    include_once('includes/message.php');
}

function tlxShCategories()
{
    global $DB, $C;

    VerifyPrivileges(P_CATEGORY);

    include_once('includes/categories.php');
}

function tlxShCategoryEdit()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_MODIFY);

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_categories` WHERE `category_id`=?', array($_REQUEST['category_id']));
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/categories-add.php');
}

function tlxShCategoryAdd()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_ADD);
    ArrayHSC($_REQUEST);

    include_once('includes/categories-add.php');
}

function tlxCategoryAdd()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_ADD);
    UnixFormat($_REQUEST['name']);
    $v =& ValidateCategoryInput(TRUE);

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShCategoryAdd');
    }

    $added = 0;

    foreach( explode("\n", $_REQUEST['name']) as $name )
    {
        $name = trim($name);

        if( IsEmptyString($name) )
        {
            continue;
        }

        $DB->Update('INSERT INTO `tlx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $name,
                          intval($_REQUEST['hidden']),
                          $_REQUEST['forward_url'],
                          $_REQUEST['page_url'],
                          $_REQUEST['banner_max_width'],
                          $_REQUEST['banner_max_height'],
                          $_REQUEST['banner_max_bytes'],
                          intval($_REQUEST['banner_force_size']),
                          intval($_REQUEST['download_banners']),
                          intval($_REQUEST['host_banners']),
                          intval($_REQUEST['allow_redirect']),
                          $_REQUEST['title_min_length'],
                          $_REQUEST['title_max_length'],
                          $_REQUEST['desc_min_length'],
                          $_REQUEST['desc_max_length'],
                          intval($_REQUEST['recip_required'])));

        $added++;
    }

    $GLOBALS['message'] = 'New ' . ($added == 1 ? 'category has' : 'categories have') . ' been successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    tlxShCategoryAdd();
}

function tlxCategoryEdit()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_MODIFY);
    $v =& ValidateCategoryInput();

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShCategoryEdit');
    }

    // Bulk update
    if( isset($_REQUEST['apply_all']) || isset($_REQUEST['apply_matched']) )
    {
        $GLOBALS['message'] = 'All categories have been successfully updated';

        $select = new SelectBuilder('*', 'tlx_categories');

        if( isset($_REQUEST['apply_matched']) )
        {
            $search = array();
            parse_str($_REQUEST['apply_matched'], $search);
            $select->AddWhere($search['field'], $search['search_type'], $search['search'], $search['search_type'] != ST_EMPTY);
            $GLOBALS['message'] = 'Matched categories have been successfully updated';
        }

        $result = $DB->Query($select->Generate(), $select->binds);

        while( $category = $DB->NextRow($result) )
        {
            $DB->Update('UPDATE `tlx_categories` SET ' .
                        '`hidden`=?, ' .
                        '`forward_url`=?, ' .
                        '`page_url`=?, ' .
                        '`banner_max_width`=?, ' .
                        '`banner_max_height`=?, ' .
                        '`banner_max_bytes`=?, ' .
                        '`banner_force_size`=?, ' .
                        '`download_banners`=?, ' .
                        '`host_banners`=?, ' .
                        '`allow_redirect`=?, ' .
                        '`title_min_length`=?, ' .
                        '`title_max_length`=?, ' .
                        '`desc_min_length`=?, ' .
                        '`desc_max_length`=?, ' .
                        '`recip_required`=? ' .
                        'WHERE `category_id`=?',
                        array(intval($_REQUEST['hidden']),
                              $_REQUEST['forward_url'],
                              $_REQUEST['page_url'],
                              $_REQUEST['banner_max_width'],
                              $_REQUEST['banner_max_height'],
                              $_REQUEST['banner_max_bytes'],
                              intval($_REQUEST['banner_force_size']),
                              intval($_REQUEST['download_banners']),
                              intval($_REQUEST['host_banners']),
                              intval($_REQUEST['allow_redirect']),
                              $_REQUEST['title_min_length'],
                              $_REQUEST['title_max_length'],
                              $_REQUEST['desc_min_length'],
                              $_REQUEST['desc_max_length'],
                              intval($_REQUEST['recip_required']),
                              $category['category_id']));
        }

        $DB->Free($result);
    }

    // Single category update
    else
    {
        $_REQUEST['name'] = trim($_REQUEST['name']);

        $DB->Update('UPDATE `tlx_categories` SET ' .
                    '`name`=?, ' .
                    '`hidden`=?, ' .
                    '`forward_url`=?, ' .
                    '`page_url`=?, ' .
                    '`banner_max_width`=?, ' .
                    '`banner_max_height`=?, ' .
                    '`banner_max_bytes`=?, ' .
                    '`banner_force_size`=?, ' .
                    '`download_banners`=?, ' .
                    '`host_banners`=?, ' .
                    '`allow_redirect`=?, ' .
                    '`title_min_length`=?, ' .
                    '`title_max_length`=?, ' .
                    '`desc_min_length`=?, ' .
                    '`desc_max_length`=?, ' .
                    '`recip_required`=? ' .
                    'WHERE `category_id`=?',
                    array($_REQUEST['name'],
                          intval($_REQUEST['hidden']),
                          $_REQUEST['forward_url'],
                          $_REQUEST['page_url'],
                          $_REQUEST['banner_max_width'],
                          $_REQUEST['banner_max_height'],
                          $_REQUEST['banner_max_bytes'],
                          intval($_REQUEST['banner_force_size']),
                          intval($_REQUEST['download_banners']),
                          intval($_REQUEST['host_banners']),
                          intval($_REQUEST['allow_redirect']),
                          $_REQUEST['title_min_length'],
                          $_REQUEST['title_max_length'],
                          $_REQUEST['desc_min_length'],
                          $_REQUEST['desc_max_length'],
                          intval($_REQUEST['recip_required']),
                          $_REQUEST['category_id']));

        $GLOBALS['message'] = 'Category has been successfully updated';
    }

    $GLOBALS['added'] = true;

    tlxShCategoryEdit();
}

function tlxShDatabaseTools()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    include_once('includes/database.php');
}

function tlxDatabaseOptimize()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    include_once('includes/header.php');
    include_once('includes/database-optimize.php');
    flush();

    foreach( array_keys($tables) as $table )
    {
        echo "Repairing " . htmlspecialchars($table) . "<br />"; flush();
        $DB->Update('REPAIR TABLE #', array($table));
        echo "Optimizing " . htmlspecialchars($table) . "<br />"; flush();
        $DB->Update('OPTIMIZE TABLE #', array($table));
    }

    echo "\n<div id=\"done\"></div></div>\n</body>\n</html>";
}

function tlxDatabaseBackup()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $filename = SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$_REQUEST['filename']}", FALSE);

    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    $GLOBALS['message'] = 'Database backup is in progress, allow a few minutes to complete before downloading the backup file';

    // Run mysqldump in the background
    if( $C['shell_exec'] && !empty($C['mysqldump']) )
    {
        $command = "{$C['mysqldump']} " .
                   "-u" . escapeshellarg($C['db_username']) . " " .
                   "-p" . escapeshellarg($C['db_password']) . " " .
                   "-h" . escapeshellarg($C['db_hostname']) . " " .
                   "--opt " .
                   escapeshellarg($C['db_name']) . " " .
                   join(' ', array_keys($tables)) .
                   " >" . escapeshellarg($filename) . " 2>&1 &";

        shell_exec($command);
    }

    // Use built in database backup function in the background
    else if( $C['shell_exec'] && !empty($C['php_cli']) )
    {
        shell_exec("{$C['php_cli']} cron.php --backup " . escapeshellarg($filename) . " >/dev/null 2>&1 &");
    }

    // Give it our best shot
    else
    {
        DoBackup($filename, $tables);
        $GLOBALS['message'] = 'Database backup has been completed';
    }

    StoreValue('last_backup', MYSQL_NOW);

    @chmod($filename, 0666);

    tlxShDatabaseTools();
}

function tlxDatabaseRestore()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $filename = SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$_REQUEST['filename']}", FALSE);

    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    $GLOBALS['message'] = 'Database restore is in progress, allow a few minutes before using the software as normal';

    // Run mysql in the background
    if( $C['shell_exec'] && !empty($C['mysql']) )
    {
        $command = "{$C['mysql']} " .
                   "-u" . escapeshellarg($C['db_username']) . " " .
                   "-p" . escapeshellarg($C['db_password']) . " " .
                   "-h" . escapeshellarg($C['db_hostname']) . " " .
                   "-f " .
                   escapeshellarg($C['db_name']) . " " .
                   " <$filename >/dev/null 2>&1 &";

        shell_exec($command);
    }

    // Use built in database backup function in the background
    else if( $C['shell_exec'] && !empty($C['php_cli']) )
    {
        shell_exec("{$C['php_cli']} cron.php --restore " . escapeshellarg($filename) . " >/dev/null 2>&1 &");
    }

    // Give it our best shot
    else
    {
        DoRestore($filename);
        $GLOBALS['message'] = 'Database restore has been completed';
    }

    tlxShDatabaseTools();
}

function tlxShAccountFields()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/account-fields.php');
}

function tlxShAccountFieldAdd()
{
    global $DB, $C, $FIELD_TYPES, $VALIDATION_TYPES;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/account-fields-add.php');
}

function tlxShAccountFieldEdit()
{
    global $DB, $C, $FIELD_TYPES, $VALIDATION_TYPES;

    VerifyAdministrator();

    $editing = TRUE;

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_account_field_defs` WHERE `field_id`=?', array($_REQUEST['field_id']));
        $_REQUEST['old_name'] = $_REQUEST['name'];
    }

    ArrayHSC($_REQUEST);

    include_once('includes/account-fields-add.php');
}

function tlxAccountFieldAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v =& ValidateUserDefined('tlx_account_field_defs', 'tlx_accounts');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountFieldAdd');
    }

    $_REQUEST['options'] = FormatCommaSeparated($_REQUEST['options']);

    $DB->Update("ALTER TABLE `tlx_account_fields` ADD COLUMN # TEXT", array($_REQUEST['name']));
    $DB->Update('INSERT INTO `tlx_account_field_defs` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array(NULL,
                      $_REQUEST['name'],
                      $_REQUEST['label'],
                      $_REQUEST['type'],
                      $_REQUEST['tag_attributes'],
                      $_REQUEST['options'],
                      $_REQUEST['validation'],
                      $_REQUEST['validation_extras'],
                      $_REQUEST['validation_message'],
                      intval($_REQUEST['on_create']),
                      intval($_REQUEST['required_create']),
                      intval($_REQUEST['on_edit']),
                      intval($_REQUEST['required_edit'])));

    $GLOBALS['message'] = 'New account field successfully added';
    $GLOBALS['added'] = true;

    UnsetArray($_REQUEST);
    tlxShAccountFieldAdd();
}

function tlxAccountFieldEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v =& ValidateUserDefined('tlx_account_field_defs', 'tlx_accounts', TRUE);

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountFieldEdit');
    }

    $_REQUEST['options'] = FormatCommaSeparated($_REQUEST['options']);

    if( $_REQUEST['name'] != $_REQUEST['old_name'] )
        $DB->Update("ALTER TABLE `tlx_account_fields` CHANGE # # TEXT", array($_REQUEST['old_name'], $_REQUEST['name']));

    $DB->Update('UPDATE `tlx_account_field_defs` SET ' .
                '`name`=?, ' .
                '`label`=?, ' .
                '`type`=?, ' .
                '`tag_attributes`=?, ' .
                '`options`=?, ' .
                '`validation`=?, ' .
                '`validation_extras`=?, ' .
                '`validation_message`=?, ' .
                '`on_create`=?, ' .
                '`required_create`=?, ' .
                '`on_edit`=?, ' .
                '`required_edit`=? ' .
                'WHERE `field_id`=?',
                array($_REQUEST['name'],
                      $_REQUEST['label'],
                      $_REQUEST['type'],
                      $_REQUEST['tag_attributes'],
                      $_REQUEST['options'],
                      $_REQUEST['validation'],
                      $_REQUEST['validation_extras'],
                      $_REQUEST['validation_message'],
                      intval($_REQUEST['on_create']),
                      intval($_REQUEST['required_create']),
                      intval($_REQUEST['on_edit']),
                      intval($_REQUEST['required_edit']),
                      $_REQUEST['field_id']));

    $GLOBALS['message'] = 'Account field has been successfully updated';
    $GLOBALS['added'] = true;

    tlxShAccountFieldEdit();
}

function tlxShPhpInfo()
{
    global $DB, $C;

    CheckAccessList();
    VerifyAdministrator();

    phpinfo();
}

function tlxShIcons()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/icons.php');
}

function tlxShIconAdd()
{
    global $C, $DB;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/icons-add.php');
}

function tlxShIconEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_icons` WHERE `icon_id`=?', array($_REQUEST['icon_id']));
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/icons-add.php');
}

function tlxIconAdd()
{
    global $C, $DB;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['icon_html'], V_EMPTY, 'The Icon HTML field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShIconAdd');
    }

    $DB->Update('INSERT INTO `tlx_icons` VALUES (?,?,?)',
                array(null,
                      $_REQUEST['identifier'],
                      $_REQUEST['icon_html']));

    $GLOBALS['message'] = 'New icon has been successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    tlxShIconAdd();
}

function tlxIconEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['icon_html'], V_EMPTY, 'The Icon HTML field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShIconEdit');
    }

    $DB->Update('UPDATE `tlx_icons` SET ' .
                '`identifier`=?, ' .
                '`icon_html`=? ' .
                'WHERE `icon_id`=?',
                array($_REQUEST['identifier'],
                      $_REQUEST['icon_html'],
                      $_REQUEST['icon_id']));

    $GLOBALS['message'] = 'Icon has been successfully updated';
    $GLOBALS['added'] = true;

    tlxShIconEdit();
}

function tlxShEmailTemplates()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    ArrayHSC($_REQUEST);

    include_once('includes/templates-email.php');
}

function tlxEmailTemplateLoad()
{
    global $DB, $C;

    VerifyAdministrator();

    $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['template']}");
    IniParse($template_file, TRUE, $_REQUEST);
    $_REQUEST['loaded_template'] = $_REQUEST['template'];

    tlxShEmailTemplates();
}

function tlxEmailTemplateSave()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $_REQUEST['plain'] = trim($_REQUEST['plain']);
    $_REQUEST['html'] = trim($_REQUEST['html']);
    $ini_data = IniWrite(null, $_REQUEST, array('subject', 'plain', 'html'));

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($ini_data, $compiled_code) )
    {
        $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['loaded_template']}");
        FileWrite($template_file, $ini_data);
        $GLOBALS['message'] = 'Template has been successully saved';
    }
    else
    {
        $GLOBALS['errstr'] = "Template could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    tlxShEmailTemplates();
}

function tlxShScriptTemplates()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    ArrayHSC($_REQUEST);

    include_once('includes/templates-script.php');
}

function tlxScriptTemplateLoad()
{
    global $DB, $C;

    VerifyAdministrator();

    $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['template']}");
    $_REQUEST['code'] = file_get_contents($template_file);
    $_REQUEST['loaded_template'] = $_REQUEST['template'];

    tlxShScriptTemplates();
}

function tlxScriptTemplateSave()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $_REQUEST['code'] = trim($_REQUEST['code']);

    // Compile global templates first, if this is not one
    if( !preg_match('~^global-~', $_REQUEST['loaded_template']) )
    {
        $t = new Template();
        foreach( glob("{$GLOBALS['BASE_DIR']}/templates/global-*.tpl") as $global_template )
        {
            $t->compile_template(basename($global_template));
        }
    }

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($_REQUEST['code'], $compiled_code) )
    {
        $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['loaded_template']}");
        FileWrite($template_file, $_REQUEST['code']);

        $compiled_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/compiled/{$_REQUEST['loaded_template']}", FALSE);
        FileWrite($compiled_file, $compiled_code);

        $GLOBALS['message'] = 'Template has been successully saved';
    }
    else
    {
        $GLOBALS['errstr'] = "Template could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    $GLOBALS['warnstr'] = CheckTemplateCode($_REQUEST['code']);

    // Recompile all templates if a global template was updated
    if( preg_match('~^global-~', $_REQUEST['loaded_template']) )
    {
        RecompileTemplates();
    }

    tlxShScriptTemplates();
}

function tlxShRejectionTemplates()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/rejections.php');
}

function tlxShRejectionTemplateAdd()
{
    global $C, $DB;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/rejections-add.php');
}

function tlxShRejectionTemplateEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_rejections` WHERE `email_id`=?', array($_REQUEST['email_id']));
        IniParse($_REQUEST['plain'], FALSE, $_REQUEST);
    }

    ArrayHSC($_REQUEST);

    include_once('includes/rejections-add.php');
}

function tlxRejectionTemplateAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['subject'], V_EMPTY,  'The Subject field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShRejectionTemplateAdd');
    }

    $_REQUEST['plain'] = trim($_REQUEST['plain']);
    $_REQUEST['html'] = trim($_REQUEST['html']);
    $ini_data = IniWrite(null, $_REQUEST, array('subject', 'plain', 'html'));

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($ini_data, $compiled_code) )
    {
        $DB->Update('INSERT INTO `tlx_rejections` VALUES (?,?,?,?)',
                    array(NULL,
                          $_REQUEST['identifier'],
                          $ini_data,
                          $compiled_code));

        $GLOBALS['message'] = 'New rejection e-mail successfully added';
        $GLOBALS['added'] = true;

        UnsetArray($_REQUEST);
    }
    else
    {
        $GLOBALS['errstr'] = "Rejection e-mail could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    tlxShRejectionTemplateAdd();
}

function tlxRejectionTemplateEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['subject'], V_EMPTY,  'The Subject field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShRejectionTemplateEdit');
    }

    $_REQUEST['plain'] = trim($_REQUEST['plain']);
    $_REQUEST['html'] = trim($_REQUEST['html']);
    $ini_data = IniWrite(null, $_REQUEST, array('subject', 'plain', 'html'));

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($ini_data, $compiled_code) )
    {
        $DB->Update('UPDATE `tlx_rejections` SET ' .
                    '`identifier`=?, ' .
                    '`plain`=?, ' .
                    '`compiled`=? ' .
                    'WHERE `email_id`=?',
                    array($_REQUEST['identifier'],
                          $ini_data,
                          $compiled_code,
                          $_REQUEST['email_id']));

        $GLOBALS['message'] = 'Rejection e-mail has been successfully updated';
        $GLOBALS['added'] = true;
    }
    else
    {
        $GLOBALS['errstr'] = "Rejection e-mail could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    tlxShRejectionTemplateEdit();
}

function tlxShLanguageFile()
{
    global $DB, $C, $L;

    VerifyAdministrator();

    include_once('includes/language.php');
}

function tlxLanguageFileSave()
{
    global $DB, $C, $L;

    VerifyAdministrator();

    if( is_writable("{$GLOBALS['BASE_DIR']}/includes/language.php") )
    {
        $language = "<?PHP\n";

        foreach( $L as $key => $value )
        {
            $L[$key] = $_REQUEST[$key];
            $value = str_replace("'", "\'", $_REQUEST[$key]);
            $language .= "\$L['$key'] = '$value';\n";
        }

        $language .= "?>";

        FileWrite("{$GLOBALS['BASE_DIR']}/includes/language.php", $language);

        $GLOBALS['message'] = 'The language file has been successfully updated';
    }

    tlxShLanguageFile();
}

function tlxShRegexTest()
{
    global $DB, $C;

    include_once('includes/regex-test.php');
}

function tlxShBlacklist()
{
    global $DB, $C, $BLIST_TYPES;

    VerifyAdministrator();

    include_once('includes/blacklist.php');
}

function tlxShBlacklistAdd()
{
    global $DB, $C, $BLIST_TYPES;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/blacklist-add.php');
}

function tlxShBlacklistEdit()
{
    global $DB, $C, $BLIST_TYPES;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_blacklist` WHERE `blacklist_id`=?', array($_REQUEST['blacklist_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/blacklist-add.php');
}

function tlxBlacklistAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['value'], V_EMPTY, 'The Value(s) field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShBlacklistAdd');
    }

    UnixFormat($_REQUEST['value']);
    $added = 0;

    foreach( explode("\n", $_REQUEST['value']) as $value )
    {
        list($value, $reason) = explode('|', $value);

        if( IsEmptyString($value) )
        {
            continue;
        }

        if( !$reason )
        {
            $reason = $_REQUEST['reason'];
        }

        // Add blacklist item data to the database
        $DB->Update('INSERT INTO `tlx_blacklist` VALUES (?,?,?,?,?)',
                    array(NULL,
                          $_REQUEST['type'],
                          intval($_REQUEST['regex']),
                          $value,
                          $reason));

        $added++;
    }

    $GLOBALS['message'] = 'New blacklist item' . ($added == 1 ? '' : 's') . ' successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    tlxShBlacklistAdd();
}

function tlxBlacklistEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['value'], V_EMPTY, 'The Value(s) field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShBlacklistEdit');
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tlx_blacklist` SET ' .
                '`value`=?, ' .
                '`type`=?, ' .
                '`regex`=?, ' .
                '`reason`=? ' .
                'WHERE `blacklist_id`=?',
                array($_REQUEST['value'],
                      $_REQUEST['type'],
                      intval($_REQUEST['regex']),
                      $_REQUEST['reason'],
                      $_REQUEST['blacklist_id']));

    $GLOBALS['message'] = 'Blacklist item successfully updated';
    $GLOBALS['added'] = true;
    tlxShBlacklistEdit();
}

function tlxShAdministrators()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/administrators.php');
}

function tlxAdministratorAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $user_count = $DB->Count('SELECT COUNT(*) FROM `tlx_administrators` WHERE `username`=?', array($_REQUEST['username']));

    $v = new Validator();
    $v->Register($_REQUEST['username'], V_LENGTH, 'The username must be between 3 and 32 characters in length', array('min'=>3,'max'=>32));
    $v->Register($_REQUEST['username'], V_ALPHANUM, 'The username can only contain letters and numbers');
    $v->Register($_REQUEST['password'], V_LENGTH, 'The password must contain at least 4 characters', array('min'=>4,'max'=>999));
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');

    if( $user_count > 0 )
    {
        $v->SetError('An administrator account already exists with that username');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAdministratorAdd');
    }

    // Determine the privileges and notifications for this account
    $privileges = GenerateFlags($_REQUEST, '^p_');
    $notifications = GenerateFlags($_REQUEST, '^e_');

    // Add account data to the database
    $DB->Update('INSERT INTO `tlx_administrators` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array($_REQUEST['username'],
                      sha1($_REQUEST['password']),
                      NULL,
                      NULL,
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_REQUEST['type'],
                      NULL,
                      NULL,
                      NULL,
                      NULL,
                      $notifications,
                      $privileges));

    $GLOBALS['message'] = 'New administrator successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    tlxShAdministratorAdd();
}

function tlxAdministratorEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $administrator = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($_REQUEST['username']));

    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');
    if( $_REQUEST['password'] )
    {
        $v->Register($_REQUEST['password'], V_LENGTH, 'The password must contain at least 4 characters', array('min'=>4,'max'=>999));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAdministratorEdit');
    }

    if( $_REQUEST['password'] )
    {
        // Password has changed, so invalidate any current session that may be active
        if( $_REQUEST['username'] != $_SERVER['REMOTE_USER'] )
        {
            $DB->Update('UPDATE `tlx_administrators` SET `session`=NULL,`session_start`=NULL WHERE `username`=?', array($_REQUEST['username']));
        }

        $_REQUEST['password'] = sha1($_REQUEST['password']);
    }
    else
    {
        $_REQUEST['password'] = $administrator['password'];
    }

    // Determine the privileges and notifications for this account
    $privileges = GenerateFlags($_REQUEST, '^p_');
    $notifications = GenerateFlags($_REQUEST, '^e_');

    // Update account information
    $DB->Update('UPDATE `tlx_administrators` SET ' .
                '`password`=?, ' .
                '`name`=?, ' .
                '`email`=?, ' .
                '`type`=?, ' .
                '`notifications`=?, ' .
                '`rights`=? ' .
                'WHERE `username`=?',
                array($_REQUEST['password'],
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_REQUEST['type'],
                      $notifications,
                      $privileges,
                      $_REQUEST['username']));

    $GLOBALS['message'] = 'Administrator account successfully updated';
    $GLOBALS['added'] = true;
    tlxShAdministratorEdit();
}

function tlxShAdministratorEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($_REQUEST['username']));
    }

    unset($_REQUEST['password']);
    ArrayHSC($_REQUEST);

    include_once('includes/administrators-add.php');
}

function tlxShAdministratorAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/administrators-add.php');
}

function tlxShAdministratorMail()
{
    global $DB, $C;

    VerifyAdministrator();

    ArrayHSC($_REQUEST);

    if( is_array($_REQUEST['username']) )
    {
        $_REQUEST['to'] = join(', ', $_REQUEST['username']);
        $_REQUEST['to_list'] = join(',', $_REQUEST['username']);
    }
    else
    {
        $_REQUEST['to'] = $_REQUEST['to_list'] = $_REQUEST['username'];
    }

    $function = 'tlxAdministratorMail';
    include_once('includes/email-compose.php');
}

function tlxAdministratorMail()
{
    global $DB, $C, $t;

    VerifyAdministrator();

    $message = PrepareMessage();
    $t = new Template();
    $t->assign_by_ref('config', $C);

    foreach( explode(',', $_REQUEST['to']) as $to_account )
    {
        $account = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($to_account));

        if( $account )
        {
            $t->assign_by_ref('account', $account);
            SendMail($account['email'], $message, $t, FALSE);
        }
    }

    $message = 'The selected administrator accounts have been e-mailed';
    include_once('includes/message.php');
}

function tlxShGeneralSettings()
{
    global $C;

    VerifyAdministrator();
    CheckAccessList();
    ArrayHSC($C);

    $C = array_merge($C, ($GLOBALS['_server_'] == null ? GetServerCapabilities() : $GLOBALS['_server_']));

    include_once('includes/settings-general.php');
}

function tlxGeneralSettingsSave()
{
    global $C;

    VerifyAdministrator();
    CheckAccessList();

    $server = GetServerCapabilities();
    $GLOBALS['_server_'] = $server;

    $v = new Validator();

    $required = array('document_root' => 'Document Root',
                      'install_url' => 'ToplistX URL',
                      'cookie_domain' => 'Cookie Domain',
                      'from_email' => 'E-mail Address',
                      'from_email_name' => 'E-mail Name',
                      'date_format' => 'Date Format',
                      'time_format' => 'Time Format',
                      'dec_point' => 'Decimal Point',
                      'thousands_sep' => 'Thousands Separator',
                      'secret_key' => 'Secret Key',
                      'forward_url' => 'Default Forward URL',
                      'alternate_out_url' => 'Alternate Out URL',
                      'redirect_code' => 'Redirect Status Code',
                      'max_rating' => 'Maximum Site Rating',
                      'min_comment_length' => 'Minimum Comment Length',
                      'max_comment_length' => 'Maximum Comment Length',
                      'comment_interval' => 'Comment Interval',
                      'min_desc_length' => 'Minimum Description Length',
                      'max_desc_length' => 'Maximum Description Length',
                      'max_keywords' => 'Maximum Keywords',
                      'return_percent' => 'Default Return Percent',
                      'banner_max_width' => 'Maximum Banner Width',
                      'banner_max_height' => 'Maximum Banner Height',
                      'banner_max_bytes' => 'Maximum Banner Filesize',
                      'font_dir' => 'Font Directory',
                      'min_code_length' => 'Minimum Code Length',
                      'max_code_length' => 'Maximum Code Length');

    if( !$_REQUEST['using_cron'] )
    {
        $required['rebuild_interval'] = 'Rebuild Interval';
        $v->Register($_REQUEST['rebuild_interval'], V_GREATER_EQ, 'The Rebuild Interval must be 60 or larger', 60);
    }

    foreach($required as $field => $name)
    {
        $v->Register($_REQUEST[$field], V_EMPTY, "The $name field is required");
    }

    $_REQUEST['return_percent'] /= 100;
    $_REQUEST['document_root'] = preg_replace('~/$~', '', $_REQUEST['document_root']);
    $_REQUEST['install_url'] = preg_replace('~/$~', '', $_REQUEST['install_url']);
    $_REQUEST['domain'] = preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']);
    $_REQUEST['banner_dir'] = DirectoryFromRoot($_REQUEST['document_root'], $_REQUEST['banner_url']);

    if( !$v->Validate() )
    {
        $C = array_merge($C, $_REQUEST);
        return $v->ValidationError('tlxShGeneralSettings');
    }

    $_REQUEST = array_merge($server, $_REQUEST);

    WriteConfig($_REQUEST);

    $GLOBALS['message'] = 'Your settings have been successfully updated';

    tlxShGeneralSettings();
}

function tlxLogOut()
{
    global $DB;

    $DB->Update('UPDATE `tlx_administrators` SET `session`=NULL,`session_start`=NULL WHERE `username`=?', array($_SERVER['REMOTE_USER']));

    setcookie('toplistx', '', time()-3600);
    header('Expires: Mon, 26 Jul 1990 05:00:00 GMT');
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Location: index.php');
}

?>
