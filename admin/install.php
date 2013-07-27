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
require_once("{$GLOBALS['BASE_DIR']}/includes/compiler.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

SetupRequest();

$t = new Template();
$errors = array();

function Initialize()
{
    global $errors, $t, $C, $template;
        
    // Already initialized
    if( !empty($C['db_username']) )
    {
        $t->assign('mode', 'done');
        echo $t->parse($template);
    }
    else
    {
        // Form submitted
        if( $_SERVER['REQUEST_METHOD'] == 'POST' )
        {
            $connection = TestDBConnection();

            if( !$connection )
            {
                $t->assign_by_ref('errors', $errors);
                $t->assign_by_ref('request', $_REQUEST);
                $t->assign('mode', 'getdb');
                echo $t->parse($template);
            }
            else
            {
                // Create database tables and setup initial login
                FileWrite("{$GLOBALS['BASE_DIR']}/data/.htaccess", "deny from all");
                CreateTables();
                WriteConfig($_REQUEST);
                RecompileTemplates();

                // Display initialization finished screen
                $t->assign('control_panel', "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/index.php");
                $t->assign('mode', 'login');
                echo $t->parse($template);
            }
        }


        // Check that files are installed correctly
        else
        {
            // Run pre-initialization tests
            ServerTest();
            FilesTest();
            DirectoriesTest();
            TemplatesTest();

            if( is_dir('../utilities') )
            {
                $errors[] = 'For security purposes, the utilities directory must be removed';
            }


            if( count($errors) )
            {
                // Display failed test information
                $t->assign('mode', 'errors');
                $t->assign_by_ref('errors', $errors);
                echo $t->parse($template);
            }
            else
            {
                $_REQUEST['db_hostname'] = 'localhost';
                $t->assign_by_ref('request', $_REQUEST);
                $t->assign_by_ref('errors', $errors);
                $t->assign('mode', 'getdb');
                echo $t->parse($template);   
            }
        }
    }
}

function ServerTest()
{
    global $errors;

    $server = GetServerCapabilities();
    
    if( $server['safe_mode'] )
    {
        $errors[] = 'PHP safe_mode appears to be enabled; this must be disabled for ToplistX to function properly';
    }
    
    if( !$server['shell_exec'] )
    {
        $errors[] = "The PHP shell_exec() function " . ($server['safe_mode'] ? "cannot be used because of the safe_mode setting" : "is disabled") . ". This function is required for ToplistX to work properly";
    }
    
    if( !$server['php_cli'] )
    {
        $errors[] = 'The CLI version of PHP could not be found on your server.  This is required for ToplistX to function properly';
    }
    else
    {
        if( $server['php_cli_safe_mode'] )
        {
            $errors[] = 'The CLI version of PHP is running with safe_mode enabled.  This must be disabled for ToplistX to function properly';
        }
    }
}

function TemplatesTest()
{
    global $errors;

    foreach( glob("{$GLOBALS['BASE_DIR']}/templates/*.*") as $filename )
    {
        if( !is_writeable($filename) )
        {
            $errors[] = "Template file $filename has incorrect permissions; change to 666";
        }
    }
}

function FilesTest()
{
    global $errors;

    $files = array("{$GLOBALS['BASE_DIR']}/includes/language.php",
                   "{$GLOBALS['BASE_DIR']}/includes/config.php");
    
    foreach( $files as $file )
    {
        if( !is_file($file) )
        {
            $errors[] = "File " . basename($file) . " is missing; please upload this file and set permissions to 666";
        }
        else if( !is_writeable($file) )
        {
            $errors[] = "File " . basename($file) . " has incorrect permissions; change to 666";
        }
    }
}

function DirectoriesTest()
{
    global $errors;

    $dirs = array(array('dir' => "{$GLOBALS['BASE_DIR']}/data", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/templates/compiled", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/templates/cache", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/fonts", 'writeable' => FALSE));
    
    foreach( $dirs as $dir )
    {
        if( !is_dir($dir['dir']) )
        {
            $errors[] = "Directory {$dir['dir']} is missing; please create this directory" . ($dir['writeable'] === TRUE ? " and set permissions to 777" : '');
        }
        else if( $dir['writeable'] === TRUE && !is_writeable($dir['dir']) )
        {
            $errors[] = "Directory {$dir['dir']} has incorrect permissions; change to 777";
        }
    }
}

function CreateTables()
{
    global $t, $DB;
    
    $DB = new DB($_REQUEST['db_hostname'], $_REQUEST['db_username'], $_REQUEST['db_password'], $_REQUEST['db_name']);
    $DB->Connect();
    
    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    foreach( $tables as $name => $create )
    {
        $DB->Update("CREATE TABLE IF NOT EXISTS $name ( $create ) TYPE=MyISAM");
    }
    
    $password = RandomPassword();
    $domain = preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']);
    
    $t->assign('password', $password);                      

    // Setup administrator account
    $DB->Update('DELETE FROM `tlx_administrators` WHERE `username`=?', array('administrator'));
    $DB->Update('INSERT INTO `tlx_administrators` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array('administrator',
                      sha1($password),
                      '',
                      0,
                      'Administrator',
                      "webmaster@$domain",
                      'administrator',
                      null,
                      null,
                      null,
                      null,
                      0,
                      0));
              

    if( ($last_updates = GetValue('last_updates')) === null )
    {
        $last_updates = array();
        $last_updates['daily'] = date('Y-m-d');
        $last_updates['hourly'] = null;
        
        StoreValue('last_updates', serialize($last_updates));
    }
    
    $DB->Update('INSERT INTO `tlx_skim_ratio` VALUES (0,0)');
        
    $DB->Update('DELETE FROM `tlx_ip2country`');
    $DB->Update('DELETE FROM `tlx_countries`');
    $DB->Update('DELETE FROM `tlx_country_stats`');
    
    $countries = file('../includes/countries.php');
    foreach( $countries as $line )
    {
        $DB->Update($line);
    }
              
    //$DB->Disconnect();
}

function TestDBConnection()
{
    global $errors;

    restore_error_handler();

    $handle = @mysql_connect($_REQUEST['db_hostname'], $_REQUEST['db_username'], $_REQUEST['db_password']);

    if( !$handle )
    {
        $errors[] = mysql_error();
    }
    else
    {
        if( !mysql_select_db($_REQUEST['db_name'], $handle) )
        {
            $errors[] = mysql_error($handle);
        }

        $result = mysql_query("SELECT VERSION()", $handle);
        $row = mysql_fetch_row($result);
        mysql_free_result($result);
       
        $version = explode('.', $row[0]);

        if( $version[0] < 4 )
        {
            $errors[] = "This software requires MySQL version 4.0.0 or newer\nYour server has version {$row[0]} installed.";
        }        

        mysql_close($handle);
    }

    set_error_handler('Error');
    
    if( count($errors) )
    {
        return FALSE;
    }

    return TRUE;
}


$template = <<<TEMPLATE
{php}
require_once("includes/header.php");
{/php}

<div id="main-content">
  <div id="centered-content" style="width: 800px;">
      <div class="heading">
      <div class="heading-icon">
        <a href="docs/install-script.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      ToplistX Installation
    </div>

      {if \$mode == 'getdb'}
      <form action="install.php" method="POST">
      <div class="margin-bottom margin-top">
        Please enter your MySQL database information in the fields below
      </div>
            
      {if count(\$errors)}
      <div class="alert margin-bottom">
        {foreach var=\$error from=\$errors}
          {\$error|htmlspecialchars|nl2br}<br />
        {/foreach}
        Please double check your MySQL information and try again.
      </div>
      {/if}

      <div class="fieldgroup">
        <label for="db_username" style="width: 300px;">MySQL Username:</label>
        <input type="text" name="db_username" id="db_username" size="20" value="{\$request.db_username|htmlspecialchars}" />
      </div>

      <div class="fieldgroup">
        <label for="db_password" style="width: 300px;">MySQL Password:</label>
        <input type="text" name="db_password" id="db_password" size="20" value="{\$request.db_password|htmlspecialchars}" />
      </div>
      
      <div class="fieldgroup">
        <label for="db_name" style="width: 300px;">MySQL Database Name:</label>
        <input type="text" name="db_name" id="db_name" size="20" value="{\$request.db_name|htmlspecialchars}" />
      </div>

      <div class="fieldgroup">
        <label for="db_hostname" style="width: 300px;">MySQL Hostname:</label>
        <input type="text" name="db_hostname" id="db_hostname" size="20" value="{\$request.db_hostname|htmlspecialchars}" />
      </div>
        
      <div class="fieldgroup">
        <label for="" style="width: 300px;"></label>
        <button type="submit">Submit</button>
      </div>
    </form>
    {elseif \$mode == 'errors'}
      <div class="margin-bottom margin-top">
        Some of the pre-installation tests have failed.  Please see the error messages listed below and correct these issues.
        Once they have been corrected, you can reload this script to continue the installation process.
      </div>
      
      <div class="alert margin-bottom">
        {foreach var=\$error from=\$errors}
          {\$error|htmlspecialchars}<br />
        {/foreach}
      </div>
    {elseif \$mode == 'login'}
    <div class="notice margin-bottom margin-top">
      The software initialization has been completed; use the information below to login to the control panel
    </div>
    
    <b>Control Panel URL:</b> <a href="{\$control_panel}" onclick="return confirm('Have you written down your username and password?')">{\$control_panel}</a><br />
    <b>Username:</b> administrator<br />
    <b>Password:</b> {\$password|htmlspecialchars}
    {else}
      <div class="notice margin-bottom margin-top">
      The software has already been installed, please remove this file from your server
      </div>
    {/if}
  </div>
</div>


</body>
</html>
TEMPLATE;

Initialize();

?>

