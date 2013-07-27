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

// Globals
$GLOBALS['VERSION'] = '1.0.0-SS';
$GLOBALS['RELEASE'] = 'November 21, 2010 09:09';
$GLOBALS['BASE_DIR'] = realpath(dirname(__FILE__) . '/..');
$GLOBALS['ADMIN_DIR'] = "$BASE_DIR/admin";
$GLOBALS['FILE_PERMISSIONS'] = 0666;
$GLOBALS['DEFAULT_PAGINATION'] = array('total' => 0, 'pages' => 0, 'page' => 1, 'limit' => 0, 'start' => 0, 'end' => 0, 'prev' => 0, 'next' => 0);
$GLOBALS['L'] = array();
$GLOBALS['DEBUG'] = FALSE;


// Setup error reporting
if( !defined('E_STRICT') ) define('E_STRICT', 2048);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
@set_time_limit(0);
@ini_set('pcre.backtrack_limit', 1000000); // PHP 5 sets limits when running regex on large strings, so increase the default
set_error_handler('Error');
@set_magic_quotes_runtime(0);
if( function_exists('date_default_timezone_set') )
{
    date_default_timezone_set('America/Chicago');
}
register_shutdown_function('Shutdown');


// Load the language file
if( file_exists("{$GLOBALS['BASE_DIR']}/includes/language.php") )
{
    require_once("{$GLOBALS['BASE_DIR']}/includes/language.php");
}


// Load variables
if( file_exists("{$GLOBALS['BASE_DIR']}/includes/config.php") )
{
    require_once("{$GLOBALS['BASE_DIR']}/includes/config.php");
}


// Statuses
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_UNCONFIRMED', 'unconfirmed');
define('STATUS_ACTIVE', 'active');


// Notifications
define('E_ACCOUNT_ADDED',  0x00000001);
define('E_ACCOUNT_EDITED', 0x00000002);


// Field types
define('FT_CHECKBOX', 'Checkbox');
define('FT_TEXTAREA', 'Textarea');
define('FT_TEXT', 'Text');
define('FT_SELECT', 'Select');


// Date formats
define('DF_DATETIME', 'Y-m-d H:i:s');
define('DF_DATE', 'Y-m-d');
define('DF_SHORT', 'm-d-Y h:ia');


// Mail types
define('MT_PHP', 0);
define('MT_SENDMAIL', 1);
define('MT_SMTP', 2);


// Search types
define('ST_CONTAINS', 'contains');
define('ST_MATCHES', 'matches');
define('ST_STARTS', 'starts');
define('ST_BETWEEN', 'between');
define('ST_GREATER', 'greater');
define('ST_GREATER_EQ', 'greatereq');
define('ST_LESS', 'less');
define('ST_LESS_EQ', 'lesseq');
define('ST_EMPTY', 'empty');
define('ST_ANY', 'any');
define('ST_IN', 'in');
define('ST_NOT_IN', 'not_in');
define('ST_NOT_EMPTY', 'not_empty');
define('ST_NULL', 'null');
define('ST_NOT_MATCHES', 'not_matches');
define('ST_NOT_NULL', 'not_null');


// Other
define('MYSQL_EXPIRES', '2000-01-01 00:00:00');
define('MYSQL_NOW', gmdate(DF_DATETIME, TimeWithTz()));
define('MYSQL_CURDATE', gmdate(DF_DATE, TimeWithTz()));
define('TIME_NOW', TimeWithTz());
define('RE_DATETIME', '~^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$~');
define('JSON_SUCCESS', 'Success');
define('JSON_FAILURE', 'Failure');


// Blacklist types
$GLOBALS['BLIST_TYPES'] = array('submit_ip' => 'Submitter IP',
                                'email' => 'E-mail Address',
                                'url' => 'Domain/URL',
                                'domain_ip' => 'Domain IP',
                                'word' => 'Word',
                                'html' => 'HTML',
                                'headers' => 'HTTP Headers',
                                'dns' => 'DNS Server');

// Image file extensions
$GLOBALS['IMAGE_EXTENSIONS'] = array('',
                                     'gif',
                                     'jpg',
                                     'png');

function ProcessHourlyStats()
{
    global $DB, $C;

    $now = TIME_NOW;
    $datetime = gmdate('Y-m-d-G', $now);

    // Lock tables to prevent multiple updates
    $DB->Query('LOCK TABLES `tlx_stored_values` WRITE, `tlx_account_hourly_stats` WRITE, `tlx_ip_log_in` WRITE, `tlx_ip_log_out` WRITE, `tlx_ip_log_clicks` WRITE');

    $last_updates = unserialize(GetValue('last_updates'));

    // Determine if it is time for the hourly update
    if( $last_updates['hourly'] === null )
    {
        $last_updates['hourly'] = $datetime;
        StoreValue('last_updates', serialize($last_updates));
    }
    else if( $last_updates['hourly'] != $datetime )
    {
        list($this_year, $this_month, $this_day, $this_hour) = explode('-', $datetime);
        list($last_year, $last_month, $last_day, $last_hour) = explode('-', $last_updates['hourly']);

        $hour_diff = (mktime($this_hour, 0, 0, $this_month, $this_day, $this_year) - mktime($last_hour, 0, 0, $last_month, $last_day, $last_year)) / 3600;

        if( $hour_diff >= 24 )
        {
            $hour_fields = array();
            foreach( range(0,23) as $index )
            {
                array_push($hour_fields, "`raw_in_$index`=0", "`unique_in_$index`=0", "`raw_out_$index`=0", "`unique_out_$index`=0", "`clicks_$index`=0");
            }

            $DB->Update('UPDATE `tlx_account_hourly_stats` SET ' .
                        join(',', $hour_fields) . ',' .
                        '`raw_in_total`=0,' .
                        '`unique_in_total`=0,' .
                        '`raw_out_total`=0,' .
                        '`unique_out_total`=0,' .
                        '`clicks_total`=0');
        }
        else
        {
            $hours = range($this_hour-$hour_diff+1,$this_hour);
            array_walk($hours, create_function('&$value,$key', 'if( $value < 0 ) $value += 24;'));

            $hour_fields = array();
            foreach( $hours as $index )
            {
                array_push($hour_fields, "`raw_in_$index`=0", "`unique_in_$index`=0", "`raw_out_$index`=0", "`unique_out_$index`=0", "`clicks_$index`=0");
            }

            $DB->Update('UPDATE `tlx_account_hourly_stats` SET ' .
                        join(',', $hour_fields) . ',' .
                        '`raw_in_total`=`raw_in_0`+`raw_in_1`+`raw_in_2`+`raw_in_3`+`raw_in_4`+`raw_in_5`+`raw_in_6`+`raw_in_7`+`raw_in_8`+`raw_in_9`+`raw_in_10`+`raw_in_11`+`raw_in_12`+`raw_in_13`+`raw_in_14`+`raw_in_15`+`raw_in_16`+`raw_in_17`+`raw_in_18`+`raw_in_19`+`raw_in_20`+`raw_in_21`+`raw_in_22`+`raw_in_23`,' .
                        '`unique_in_total`=`unique_in_0`+`unique_in_1`+`unique_in_2`+`unique_in_3`+`unique_in_4`+`unique_in_5`+`unique_in_6`+`unique_in_7`+`unique_in_8`+`unique_in_9`+`unique_in_10`+`unique_in_11`+`unique_in_12`+`unique_in_13`+`unique_in_14`+`unique_in_15`+`unique_in_16`+`unique_in_17`+`unique_in_18`+`unique_in_19`+`unique_in_20`+`unique_in_21`+`unique_in_22`+`unique_in_23`,' .
                        '`raw_out_total`=`raw_out_0`+`raw_out_1`+`raw_out_2`+`raw_out_3`+`raw_out_4`+`raw_out_5`+`raw_out_6`+`raw_out_7`+`raw_out_8`+`raw_out_9`+`raw_out_10`+`raw_out_11`+`raw_out_12`+`raw_out_13`+`raw_out_14`+`raw_out_15`+`raw_out_16`+`raw_out_17`+`raw_out_18`+`raw_out_19`+`raw_out_20`+`raw_out_21`+`raw_out_22`+`raw_out_23`,' .
                        '`unique_out_total`=`unique_out_0`+`unique_out_1`+`unique_out_2`+`unique_out_3`+`unique_out_4`+`unique_out_5`+`unique_out_6`+`unique_out_7`+`unique_out_8`+`unique_out_9`+`unique_out_10`+`unique_out_11`+`unique_out_12`+`unique_out_13`+`unique_out_14`+`unique_out_15`+`unique_out_16`+`unique_out_17`+`unique_out_18`+`unique_out_19`+`unique_out_20`+`unique_out_21`+`unique_out_22`+`unique_out_23`,' .
                        '`clicks_total`=`clicks_0`+`clicks_1`+`clicks_2`+`clicks_3`+`clicks_4`+`clicks_5`+`clicks_6`+`clicks_7`+`clicks_8`+`clicks_9`+`clicks_10`+`clicks_11`+`clicks_12`+`clicks_13`+`clicks_14`+`clicks_15`+`clicks_16`+`clicks_17`+`clicks_18`+`clicks_19`+`clicks_20`+`clicks_21`+`clicks_22`+`clicks_23`');
        }

        // Clear old IP data
        $DB->Update('DELETE FROM `tlx_ip_log_in` WHERE `last_visit` <= DATE_ADD(NOW(), INTERVAL -24 HOUR)');
        $DB->Update('DELETE FROM `tlx_ip_log_out` WHERE `last_visit` <= DATE_ADD(NOW(), INTERVAL -24 HOUR)');
        $DB->Update('DELETE FROM `tlx_ip_log_clicks` WHERE `last_visit` <= DATE_ADD(NOW(), INTERVAL -24 HOUR)');

        // Update proxy and robot percentage
        $result = $DB->Query('SELECT `username`,SUM(`proxy`*`raw_in`)/SUM(`raw_in`) AS `proxy_percent`,SUM(`robot`*`raw_in`)/SUM(`raw_in`) AS `robot_percent` FROM `tlx_ip_log_in` GROUP BY `username`');
        while( $row = $DB->NextRow($result) )
        {
            $DB->Update('UPDATE `tlx_account_hourly_stats` SET `proxy_percent`=?,`robot_percent`=? WHERE `username`=?', array($row['proxy_percent'], $row['robot_percent'], $row['username']));
        }
        $DB->Free($result);

        // Update the hourly value
        $last_updates['hourly'] = $datetime;
        StoreValue('last_updates', serialize($last_updates));
    }

    $DB->Query('UNLOCK TABLES');
}

function ProcessDailyStats()
{
    global $C, $DB;

    $now = TIME_NOW;
    $today = gmdate('Y-m-d', $now);

    // Lock tables to prevent multiple updates
    $DB->Query('LOCK TABLES `tlx_stored_values` WRITE, `tlx_daily_stats` WRITE, `tlx_account_daily_stats` WRITE, `tlx_account_hourly_stats` WRITE, `tlx_accounts` WRITE');

    $last_updates = unserialize(GetValue('last_updates'));

    // Determine if it is time for the daily update
    if( $last_updates['daily'] != $today )
    {
        // Generate global stats for the day
        $DB->Update('INSERT INTO ' .
                    '`tlx_daily_stats` ' .
                    'SELECT ' .
                    '?, ' .
                    'SUM(`raw_in_total`), ' .
                    'SUM(`unique_in_total`), ' .
                    'SUM(`raw_out_total`), ' .
                    'SUM(`unique_out_total`), ' .
                    'SUM(`clicks_total`) ' .
                    'FROM ' .
                    '`tlx_account_hourly_stats`', array($last_updates['daily']));

        // Generate stats for each account for the day
        $DB->Update('INSERT INTO ' .
                    '`tlx_account_daily_stats` ' .
                    'SELECT ' .
                    '`username`, ' .
                    '?, ' .
                    '`raw_in_total`, ' .
                    '`unique_in_total`, ' .
                    '`raw_out_total`, ' .
                    '`unique_out_total`, ' .
                    '`clicks_total` ' .
                    'FROM ' .
                    '`tlx_account_hourly_stats`', array($last_updates['daily']));

        // Increment inactive count
        $DB->Update('UPDATE `tlx_accounts` JOIN `tlx_account_daily_stats` USING (`username`) SET `inactive`=`inactive`+1 WHERE `date_stats`=? AND `raw_in`=0', array($last_updates['daily']));

        // Remove global stats for days over a year old
        $DB->Update('DELETE FROM `tlx_daily_stats` WHERE `date_stats` < DATE_ADD(?, INTERVAL -365 DAY)', array("$today 00:00:00"));

        // Remove account stats for days over a year old
        $DB->Update('DELETE FROM `tlx_account_daily_stats` WHERE `date_stats` < DATE_ADD(?, INTERVAL -365 DAY)', array("$today 00:00:00"));

        // Update the daily value
        $last_updates['daily'] = $today;
        StoreValue('last_updates', serialize($last_updates));
    }

    $DB->Query('UNLOCK TABLES');
}

function SorterLastHours($field, $hours)
{
    $hour_now = gmdate('G', TIME_NOW);
    $fields = array();

    for( $i = $hour_now-1; $i > $hour_now-1-$hours; $i-- )
    {
         $fields[] = str_replace('%%', ($i % 24 < 0 ?  24 - abs($i % 24) : $i % 24), "`$field`");
    }

    return join('+', $fields);
}

function RebuildPages($callback = null)
{
    global $DB, $C, $L;

    // One at a time please
    $wouldblock = FALSE;
    $fd = fopen("{$GLOBALS['BASE_DIR']}/data/_build_lock", 'w');
    flock($fd, LOCK_EX|LOCK_NB, $wouldblock);
    if( $wouldblock ) return;


    if( !preg_match('~^\d\d\d$~', $C['page_permissions']) )
    {
        $C['page_permissions'] = $GLOBALS['FILE_PERMISSIONS'];
    }
    else
    {
        $C['page_permissions'] = octdec('0'.$C['page_permissions']);
    }

    // Cache items
    $GLOBALS['ICON_CACHE'] =& $DB->FetchAll('SELECT * FROM `tlx_icons`', null, 'icon_id');
    $GLOBALS['CATEGORY_CACHE'] =& $DB->FetchAll('SELECT * FROM `tlx_categories`', null, 'category_id');

    // Count total thumbs and galleries
    $GLOBALS['_total_accounts'] = $DB->Count("SELECT COUNT(*) FROM `tlx_accounts` WHERE `status`='active' AND `disabled`=0");

    // Update the last rank information and clear the rank tables
    $DB->Update('UPDATE `tlx_accounts` LEFT JOIN `tlx_account_ranks` USING (`username`) SET `last_rank`=`rank`');
    $DB->Update('UPDATE `tlx_accounts` LEFT JOIN `tlx_account_category_ranks` USING (`username`) SET `last_category_rank`=`category_rank`');
    $DB->Update('DELETE FROM `tlx_account_ranks`');
    $DB->Update('DELETE FROM `tlx_account_category_ranks`');

    // Build each page
    $result = $DB->Query('SELECT * FROM `tlx_pages` ORDER BY `build_order`');
    while( $page = $DB->NextRow($result) )
    {
        if( $callback )
        {
            call_user_func($callback, $page);
        }

        BuildPage($page);
    }
    $DB->Free($result);

    StoreValue('last_rebuild', TIME_NOW);

    flock($fd, LOCK_UN);
    fclose($fd);
    @chmod("{$GLOBALS['BASE_DIR']}/data/_build_lock", 0666);
}

function BuildPage($page)
{
    global $DB, $C, $L;

    $t = new Template();
    $t->assign_by_ref('this_page', $page);
    $t->assign_by_ref('config', $C);
    $t->assign_by_ref('page_category', $GLOBALS['CATEGORY_CACHE'][$page['category_id']]);
    $t->assign('total_accounts', $GLOBALS['_total_accounts']);

    $fd = fopen("{$C['document_root']}/{$page['filename']}", 'w');
    flock($fd, LOCK_EX);

    // Parse the template
    $generated = $t->parse_compiled($page['compiled']);
    fwrite($fd, trim($generated));
    flock($fd, LOCK_UN);
    fclose($fd);
    @chmod("{$C['document_root']}/{$page['filename']}", $C['page_permissions']);

    $t->cleanup();
}

function &LoadAccounts($query, $ranks, &$fillranks, $storeranks = FALSE, $storecatranks = FALSE, $stats = '')
{
    global $DB, $L, $C;

    $accounts = array();

    list($rank, $end) = explode('-', $ranks);
    $result = $DB->Query($query);
    while( $account = $DB->NextRow($result) )
    {
        $account['rank'] = $rank++;
        PopulateAccountInfo($account, $stats);

        $accounts[] = $account;

        if( $storeranks )
        {
            $DB->Update('REPLACE INTO `tlx_account_ranks` VALUES (?,?)', array($account['username'], $account['rank']));
        }

        if( $storecatranks )
        {
            $DB->Update('REPLACE INTO `tlx_account_category_ranks` VALUES (?,?)', array($account['username'], $account['rank']));
        }
    }
    $DB->Free($result);

    $fillranks = null;
    if( $rank <= $end )
    {
        $fillranks = array('start' => $rank, 'end' => $end);
    }

    return $accounts;
}

function PopulateAccountInfo(&$account, &$stats)
{
    global $DB, $C, $L;

    $fields = $DB->Row('SELECT * FROM `tlx_account_fields` WHERE `username`=?', array($account['username']));
    if( is_array($fields) )
    {
        $account = array_merge($account, $fields);
    }

    if( empty($account['sorter']) )
        $account['sorter'] = 0;

    $account['timestamp_added'] = strtotime($account['date_added']);
    $account['timestamp_activated'] = strtotime($account['date_activated']);
    $account['average_rating'] = sprintf('%0.2f', ($account['ratings'] > 0 ? $account['ratings_total']/$account['ratings'] : 0));
    $account['comments'] = $DB->Count('SELECT COUNT(*) FROM `tlx_account_comments` WHERE `status`=? AND `username`=?', array('approved', $account['username']));
    $account['category'] = $GLOBALS['CATEGORY_CACHE'][$account['category_id']]['name'];
    $account['category_url'] = $GLOBALS['CATEGORY_CACHE'][$account['category_id']]['page_url'];

    $account['icons'] = array();
    $icons =& $DB->FetchAll('SELECT * FROM `tlx_account_icons` WHERE `username`=?', array($account['username']));
    foreach( $icons as $icon )
    {
        $account['icons'][] = $GLOBALS['ICON_CACHE'][$icon['icon_id']]['icon_html'];
    }

    if( !empty($stats) )
    {
        foreach( explode(',', $stats) as $stat )
        {
            if( empty($stat) )
            {
                continue;
            }

            if( preg_match('~^(.*?)_(last|this|yesterday)_?(\d+)?_?(.*)?$~', $stat, $matches) )
            {
                list($full, $field, $type, $amount, $period) = $matches;

                if( $type == 'yesterday' )
                {
                    $type = 'last';
                    $amount = 1;
                    $period = 'day';
                }

                if( empty($amount) )
                {
                    $amount = 1;
                }

                if( stristr($period, 'hour') )
                {
                    $account[$stat] = $DB->Count('SELECT ' .
                                                 ($amount == 24 ? "`$field".'_total`' : SorterLastHours($field.'_%%', $amount)) .
                                                 ' AS `stat` FROM `tlx_account_hourly_stats` WHERE `username`=?', array($account['username']));
                }
                else if( stristr($period, 'day') )
                {
                    $account[$stat] = $DB->Count('SELECT ' .
                                                 ($amount >= 365 ? "SUM(`$field`)" : "SUM(IF(`date_stats` >= DATE_ADD('".MYSQL_CURDATE."', INTERVAL -$amount DAY), `$field`, 0))") .
                                                 ' AS `stat` FROM `tlx_account_daily_stats` WHERE `username`=?', array($account['username']));
                }
            }
        }
    }
}

function &DeleteAccount($username, $account = null)
{
    global $DB, $C;

    if( $account == null )
    {
        $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($username));
    }

    if( $account )
    {
        // Remove account data
        $DB->Update('DELETE FROM `tlx_accounts` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_fields` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_confirms` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_icons` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_logins` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_reports` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_hourly_stats` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_daily_stats` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_country_stats` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_account_referrer_stats` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_ip_log_in` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_ip_log_out` WHERE `username`=?', array($username));
        $DB->Update('DELETE FROM `tlx_ip_log_ratings` WHERE `username`=?', array($username));

        // Remove banner, if exists
        if( stristr($account['banner_url'], $C['banner_url']) )
        {
            $filename = basename($account['banner_url']);
            @unlink("{$C['banner_dir']}/$filename");
        }
    }

    return $account;
}

function RssTimezone()
{
    global $C;

    list($hour, $half) = explode('.', $C['timezone']);

    return sprintf('%s%02d%02d', $hour < 0 ? '-' : '+', abs($hour), $half ? 30 : 0);
}

function strtogmtime($string)
{
    global $C;

    $timezone = $C['timezone'];

    if( date('I', $timestamp) )
    {
        $timezone++;
    }

    $time = strtotime($string);

    if( $time != -1 && $time !== FALSE )
    {
        $zone = intval(date('O')) / 100;
        $time += $zone * 60 * 60;
        return $time - (3600 * $timezone);
    }
    else
    {
        return -1;
    }
}

function RandomPassword()
{
    $chars = array_merge(range('a', 'z'), range('A', 'Z'));
    $numbers = range(0, 9);
    $number_locations = array(rand(0, 7), rand(0, 7));
    $password = '';

    for( $i = 0; $i < 8; $i++ )
    {
        if( in_array($i, $number_locations) )
        {
            $password .= $numbers[array_rand($numbers)];
        }
        else
        {
            $password .= $chars[array_rand($chars)];
        }
    }

    return $password;
}

function LevelUpUrl($url)
{
    $slash = strrpos($url, '/');

    if( $slash <= 7 )
    {
        return $url;
    }

    return substr($url, 0, $slash);
}

function SendMail($to, $template, &$t, $is_file = TRUE)
{
    global $C;

    if( !class_exists('mailer') )
    {
        require_once("{$GLOBALS['BASE_DIR']}/includes/mailer.class.php");
    }

    $m = new Mailer();
    $m->mailer = $C['email_type'];
    $m->from = $C['from_email'];
    $m->from_name = $C['from_email_name'];
    $m->to = $to;

    switch($C['email_type'])
    {
        case MT_PHP:
            break;

        case MT_SENDMAIL:
            $m->sendmail = $C['mailer'];
            break;

        case MT_SMTP:
            $m->host = $C['mailer'];
            break;
    }

    if( $is_file )
    {
        $template = file_get_contents("{$GLOBALS['BASE_DIR']}/templates/$template");
    }

    $message_parts = array();
    $parsed_template = $t->parse($template);
    IniParse($parsed_template, FALSE, $message_parts);

    $m->subject = $message_parts['subject'];
    $m->text_body = $message_parts['plain'];
    $m->html_body = $message_parts['html'];

    return $m->Send();
}

function IsEmptyString(&$string)
{
    if( preg_match("/^\s*$/s", $string) )
    {
        return TRUE;
    }

    return FALSE;
}

function UserDefinedUpdate($table, $defs_table, $key_name, $key_value, &$data)
{
    global $DB;

    $bind_list = array();
    $binds = array($table);
    $fields =& $DB->FetchAll('SELECT * FROM #', array($defs_table));

    foreach( $fields as $field )
    {
        // Handle unchecked checkboxes
        if( $field['type'] == FT_CHECKBOX && !isset($data[$field['name']]) )
        {
            $data[$field['name']] = null;
        }

        // See if new data was supplied
        if( array_key_exists($field['name'], $data) )
        {
            $binds[] = $field['name'];
            $binds[] = $data[$field['name']];
            $bind_list[] = '#=?';
        }
    }

    if( count($binds) > 1 )
    {
        $binds[] = $key_name;
        $binds[] = $key_value;
        $DB->Update('UPDATE # SET '.join(',', $bind_list).' WHERE #=?', $binds);
    }
}

function &GetUserAccountFields($account_data = null)
{
    global $DB;

    if( $account_data == null )
    {
        $account_data = $_REQUEST;
    }

    $fields = array();
    $result = $DB->Query('SELECT * FROM `tlx_account_field_defs`');
    while( $field = $DB->NextRow($result) )
    {
        if( isset($account_data[$field['name']]) )
        {
            $field['value'] = $account_data[$field['name']];
        }
        $fields[] = $field;
    }
    $DB->Free($result);

    return $fields;
}

function TimeWithTz($timestamp = null)
{
    global $C;

    $timezone = $C['timezone'];

    if( $timestamp == null )
    {
        $timestamp = time();
    }

    if( date('I', $timestamp) )
    {
        $timezone++;
    }

    return $timestamp + 3600 * $timezone;
}

function UnsetArray(&$array)
{
    $array = array();
}

function ArrayHSC(&$array)
{
    if( !is_array($array) )
        return;

    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            ArrayHSC($array[$key]);
        }
        else
        {
            $array[$key] = htmlspecialchars($array[$key], ENT_QUOTES);
        }
    }
}

function IniWrite($filename, &$hash, $keys = null)
{
    if( $keys == null )
        $keys = array_keys($hash);

    $data = '';

    foreach( $keys as $key )
    {
        UnixFormat($hash[$key]);

        $data .= "=>[$key]\n" .
                 trim($hash[$key]) . "\n";
    }

    if( $filename != null )
        FileWrite($filename, $data);
    else
        return $data;
}

function IniParse($string, $isfile = TRUE, &$hash)
{
    if( $hash == null )
        $hash = array();

    if( $isfile )
        $string = file_get_contents($string);

    UnixFormat($string);

    foreach(explode("\n", $string) as $line)
    {
        if( preg_match("/^=>\[(.*?)\]$/", $line, $submatch) )
        {
            if( isset($key) )
            {
                $hash[$key] = trim($hash[$key]);
            }

            $key = $submatch[1];
            $hash[$key] = '';
        }
        else
        {
            $hash[$key] .= "$line\n";
        }
    }

    if( isset($key) )
    {
        $hash[$key] = rtrim($hash[$key]);
    }
}

function StringChop($string, $length, $center = false, $append = null)
{
    // Set the default append string
    if ($append === null) {
        $append = ($center === true) ? ' ... ' : '...';
    }

    // Get some measurements
    $len_string = strlen($string);
    $len_append = strlen($append);

    // If the string is longer than the maximum length, we need to chop it
    if ($len_string > $length) {
        // Check if we want to chop it in half
        if ($center === true) {
            // Get the lengths of each segment
            $len_start = $length / 2;
            $len_end = $len_string - $len_start;

            // Get each segment
            $seg_start = substr($string, 0, $len_start);
            $seg_end = substr($string, $len_end);

            // Stick them together
            $string = trim($seg_start) . $append . trim($seg_end);
        } else {
            // Otherwise, just chop the end off
            $string = trim(substr($string, 0, $length - $len_append)) . $append;
        }
    }

    return $string;
}

function FormatCommaSeparated($string)
{
    if( strlen($string) < 1 || strstr($string, ',') === FALSE )
        return $string;

    $items = array();
    $string = trim($string);

    foreach( explode(',', $string) as $item )
    {
        $items[] = trim($item);
    }

    return join(',', $items);
}

function FormField($options, $value)
{
    $html = '';
    $select_options = explode(',', $options['options']);

    $options['tag_attributes'] = str_replace(array('&quot;', '&#039;'), array('"', "'"), $options['tag_attributes']);

    switch($options['type'])
    {
    case FT_CHECKBOX:
        $tag_value = null;

        if( preg_match('/value\s*=\s*["\']?([^\'"]+)\s?/i', $options['tag_attributes'], $matches) )
        {
            $tag_value = $matches[1];
        }
        else
        {
            $tag_value = 1;
            $options['tag_attributes'] .= ' value="1"';
        }

        $html = "<input " .
                "type=\"checkbox\" " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                ($value == $tag_value ? "checked=\"checked\" " : '') .
                "{$options['tag_attributes']} />\n";
        break;

    case FT_SELECT:
        $html = "<select " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                "{$options['tag_attributes']}>\n" .
                OptionTags($select_options, $value, TRUE) .
                "</select>\n";
        break;

    case FT_TEXT:
        $html = "<input " .
                "type=\"text\" " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                "value=\"$value\" " .
                "{$options['tag_attributes']} />\n";
        break;

    case FT_TEXTAREA:
        $html = "<textarea " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                "{$options['tag_attributes']}>" .
                $value .
                "</textarea>\n";
        break;
    }

    return $html;
}

function OptionTags($options, $selected = null, $use_values = FALSE, $max_length = 9999)
{
    $html = '';

    if( is_array($options) )
    {
        foreach($options as $key => $value)
        {
            if( $use_values )
                $key = $value;

            $html .= "<option value=\"" . htmlspecialchars($key) . "\"" .
                     ($key == $selected ? ' selected="selected"' : '') .
                     ">" . htmlspecialchars(StringChop($value, $max_length)) . "</option>\n";
        }
    }

    return $html;
}

function OptionTagsAdv($options, $selected, $value, $name, $max_length = 9999)
{
    $html = '';

    if( is_array($options) )
    {
        foreach($options as $option)
        {
            $html .= "<option value=\"" . htmlspecialchars($option[$value]) . "\"" .
                     ((is_array($selected) && in_array($option[$value], $selected) || $option[$value] == $selected) ? ' selected="selected"' : '') .
                     ">" . htmlspecialchars(StringChop($option[$name], $max_length)) . "</option>\n";
        }
    }

    return $html;
}

function UnixFormat(&$string)
{
    $string = str_replace(array("\r\n", "\r"), "\n", $string);
}

function WindowsFormat(&$string)
{
    $string = str_replace(array("\r\n", "\r"), "\n", $string);
    $string = str_replace("\n", "\r\n", $string);
}

function ToBool($value)
{
    if( is_numeric($value) )
    {
        if( $value == 0 )
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
    else if( preg_match('~^true$~i', $value) )
    {
        return TRUE;
    }
    else if( preg_match('~^false$~i', $value) )
    {
        return FALSE;
    }

    return FALSE;
}

function IsBool($value)
{
    return is_bool($value) || preg_match('/^true|false$/i', $value);
}

function SafeAddSlashes(&$string)
{
    $string = preg_replace("/(?<!\\\)'/", "\'", $string);
}

function ArrayCombine($keys, $values)
{
    $combined = array();

    for( $i = 0; $i < count($keys); $i++ )
    {
        $combined[$keys[$i]] = $values[$i];
    }

    return $combined;
}

function ArrayAddSlashes(&$array)
{
    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            ArrayAddSlashes($array[$key]);
        }
        else
        {
            $array[$key] = preg_replace("/(?<!\\\)'/", "\'", $value);
        }
    }
}

function ArrayStripSlashes(&$array)
{
    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            ArrayStripSlashes($array[$key]);
        }
        else
        {
            $array[$key] = stripslashes($value);
        }
    }
}

function SafeFilename($filename, $must_exist = TRUE)
{
    global $L;

    $unsafe_exts = array('php', 'php3', 'php4', 'php5', 'cgi', 'pl', 'exe', 'js');
    $path_info = pathinfo($filename);

    if( $must_exist && !file_exists($filename) )
        trigger_error("{$L['NOT_A_FILE']}: $filename", E_USER_ERROR);

    if( is_dir($filename) )
        trigger_error("{$L['NOT_A_FILE']}: $filename", E_USER_ERROR);

    if( strstr($filename, '..') != FALSE || strstr($filename, '|') != FALSE || strstr($filename, ';') != FALSE)
        trigger_error("{$L['UNSAFE_FILENAME']}: $file", E_USER_ERROR);

    if( in_array($path_info['extension'], $unsafe_exts) )
        trigger_error("{$L['UNSAFE_FILE_EXTENSION']}: $filename", E_USER_ERROR);

    return $filename;
}

function FileReadLine($file)
{
    $line = '';
    $fh = fopen($file, 'r');

    if( $fh )
    {
        $line = trim(fgets($fh));
        fclose($fh);
    }

    return $line;
}

function FileWrite($file, $data)
{
    $file_mode = file_exists($file) ? 'r+' : 'w';

    $fh = fopen($file, $file_mode);
    flock($fh, LOCK_EX);
    fseek($fh, 0);
    fwrite($fh, $data);
    ftruncate($fh, ftell($fh));
    flock($fh, LOCK_UN);
    fclose($fh);

    @chmod($file, $GLOBALS['FILE_PERMISSIONS']);
}

function FileWriteNew($file, $data)
{
    if( !file_exists($file) )
    {
        FileWrite($file, $data);
    }
}

function FileAppend($file, $data)
{
    $fh = fopen($file, 'a');
    flock($fh, LOCK_EX);
    fwrite($fh, $data);
    flock($fh, LOCK_UN);
    fclose($fh);

    @chmod($file, $GLOBALS['FILE_PERMISSIONS']);
}

function FileRemove($file)
{
    unlink($file);
}

function FileCreate($file)
{
    if( !file_exists($file) )
    {
        FileWrite($file, '');
    }
}

function &DirRead($dir, $pattern)
{
    $contents = array();

    DirTaint($dir);

    $dh = opendir($dir);

    while( false !== ($file = readdir($dh)) )
    {
        $contents[] = $file;
    }

    closedir($dh);

    $contents = preg_grep("/$pattern/i", $contents);

    return $contents;
}

function DirTaint($dir)
{
    if( is_file($dir) )
        trigger_error("Not A Directory: $dir", E_USER_ERROR);

    if( stristr($dir, '..') != FALSE )
        trigger_error("Security Violation: $dir", E_USER_ERROR);
}

function SetupRequest()
{
    if( get_magic_quotes_gpc() == 1 )
    {
        ArrayStripSlashes($_POST);
        ArrayStripSlashes($_GET);
        ArrayStripSlashes($_COOKIE);
    }

    $_REQUEST = array_merge($_POST, $_GET);
}

function Shutdown()
{
    global $DB;

    if( @get_class($DB) == 'db' )
    {
        $DB->Disconnect();
    }
}

function Error($code, $string, $file, $line)
{
    global $C;

    $reporting = error_reporting();

    if( $reporting == 0 || !($code & $reporting) )
    {
        return;
    }

    $sapi = php_sapi_name();

    if( $sapi != 'cli' )
    {
        require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
        $t = new Template();
    }

    $file = basename($file);

    // Generate stack trace
    $backtrace = debug_backtrace();
    for( $i = 1; $i < count($backtrace); $i++ )
    {
        $tracefile = $backtrace[$i];

        if( !$tracefile['line'] )
            continue;

        $trace .= "{$tracefile['function']} in " . basename($tracefile['file']) . " on line {$tracefile['line']}<br />";
    }

    if( $sapi != 'cli' )
    {
        $t->assign('trace', $trace);
        $t->assign('error', $string);
        $t->assign('file', $file);
        $t->assign('line', $line);
        $t->assign_by_ref('config', $C);

        if( defined('ToplistX') )
        {
            $t->assign('levelup', '../');
        }

        $t->display('error-fatal.tpl');
    }
    else
    {
        echo "Error on line $line of file $file\n" .
             "$string\n\n" .
             "STACK TRACE:\n" . str_replace('<br />', "\n", $trace) . "\n";
    }

    exit;
}

function VerifyCaptcha(&$v, $cookie = 'toplistxcaptcha')
{
    global $DB, $L, $C;

    if( !isset($_COOKIE[$cookie]) )
    {
        $v->SetError($L['COOKIES_REQUIRED']);
    }
    else
    {
        $captcha = $DB->Row('SELECT * FROM `tlx_captcha` WHERE `session`=?', array($_COOKIE[$cookie]));

        if( strtoupper($captcha['code']) != strtoupper($_REQUEST['captcha']) )
        {
            $v->SetError($L['INVALID_CODE']);
        }
        else
        {
            $DB->Update('DELETE FROM `tlx_captcha` WHERE `session`=?', array($_COOKIE[$cookie]));
            setcookie($cookie, '', time() - 3600, '/', $C['cookie_domain']);
        }
    }
}

function CheckDsbl($ip_address)
{
    list($a, $b, $c, $d) = explode('.', $ip_address);

    $hostname = "$d.$c.$b.$a.list.dsbl.org";
    $ip_address = gethostbyname("$d.$c.$b.$a.list.dsbl.org");

    if( $hostname != $ip_address )
    {
        return TRUE;
    }

    return FALSE;
}

function NullIfEmpty(&$string)
{
    if( IsEmptyString($string) )
    {
        $string = null;
    }
}

function DatetimeToTime(&$datetime)
{
    if( !empty($datetime) )
    {
        $datetime = strtotime($datetime);
    }
}

function PrepareCategoriesBuild()
{
    global $DB;

    $GLOBALS['_prep_category_build'] = TRUE;

    $DB->Update('DELETE FROM `tlx_categories_build`');

    $result = $DB->Query('SELECT * FROM `tlx_categories` WHERE `hidden`=0');
    while( $category = $DB->NextRow($result) )
    {
        $page = $DB->Row('SELECT * FROM `tlx_pages` WHERE `category_id`=? ORDER BY `build_order` LIMIT 1', array($category['category_id']));
        $accounts = $DB->Count('SELECT COUNT(*) FROM `tlx_accounts` WHERE `status`=? AND `disabled`=0 AND `category_id`=?', array(STATUS_ACTIVE, $category['category_id']));

        $DB->Update('INSERT INTO `tlx_categories_build` VALUES (?,?,?,?)',
                    array($category['category_id'],
                          $category['name'],
                          $accounts,
                          $page['filename']));
    }
    $DB->Free($result);
}

function ValidAccountLogin()
{
    global $DB, $C, $L;

    $error = $L['INVALID_LOGIN'];

    if( isset($_REQUEST['login_username']) && isset($_REQUEST['login_password']) )
    {
        $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=? AND `password`=?', array($_REQUEST['login_username'], sha1($_REQUEST['login_password'])));

        if( $account )
        {
            // Only allow active accounts to login
            if( $account['status'] == STATUS_ACTIVE )
            {
                // Setup the session
                $session = sha1(uniqid(rand(), true) . $_REQUEST['login_password']);
                setcookie('toplistxaccount', 'username=' . urlencode($_REQUEST['login_username']) . '&session=' . $session, time() + 86400, '/', $C['cookie_domain']);
                $DB->Update('DELETE FROM `tlx_account_logins` WHERE `username`=?', array($account['username']));
                $DB->Update('INSERT INTO `tlx_account_logins` VALUES (?,?,?)',
                            array($account['username'],
                                  $session,
                                  time()));

                // Get user defined fields and merge with default partner data
                $user_fields = $DB->Row('SELECT * FROM `tlx_account_fields` WHERE `username`=?', array($account['username']));
                $account = array_merge($account, $user_fields);

                return $account;
            }
            else
            {
                $error = $account['suspended'] ? $L['ACCOUNT_SUSPENDED'] : $L['ACCOUNT_PENDING'];
            }
        }
    }
    else if( isset($_COOKIE['toplistxaccount']) )
    {
        parse_str($_COOKIE['toplistxaccount'], $cookie);

        $session = $DB->Row('SELECT * FROM `tlx_account_logins` WHERE `username`=? AND `session`=?', array($cookie['username'], $cookie['session']));

        if( $session )
        {
            $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($session['username']));

            if( $account['status'] == STATUS_ACTIVE )
            {
                // Get user defined fields and merge with default partner data
                $user_fields = $DB->Row('SELECT * FROM `tlx_account_fields` WHERE `username`=?', array($account['username']));
                $account = array_merge($account, $user_fields);

                return $account;
            }
            else
            {
                $error = $account['suspended'] ? $L['ACCOUNT_SUSPENDED'] : $L['ACCOUNT_PENDING'];
            }
        }
        else
        {
            $error = $L['EXPIRED_LOGIN'];
        }
    }

    tlxShAccountLogin(array($error));
    return FALSE;
}

function FormatSpaceSeparated($words)
{
    $words = str_replace(array('.', ',', '?', ';', ':', '(', ')', '{', '}', '*', '&', '%', '$', '#', '@', '!', '-'), ' ', $words);
    $words = preg_replace('/\s+/', ' ', $words);

    return join(' ', array_unique(explode(' ', $words)));
}

function GetNameServers($url)
{
    global $C;

    $nameservers = array();

    if( $C['dig'] )
    {
        $parsed_url = @parse_url($url);

        if( $parsed_url !== FALSE )
        {
            $domain = str_replace('www.', '', $parsed_url['host']);
            $found = FALSE;

            while( substr_count($domain, '.') >= 1 )
            {
                $output = shell_exec("{$C['dig']} $domain NS +nocmd +nostats +noquestion +nocomment");

                foreach( explode("\n", $output) as $line )
                {
                    if( preg_match('~NS\s+([^\s]+)$~i', $line, $matches) )
                    {
                        $nameservers[] = preg_replace('~\.$~', '', $matches[1]);
                    }
                }

                if( $found )
                {
                    break;
                }

                $domain = substr($domain, strpos($domain, '.') + 1);
            }
        }
    }

    return array_unique($nameservers);
}

function CheckBlacklistRating(&$rating, $full_check = FALSE)
{
    $checks = array('email' => array($rating['email']),
                    'url' => null,
                    'domain_ip' => null,
                    'submit_ip' => array($_SERVER['REMOTE_ADDR']),
                    'word' => array($rating['name'], $rating['comment']),
                    'html' => null,
                    'headers' => null,
                    'dns' => null);

    return CheckBlacklistGeneric($checks, $full_check);
}

function CheckBlacklistAccount(&$account, $full_check = FALSE)
{
    $checks = array('email' => array($account['email']),
                    'url' => array($account['site_url']),
                    'domain_ip' => array(GetIpFromUrl($account['gallery_url'])),
                    'submit_ip' => array($_SERVER['REMOTE_ADDR']),
                    'word' => array($account['title'], $account['description'], $account['keywords']),
                    'html' => array($account['html']),
                    'headers' => array($account['headers']),
                    'dns' => GetNameServers($account['site_url']));

    return CheckBlacklistGeneric($checks, $full_check);
}

function CheckBlacklistGeneric(&$checks, $full_check = FALSE)
{
    global $DB, $BL_CACHE;

    $found = array();

    if( !is_array($BL_CACHE) )
    {
        $BL_CACHE = array();

        $result = $DB->Query('SELECT * FROM `tlx_blacklist`');
        while( $item = $DB->NextRow($result) )
        {
            $BL_CACHE[] = $item;
        }
        $DB->Free($result);
    }

    foreach( $BL_CACHE as $item )
    {
        $to_check = $checks[$item['type']];

        if( !$item['regex'] )
        {
            $item['value'] = preg_quote($item['value'], '~');
        }
        else
        {
            $item['value'] = preg_replace("%(?<!\\\)~%", '\\~', $item['value']);
        }

        if( is_array($to_check) )
        {
            foreach( $to_check as $check_item )
            {
                if( empty($check_item) )
                {
                    continue;
                }

                if( preg_match("~({$item['value']})~i", $check_item, $matches) )
                {
                    $item['match'] = $matches[1];
                    $found[] = $item;

                    if( !$full_check )
                    {
                        break;
                    }
                }
            }
        }

        if( !$full_check && count($found) )
        {
            break;
        }
    }

    if( count($found) )
    {
        return $found;
    }
    else
    {
        return FALSE;
    }
}

function CreateBindList(&$items)
{
    $bind_list = array();

    if( !is_array($items) )
    {
        $items = array($items);
    }

    for($i = 0; $i < count($items); $i++)
    {
        $bind_list[] = '?';
    }

    return join(',', $bind_list);
}

function CreateUserInsert($table, &$values, $columns = null)
{
    global $DB;

    $query = array('bind_list' => array(), 'binds' => array());

    if( $columns == null )
    {
        $columns = $DB->GetColumns($table);
    }

    foreach( $columns as $column )
    {
        $query['binds'][] = $values[$column];
        $query['bind_list'][] = '?';
    }

    $query['bind_list'] = join(',', $query['bind_list']);

    return $query;
}

function GetIpFromUrl($url)
{
    $parsed = parse_url($url);
    return gethostbyname($parsed['host']);
}

function ResolvePath($path)
{
    $path = explode('/', str_replace('//', '/', $path));

    for( $i = 0; $i < count($path); $i++ )
    {
        if( $path[$i] == '.' )
        {
            unset($path[$i]);
            $path = array_values($path);
            $i--;
        }
        elseif( $path[$i] == '..' AND ($i > 1 OR ($i == 1 AND $path[0] != '')) )
        {
            unset($path[$i]);
            unset($path[$i-1]);
            $path = array_values($path);
            $i -= 2;
        }
        elseif( $path[$i] == '..' AND $i == 1 AND $path[0] == '' )
        {
            unset($path[$i]);
            $path = array_values($path);
            $i--;
        }
        else
        {
            continue;
        }
    }

    return implode('/', $path);
}

function RelativeToAbsolute($start_url, $relative_url)
{
    if( preg_match('~^https?://~', $relative_url) )
    {
        return $relative_url;
    }

    $parsed = parse_url($start_url);
    $base_url = "{$parsed['scheme']}://{$parsed['host']}" . ($parsed['port'] ? ":{$parsed['port']}" : "");
    $path = $parsed['path'];

    if( $relative_url{0} == '/' )
    {
        return $base_url . ResolvePath($relative_url);
    }

    $path = preg_replace('~[^/]+$~', '', $path);

    return $base_url . ResolvePath($path . $relative_url);
}

class SelectBuilder
{
    var $query;
    var $binds = array();
    var $wheres = array();
    var $havings = array();
    var $orders = array();
    var $joins = array();
    var $error = FALSE;
    var $limit = null;
    var $group = null;
    var $order_string = null;
    var $errstr;

    function SelectBuilder($items, $table)
    {
        $this->query = "SELECT $items FROM `$table`";
    }

    function ProcessFieldName($field)
    {
        preg_match_all('~([a-z0-9_]+)([./+\-*])?~i', $field, $field_parts, PREG_SET_ORDER);
        $placeholders = array();
        $parts = array('placeholders' => '', 'binds' => array());

        foreach( $field_parts as $part )
        {
            $placeholders[] = '#';

            if( count($part) > 1 )
            {
                $placeholders[] = $part[2];
            }

            $parts['binds'][] = $part[1];
        }

        $parts['placeholders'] = join('', $placeholders);

        return $parts;
    }

    function GeneratePiece($field, $operator, $value)
    {
        $piece = '';

        $field = $this->ProcessFieldName($field);

        switch($operator)
        {
        case ST_STARTS:
            $piece = "{$field['placeholders']} LIKE ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = "$value%";
            break;

        case ST_MATCHES:
            $piece = "{$field['placeholders']}=?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_NOT_MATCHES:
            $piece = "{$field['placeholders']}!=?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_BETWEEN:
            list($min, $max) = explode(',', $value);

            $piece = "{$field['placeholders']} BETWEEN ? AND ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $min;
            $this->binds[] = $max;
            break;

        case ST_GREATER:
            $piece = "{$field['placeholders']} > ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_GREATER_EQ:
            $piece = "{$field['placeholders']} >= ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_LESS:
            $piece = "{$field['placeholders']} < ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_LESS_EQ:
            $piece = "{$field['placeholders']} <= ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_EMPTY:
            $piece = "({$field['placeholders']}='' OR {$field['placeholders']} IS NULL)";
            $this->binds = array_merge($this->binds, $field['binds'], $field['binds']);
            break;

        case ST_NOT_EMPTY:
            $piece = "({$field['placeholders']}!='' AND {$field['placeholders']} IS NOT NULL)";
            $this->binds = array_merge($this->binds, $field['binds'], $field['binds']);
            break;

        case ST_NULL:
            $piece = "{$field['placeholders']} IS NULL";
            $this->binds = array_merge($this->binds, $field['binds']);
            break;

        case ST_NOT_NULL:
            $piece = "{$field['placeholders']} IS NOT NULL";
            $this->binds = array_merge($this->binds, $field['binds']);
            break;

        case ST_IN:
            $items = array_unique(explode(',', $value));

            $piece = "{$field['placeholders']} IN (".CreateBindList($items).")";
            $this->binds = array_merge($this->binds, $field['binds'], $items);
            break;

        case ST_NOT_IN:
            $items = array_unique(explode(',', $value));

            $piece = "{$field['placeholders']} NOT IN (".CreateBindList($items).")";
            $this->binds = array_merge($this->binds, $field['binds'], $items);
            break;

        case ST_ANY:
            break;

        // 'contains' is the default
        default:
            $piece = "{$field['placeholders']} LIKE ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = "%$value%";
            break;
        }

        return $piece;
    }

    function AddWhereString($clause)
    {
        $this->wheres[] = $clause;
    }

    function AddWhere($field, $operator, $value = '', $no_value = FALSE)
    {
        if( $no_value && $value == '' )
            return;

        $newpiece = $this->GeneratePiece($field, $operator, $value);

        if( !empty($newpiece) )
        {
            $this->wheres[] = $newpiece;
        }
    }

    function AddHaving($field, $operator, $value = '', $no_value = FALSE)
    {
        if( $no_value && $value == '' )
            return;

        $newpiece = $this->GeneratePiece($field, $operator, $value);

        if( !empty($newpiece) )
        {
            $this->havings[] = $newpiece;
        }
    }

    function AddHavingString($clause)
    {
        $this->havings[] = $clause;
    }

    function AddMultiWhere($fields, $operators, $values, $no_value = FALSE)
    {
        if( $no_value && count($value) < 1 )
            return;

        $ors = array();

        for( $i = 0; $i < count($fields); $i++ )
        {
            $newpiece = $this->GeneratePiece($fields[$i], $operators[$i], $values[$i]);

            if( !empty($newpiece) )
            {
                $ors[] = $newpiece;
            }
        }

        $this->wheres[] = "(" . join(' OR ', $ors) . ")";
    }

    function AddFulltextWhere($field, $value, $no_value = FALSE)
    {
        if( $no_value && $value == '' )
            return;

        $field_parts = explode(',', $field);
        $parts = array();

        foreach( $field_parts as $part )
        {
            $parts[] = '#';
            $this->binds[] = $part;
        }

        $this->wheres[] = 'MATCH('. join(',', $parts) .') AGAINST (? IN BOOLEAN MODE)';
        $this->binds[] = $value;
    }

    function AddOrder($field, $direction = 'ASC')
    {
        if( preg_match('~^RAND\(~', $field) )
        {
            $this->orders[] = $field;
        }
        else
        {
            $field = $this->ProcessFieldName($field);

            if( $direction != 'ASC' && $direction != 'DESC' )
            {
                $direction = 'ASC';
            }

            $this->binds = array_merge($this->binds, $field['binds']);
            $this->orders[] = "{$field['placeholders']} $direction";
        }
    }

    function SetOrderString($string, &$fields)
    {
        foreach( $fields as $field )
        {
            $string = str_replace($field, "`$field`", $string);
        }

        $this->order_string = $string;
    }

    function AddJoin($left_table, $right_table, $join, $field)
    {
        $this->joins[] = "$join JOIN `$right_table` ON `$right_table`.`$field`=`$left_table`.`$field`";
    }

    function AddGroup($field)
    {
        $field = $this->ProcessFieldName($field);
        $this->group = '`' . join('`.`', $field['binds']) . '`';
    }

    function SetLimit($limit)
    {
        $this->limit = $limit;
    }

    function Generate()
    {
        $select = $this->query;

        if( count($this->joins) )
        {
            $select .= " " . join(' ', $this->joins);
        }

        if( count($this->wheres) )
        {
            $select .= " WHERE " . join(' AND ', $this->wheres);
        }

        if( isset($this->group) )
        {
            $select .= " GROUP BY " . $this->group;
        }

        if( count($this->havings) )
        {
            $select .= " HAVING " . join(' AND ', $this->havings);
        }

        if( isset($this->order_string) )
        {
            $select .= " ORDER BY " . $this->order_string;
        }
        else if( count($this->orders) )
        {
            $select .= " ORDER BY " . join(',', $this->orders);
        }

        if( isset($this->limit) )
        {
            $select .= " LIMIT {$this->limit}";
        }

        return $select;
    }
}


class UpdateBuilder extends SelectBuilder
{
    var $table;
    var $updates = array();

    function UpdateBuilder($table)
    {
        $this->table = $table;
    }

    function AddSet($field, $value)
    {
        $field = $this->ProcessFieldName($field);

        $this->updates[] = "{$field['placeholders']}=?";
        $this->binds = array_merge($this->binds, $field['binds']);
        $this->binds[] = $value;
    }

    function Generate()
    {
        $select = "UPDATE {$this->table} ";

        if( count($this->joins) )
        {
            $select .= " " . join(' ', $this->joins);
        }

        $select .= " SET " . join(' ', $this->updates);

        if( count($this->wheres) )
        {
            $select .= " WHERE " . join(' AND ', $this->wheres);
        }

        return $select;
    }
}
?>
