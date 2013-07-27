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

if( !defined('E_STRICT') ) define('E_STRICT', 2048);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require_once('includes/config.php');
require_once('includes/template.class.php');
require_once('includes/mysql.class.php');

@set_magic_quotes_runtime(0);
if( function_exists('date_default_timezone_set') )
{
    date_default_timezone_set('America/Chicago');
}

if( get_magic_quotes_gpc() )
{
    astripslashes($_POST);
}

$_POST['s'] = trim($_POST['s']);
$page = !empty($_POST['p']) ? $_POST['p'] : 1;
$per_page = !empty($_POST['pp']) ? $_POST['pp'] : 20;
$too_short = strlen($_POST['s']) < 4;
$search_id = sha1("{$_POST['s']}-{$_POST['c']}-$page");

$t = new Template();
$t->caching = TRUE;
$t->cache_lifetime = 3600;

$t->assign('search_term', $_POST['s']);
$t->assign('search_category', $_POST['c']);
$t->assign('page', $page);
$t->assign('per_page', $per_page);
$t->assign('search_too_short', $too_short);
$t->assign_by_ref('config', $C);

if( !$too_short && !$t->is_cached('search-results.tpl', $search_id) )
{
    $DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
    $DB->Connect();

    $accounts = array();
    $result = $DB->QueryWithPagination('SELECT * FROM `tlx_accounts` JOIN `tlx_account_hourly_stats` USING (`username`) WHERE ' .
                                       'MATCH(`title`,`description`,`keywords`) AGAINST(? IN BOOLEAN MODE) AND ' .
                                       '`status`=? AND ' .
                                       '`disabled`=0 ' .
                                       (!empty($_POST['c']) && is_numeric($_POST['c']) ? ' AND `category_id`=' . $DB->Escape($_POST['c']) . ' ' : '') .
                                       'ORDER BY `unique_in_total` DESC',
                                       array($_POST['s'], 'active'),
                                       $page,
                                       $per_page);

    if( $result['result'] )
    {
        while( $account = $DB->NextRow($result['result']) )
        {
            $accounts[] = array_merge($account, $DB->Row('SELECT * FROM `tlx_account_fields` WHERE `username`=?', array($account['username'])));
        }

        $DB->Free($result['result']);
        unset($result['result']);
    }

    $categories = $DB->FetchAll('SELECT * FROM `tlx_categories` ORDER BY `name`');
    if( !$categories )
    {
        $categories = array();
    }

    $t->assign_by_ref('categories', $categories);
    $t->assign_by_ref('pagination', $result);
    $t->assign_by_ref('results', $accounts);

    $DB->Disconnect();
}
else if( $too_short )
{
    $DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
    $DB->Connect();
    $t->assign_by_ref('categories', $DB->FetchAll('SELECT * FROM `tlx_categories` ORDER BY `name`'));
    $DB->Disconnect();
}

$t->display('search-results.tpl', $search_id);

function thilite($string)
{
    $term = $_POST['s'];

    if( $term )
    {
        if( isset($GLOBALS['re_matches']) || preg_match_all('~("[^"]+"|\w+\*\w*?|\b\w+\b)~', $term, $GLOBALS['re_matches']) )
        {
            foreach( $GLOBALS['re_matches'][0] as $match )
            {
                $match = preg_quote(str_replace(array('+', '-', '"', '(', ')'), '', $match));
                $match = str_replace('\*', '.*?', $match);
                $string = preg_replace("/\b($match)\b/i", "<span class=\"hilite\">$1</span>", $string);
            }
        }
    }

    return $string;
}

function astripslashes(&$array)
{
    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            astripslashes($array[$key]);
        }
        else
        {
            $array[$key] = stripslashes($value);
        }
    }
}

?>