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

// Initialization
if( !defined('E_STRICT') ) define('E_STRICT', 2048);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
@set_time_limit(0);
@set_magic_quotes_runtime(0);
if( function_exists('date_default_timezone_set') )
{
    date_default_timezone_set('America/Chicago');
}


// Prepare request data
if( get_magic_quotes_gpc() == 1 )
{
    foreach($_GET as $key => $value)
    {
        $_GET[$key] = stripslashes($value);
    }
}

// Load configuration settings
require('includes/config.php');

if( !isset($C['redirect_code']) )
{
    $C['redirect_code'] = 301;
}

// Only allow GET requests
if( $_SERVER['REQUEST_METHOD'] == 'GET' )
{
    $raw_click = FALSE;
    $now = time() + 3600 * $C['timezone'];
    $today = gmdate('Y-m-d', $now);
    $this_hour = gmdate('G', $now);
    $datetime = "$today-$this_hour";


    // Connect to database
    @mysql_connect($C['db_hostname'], $C['db_username'], $C['db_password']) or die(mysql_error());
    @mysql_select_db($C['db_name']) or die(mysql_error());


    if( !$C['using_cron'] )
    {
        // Check if it is time for a page rebuild
        $result = @mysql_query("SELECT `value` FROM `tlx_stored_values` WHERE `name`='last_rebuild'") or die(mysql_error());
        list($last_rebuild) = mysql_fetch_row($result);
        mysql_free_result($result);

        if( $last_rebuild <= $now - $C['rebuild_interval'] )
        {
            shell_exec("{$C['php_cli']} admin/cron.php --rebuild >/dev/null 2>&1 &");
        }

        // Check if it is time for a daily or hourly update
        $result = @mysql_query("SELECT `value` FROM `tlx_stored_values` WHERE `name`='last_updates'") or die(mysql_error());
        list($last_updates) = mysql_fetch_row($result);
        $last_updates = unserialize($last_updates);
        mysql_free_result($result);

        if( $last_updates['daily'] != $today )
        {
            shell_exec("{$C['php_cli']} admin/cron.php --daily-stats >/dev/null 2>&1 &");
        }

        if( $last_updates['hourly'] != $datetime )
        {
            shell_exec("{$C['php_cli']} admin/cron.php --hourly-stats >/dev/null 2>&1 &");
        }
    }


    // Get account from cookie
 if( isset($_GET['d']) )
    {
        $result = @mysql_query(mysql_prepare('SELECT * FROM `tlx_accounts` WHERE `domain`=?', array($_GET['d']))) or die(mysql_error());
    }

    // Get account by username
    else if( isset($_GET['id']) )
    {
        $result = @mysql_query(mysql_prepare('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_GET['id']))) or die(mysql_error());
    }

    // Get account by HTTP_REFERER
    else
    {
        if( !empty($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '-' )
        {
            $parsed_url = @parse_url($_SERVER['HTTP_REFERER']);

            if( $parsed_url !== FALSE )
            {
                $domain = preg_replace('~^www\.~i', '', strtolower($parsed_url['host']));

                if( !empty($domain) )
                {
                    $result = @mysql_query(mysql_prepare('SELECT * FROM `tlx_accounts` WHERE `domain`=?', array($domain))) or die(mysql_error());
                }
            }
            else
            {
                // Referrer URL cannot be parsed
                if( $C['tracking_mode'] != 'embedded' )
                {
                    header("Location: {$C['forward_url']}", true, $C['redirect_code']);
                }
                return;
            }
        }
        else
        {
            // Handle no referrer (bookmarker)
            @mysql_query(mysql_prepare('UPDATE `tlx_bookmarker_stats` SET `visits`=`visits`+1 WHERE `date_stats`=?', array($today))) or die(mysql_error());
            if( @mysql_affected_rows() == 0 )
            {
                @mysql_query(mysql_prepare('INSERT INTO `tlx_bookmarker_stats` VALUES (?,?)', array($today, 1))) or die(mysql_error());
            }

            return;
        }
    }

    $account = FALSE;
    if( $result )
    {
        $account = @mysql_fetch_assoc($result);
    }
    else
    {
        // Bad SQL result
        if( $C['tracking_mode'] != 'embedded' )
        {
            header("Location: {$C['forward_url']}", true, $C['redirect_code']);
        }
        return;
    }

    if( $account )
    {
        // Check the secret key
        if( $C['tracking_mode'] == 'embedded' && $_GET['sk'] != $C['secret_key'] )
        {
            // TODO: Log this cheating attempt
            return;
        }

        // Proxy check
        $proxy = detect_proxy();

        // Bot lookup
        $robot = detect_robot();

        // GeoIP lookup
        $long_ip = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
        $result = @mysql_query(mysql_prepare('SELECT * FROM `tlx_ip2country` WHERE `ip_end` >= ?', array($long_ip))) or die(mysql_error());
        if( $result )
        {
            $geoip = @mysql_fetch_assoc($result);

            if( $geoip && $geoip['country'] == 'A1' )
            {
                $proxy = TRUE;
            }
        }

        // Track referring URLs
        $_SERVER['HTTP_REFERER'] = empty($_SERVER['HTTP_REFERER']) ? '-' : $_SERVER['HTTP_REFERER'];
        @mysql_query(mysql_prepare('UPDATE `tlx_account_referrer_stats` SET `raw_in`=`raw_in`+1 WHERE `username`=? AND `referrer`=?', array($account['username'], $_SERVER['HTTP_REFERER']))) or die(mysql_error());
        if( @mysql_affected_rows() == 0 )
        {
            @mysql_query(mysql_prepare('INSERT INTO `tlx_account_referrer_stats` VALUES (?,?,?)', array($account['username'], $_SERVER['HTTP_REFERER'], 1))) or die(mysql_error());
        }


        // Update the IP log
        @mysql_query(mysql_prepare('UPDATE `tlx_ip_log_in` SET `raw_in`=`raw_in`+1,`last_visit`=NOW() WHERE `username`=? AND `ip_address`=?', array($account['username'], $long_ip))) or die(mysql_error());
        if( @mysql_affected_rows() == 0 )
        {
            @mysql_query(mysql_prepare('INSERT INTO `tlx_ip_log_in` VALUES (?,?,?,?,?,NOW())', array($account['username'], $long_ip, 1, $proxy, $robot))) or die(mysql_error());
        }
        else
        {
            $raw_click = TRUE;
        }

        // Update raw and unique click counts
        if( $raw_click )
        {
            @mysql_query(mysql_prepare('UPDATE `tlx_account_hourly_stats` SET #=#+1,`raw_in_total`=`raw_in_total`+1 WHERE `username`=?',
                                       array("raw_in_$this_hour", "raw_in_$this_hour", $account['username']))) or die(mysql_error());

            @mysql_query(mysql_prepare('UPDATE `tlx_account_country_stats` SET `raw_in`=`raw_in`+1 WHERE `username`=? AND `country`=?',
                                       array($account['username'], $geoip['country']))) or die(mysql_error());

            if( @mysql_affected_rows() == 0 )
            {
                @mysql_query(mysql_prepare('INSERT INTO `tlx_account_country_stats` VALUES (?,?,?,?,?,?,?)', array($account['username'], $geoip['country'], 1, 1, 0, 0, 0))) or die(mysql_error());
            }

            @mysql_query(mysql_prepare('UPDATE `tlx_country_stats` SET `raw_in`=`raw_in`+1 WHERE `country`=?', array($geoip['country']))) or die(mysql_error());
        }
        else
        {
            @mysql_query(mysql_prepare('UPDATE `tlx_account_hourly_stats` SET #=#+1,#=#+1,`raw_in_total`=`raw_in_total`+1,`unique_in_total`=`unique_in_total`+1 WHERE `username`=?',
                                       array("raw_in_$this_hour", "raw_in_$this_hour", "unique_in_$this_hour", "unique_in_$this_hour", $account['username']))) or die(mysql_error());

            @mysql_query(mysql_prepare('UPDATE `tlx_account_country_stats` SET `raw_in`=`raw_in`+1,`unique_in`=`unique_in`+1 WHERE `username`=? AND `country`=?',
                                       array($account['username'], $geoip['country']))) or die(mysql_error());

            if( @mysql_affected_rows() == 0 )
            {
                @mysql_query(mysql_prepare('INSERT INTO `tlx_account_country_stats` VALUES (?,?,?,?,?,?,?)', array($account['username'], $geoip['country'], 1, 1, 0, 0, 0))) or die(mysql_error());
            }

            @mysql_query(mysql_prepare('UPDATE `tlx_country_stats` SET `raw_in`=`raw_in`+1,`unique_in`=`unique_in`+1 WHERE `country`=?', array($geoip['country']))) or die(mysql_error());
        }


        @mysql_query(mysql_prepare('UPDATE `tlx_accounts` SET `inactive`=0 WHERE `username`=?', array($account['username']))) or die(mysql_error());
        // TODO: Check maximum clicks from an IP address (maybe only hourly?)
        // TODO: Reject clicks from specified countries

        mysql_close();

        if( $C['tracking_mode'] == 'embedded' )
        {
            if( !isset($_COOKIE['tlxreferrer']) )
            {
                echo '<script language="JavaScript" type="text/javascript">' .
                     "document.cookie = 'tlxreferrer=".$account['username']."; path=/; expires=".gmdate('l, d-M-y H:i:s T', time() + 86400)."; domain={$C['cookie_domain']};'" .
                     '</script>';
            }
            return;
        }
        else
        {
            // TODO: Forward surfer by category
            // TODO: Forward surfer by country
            setcookie('tlxreferrer', $account['username'], time()+86400, '/', $C['cookie_domain']);
            header("Location: {$C['forward_url']}", true, $C['redirect_code']);
        }
    }
    // No matching account
    else
    {
        if( $C['tracking_mode'] != 'embedded' )
        {
            header("Location: {$C['forward_url']}", true, $C['redirect_code']);
        }
        return;
    }
}
else
{
    // Handle non GET requests
    if( $C['tracking_mode'] != 'embedded' )
    {
        header("Location: {$C['forward_url']}", true, $C['redirect_code']);
    }
    return;
}

function mysql_prepare($query, $binds)
{
    $query_result = '';
    $index = 0;

    $pieces = preg_split('/(\?|#)/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach( $pieces as $piece )
    {
        if( $piece == '?' )
        {
            if( $binds[$index] === NULL )
                $query_result .= 'NULL';
            else if( is_numeric($binds[$index]) )
                $query_result .= $binds[$index];
            else
                $query_result .= "'" . mysql_real_escape_string($binds[$index]) . "'";

            $index++;
        }
        else if( $piece == '#' )
        {
            $binds[$index] = str_replace('`', '\`', $binds[$index]);
            $query_result .= "`" . $binds[$index] . "`";
            $index++;
        }
        else
        {
            $query_result .= $piece;
        }
    }

    return $query_result;
}

function detect_robot()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

    // General filter for robots
    if( preg_match('~(bot|crawl|spider)~', $agent) )
    {
        return TRUE;
    }

    // Mozilla browsers pass the test
    if( preg_match('~^mozilla~', $agent) )
    {
        return FALSE;
    }

    $robots = file('includes/robots.php');
    $start = unserialize($robots[2]);

    if( isset($start[$agent[0]]) )
    {
        foreach( range($start[$agent[0]]['s'], $start[$agent[0]]['e']) as $i )
        {
            $robot = trim($robots[$i]);
            if( preg_match("~^$robot~", $agent) )
            {
                return TRUE;
            }
        }
    }

    return FALSE;
}

function detect_proxy()
{
    $headers = array('HTTP_COMING_FROM',
                     'HTTP_CLIENT_IP',
                     'HTTP_PC_REMOTE_ADDR',
                     'HTTP_CLIENTADDRESS',
                     'HTTP_CLIENT_ADDRESS',
                     'HTTP_SP_HOST',
                     'HTTP_SP_CLIENT',
                     'HTTP_10_0_0_0',
                     'HTTP_RLNCLIENTIPADDR',
                     'HTTP_REMOTE_HOST_WP',
                     'HTTP_XONNECTION');

    $joined_headers = join(' ', array_keys($_SERVER));

    // Regular header detection
    foreach( $headers as $header )
    {
        if( isset($_SERVER[$header]) )
        {
            return TRUE;
        }
    }

    // Check headers by regular expression
    if( preg_match('~(_X_|PROXY|VIA|CACHE|XXX|FORWARD)~i', $joined_headers) )
    {
        return TRUE;
    }

    if( isset($_SERVER['HTTP_FROM']) && preg_match('/(\d{1,3}\.){3}\d{1,3}/', $_SERVER['HTTP_FROM']) )
    {
        return TRUE;
    }

    return FALSE;
}

?>