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

$functions = array('comment' => 'tlxShAccountComment',
                   'docomment' => 'tlxAccountComment',
                   'rate' => 'tlxShAccountRate',
                   'dorate' => 'tlxAccountRate',
                   'dorateandcomment' => 'tlxAccountRateAndComment');

require_once('includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");

SetupRequest();

$t = new Template();
$t->assign_by_ref('config', $C);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

if( isset($functions[$_REQUEST['r']]) && function_exists($functions[$_REQUEST['r']]) )
{
    call_user_func($functions[$_REQUEST['r']]);
}
else
{
    tlxShAccountRateAndComment();
}

$DB->Disconnect();

function tlxShAccountComment($errors = null)
{
    global $C, $DB, $L, $t;

    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

    if( $account )
    {
        $t->assign_by_ref('errors', $errors);
        $t->assign_by_ref('account', $account);
        $t->assign_by_ref('rating', $_REQUEST);

        if( !isset($_REQUEST['rating']) )
        {
            $_REQUEST['rating'] = ceil($C['max_rating']/2);
        }

        $t->display('rate-only-comment.tpl');
    }
    else
    {
        $t->assign('error', $L['BAD_ACCOUNT']);
        $t->display('error-nice.tpl');
    }
}

function tlxAccountComment()
{
    global $C, $DB, $L, $t;

    $v = new Validator();

    $v->Register($_REQUEST['name'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['NAME']));
    $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
    $v->Register($_REQUEST['comment'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['COMMENT']));
    $v->Register($_REQUEST['comment'], V_LENGTH, sprintf($L['COMMENT_LENGTH'], $C['min_comment_length'], $C['max_comment_length']), "{$C['min_comment_length']},{$C['max_comment_length']}");


    // Verify captcha code
    if( $C['rate_captcha'] )
    {
        VerifyCaptcha($v);
    }

    // Check blacklist
    if( ($blacklisted = CheckBlacklistRating($_REQUEST)) !== FALSE )
    {
        $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountComment', TRUE);
    }

    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

    if( $account )
    {
        ProcessComment();
    }

    $t->assign_by_ref('account', $account);
    $t->assign_by_ref('rating', $_REQUEST);
    $t->display('rate-submitted.tpl');
}

function tlxShAccountRate($errors = null)
{
    global $C, $DB, $L, $t;

    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

    if( $account )
    {
        $t->assign_by_ref('errors', $errors);
        $t->assign_by_ref('account', $account);
        $t->assign_by_ref('rating', $_REQUEST);

        if( !isset($_REQUEST['rating']) )
        {
            $_REQUEST['rating'] = ceil($C['max_rating']/2);
        }

        $t->display('rate-only-rate.tpl');
    }
    else
    {
        $t->assign('error', $L['BAD_ACCOUNT']);
        $t->display('error-nice.tpl');
    }
}

function tlxAccountRate()
{
    global $C, $DB, $L, $t;

    $v = new Validator();

    $v->Register($_REQUEST['rating'], V_BETWEEN, sprintf($L['RATING_RANGE'], $C['max_rating']), array('min' => 1, 'max' => $C['max_rating']));

    // Verify captcha code
    if( $C['rate_captcha'] )
    {
        VerifyCaptcha($v);
    }

    // Check blacklist
    if( ($blacklisted = CheckBlacklistRating($_REQUEST)) !== FALSE )
    {
        $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountRate', TRUE);
    }

    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

    if( $account )
    {
        ProcessRating($account);
    }

    $t->assign_by_ref('account', $account);
    $t->assign_by_ref('rating', $_REQUEST);
    $t->display('rate-submitted.tpl');
}

function tlxShAccountRateAndComment($errors = null)
{
    global $C, $DB, $L, $t;

    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

    if( $account )
    {
        $t->assign_by_ref('errors', $errors);
        $t->assign_by_ref('account', $account);
        $t->assign_by_ref('rating', $_REQUEST);

        if( !isset($_REQUEST['rating']) )
        {
            $_REQUEST['rating'] = ceil($C['max_rating']/2);
        }

        $t->display('rate-comment-and-rate.tpl');
    }
    else
    {
        $t->assign('error', $L['BAD_ACCOUNT']);
        $t->display('error-nice.tpl');
    }
}

function tlxAccountRateAndComment()
{
    global $C, $DB, $L, $t;

    $v = new Validator();

    $v->Register($_REQUEST['rating'], V_BETWEEN, sprintf($L['RATING_RANGE'], $C['max_rating']), array('min' => 1, 'max' => $C['max_rating']));

    $comment = FALSE;
    if( !IsEmptyString($_REQUEST['name']) || !IsEmptyString($_REQUEST['email']) || !IsEmptyString($_REQUEST['comment']) )
    {
        $comment = TRUE;
        $v->Register($_REQUEST['name'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['NAME']));
        $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
        $v->Register($_REQUEST['comment'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['COMMENT']));
        $v->Register($_REQUEST['comment'], V_LENGTH, sprintf($L['COMMENT_LENGTH'], $C['min_comment_length'], $C['max_comment_length']), "{$C['min_comment_length']},{$C['max_comment_length']}");
    }

    // Verify captcha code
    if( $C['rate_captcha'] )
    {
        VerifyCaptcha($v);
    }

    // Check blacklist
    if( ($blacklisted = CheckBlacklistRating($_REQUEST)) !== FALSE )
    {
        $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('tlxShAccountRateAndComment', TRUE);
    }

    $account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

    if( $account )
    {
        ProcessRating($account);

        if( $comment )
        {
            ProcessComment();
        }
    }

    $t->assign_by_ref('account', $account);
    $t->assign_by_ref('rating', $_REQUEST);
    $t->display('rate-submitted.tpl');
}

function ProcessRating(&$account)
{
    global $C, $DB, $L, $t;

    $bad_rating = FALSE;
    $referrer = $_SERVER['HTTP_REFERER'];
    $parsed_referrer = parse_url($_SERVER['HTTP_REFERER']);
    if( IsEmptyString($referrer) || $referrer == '-' || (strpos($C['install_url'], $parsed_referrer['host']) === FALSE && strpos($parsed_referrer['host'], $account['domain']) === FALSE) )
    {
        $bad_rating = TRUE;
    }

    $ratings = array();
    if( $_COOKIE['toplistx_ratings'] )
    {
        $ratings = unserialize($_COOKIE['toplistx_ratings']);

        if( $ratings[$account['username']] )
        {
            $bad_rating = TRUE;
        }

        $ratings[$account['username']] = TRUE;
    }

    $long_ip = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));

    if( !$bad_rating && $DB->Count('SELECT COUNT(*) FROM `tlx_ip_log_ratings` WHERE `username`=? AND `ip_address`=?', array($account['username'], $long_ip)) )
    {
        $bad_rating = TRUE;
    }

    // Update rating information
    if( !$bad_rating )
    {
        $DB->Update('INSERT INTO `tlx_ip_log_ratings` VALUES (?,?,?,?)', array($account['username'], $long_ip, 1, MYSQL_NOW));
        $DB->Update('UPDATE `tlx_accounts` SET `ratings`=`ratings`+1,`ratings_total`=`ratings_total`+? WHERE `username`=?', array($_REQUEST['rating'], $_REQUEST['id']));
    }

    setcookie('toplistx_ratings', serialize($ratings), time()+604800, '/', $C['cookie_domain']);
}

function ProcessComment()
{
    global $C, $DB, $L, $t;

    // Check comments to see if there is comment flooding happening
    $flood = $DB->Count('SELECT COUNT(*) FROM `tlx_account_comments` WHERE `date_submitted` >= DATE_SUB(?, INTERVAL ? SECOND) AND `username`=? AND (`ip_address`=? OR `email`=?)',
                        array(MYSQL_NOW,
                             $C['comment_interval'],
                             $_REQUEST['id'],
                             $_SERVER['REMOTE_ADDR'],
                             $_REQUEST['email']));

    if( $flood )
    {
        // TODO: Handle comment flooding
    }
    else
    {
        // Insert new comment
        $DB->Update('INSERT INTO `tlx_account_comments` VALUES (?,?,?,?,?,?,?,?)',
                    array(null,
                          $_REQUEST['id'],
                          MYSQL_NOW,
                          $_SERVER['REMOTE_ADDR'],
                          $_REQUEST['name'],
                          $_REQUEST['email'],
                          $C['review_comments'] ? 'pending' : 'approved',
                          $_REQUEST['comment']));
    }
}
?>