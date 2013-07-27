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

define('ToplistX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/json.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

// Setup JSON response
$json = new JSON();

set_error_handler('AjaxError');

// Do not allow browsers to cache this script
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

SetupRequest();

// Setup database connection
$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

if( ($error = ValidLogin()) === TRUE )
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
else
{
    if( !$error )
        $error = 'Control panel login has expired';
        
    echo $json->encode(array('status' => JSON_FAILURE, 'message' => $error));
}

$DB->Disconnect();

function tlxAccountSearchAndReplace()
{
    global $DB, $json, $C;
    
    VerifyPrivileges(P_ACCOUNT_MODIFY, TRUE);

    $user_columns = $DB->GetColumns('tlx_account_fields');
    $update = 'UPDATE `tlx_accounts`';
    
    if( in_array($_REQUEST['field'], $user_columns) )
    {
        $update = 'UPDATE `tlx_accounts` JOIN `tlx_account_fields` USING (`username`)';
    }
    
    if( $_REQUEST['search'] == 'NULL' )
    {
        $replacements = $DB->Update($update.' SET #=? WHERE #=? OR # IS NULL', 
                                    array($_REQUEST['field'], 
                                          $_REQUEST['replace'],
                                          $_REQUEST['field'], 
                                          '', 
                                          $_REQUEST['field']));
    }
    else
    {
        $replacements = $DB->Update($update.' SET #=REPLACE(#, ?, ?)', array($_REQUEST['field'], $_REQUEST['field'], $_REQUEST['search'], $_REQUEST['replace']));
    }
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$replacements replacements have been made"));
}

function tlxAccountSearchAndSet()
{
    global $DB, $json, $C;
    
    VerifyPrivileges(P_ACCOUNT_MODIFY, TRUE);

    $user_columns = $DB->GetColumns('tlx_account_fields');
    $search_type = ($_REQUEST['search'] == 'NULL' ? ST_EMPTY : ST_CONTAINS);
    $u = new UpdateBuilder('tlx_accounts');
    
    if( in_array($_REQUEST['field'], $user_columns) || in_array($_REQUEST['set_field'], $user_columns) )
    {
        $u->AddJoin('tlx_accounts', 'tlx_account_fields', '', 'username');
    }
    
    if( $_REQUEST['replace'] == 'NULL' )
    {
        $_REQUEST['replace'] = null;
    }

    if( $_REQUEST['field'] == 'return_percent' )
    {
        $_REQUEST['search'] = $_REQUEST['search']/100;
    }
    
    if( $_REQUEST['set_field'] == 'return_percent' )
    {
        $_REQUEST['replace'] = $_REQUEST['replace']/100;
    }
    
    $u->AddSet($_REQUEST['set_field'], $_REQUEST['replace']);
    $u->AddWhere($_REQUEST['field'], $search_type, $_REQUEST['search']);
    
    $replacements = $DB->Update($u->Generate(), $u->binds);
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$replacements changes have been made"));
}

function tlxAccountRemoveUnconfirmed()
{
    global $DB, $json, $C;
    
    $removed = DeleteUnconfirmed();
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$removed unconfirmed accounts have been removed"));
}

function tlxRebuild()
{
    global $DB, $json, $C;
    
    VerifyAdministrator(TRUE);
    
    shell_exec("{$C['php_cli']} cron.php --rebuild >/dev/null 2>&1 &");

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'Page building has been started in the background - this may take several minutes to complete'));
}

function tlxCommentsSearch()
{
    global $DB, $json, $C;
    
    $out =& GenericSearch('tlx_account_comments', 'comments-search-tr.php', 'tlxCommentsSearchCallback');
      
    echo $json->encode($out);
}

function tlxCommentsSearchCallback(&$select)
{
    if( count($_REQUEST['status']) == 1 )
    {
        $select->AddWhere('status', ST_MATCHES, $_REQUEST['status'][0]);
    }
    
    if( $_REQUEST['field'] == 'comment' && $_REQUEST['search_type'] != ST_EMPTY )
    {
        $select->AddFulltextWhere('comment', $_REQUEST['search'], TRUE);
        return TRUE;
    }
    
    return FALSE;
}

function tlxCommentDelete()
{
    global $json, $DB;
    
    VerifyPrivileges(P_COMMENT_REMOVE, TRUE);

    if( !is_array($_REQUEST['comment_id']) )
    {
        $_REQUEST['comment_id'] = array($_REQUEST['comment_id']);
    }
    
    foreach($_REQUEST['comment_id'] as $comment_id)
    {       
        $DB->Update('DELETE FROM `tlx_account_comments` WHERE `comment_id`=?', array($comment_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected comments have been deleted'));
}

function tlxCommentApprove()
{
    global $json, $DB;
    
    VerifyPrivileges(P_COMMENT_ADD, TRUE);

    if( !is_array($_REQUEST['comment_id']) )
    {
        $_REQUEST['comment_id'] = array($_REQUEST['comment_id']);
    }
    
    foreach($_REQUEST['comment_id'] as $comment_id)
    {
        $comment = $DB->Row('SELECT * FROM `tlx_account_comments` WHERE `comment_id`=?', array($comment_id));
        
        if( $comment['status'] == STATUS_PENDING )
        {
            $DB->Update('UPDATE `tlx_account_comments` SET `status`=? WHERE `comment_id`=?', array(STATUS_APPROVED, $comment_id));
        }
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected comments have been approved'));
}

function tlxCommentReject()
{
    global $json, $DB;
    
    VerifyPrivileges(P_COMMENT_REMOVE, TRUE);

    if( !is_array($_REQUEST['comment_id']) )
    {
        $_REQUEST['comment_id'] = array($_REQUEST['comment_id']);
    }
    
    foreach($_REQUEST['comment_id'] as $comment_id)
    {       
        $comment = $DB->Row('SELECT * FROM `tlx_account_comments` WHERE `comment_id`=?', array($comment_id));
        
        if( $comment['status'] == STATUS_PENDING )
        {
            $DB->Update('DELETE FROM `tlx_account_comments` WHERE `comment_id`=?', array($comment_id));
        }
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected comments have been rejected'));
}

function tlxScannerHistorySearch()
{
    global $DB, $json, $C;
    
    $_REQUEST['order'] = 'date_start';
    $_REQUEST['direction'] = 'DESC';
    
    $out =& GenericSearch('tlx_scanner_history', 'accounts-scanner-history-tr.php', 'tlxScannerHistorySelect');
      
    echo $json->encode($out);
}

function tlxScannerHistorySelect(&$select)
{
    $select->AddWhere('config_id', ST_MATCHES, $_REQUEST['config_id']);
    return FALSE;
}

function tlxScannerHistoryClear()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);

    $DB->Update('DELETE FROM `tlx_scanner_history` WHERE `config_id`=?', array($_REQUEST['config_id']));
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The account scanner history for this configuration has been cleared'));
}

function tlxScannerResultsSearch()
{
    global $DB, $json, $C;
       
    $out =& GenericSearch('tlx_scanner_results', 'accounts-scanner-results-tr.php', 'tlxScannerResultsSelect');
      
    echo $json->encode($out);
}

function tlxScannerResultsSelect(&$select)
{
    $select->AddWhere('config_id', ST_MATCHES, $_REQUEST['config_id']);
    return FALSE;
}

function tlxScannerConfigSearch()
{
    global $DB, $json, $C;
    
    $_REQUEST['order'] = 'identifier';
    $_REQUEST['direction'] = 'ASC';
    
    $out =& GenericSearch('tlx_scanner_configs', 'accounts-scanner-tr.php');
      
    echo $json->encode($out);
}

function tlxScannerConfigDelete()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['config_id']) )
    {
        $_REQUEST['config_id'] = array($_REQUEST['config_id']);
    }
    
    foreach($_REQUEST['config_id'] as $config_id)
    {
        $scanner = $DB->Row('SELECT * FROM `tlx_scanner_configs` WHERE `config_id`=?', array($config_id));
        
        if( $scanner['current_status'] != 'Not Running' )
        {
            // Stop the scanner and wait a few seconds
            $DB->Update('UPDATE `tlx_scanner_configs` SET `pid`=0 WHERE `config_id`=?', array($config_id));
            sleep(2);
        }
        
        $DB->Update('DELETE FROM `tlx_scanner_configs` WHERE `config_id`=?', array($config_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected account scanner configurations have been deleted'));
}

function tlxScannerStart()
{
    global $DB, $json, $C;
    
    VerifyAdministrator(TRUE);
    CheckAccessList(TRUE);
       
    shell_exec("{$C['php_cli']} scanner.php " . escapeshellarg($_REQUEST['config_id']) . " >/dev/null 2>&1 &");
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'Your request to start the account scanner has been processed'));
}

function tlxScannerStop()
{
    global $DB, $json, $C;
    
    VerifyAdministrator(TRUE);
    
    $DB->Update('UPDATE `tlx_scanner_configs` SET `pid`=0,`current_status`=? WHERE `config_id`=?', array('Not Running', $_REQUEST['config_id']));
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'Your request to stop the account scanner has been processed'));
}

function tlxScannerStatus()
{
    global $DB, $json, $C;
    
    VerifyAdministrator(TRUE);
    
    $configs = array();
    $result = $DB->Query('SELECT * FROM `tlx_scanner_configs`');
    
    while( $config = $DB->NextRow($result) )
    {
        // Scanner most likely stopped
        if( $config['status_updated'] < time() - 600 )
        {
            $DB->Update('UPDATE `tlx_scanner_configs` SET `current_status`=?,`status_updated`=?,`pid`=? WHERE `config_id`=?',
                        array('Not Running',
                              time(),
                              0,
                              $config['config_id']));
                        
            $config['current_status'] = 'Not Running';
        }
        
        $config['date_last_run'] = ($config['date_last_run'] ? date(DF_SHORT, strtotime($config['date_last_run'])) : '-');
        unset($config['configuration']);
        unset($config['identifier']);
        $configs[] = $config;
    }
    
    $DB->Free($result);
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'configs' => $configs));
}

function tlxPageSearch()
{
    global $DB, $json, $C;
    
    $GLOBALS['categories'] =& $DB->FetchAll('SELECT `category_id`,`name` FROM `tlx_categories`', array(), 'category_id');
    $out =& GenericSearch('tlx_pages', 'pages-tr.php', 'txPageSelect');

    echo $json->encode($out);
}

function tlxPageSelect(&$select)
{
    global $DB;
    
    if( $_REQUEST['field'] == 'tags' && $_REQUEST['search_type'] != ST_EMPTY )
    {
        $select->AddFulltextWhere('tags', $_REQUEST['search'], TRUE);
        return TRUE;
    }
    else if( $_REQUEST['field'] == 'category_id' )
    {
        if( strtolower($_REQUEST['search']) == 'mixed' )
        {
            $select->AddWhere('category_id', ST_NULL, null);
        }
        else
        {
            $csb = new SelectBuilder('*', 'tlx_categories');        
            $csb->AddWhere('name', $_REQUEST['search_type'], $_REQUEST['search'], TRUE);        
            $categories =& $DB->FetchAll($csb->Generate(), $csb->binds, 'category_id');
            
            $select->AddWhere('category_id', ST_IN, join(',', array_keys($categories)));
        }
        
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}

function tlxPageBuild()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);
    
    BuildPages();

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'Your ranking pages are being rebuilt; this may take a few minutes to complete'));
}

function tlxPageDelete()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['page_id']) )
    {
        $_REQUEST['page_id'] = array($_REQUEST['page_id']);
    }
    
    foreach($_REQUEST['page_id'] as $page_id)
    {       
        $DB->Update('DELETE FROM `tlx_pages` WHERE `page_id`=?', array($page_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected ranking pages have been deleted'));
}

function tlxAccountSearch()
{
    global $DB, $json, $C;
    
    VerifyPrivileges(P_ACCOUNT, TRUE);

    $GLOBALS['_fields_'] = array('site_url' => 'Site URL',
                                 'banner_url' => 'Banner URL',
                                 'email' => 'E-mail',
                                 'category_id' => 'Category');
    
    $GLOBALS['_categories_'] =& $DB->FetchAll('SELECT * FROM `tlx_categories`', null, 'category_id');
    $GLOBALS['_rejects_'] =& $DB->FetchAll('SELECT * FROM `tlx_rejections` ORDER BY `identifier`', null, 'email_id');
    $out =& GenericSearch('tlx_accounts', 'accounts-search-tr.php', 'AccountSearchSelect', 'AccountItemCallback');
    
    if( extension_loaded('zlib') && !ini_get('zlib.output_compression') )
    {
        header('Content-Encoding: gzip');
        print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
        echo gzcompress($json->encode($out), 9);
    }
    else
    {
        echo $json->encode($out);
    }
}

function AccountSearchSelect(&$s, $request = null)
{
    global $DB;
    
    if( $request != null )
    {
        $_REQUEST = array_merge($_REQUEST, $request);
    }
    
    $last_hour = gmdate('G', TIME_NOW - 3600);
    $this_hour = gmdate('G', TIME_NOW);
    
    $sorters = array_merge($DB->GetColumns('tlx_accounts', TRUE, TRUE),
                           $DB->GetColumns('tlx_account_fields', TRUE, TRUE),
                           $DB->GetColumns('tlx_account_hourly_stats', TRUE, TRUE),
                           array('username' => '`tlx_accounts`.`username`',
                                 'avg_rating' => '`ratings_total`/`ratings`',
                                 'raw_in_last_hr' => '`raw_in_'.$last_hour.'`', 
                                 'unique_in_last_hr' => '`unique_in_'.$last_hour.'`',
                                 'raw_out_last_hr' => '`raw_out_'.$last_hour.'`',
                                 'unique_out_last_hr' => '`unique_out_'.$last_hour.'`',
                                 'clicks_last_hr' => '`clicks_'.$last_hour.'`',
                                 'raw_in_this_hr' => '`raw_in_'.$this_hour.'`',
                                 'unique_in_this_hr' => '`unique_in_'.$this_hour.'`',
                                 'raw_out_this_hr' => '`raw_out_'.$this_hour.'`',
                                 'unique_out_this_hr' => '`unique_out_'.$this_hour.'`',
                                 'clicks_this_hr' => '`clicks_'.$this_hour.'`'));
                                                         
    if( preg_match('~(.*?)_days_(\d+)~', $_REQUEST['order'], $matches) )
    {
        $sorters[$_REQUEST['order']] = "SUM(`{$matches[1]}`)";
    }
    
    $s = new SelectBuilder('*,' . $sorters[$_REQUEST['order']] . ' AS `sorter`', 'tlx_accounts');
    
    $fulltext = array('title,description,keywords');
    $user = $DB->GetColumns('tlx_account_fields');
    
    if( $_REQUEST['field'] == 'avg_rating' )
    {
        $_REQUEST['field'] = 'ratings_total/ratings';
    }
    
    if( $_REQUEST['field'] == 'return_percent' )
    {
        $_REQUEST['search'] = $_REQUEST['search'] / 100;
    }
    
    // Special handling of date searches (transform MM-DD-YYYY to YYYY-MM-DD format)
    if( preg_match('~^date_~', $_REQUEST['field']) )
    {
        $_REQUEST['search'] = trim($_REQUEST['search']);
        
        if( preg_match('~^(\d\d)-(\d\d)-(\d\d\d\d)$~', $_REQUEST['search'], $date) )
        {
            $_REQUEST['search_type'] = ST_BETWEEN;
            $_REQUEST['search'] = "{$date[3]}-{$date[1]}-{$date[2]} 00:00:00,{$date[3]}-{$date[1]}-{$date[2]} 23:59:59";
        }
        else if( preg_match('~^\d\d\d\d-\d\d-\d\d$~', $_REQUEST['search']) )
        {
            $_REQUEST['search_type'] = ST_BETWEEN;
            $_REQUEST['search'] = "{$_REQUEST['search']} 00:00:00,{$_REQUEST['search']} 23:59:59";
        }
        
        $_REQUEST['search'] = preg_replace('~(\d\d)-(\d\d)-(\d\d\d\d)~', '\3-\1-\2', $_REQUEST['search']);
    }
    
    if( preg_match('~_days_\d+~', $_REQUEST['order']) )
    {
        $s->AddJoin('tlx_accounts', 'tlx_account_daily_stats', 'LEFT', 'username');
        $s->AddGroup('tlx_accounts.username');
        $s->AddWhereString("`date_stats` >= DATE_ADD('".MYSQL_CURDATE."', INTERVAL -{$matches[2]} DAY)");
    }
    else if( preg_match('~(raw_|unique_|clicks_)~', $_REQUEST['order']) )
    {
        $s->AddJoin('tlx_accounts', 'tlx_account_hourly_stats', '', 'username');
    } 
    
    if( in_array($_REQUEST['field'], $user) || in_array($_REQUEST['order'], $user) )
    {
        $s->AddJoin('tlx_accounts', 'tlx_account_fields', '', 'username');
    }
    
    if( in_array($_REQUEST['field'], $user) )
    {
        $s->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    else if( in_array($_REQUEST['field'], $fulltext) )
    {
        $s->AddFulltextWhere($_REQUEST['field'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    else
    {
        $s->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    
    $s_checked = count($_REQUEST['status']);
    if( $s_checked > 0 && $s_checked < 3 )
    {
        $s->AddWhere('status', ST_IN, join(',', $_REQUEST['status']));
    }
    
    if( isset($_REQUEST['locked']) )
    {
        $s->AddWhere('locked', ST_MATCHES, 1);
    }
    
    if( isset($_REQUEST['disabled']) )
    {
        $s->AddWhere('disabled', ST_MATCHES, 1);
    }
    
    if( isset($_REQUEST['edited']) )
    {
        $s->AddWhere('edited', ST_MATCHES, 1);
    }
    if( count($_REQUEST['categories']) > 0 && !in_array('', $_REQUEST['categories']) )
    {
        $s->AddWhere('category_id', isset($_REQUEST['cat_exclude']) ? ST_NOT_IN : ST_IN, join(',', $_REQUEST['categories']));
    }
    
    $_REQUEST['order'] = 'sorter';
    $_REQUEST['order_next'] = 'tlx_accounts.username';
    
    return TRUE;
}

function AccountItemCallback(&$item)
{
    global $C, $DB;
    
    $item['tlx_accounts.username'] = $item['username'];
    $item = array_merge($item, $DB->Row('SELECT * FROM `tlx_account_fields` WHERE `username`=?', array($item['username'])));
    $item = array_merge($item, $DB->Row('SELECT * FROM `tlx_account_hourly_stats` WHERE `username`=?', array($item['username'])));
    $item['return_percent'] *= 100;
    $item['banner_width'] = ($item['banner_width'] ? $item['banner_width'] : '');
    $item['banner_height'] = ($item['banner_height'] ? $item['banner_height'] : '');
    $item['date_scanned'] = ($item['date_scanned'] ? date(DF_SHORT, strtotime($item['date_scanned'])) : '-');
    $item['date_added'] = ($item['date_added'] ? date(DF_SHORT, strtotime($item['date_added'])) : '-');
    $item['date_activated'] = ($item['date_activated'] ? date(DF_SHORT, strtotime($item['date_activated'])) : '-');
    $item['icons'] =& $DB->FetchAll('SELECT * FROM `tlx_account_icons` WHERE `username`=?', array($item['username']));
    $item['comments'] = $DB->Count('SELECT COUNT(*) FROM `tlx_account_comments` WHERE `username`=?', array($item['username']));
    
    if( preg_match(RE_DATETIME, $item['sorter']) )
    {
        $item['sorter'] = ($item['sorter'] ? date(DF_SHORT, strtotime($item['sorter'])) : '-');
    }
    else if( is_numeric($item['sorter']) )
    {
        $item['sorter'] = number_format($item['sorter'], 0, $C['dec_point'], $C['thousands_sep']);
    }    
    
    $icons = array();
    foreach( $item['icons'] as $icon )
    {
        $icons[] = $icon['icon_id'];
    }
    
    $item['icons'] = join(', ', $icons);
}

function tlxAccountDelete()
{
    global $DB, $json, $C;
   
    VerifyPrivileges(P_ACCOUNT_REMOVE, TRUE);
    
    if( !is_array($_REQUEST['username']) )
    {
        $_REQUEST['username'] = array($_REQUEST['username']);
    }
    
    foreach($_REQUEST['username'] as $username)
    {
        DeleteAccount($username);
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS));
}

function tlxAccountAction()
{
    global $DB, $json, $C;
   
    VerifyPrivileges(P_ACCOUNT_MODIFY, TRUE);
    
    if( !is_array($_REQUEST['username']) )
    {
        $_REQUEST['username'] = array($_REQUEST['username']);
    }
    
    switch( $_REQUEST['w'] )
    {
        case 'enable':
            $action = 'enabled';
            $query = 'UPDATE `tlx_accounts` SET `disabled`=0 WHERE `username`=?';
            break;
            
        case 'disable':
            $action = 'disabled';
            $query = 'UPDATE `tlx_accounts` SET `disabled`=1 WHERE `username`=?';
            break;
            
        case 'lock':
            $action = 'locked';
            $query = 'UPDATE `tlx_accounts` SET `locked`=1 WHERE `username`=?';
            break;
            
        case 'unlock':
            $action = 'unlocked';
            $query = 'UPDATE `tlx_accounts` SET `locked`=0 WHERE `username`=?';
            break;
    }
    
    foreach($_REQUEST['username'] as $username)
    {
        $DB->Query($query, array($username));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "The selected accounts have been $action"));
}

function tlxAccountProcess()
{
    global $DB, $json, $C, $L;
    
    VerifyPrivileges(P_ACCOUNT_MODIFY, TRUE);
    
    if( !is_array($_REQUEST['username']) )
    {
        $_REQUEST['username'] = array($_REQUEST['username']);
    }
    
    $t = new Template();
    $t->assign_by_ref('config', $C);
    
    if( $_REQUEST['w'] == 'reject' )
    {
        $action = 'rejected';
        $rejections =& $DB->FetchAll('SELECT * FROM `tlx_rejections`', null, 'email_id');
        
        foreach($_REQUEST['username'] as $username)
        {
            $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($username));
            DeleteAccount($account['username'], $account);

            // Send rejection e-mail
            if( isset($rejections[$_REQUEST['reject'][$username]]) )
            {
                $t->assign_by_ref('account', $account);
                SendMail($account['email'], $rejections[$_REQUEST['reject'][$username]]['compiled'], $t, FALSE);
            }
        }
    }
    else if( $_REQUEST['w'] == 'approve' )
    {
        $action = 'approved';
        
        foreach($_REQUEST['username'] as $username)
        {
            $DB->Update('UPDATE `tlx_accounts` SET `status`=?,`date_activated`=? WHERE `username`=?', array(STATUS_ACTIVE, MYSQL_NOW, $username));
            $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($username));
            
            if( $C['email_new_accounts'] )
            {
                $account['password'] = $L['ENCRYPTED_PASSWORD'];
                $t->assign_by_ref('account', $account);
                SendMail($account['email'], 'email-account-added.tpl', $t);
            }
        }
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS));
}

function tlxAccountEditProcess()
{
    global $DB, $json, $C, $L;

    VerifyPrivileges(P_ACCOUNT_MODIFY, TRUE);

    if( $_REQUEST['w'] == 'reject' )
    {
        $DB->Update('UPDATE `tlx_accounts` SET `edited`=0,`edit_data`=NULL WHERE `username`=?', array($_REQUEST['username']));
    }
    else if( $_REQUEST['w'] == 'approve' )
    {        
        $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['username']));
        $edits = unserialize(base64_decode($account['edit_data']));
        
        if( $edits )
        {
            if( $edits['banner_data'] )
            {
                $parsed = parse_url($edits['banner_url_local']);
                
                if( $parsed !== FALSE )
                {
                    $banner_file = SafeFilename("{$C['banner_dir']}/".basename($parsed['path']), FALSE);
                    FileWrite($banner_file, $edits['banner_data']);
                }
                
                unset($edits['banner_data']);
            }
            
			$user_fields = $DB->GetColumns('tlx_account_fields');
            $default_updates = array("`edited`=?", "`edit_data`=?");
            $default_updates_binds = array(0, null);
            $user_updates = array();
            $user_updates_binds = array();
            foreach( $edits as $name => $value )
            {
                $name = str_replace('`', '\`', $name);
                $value = mysql_real_escape_string($value, $DB->handle);

                if( in_array($name, $user_fields) )
                {
                    $user_updates[] = "`$name`=?";
                    $user_updates_binds[] = $value;
                }
                else
                {
                    $default_updates[] = "`$name`=?";
                    $default_updates_binds[] = $value;
                }
            }

            $user_updates_binds[] = $_REQUEST['username'];
            $default_updates_binds[] = $_REQUEST['username'];

            if( count($user_updates) )
            {
                $DB->Update('UPDATE `tlx_account_fields` SET ' . join(',', $user_updates) . ' WHERE `username`=?', $user_updates_binds);
            }

            $DB->Update('UPDATE `tlx_accounts` SET ' . join(',', $default_updates) . ' WHERE `username`=?', $default_updates_binds);
        }
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS));
}

function tlxCategorySearch()
{
    global $DB, $json, $C;
    
    $out =& GenericSearch('tlx_categories', 'categories-tr.php');
      
    echo $json->encode($out);
}

function tlxCategoryDelete()
{
    global $DB, $json, $C;
   
    VerifyPrivileges(P_CATEGORY_REMOVE, TRUE);
    
    if( !is_array($_REQUEST['category_id']) )
    {
        $_REQUEST['category_id'] = array($_REQUEST['category_id']);
    }
    
    foreach($_REQUEST['category_id'] as $category_id)
    {
        $accounts =& $DB->FetchAll('SELECT * FROM `tlx_accounts` WHERE `category_id`=?', array($category_id));
        
        foreach($accounts as $account)
        {
            DeleteAccount($username);
        }
        
        $DB->Update('DELETE FROM `tlx_categories` WHERE `category_id`=?', array($category_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected categories have been deleted'));
}

function tlxDatabaseRawQuery()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);
    CheckAccessList(TRUE);

    if( preg_match('~^SELECT COUNT~i', $_REQUEST['query']) )
    {
        $affected = $DB->Count($_REQUEST['query']);
    }
    else if( preg_match('~^SELECT~i', $_REQUEST['query']) )
    {
        $result = $DB->Query($_REQUEST['query']);
        $affected = $DB->NumRows($result);
        $DB->Free($result);
    }
    else
    {
        $affected = $DB->Update($_REQUEST['query']);
    }
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "SQL query has been executed; a total of $affected rows were affected by this query"));
}

function tlxAccountFieldSearch()
{
    global $DB, $json, $C;
    
    $out =& GenericSearch('tlx_account_field_defs', 'account-fields-tr.php');
      
    echo $json->encode($out);
}

function tlxAccountFieldDelete()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['field_id']) )
    {
        $_REQUEST['field_id'] = array($_REQUEST['field_id']);
    }
    
    foreach($_REQUEST['field_id'] as $field_id)
    {
        $field = $DB->Row('SELECT * FROM `tlx_account_field_defs` WHERE `field_id`=?', array($field_id));
        $DB->Update("ALTER TABLE `tlx_account_fields` DROP COLUMN #", array($field['name']));
        $DB->Update('DELETE FROM `tlx_account_field_defs` WHERE `field_id`=?', array($field_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected user defined account fields have been deleted'));
}

function tlxIconSearch()
{
    global $DB, $json, $C;
    
    $out =& GenericSearch('tlx_icons', 'icons-tr.php');
      
    echo $json->encode($out);
}

function tlxIconDelete()
{
    global $DB, $json, $C;
    
    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['icon_id']) )
    {
        $_REQUEST['icon_id'] = array($_REQUEST['icon_id']);
    }
    
    foreach($_REQUEST['icon_id'] as $icon_id)
    {       
        $DB->Update('DELETE FROM `tlx_icons` WHERE `icon_id`=?', array($icon_id));
    }
    
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected icons have been deleted'));
}

function tlxRejectionTemplateDelete()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['email_id']) )
    {
        $_REQUEST['email_id'] = array($_REQUEST['email_id']);
    }
    
    foreach($_REQUEST['email_id'] as $email_id)
    {
        $DB->Update('DELETE FROM `tlx_rejections` WHERE `email_id`=?', array($email_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected rejection e-mails have been deleted'));
}

function tlxRejectionTemplateSearch()
{
    global $DB, $json, $C;
        
    $out =& GenericSearch('tlx_rejections', 'rejections-tr.php', null, 'tlxRejectionTemplateItem');
      
    echo $json->encode($out);
}

function tlxRejectionTemplateItem(&$item)
{
    $item['message'] = array();
    IniParse(html_entity_decode($item['plain']), FALSE, $item['message']);
}

function tlxRegexTest()
{
    global $json;
    
    $out = array('status' => JSON_SUCCESS, 'matches' => 'No', 'matched' => '');
    
    if( preg_match("~({$_REQUEST['regex']})~i", $_REQUEST['string'], $matches) )
    {
        $out['matches'] = 'Yes';
        $out['matched'] = $matches[0];
    }
    
    ArrayHSC($out);
    
    echo $json->encode($out);
}

function tlxBlacklistSearch()
{
    global $DB, $json, $C;
       
    $out =& GenericSearch('tlx_blacklist', 'blacklist-tr.php', 'tlxBlacklistSelect');
      
    echo $json->encode($out);
}

function tlxBlacklistSelect(&$select)
{
    $select->AddWhere('type', ST_MATCHES, $_REQUEST['type'], TRUE);
    return FALSE;
}

function tlxBlacklistDelete()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['blacklist_id']) )
    {
        $_REQUEST['blacklist_id'] = array($_REQUEST['blacklist_id']);
    }
    
    foreach($_REQUEST['blacklist_id'] as $blacklist_id)
    {
        $DB->Update('DELETE FROM `tlx_blacklist` WHERE `blacklist_id`=?', array($blacklist_id));
    }
        
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected blacklist items have been deleted'));
}

function tlxAdministratorSearch()
{
    global $DB, $json, $C;
       
    $out =& GenericSearch('tlx_administrators', 'administrators-tr.php');
      
    echo $json->encode($out);
}

function tlxAdministratorDelete()
{
    global $json, $DB;
    
    VerifyAdministrator(TRUE);
    
    if( !is_array($_REQUEST['username']) )
    {
        $_REQUEST['username'] = array($_REQUEST['username']);
    }
    
    // No deleting your own account
    if( in_array($_SERVER['REMOTE_USER'], $_REQUEST['username']) )
    {
        echo $json->encode(array('status' => JSON_FAILURE, 'message' => 'You cannot delete your own account'));
        exit;
    }
    
    foreach($_REQUEST['username'] as $username)
    {
        $DB->Update('DELETE FROM `tlx_administrators` WHERE `username`=?', array($username));
    }
           
    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected administrator accounts have been deleted'));
}

function &GenericSearch($table, $files, $select_callback = null, $item_callback = null)
{
    global $C, $DB, $BLIST_TYPES, $WLIST_TYPES, $ANN_LOCATIONS;
    
    $out = array('status' => JSON_SUCCESS, 'html' => '', 'pagination' => $GLOBALS['DEFAULT_PAGINATION'], 'pagelinks' => '');
        
    $per_page = isset($_REQUEST['per_page']) && $_REQUEST['per_page'] > 0 ? $_REQUEST['per_page'] : 20;
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;       
    $select = new SelectBuilder('*', $table);
    $override = FALSE;
    
    if( function_exists($select_callback) )
    {
        $override = $select_callback($select);
    }    
    
    if( !$override )
    {    
        $select->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    
    $select->AddOrder($_REQUEST['order'], $_REQUEST['direction']);

    if( !empty($_REQUEST['order_next']) )
    {
        $select->AddOrder($_REQUEST['order_next'], $_REQUEST['direction_next']);
    }
    
    $result = $DB->QueryWithPagination($select->Generate(), $select->binds, $page, $per_page);
    
    $out['pagination'] = $result;
    $out['pagelinks'] = PageLinks($result);
    
    if( $result['result'] )
    {
        if( !is_array($files) )
        {
            $files = array($files);
        }
        
        $row_html = '';
        foreach( $files as $file )
        {
            $row_html .= file_get_contents("includes/$file");
        }
        
        while( $item = $DB->NextRow($result['result']) )
        {
            ArrayHSC($item);
            
            if( function_exists($item_callback) )
            {
                $item_callback($item);
            }
           
            ob_start();
            eval('?>' . $row_html);
            $out['html'] .= ob_get_contents();
            ob_end_clean();
        }
        
        $DB->Free($result['result']);
    }
    
    return $out;
}

function AjaxError($code, $string, $file, $line)
{
    global $json;
    
    $reporting = error_reporting();
    
    if( $reporting == 0 || !($code & $reporting) )
    {
        return;
    }
 
    $error = array();
   
    $error['message'] = "$string on line $line of " . basename($file);
    $error['status'] = JSON_FAILURE;
    
    echo $json->encode($error);

    exit;
}
?>