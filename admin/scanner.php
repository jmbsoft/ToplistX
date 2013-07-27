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
    echo "Invalid access: This script requires the CLI version of PHP\n";
    exit;
}

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");


// Get the configuration ID from command line parameter
$config_id = $GLOBALS['argv'][1];

// Define penalties
$penalties = array('ignore' => 0x00000000,
                   'report' => 0x00000001,
                   'disable' => 0x00000002,
                   'delete' => 0x00000004,
                   'blacklist' => 0x00000008);

// Exception bitmasks
$exceptions = array('connect' => 0x00000001,
                    'forward' => 0x00000002,
                    'broken' => 0x00000004,
                    'blacklist' => 0x00000008);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

// Get scanner configuration information
$config = $DB->Row('SELECT * FROM `tlx_scanner_configs` WHERE `config_id`=?', array($config_id));
if( !$config )
{
    echo "Invalid configuration ID $config_id\n";
    exit;
}
$configuration = unserialize($config['configuration']);


// See if another instance of this scanner configuration is already running
if( $config['pid'] != 0 && $config['status_updated'] > time() - 300 )
{
    echo "This scanner configuration is already running\n";
    exit;
}


// Clear previous scan results
$DB->Update('DELETE FROM `tlx_scanner_results` WHERE `config_id`=?', array($config_id));


// Set the last run time, pid, and status
$DB->Update('UPDATE `tlx_scanner_configs` SET `current_status`=?,`status_updated`=?,`date_last_run`=?,`pid`=? WHERE `config_id`=?',
            array('Starting...',
                  time(),
                  MYSQL_NOW,
                  getmypid(),
                  $config_id));
                  
                  
// Setup the MySQL query
$s =& GenerateQuery();                  


// Get the galleries to scan
$result = $DB->Query($s->Generate(), $s->binds);
$current_account = 0;
$total_accounts = $DB->NumRows($result);


// Create history entry
$DB->Update('INSERT INTO `tlx_scanner_history` VALUES (?,?,?,?,?,?,?,?,?,?)', 
            array(null,
                  $config_id,
                  MYSQL_NOW,
                  null,
                  $total_accounts,
                  0,
                  0,
                  0,
                  0,
                  0));
                  
$history_id = $DB->InsertID();

if( $total_accounts == 0 )
{
    $DB->Update('UPDATE `tlx_scanner_configs` SET `current_status`=?,`status_updated`=? WHERE `config_id`=?', 
                array("No accounts to scan - exiting",
                      time(),
                      $config_id));
                      
    sleep(10);
}

while( $account = $DB->NextRow($result) )
{
    $exception = 0x00000000;
    $current_account++;
    
    // Exit if stopped (pid set to 0)
    $pid = $DB->Count('SELECT `pid` FROM `tlx_scanner_configs` WHERE `config_id`=?', array($config_id));
    if( $pid == 0 )
    {
        break;
    }
    
    // Update scanner status
    $DB->Update('UPDATE `tlx_scanner_configs` SET `current_status`=?,`status_updated`=? WHERE `config_id`=?', 
                array("Scanning account $current_account of $total_accounts",
                      time(),
                      $config_id));
                      
    // Update history
    $DB->Update('UPDATE `tlx_scanner_history` SET `scanned`=? WHERE `history_id`=?', array($current_account, $history_id));

    
    // Check if the site URL is working
    $http = new Http();    
    if( $http->Get($account['site_url'], $C['allow_redirect']) )
    {
        $account['html'] = $http->body;
        $account['headers'] = $http->raw_response_headers;
    }
    else
    {
        // Bad status code
        if( !empty($http->response_headers['status']) )
        {
            if( preg_match('~^3\d\d~', $http->response_headers['status']) )
            {
                $exception = $exceptions['forward'];
            }
            else
            {
                $exception = $exceptions['broken'];
            }
        }
        
        // Connection error
        else
        {
            $exception = $exceptions['connect'];
        }
    }
    
    $account['http'] =& $http;
    
    // Check the blacklist
    if( $configuration['action_blacklist'] != 0 && ($blacklisted = CheckBlacklistAccount($account)) !== FALSE )
    {
        $exception |= $exceptions['blacklist'];
        $account['blacklist_item'] = $blacklisted[0]['match'];
    }  
    
    // Handle any exceptions
    $processed = FALSE;
    if( $exception )
    {
        $processed = ProcessAccount($account, $exception);
    }    
    
    // Re-enable an account if there are no exceptions
    if( $configuration['enable_disabled'] && !$processed && !$exception && $account['disabled'] )
    {
        $DB->Update('UPDATE `tlx_accounts` SET `disabled`=0 WHERE `username`=?', array($account['username']));
    }
    
    // Update date of last scan
    if( !$processed )
    {
        $gallery['date_scanned'] = gmdate(DF_DATETIME, TimeWithTz());
        $DB->Update('UPDATE `tlx_accounts` SET `date_scanned`=? WHERE `username`=?', 
                    array(gmdate(DF_DATETIME, TimeWithTz()), 
                          $account['username']));
    }

    unset($http);
    unset($account);
}

$DB->Free($result);

// Update history
$DB->Update('UPDATE `tlx_scanner_history` SET `date_end`=? WHERE `history_id`=?', array(gmdate(DF_DATETIME, TimeWithTz()), $history_id));

// Mark the scanner as no longer running
$DB->Update('UPDATE `tlx_scanner_configs` SET `current_status`=?,`status_updated`=?,`pid`=? WHERE `config_id`=?',
            array('Not Running',
                  time(),
                  0,
                  $config_id));


// E-mail administrators
if( $configuration['process_emailadmin'] )
{
    $administrators =& $DB->FetchAll('SELECT * FROM `tlx_administrators`');
    
    $t = new Template();
    $t->assign_by_ref('config', $C);
    $t->assign('total', $total_accounts);
    $t->assign('scanned', $current_account);
    $t->assign('config_id', $config_id);
    
    foreach( $administrators as $admininstrator )
    {
        if( $admininstrator['notifications'] & E_SCANNER_COMPLETE )
        {
            SendMail($administrator['email'], 'email-admin-scanner.tpl', $t);
        }
    }
}


// Rebuild ranking pages
if( $configuration['process_rebuild'] )
{
    RebuildPages();
}

                
                
$DB->Disconnect();

exit;

function ProcessAccount(&$account, &$exception)
{
    global $configuration, $exceptions, $penalties, $DB, $config_id, $history_id;

    $removed = FALSE;
    $message = '';
    $penalty = 0x00000000;
    $reasons =  array('connect' => "Connection error: {$account['http']->errstr}",
                      'forward' => "Redirecting URL: {$account['http']->response_headers['status']}",
                      'broken' => "Broken URL: {$account['http']->response_headers['status']}",                    
                      'blacklist' => "Blacklisted data: " . htmlspecialchars($account['blacklist_item']));


    // Determine the most strict penalty based on the infractions that were found
    foreach( $exceptions as $key => $value )
    {
        if( ($exception & $value) && ($configuration['action_'.$key] >= $penalty) )
        {
            $message = $reasons[$key];
            $penalty = intval($configuration['action_'.$key], 16);
        }
    }

    
    // Blacklist
    if( $penalty & $penalties['blacklist'] )
    {
        $action = 'Blacklisted';
        $removed = TRUE;    

        AutoBlacklist($account);
        DeleteAccount($account['username'], $account);
        
        // Update history
        $DB->Update('UPDATE `tlx_scanner_history` SET `exceptions`=`exceptions`+1,`blacklisted`=`blacklisted`+1 WHERE `history_id`=?', array($history_id));
    }

    // Delete
    else if( $penalty & $penalties['delete'] )
    {
        $action = 'Deleted';
        $removed = TRUE;
        
        DeleteAccount($account['username'], $account);
        
        // Update history
        $DB->Update('UPDATE `tlx_scanner_history` SET `exceptions`=`exceptions`+1,`deleted`=`deleted`+1 WHERE `history_id`=?', array($history_id));
    }

    // Disable
    else if( $penalty & $penalties['disable'] )
    {
        $action = 'Disabled';
        
        // Disable account
        $DB->Update('UPDATE `tlx_accounts` SET `disabled`=1 WHERE `username`=?', array($account['username']));
        
        // Update history
        $DB->Update('UPDATE `tlx_scanner_history` SET `exceptions`=`exceptions`+1,`disabled`=`disabled`+1 WHERE `history_id`=?', array($history_id));
    }


    // Display in report
    else if( $penalty & $penalties['report'] )
    {
        $action = 'Unchanged';
        
        // Update history
        $DB->Update('UPDATE `tlx_scanner_history` SET `exceptions`=`exceptions`+1 WHERE `history_id`=?', array($history_id));
    }

    // Ignore
    else
    {
        // Do nothing
        $exception = 0x00000000;
        return $removed;
    }


    $DB->Update('INSERT INTO `tlx_scanner_results` VALUES (?,?,?,?,?,?,?)',
                array($config_id,
                      $account['username'],
                      $account['site_url'],
                      $account['http']->response_headers['status'],
                      gmdate(DF_DATETIME, TimeWithTz()),
                      $action,
                      $message));
                      
    return $removed;             
}

function &GenerateQuery()
{
    global $DB, $configuration;
    
    $s = new SelectBuilder('*', 'tlx_accounts');
    
    if( count($configuration['status']) > 0 && count($configuration['status']) < 5 )
    {
        $s->AddWhere('status', ST_IN, join(',', array_keys($configuration['status'])));
    }
    
    if( preg_match(RE_DATETIME, $configuration['date_added_start']) && preg_match(RE_DATETIME, $configuration['date_added_end']) )
    {
        $s->AddWhere('date_added', ST_BETWEEN, "{$configuration['date_added_start']},{$configuration['date_added_end']}");
    }
    
    if( preg_match(RE_DATETIME, $configuration['date_scanned_start']) && preg_match(RE_DATETIME, $configuration['date_scanned_end']) )
    {
        $s->AddWhere('date_scanned', ST_BETWEEN, "{$configuration['date_scanned_start']},{$configuration['date_scanned_end']}");
    }
        
    // Specific categories selected
    if( !IsEmptyString($configuration['categories'][0]) )
    {
        $s->AddWhere('category_id', ST_IN, join(',', $configuration['categories']));
    }
    
    return $s;
}

?>
