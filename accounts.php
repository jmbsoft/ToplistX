<?php
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

$functions = array('accountadd' => 'tlxAccountAdd',
                   'accountedit' => 'tlxShAccountEdit',
                   'accountupdate' => 'tlxAccountEdit',
                   'confirm' => 'tlxShConfirm',
                   'pwreset' => 'tlxShPasswordReset',
                   'pwresetsend' => 'tlxPasswordReset',
                   'pwresetconfirm' => 'tlxPasswordResetConfirmed',
                   'login' => 'tlxShAccountLogin',
                   'dologin' => 'tlxAccountLogin',
                   'overview' => 'tlxShAccountOverview',
                   'logout' => 'tlxAccountLogout',
                   'edit' => 'tlxShAccountEdit',
                   'doedit' => 'tlxAccountEdit',
                   'links' => 'tlxShAccountLinks');

require_once('includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");

SetupRequest();

$t = new Template();
$t->assign_by_ref('config', $C);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();


if( $C['accounts_status'] == 'closed' )
{
    $t->display('accounts-closed.tpl');
    $DB->Disconnect();
    return;
}

if( isset($functions[$_REQUEST['r']]) && function_exists($functions[$_REQUEST['r']]) )
{
    call_user_func($functions[$_REQUEST['r']]);
}
else
{
    tlxShAccountAdd();
}

$DB->Disconnect();

function tlxAccountLogout()
{
    global $C, $DB, $L, $t;

    if( isset($_COOKIE['toplistxaccount']) )
    {
        parse_str($_COOKIE['toplistxaccount'], $cookie);

        $DB->Update('DELETE FROM `tlx_account_logins` WHERE `username`=? AND `session`=?',
                    array($cookie['username'],
                          $cookie['session']));
    }

    setcookie('toplistxaccount', '', time() - 3600, '/', $C['cookie_domain']);

    $t->assign('logged_out', TRUE);

    tlxShAccountLogin();
}

function tlxShAccountLogin($errors = null)
{
    global $C, $DB, $L, $t;

    $t->assign_by_ref('errors', $errors);
    $t->assign_by_ref('login', $_REQUEST);

    $t->display('accounts-login.tpl');
}

function tlxShAccountOverview()
{
    global $C, $DB, $L, $t;

    if( ($account = ValidAccountLogin()) === FALSE )
    {
        return;
    }

    $t->assign('tracking_url', ($C['tracking_mode'] == 'unique_link' ? "{$C['in_url']}?id={$account['username']}" : $C['in_url']));
    $t->assign_by_ref('account', $account);
    $t->assign_by_ref('stats', $DB->Row('SELECT * FROM `tlx_account_hourly_stats` WHERE `username`=?', array($account['username'])));
    $t->display('accounts-overview.tpl');
}

function tlxShAccountLinks()
{
    global $C, $DB, $L, $t;

    if( ($account = ValidAccountLogin()) === FALSE )
    {
        return;
    }

    $t->assign('tracking_url', ($C['tracking_mode'] == 'unique_link' ? "{$C['in_url']}?id={$account['username']}" : $C['in_url']));
    $t->assign_by_ref('account', $account);

    $t->display('accounts-banners-links.tpl');
}

function tlxShAccountEdit($errors = null)
{
    global $C, $DB, $L, $t;

    if( ($account = ValidAccountLogin()) === FALSE )
    {
        return;
    }

    if( $account['locked'] )
    {
        $t->display('accounts-locked.tpl');
        return;
    }

    $categories =& $DB->FetchAll('SELECT * FROM `tlx_categories` WHERE `hidden`=0 ORDER BY `name`');
    $fields =& GetUserAccountFields($account);

    $account['banner_width'] = ($account['banner_width'] ? $account['banner_width'] : '');
    $account['banner_height'] = ($account['banner_height'] ? $account['banner_height'] : '');

    if( $_REQUEST['r'] == 'doedit' )
    {
        $account = array_merge($account, $_REQUEST);
    }

    $t->assign_by_ref('errors', $errors);
    $t->assign_by_ref('categories', $categories);
    $t->assign_by_ref('user_fields', $fields);
    $t->assign_by_ref('account', $account);

    $t->display('accounts-edit.tpl');
}

function tlxAccountEdit()
{
    global $C, $DB, $L, $t, $IMAGE_EXTENSIONS;

    if( ($account = ValidAccountLogin()) === FALSE )
    {
        return;
    }

    if( $account['locked'] )
    {
        $t->display('accounts-locked.tpl');
        return;
    }

    unset($_REQUEST['banner_url_local']);

    // Get domain
    $parsed_url = parse_url($_REQUEST['site_url']);
    $_REQUEST['domain'] = preg_replace('~^www\.~', '', $parsed_url['host']);

    $v = new Validator();

    // Get selected category (if any) and set variables
    if( isset($_REQUEST['category_id']) )
    {
        $category = $DB->Row('SELECT * FROM `tlx_categories` WHERE `category_id`=? AND `hidden`=0', array($_REQUEST['category_id']));

        if( $category )
        {
            $C['min_desc_length'] = $category['desc_min_length'];
            $C['max_desc_length'] = $category['desc_max_length'];
            $C['min_title_length'] = $category['title_min_length'];
            $C['max_title_length'] = $category['title_max_length'];
            $C['banner_max_width'] = $category['banner_max_width'];
            $C['banner_max_height'] = $category['banner_max_height'];
            $C['banner_max_bytes'] = $category['banner_max_bytes'];
            $C['allow_redirect'] = $category['allow_redirect'];
        }
        else
        {
            $v->SetError($L['INVALID_CATEGORY']);
        }
    }

    // Check for duplicate account information
    if( $DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE (`site_url`=? OR `email`=? OR `domain`=?) AND `username`!=?', array($_REQUEST['site_url'], $_REQUEST['email'], $_REQUEST['domain'], $account['username'])) > 0 )
    {
        $v->SetError($L['EXISTING_ACCOUNT']);
    }

    $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
    $v->Register($_REQUEST['site_url'], V_URL, sprintf($L['INVALID_URL'], $L['SITE_URL']));

    if( !empty($_REQUEST['new_password']) )
    {
        $v->Register($_REQUEST['new_password'], V_LENGTH, $L['PASSWORD_LENGTH'], '4,9999');
        $v->Register($_REQUEST['new_password'], V_NOT_EQUALS, $L['USERNAME_IS_PASSWORD'], $account['username']);
        $v->Register($_REQUEST['new_password'], V_EQUALS, $L['PASSWORDS_DONT_MATCH'], $_REQUEST['confirm_password']);

        $_REQUEST['password'] = sha1($_REQUEST['new_password']);
    }
    else
    {
        $_REQUEST['password'] = $account['password'];
    }

    // Format keywords and check number
    if( $C['allow_keywords'] )
    {
        $_REQUEST['keywords'] = FormatSpaceSeparated($_REQUEST['keywords']);
        $keywords = explode(' ', $_REQUEST['keywords']);
        $v->Register(count($keywords), V_LESS_EQ, sprintf($L['MAXIMUM_KEYWORDS'], $C['max_keywords']), $C['max_keywords']);
    }
    else
    {
        $_REQUEST['keywords'] = $account['keywords'];
    }

    if( !IsEmptyString($_REQUEST['banner_url']) )
    {
        $v->Register($_REQUEST['banner_url'], V_URL, sprintf($L['INVALID_URL'], $L['BANNER_URL']));
    }

    // Initial validation
    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountEdit', TRUE);
    }

    // Check if the site URL is working
    $http = new Http();
    if( $http->Get($_REQUEST['site_url'], $C['allow_redirect']) )
    {
        $_REQUEST['html'] = $http->body;
        $_REQUEST['headers'] = $http->raw_response_headers;
    }
    else
    {
        $v->SetError(sprintf($L['BROKEN_URL'], $_REQUEST['site_url'], $http->errstr));
    }

    // Check the blacklist
    $blacklisted = CheckBlacklistAccount($_REQUEST);
    if( $blacklisted !== FALSE )
    {
        $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
    }

    // Check site title and description length
    $v->Register($_REQUEST['title'], V_LENGTH, sprintf($L['TITLE_LENGTH'], $C['min_title_length'], $C['max_title_length']), "{$C['min_title_length']},{$C['max_title_length']}");
    $v->Register($_REQUEST['description'], V_LENGTH, sprintf($L['DESCRIPTION_LENGTH'], $C['min_desc_length'], $C['max_desc_length']), "{$C['min_desc_length']},{$C['max_desc_length']}");


    // Validation of user defined fields
    $fields =& GetUserAccountFields();
    foreach($fields as $field)
    {
        if( $field['on_edit'] )
        {
            if( $field['required_edit'] )
            {
                $v->Register($_REQUEST[$field['name']], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $field['label']));
            }

            if( !IsEmptyString($_REQUEST[$field['name']]) && $field['validation'] )
            {
                $v->Register($_REQUEST[$field['name']], $field['validation'], $field['validation_message'], $field['validation_extras']);
            }
        }
    }


    // Download banner to check size
    $banner_file = null;
    if( $_REQUEST['banner_url'] != $account['banner_url'] && !IsEmptyString($_REQUEST['banner_url']) && ($C['download_banners'] || $C['host_banners']) )
    {
        $http = new Http();

        if( $http->Get($_REQUEST['banner_url'], TRUE, $_REQUEST['site_url']) )
        {
            $unique_id = md5(uniqid(rand(), true));
            $banner_file = SafeFilename("{$C['banner_dir']}/$unique_id.jpg", FALSE);
            FileWrite($banner_file, $http->body);

            $banner_info = @getimagesize($banner_file);

            if( $banner_info !== FALSE )
            {
                $_REQUEST['banner_width'] = $banner_info[0];
                $_REQUEST['banner_height'] = $banner_info[1];

                if( filesize($banner_file) > $C['banner_max_bytes'] )
                {
                    $v->SetError(sprintf($L['BAD_BANNER_BYTES'], $C['banner_max_bytes']));
                }

                if( $C['host_banners'] )
                {
                    if( isset($IMAGE_EXTENSIONS[$banner_info[2]]) )
                    {
                        $banner_ext = strtolower($IMAGE_EXTENSIONS[$banner_info[2]]);

                        $_REQUEST['banner_url_local'] = "{$C['banner_url']}/{$account['username']}.$banner_ext";

                        if( $C['review_edited_accounts'] )
                        {
                            $_REQUEST['banner_data'] = $http->body;
                        }
                        else
                        {
                            $new_file = SafeFilename("{$C['banner_dir']}/{$account['username']}.$banner_ext", FALSE);
                            rename($banner_file, $new_file);
                            $banner_file = $new_file;
                        }
                    }
                    else
                    {
                        $v->SetError($L['BAD_BANNER_IMAGE']);
                    }
                }
                else
                {
                    @unlink($banner_file);
                    $banner_file = null;
                }

            }
            else
            {
                $v->SetError($L['BAD_BANNER_IMAGE']);
            }
        }
        else
        {
            $v->SetError(sprintf($L['BROKEN_URL'], $_REQUEST['banner_url'], $http->errstr));
        }
    }


    // Check banner dimensions
    if( $_REQUEST['banner_width'] > $C['banner_max_width'] || $_REQUEST['banner_height'] > $C['banner_max_height'] )
    {
        $v->SetError(sprintf($L['BAD_BANNER_SIZE'], $C['banner_max_width'], $C['banner_max_height']));
    }

    // Force banner dimensions
    if( $C['banner_force_size'] )
    {
        $_REQUEST['banner_width'] = $C['banner_max_width'];
        $_REQUEST['banner_height'] = $C['banner_max_height'];
    }

    if( !$v->Validate() )
    {
        if( !empty($banner_file) )
        {
            @unlink($banner_file);
        }

        return $v->ValidationError('tlxShAccountEdit', TRUE);
    }


    // Reviewing account edits
    if( $C['review_edited_accounts'] )
    {
        unset($_REQUEST['html']);
        unset($_REQUEST['headers']);
        unset($_REQUEST['password']);
        unset($_REQUEST['r']);
        unset($_REQUEST['new_password']);
        unset($_REQUEST['confirm_password']);

        $DB->Update('UPDATE `tlx_accounts` SET ' .
                    '`edited`=1, ' .
                    '`edit_data`=? ' .
                    'WHERE `username`=?',
                    array(base64_encode(serialize($_REQUEST)),
                          $account['username']));
    }

    // Not reviewing account edits
    else
    {
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
                    '`password`=?, ' .
                    '`category_id`=? ' .
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
                          $_REQUEST['password'],
                          $_REQUEST['category_id'],
                          $account['username']));

        // Update user defined fields
        UserDefinedUpdate('tlx_account_fields', 'tlx_account_field_defs', 'username', $account['username'], $_REQUEST);
    }

    $t->assign_by_ref('account', $account);
    $t->assign_by_ref('stats', $DB->Row('SELECT * FROM `tlx_account_hourly_stats` WHERE `username`=?', array($account['username'])));
    $t->assign('updated', TRUE);
    $t->display('accounts-overview.tpl');
}

function tlxShAccountAdd($errors = null)
{
    global $C, $DB, $L, $t;

    $categories =& $DB->FetchAll('SELECT * FROM `tlx_categories` WHERE `hidden`=0 ORDER BY `name`');
    $fields =& GetUserAccountFields();

    $t->assign_by_ref('errors', $errors);
    $t->assign_by_ref('categories', $categories);
    $t->assign_by_ref('user_fields', $fields);
    $t->assign_by_ref('account', $_REQUEST);

    $t->display('accounts-add.tpl');
}

function tlxAccountAdd()
{
    global $C, $DB, $L, $IMAGE_EXTENSIONS, $t;

    unset($_REQUEST['banner_url_local']);

    // Get domain
    $parsed_url = parse_url($_REQUEST['site_url']);
    $_REQUEST['domain'] = preg_replace('~^www\.~', '', $parsed_url['host']);

    $v = new Validator();

    // Get selected category (if any) and set variables
    if( isset($_REQUEST['category_id']) )
    {
        $category = $DB->Row('SELECT * FROM `tlx_categories` WHERE `category_id`=? AND `hidden`=0', array($_REQUEST['category_id']));

        if( $category )
        {
            $C['min_desc_length'] = $category['desc_min_length'];
            $C['max_desc_length'] = $category['desc_max_length'];
            $C['min_title_length'] = $category['title_min_length'];
            $C['max_title_length'] = $category['title_max_length'];
            $C['banner_max_width'] = $category['banner_max_width'];
            $C['banner_max_height'] = $category['banner_max_height'];
            $C['banner_max_bytes'] = $category['banner_max_bytes'];
            $C['allow_redirect'] = $category['allow_redirect'];
        }
        else
        {
            $v->SetError($L['INVALID_CATEGORY']);
        }
    }

    // See if username is taken
    if( $DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['username'])) > 0 )
    {
        $v->SetError($L['USERNAME_TAKEN']);
    }

    // Check for duplicate account information
    if( $DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE `site_url`=? OR `email`=? OR `domain`=?', array($_REQUEST['site_url'], $_REQUEST['email'], $_REQUEST['domain'])) > 0 )
    {
        $v->SetError($L['EXISTING_ACCOUNT']);
    }

    $v->Register($_REQUEST['username'], V_LENGTH, $L['USERNAME_LENGTH'], '4,32');
    $v->Register($_REQUEST['username'], V_ALPHANUM, $L['INVALID_USERNAME']);
    $v->Register($_REQUEST['password'], V_LENGTH, $L['PASSWORD_LENGTH'], '4,9999');
    $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
    $v->Register($_REQUEST['site_url'], V_URL, sprintf($L['INVALID_URL'], $L['SITE_URL']));
    $v->Register($_REQUEST['password'], V_NOT_EQUALS, $L['USERNAME_IS_PASSWORD'], $_REQUEST['username']);
    $v->Register($_REQUEST['password'], V_EQUALS, $L['PASSWORDS_DONT_MATCH'], $_REQUEST['confirm_password']);

    if( !IsEmptyString($_REQUEST['banner_url']) )
    {
        $v->Register($_REQUEST['banner_url'], V_URL, sprintf($L['INVALID_URL'], $L['BANNER_URL']));
    }

    // Format keywords and check number
    if( $C['allow_keywords'] )
    {
        $_REQUEST['keywords'] = FormatSpaceSeparated($_REQUEST['keywords']);
        $keywords = explode(' ', $_REQUEST['keywords']);
        $v->Register(count($keywords), V_LESS_EQ, sprintf($L['MAXIMUM_KEYWORDS'], $C['max_keywords']), $C['max_keywords']);
    }
    else
    {
        $_REQUEST['keywords'] = null;
    }

    // Verify captcha code
    if( $C['account_add_captcha'] )
    {
        VerifyCaptcha($v);
    }

    // Initial validation
    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountAdd', TRUE);
    }

    // Check if the site URL is working
    $http = new Http();
    if( $http->Get($_REQUEST['site_url'], $C['allow_redirect']) )
    {
        $_REQUEST['html'] = $http->body;
        $_REQUEST['headers'] = $http->raw_response_headers;
    }
    else
    {
        $v->SetError(sprintf($L['BROKEN_URL'], $_REQUEST['site_url'], $http->errstr));
    }

    // Check the blacklist
    $blacklisted = CheckBlacklistAccount($_REQUEST);
    if( $blacklisted !== FALSE )
    {
        $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
    }

    // Check site title and description length
    $v->Register($_REQUEST['title'], V_LENGTH, sprintf($L['TITLE_LENGTH'], $C['min_title_length'], $C['max_title_length']), "{$C['min_title_length']},{$C['max_title_length']}");
    $v->Register($_REQUEST['description'], V_LENGTH, sprintf($L['DESCRIPTION_LENGTH'], $C['min_desc_length'], $C['max_desc_length']), "{$C['min_desc_length']},{$C['max_desc_length']}");


    // Validation of user defined fields
    $fields =& GetUserAccountFields();
    foreach($fields as $field)
    {
        if( $field['on_create'] )
        {
            if( $field['required_create'] )
            {
                $v->Register($_REQUEST[$field['name']], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $field['label']));
            }

            if( !IsEmptyString($_REQUEST[$field['name']]) && $field['validation'] )
            {
                $v->Register($_REQUEST[$field['name']], $field['validation'], $field['validation_message'], $field['validation_extras']);
            }
        }
    }


    // Download banner to check size
    $banner_file = null;
    if( !IsEmptyString($_REQUEST['banner_url']) && ($C['download_banners'] || $C['host_banners']) )
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

                if( filesize($banner_file) > $C['banner_max_bytes'] )
                {
                    $v->SetError(sprintf($L['BAD_BANNER_BYTES'], $C['banner_max_bytes']));
                }

                if( $C['host_banners'] )
                {
                    if( isset($IMAGE_EXTENSIONS[$banner_info[2]]) )
                    {
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
                        $v->SetError($L['BAD_BANNER_IMAGE']);
                    }
                }
                else
                {
                    @unlink($banner_file);
                    $banner_file = null;
                }
            }
            else
            {
                $v->SetError($L['BAD_BANNER_IMAGE']);
            }
        }
        else
        {
            $v->SetError(sprintf($L['BROKEN_URL'], $_REQUEST['banner_url'], $http->errstr));
        }
    }

    // Check banner dimensions
    if( $_REQUEST['banner_width'] > $C['banner_max_width'] || $_REQUEST['banner_height'] > $C['banner_max_height'] )
    {
        $v->SetError(sprintf($L['BAD_BANNER_SIZE'], $C['banner_max_width'], $C['banner_max_height']));
    }


    // Force banner dimensions
    if( $C['banner_force_size'] )
    {
        $_REQUEST['banner_width'] = $C['banner_max_width'];
        $_REQUEST['banner_height'] = $C['banner_max_height'];
    }

    if( !$v->Validate() )
    {
        if( !empty($banner_file) )
        {
            @unlink($banner_file);
        }

        return $v->ValidationError('tlxShAccountAdd', TRUE);
    }


    $_REQUEST['status'] = STATUS_ACTIVE;
    $email_template = 'email-account-added.tpl';
    if( $C['confirm_accounts'] )
    {
        $_REQUEST['status'] = STATUS_UNCONFIRMED;
        $email_template = 'email-account-confirm.tpl';
        $confirm_id = md5(uniqid(rand(), true));
        $t->assign('confirm_url', "{$C['install_url']}/accounts.php?r=confirm&id=$confirm_id");

        $DB->Update('INSERT INTO `tlx_account_confirms` VALUES (?,?,?)',
                    array($_REQUEST['username'],
                          $confirm_id,
                          MYSQL_NOW));
    }
    else if( $C['review_new_accounts'] )
    {
        $_REQUEST['status'] = STATUS_PENDING;
        $email_template = 'email-account-pending.tpl';
    }


    // Add account information
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
                      MYSQL_NOW,
                      ($_REQUEST['status'] == STATUS_ACTIVE ? MYSQL_NOW : null),
                      MYSQL_NOW,
                      sha1($_REQUEST['password']),
                      $C['return_percent'],
                      $_REQUEST['status'],
                      0,
                      0,
                      0,
                      $_REQUEST['category_id'],
                      null,
                      null,
                      0,
                      0,
                      0,
                      null,
                      null));

    // Create stats tracking data
    $stats_data = array_merge(array($_REQUEST['username']), array_fill(0, 127, 0));
    $DB->Update('INSERT INTO `tlx_account_hourly_stats` VALUES (' . CreateBindList($stats_data) . ')', $stats_data);


    // Insert user defined database fields
    $query_data = CreateUserInsert('tlx_account_fields', $_REQUEST);
    $DB->Update('INSERT INTO `tlx_account_fields` VALUES ('.$query_data['bind_list'].')', $query_data['binds']);


    // Assign template values
    $_REQUEST['category'] = $category['name'];
    $t->assign_by_ref('account', $_REQUEST);
    $t->assign_by_ref('user_fields', $fields);
    $t->assign('tracking_url', ($C['tracking_mode'] == 'unique_link' ? "{$C['in_url']}?id={$_REQUEST['username']}" : $C['in_url']));

    // Send e-mail to account submitter
    if( $C['confirm_accounts'] || $C['email_new_accounts'] )
    {
        SendMail($_REQUEST['email'], $email_template, $t);
    }

    // Send e-mail to administrators
    $administrators =& $DB->FetchAll('SELECT * FROM `tlx_administrators`');
    foreach( $administrators as $administrator )
    {
        if( $administrator['notifications'] & E_ACCOUNT_ADDED )
        {
            SendMail($administrator['email'], 'email-admin-account-added.tpl', $t);
        }
    }

    // Display confirmation page
    $t->display('accounts-added.tpl');
}

function tlxShConfirm()
{
    global $C, $DB, $L, $t;

    // Delete old confirmations
    $DB->Update('DELETE FROM `tlx_account_confirms` WHERE `date_sent` < DATE_ADD(?, INTERVAL -1 DAY)', array(MYSQL_NOW));

    $confirmation = $DB->Row('SELECT * FROM `tlx_account_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));

    if( $confirmation )
    {
        $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($confirmation['username']));

        if( $account )
        {
            $account = array_merge($account, $DB->Row('SELECT * FROM `tlx_account_fields` WHERE `username`=?', array($account['username'])));
            $account['status'] = STATUS_ACTIVE;
            $email_template = 'email-account-added.tpl';

            if( $C['review_new_accounts'] )
            {
                $account['status'] = STATUS_PENDING;
                $email_template = 'email-account-pending.tpl';
            }

            $DB->Update('DELETE FROM `tlx_account_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));
            $DB->Update('UPDATE `tlx_accounts` SET `status`=?,`date_activated`=? WHERE `username`=?',
                        array($account['status'],
                              ($account['status'] == STATUS_ACTIVE ? MYSQL_NOW : null),
                              $account['username']));

            $fields =& GetUserAccountFields($account);

            $account['password'] = $L['ENCRYPTED_PASSWORD'];

            $t->assign_by_ref('account', $account);
            $t->assign_by_ref('user_fields', $fields);
            $t->assign('tracking_url', ($C['tracking_mode'] == 'unique_link' ? "{$C['in_url']}?id={$account['username']}" : $C['in_url']));

            // Display confirmation page
            $t->display('accounts-added.tpl');

            if( $C['email_new_accounts'] )
            {
                SendMail($account['email'], $email_template, $t);
            }
        }
        else
        {
            $t->assign('error', $L['BAD_ACCOUNT']);
            $t->display('error-nice.tpl');
        }
    }
    else
    {
        $t->assign('error', $L['INVALID_CONFIRMATION']);
        $t->display('error-nice.tpl');
    }
}

function tlxShPasswordReset($errors = null)
{
    global $C, $DB, $L, $t;

    $t->assign_by_ref('errors', $errors);
    $t->assign_by_ref('account', $_REQUEST);
    $t->display('accounts-password-reset.tpl');
}

function tlxPasswordReset($errors = null)
{
    global $C, $DB, $L, $t;

    $v = new Validator();

    $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);

    if( !empty($_REQUEST['email']) )
    {
        $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `email`=?', array($_REQUEST['email']));

        if( !$account )
        {
            $v->SetError($L['NO_MATCHING_EMAIL']);
        }
        else
        {
            if( $account['status'] != STATUS_ACTIVE )
            {
                $v->SetError($L['ACCOUNT_PENDING']);
            }
            else if( $account['suspended'] )
            {
                $v->SetError($L['ACCOUNT_SUSPENDED']);
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShPasswordReset', TRUE);
    }

    $confirm_id = md5(uniqid(rand(), TRUE));

    $DB->Update('DELETE FROM `tlx_account_confirms` WHERE `username`=?', array($account['username']));
    $DB->Update('INSERT INTO `tlx_account_confirms` VALUES (?,?,?)',
                array($account['username'],
                      $confirm_id,
                      MYSQL_NOW));

    $t->assign_by_ref('account', $account);
    $t->assign('confirm_id', $confirm_id);

    SendMail($account['email'], 'email-account-password-confirm.tpl', $t);

    $t->display('accounts-password-reset-confirm.tpl');
}

function tlxPasswordResetConfirmed($errors = null)
{
    global $C, $DB, $L, $t;

    // Delete old confirmations
    $DB->Update('DELETE FROM `tlx_account_confirms` WHERE `date_sent` < DATE_ADD(?, INTERVAL -1 DAY)', array(MYSQL_NOW));

    $confirmation = $DB->Row('SELECT * FROM `tlx_account_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));

    if( $confirmation )
    {
        $DB->Update('DELETE FROM `tlx_account_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));
        $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($confirmation['username']));

        if( !$account )
        {
            $t->assign('error', $L['INVALID_CONFIRMATION']);
        }
        else
        {
            $account['password'] = RandomPassword();

            $DB->Update('UPDATE `tlx_accounts` SET `password`=? WHERE `username`=?',
                        array(sha1($account['password']),
                              $account['username']));

            $DB->Update('DELETE FROM `tlx_account_logins` WHERE `username`=?', array($account['username']));

            $t->assign_by_ref('account', $account);

            SendMail($account['email'], 'email-account-password-confirmed.tpl', $t);
        }
    }
    else
    {
        $t->assign('error', $L['INVALID_CONFIRMATION']);
    }

    $t->display('accounts-password-reset-confirmed.tpl');
}
?>
