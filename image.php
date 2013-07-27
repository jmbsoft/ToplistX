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
require_once('includes/config.php');

if( $_GET['id'] )
{
    // Connect to database
    @mysql_connect($C['db_hostname'], $C['db_username'], $C['db_password']) or die(mysql_error());
    @mysql_select_db($C['db_name']) or die(mysql_error());

    $result = @mysql_query("SELECT * FROM `tlx_account_ranks` WHERE `username`='".mysql_real_escape_string($_GET['id'])."'") or die(mysql_error());
    $account = mysql_fetch_assoc($result);
    mysql_free_result($result);
    mysql_close();

    if( $account && $account['rank'] <= $C['ranking_images'] )
    {
        header("Location: {$C['ranking_images_url']}/{$account['rank']}.{$C['ranking_images_extension']}");
        return;
    }
}

header("Location: {$C['ranking_images_url']}/default.{$C['ranking_images_extension']}");

?>