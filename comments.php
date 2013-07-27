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

require_once('includes/common.php');             
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");

SetupRequest();

$t = new Template();
$t->assign_by_ref('config', $C);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();


$account = $DB->Row('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($_REQUEST['id']));

if( $account )
{
    $page = isset($_REQUEST['p']) ? $_REQUEST['p'] : 1;
    $per_page = isset($_REQUEST['pp']) ? $_REQUEST['pp'] : 20;

    $result = $DB->QueryWithPagination('SELECT * FROM `tlx_account_comments` WHERE `status`=? AND `username`=? ORDER BY `date_submitted` DESC', array('approved', $_REQUEST['id']), $page, $per_page);

    if( $result['result'] )
    {
        while( $comment = $DB->NextRow($result['result']) )
        {
            $comment['date'] = strtotime($comment['date_submitted']);
            $comments[] = $comment;
        }

        $DB->Free($result['result']);
        unset($result['result']);
    }

    $t->assign_by_ref('pagination', $result);
    $t->assign_by_ref('comments', $comments);
    $t->assign_by_ref('account', $account);

    $t->display('comments-main.tpl');
}
else
{
    $t->assign('error', $L['BAD_ACCOUNT']);
    $t->display('error-nice.tpl');
}

$DB->Disconnect();

?>
