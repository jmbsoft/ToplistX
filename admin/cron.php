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

$path = realpath(dirname(__FILE__));
chdir($path);

// Make sure CLI API is being used
if( php_sapi_name() != 'cli' )
{
    echo "Invalid access: This script requires the CLI version of PHP";
    exit;
}

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

// Run function based on command line argument
switch($GLOBALS['argv'][1])
{
case '--rebuild':
    RebuildPages();
    break;
    
case '--hourly-stats':
    ProcessHourlyStats();
    break;
    
case '--daily-stats':
    ProcessDailyStats();
    break;
 
case '--remove-unconfirmed':
    DeleteUnconfirmed();
    break;
      
case '--backup':
    CommandLineBackup($GLOBALS['argv'][2]);
    break;
    
case '--restore':
    CommandLineRestore($GLOBALS['argv'][2]);
    break;
    
case '--optimize':
    OptimizeDatabase();
    break;
    
case '--bulk-mail':
    BulkMail();
    break;
}

$DB->Disconnect();

function BulkMail()
{
    global $C, $DB;
    
    $message = GetValue('bulk_email');
    
    $t = new Template();
    $t->assign_by_ref('config', $C);
    
    $result = $DB->Query('SELECT * FROM `tlx_accounts` JOIN `tlx_account_fields` USING (`username`)');
    while( $account = $DB->NextRow($result) )
    {        
        if( $account['status'] == STATUS_ACTIVE )
        {
            $t->assign_by_ref('account', $account);
            SendMail($account['email'], $message, $t, FALSE);
        }
    }
    $DB->Free($result);
    
    DeleteValue('bulk_email');
}

function ParseCommandLine()
{
    $args = array();
    
    foreach( $GLOBALS['argv'] as $arg )
    {
        // Check if this is a valid argument in --ARG or --ARG=SOMETHING format
        if( preg_match('~--([a-z0-9\-_]+)=?(.*)?~i', $arg, $matches) )
        {
            $args[$matches[1]] = ($matches[2] ? $matches[2] : TRUE);
        }
    }
    
    return $args;
}

function OptimizeDatabase()
{
    global $DB;
    
    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    foreach( array_keys($tables) as $table )
    {
        $DB->Update('REPAIR TABLE #', array($table));
        $DB->Update('OPTIMIZE TABLE #', array($table));
    }
}

function CommandLineBackup($filename)
{
    global $C, $DB;
    
    if( IsEmptyString($filename) )
    {
        trigger_error('A filename must be supplied', E_USER_ERROR);
    }
    
    $filename = "{$GLOBALS['BASE_DIR']}/data/" . basename($filename);    
    
    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);
    
    if( !$C['safe_mode'] && $C['mysqldump'] )
    {
        $command = "{$C['mysqldump']} " .
                   "-u" . escapeshellarg($C['db_username']) . " " .
                   "-p" . escapeshellarg($C['db_password']) . " " .
                   "-h" . escapeshellarg($C['db_hostname']) . " " .
                   "--opt " .
                   escapeshellarg($C['db_name']) . " " .
                   join(' ', array_keys($tables)) . 
                   " >" . escapeshellarg($filename) . " 2>&1";

        shell_exec($command);
    }
    else
    {
        DoBackup($filename, $tables);
    }
    
    StoreValue('last_backup', MYSQL_NOW);
    
    @chmod($filename, 0666);
}

function CommandLineRestore($filename)
{   
    global $C, $DB;
    
    if( IsEmptyString($filename) )
    {
        trigger_error('A filename must be supplied', E_USER_ERROR);
    }
    
    $filename = "{$GLOBALS['BASE_DIR']}/data/" . basename($filename);    
    
    if( !$C['safe_mode'] && $C['mysql'] )
    {
        $command = "{$C['mysql']} " .
                   "-u" . escapeshellarg($C['db_username']) . " " .
                   "-p" . escapeshellarg($C['db_password']) . " " .
                   "-h" . escapeshellarg($C['db_hostname']) . " " .
                   "-f " .
                   escapeshellarg($C['db_name']) . " " .
                   " <$filename >/dev/null 2>&1";

        shell_exec($command);
    }
    else
    {
        DoRestore($filename);
    }
}

?>
