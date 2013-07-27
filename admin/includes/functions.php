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

if( !defined('ToplistX') ) die("Access denied");

define('SESSION_LENGTH', 600);
define('ACCOUNT_EDITOR', 'editor');
define('ACCOUNT_ADMINISTRATOR', 'administrator');

// Privileges
define('P_ACCOUNT_ADD',     0x00000001);
define('P_ACCOUNT_MODIFY',  0x00000002);
define('P_ACCOUNT_REMOVE',  0x00000004);
define('P_CATEGORY_ADD',    0x00000008);
define('P_CATEGORY_MODIFY', 0x00000010);
define('P_CATEGORY_REMOVE', 0x00000020);
define('P_COMMENT_ADD',     0x00000040);
define('P_COMMENT_MODIFY',  0x00000080);
define('P_COMMENT_REMOVE',  0x00000100);
define('P_CATEGORY',        P_CATEGORY_ADD|P_CATEGORY_MODIFY|P_CATEGORY_REMOVE);
define('P_ACCOUNT',         P_ACCOUNT_ADD|P_ACCOUNT_MODIFY|P_ACCOUNT_REMOVE);
define('P_COMMENT',         P_COMMENT_ADD|P_COMMENT_MODIFY|P_COMMENT_REMOVE);


// User defined field types
$FIELD_TYPES = array(FT_TEXT => FT_TEXT, 
                     FT_TEXTAREA => FT_TEXTAREA, 
                     FT_SELECT => FT_SELECT,
                     FT_CHECKBOX => FT_CHECKBOX);

// Validation types                      
$VALIDATION_TYPES = array(V_NONE => 'None',
                          V_ALPHANUM => 'Alphanumeric',
                          V_BETWEEN => 'Between',
                          V_EMAIL => 'E-mail Address',
                          V_GREATER => 'Greater Than',
                          V_LESS => 'Less Than',
                          V_LENGTH => 'Length',                            
                          V_NUMERIC => 'Numeric',
                          V_REGEX => 'Regular Expression',
                          V_URL => 'HTTP URL',
                          V_URL_WORKING_300 => 'Working URL (Redirection OK)',
                          V_URL_WORKING_400 => 'Working URL (No Redirection)');


// Annotation locations
$ANN_LOCATIONS = array('NorthWest' => 'Top Left',
                       'North' => 'Top Center',
                       'NorthEast' => 'Top Right',
                       'SouthWest' => 'Bottom Left',
                       'South' => 'Bottom Center',
                       'SouthEast' => 'Bottom Right');

function DeleteUnconfirmed()
{
    global $DB;
    
    $removed = 0;
    $result = $DB->Query('SELECT * FROM `tlx_accounts` WHERE `status`=? AND `date_added` <= ?', array(STATUS_UNCONFIRMED, gmdate(DF_DATETIME, TIME_NOW - 172800)));
    
    while( $account = $DB->NextRow($result) )
    {
        DeleteAccount($account['username'], $account);
        $removed++;
    }
    
    $DB->Free($result);
    
    return $removed;
}

function RenumberBuildOrder()
{
    global $DB;

    $DB->Update("SET @build_order=0");
    $result = $DB->Query("SELECT * FROM `tlx_pages` ORDER BY `build_order`");
    while( $page = $DB->NextRow($result) )
    {
        $DB->Update('UPDATE `tlx_pages` SET `build_order`=@build_order:=@build_order+1 WHERE `page_id`=?', array($page['page_id']));
    }
    $DB->Free($result);
}

function &GetBulkAddPages($base_url)
{
    global $DB;
    
    $pages = array();
    $ext = $_REQUEST['ext'];
    
    switch($_REQUEST['category_id'])
    {
        case '':
        {
            $prefix = $_REQUEST['prefix'];
            
            foreach( range(1, $_REQUEST['num_pages']) as $index )
            {
                $page =& $pages[];
                $page['filename'] = "$base_url/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                $page['category_id'] = null;
            }
            
            break;
        }
            
        case '__all__':
        {
            $categories =& $DB->FetchAll('SELECT * FROM `tlx_categories` ORDER BY `name`');
            
            foreach( $categories as $category )
            {
                $prefix = CategoryToPageName($category['name'], $_REQUEST['characters'], $_REQUEST['case']);
                
                foreach( range(1, $_REQUEST['num_pages']) as $index )
                {
                    $page =& $pages[];
                    $page['filename'] = "$base_url/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                    $page['category_id'] = $category['category_id'];
                }
            }
            
            break;
        }
        
        default:
        {
            $prefix = $DB->Count('SELECT `name` FROM `tlx_categories` WHERE `category_id`=?', array($_REQUEST['category_id']));
            $prefix = CategoryToPageName($prefix, $_REQUEST['characters'], $_REQUEST['case']);
            
            foreach( range(1, $_REQUEST['num_pages']) as $index )
            {
                $page =& $pages[];
                $page['filename'] = "$base_url/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                $page['category_id'] = $_REQUEST['category_id'];
            }
            
            break;
        }
    }
    
    return $pages;
}

function CategoryToPageName($name, $characters, $case)
{
    $replacement = '';
    
    switch($characters)
    {
        case 'remove':
            $replacement = '';
            break;
                    
        case 'dash':
            $replacement = '-';
            break;
            
        case 'underscore':
            $replacement = '_';
            break;
    }
    
    $name = preg_replace('~[^a-z0-9]+~i', $replacement, $name);
    
    if( $case == 'lower' )
    {
        $name = strtolower($name);
    }
    
    return $name;
}

function &ValidateCategoryInput($adding = FALSE)
{
    global $DB;
        
    $v = new Validator();
    $v->Register($_REQUEST['name'], V_EMPTY, 'The Category Name(s) field must be filled in');    
    $v->Register($_REQUEST['forward_url'], V_EMPTY, 'The Forward URL field must be filled in');
    $v->Register($_REQUEST['title_min_length'], V_EMPTY, 'The Site Title Length fields must be filled in');
    $v->Register($_REQUEST['title_max_length'], V_EMPTY, 'The Site Title Length fields must be filled in');
    $v->Register($_REQUEST['desc_min_length'], V_EMPTY, 'The Description Length fields must be filled in');
    $v->Register($_REQUEST['desc_max_length'], V_EMPTY, 'The Description Length fields must be filled in');
    $v->Register($_REQUEST['banner_max_width'], V_EMPTY, 'The Max Banner Width field must be filled in');
    $v->Register($_REQUEST['banner_max_height'], V_EMPTY, 'The Max Banner Height field must be filled in');
    $v->Register($_REQUEST['banner_max_bytes'], V_EMPTY, 'The Max Banner Filesize field must be filled in');
    
    
    if( strpos($_REQUEST['name'], ',') !== FALSE )
    {
        $v->SetError('Category names may not contain commas');   
    }
    
    foreach( explode("\n", $_REQUEST['name']) as $name )
    {
        $name = trim($name);
        
        if( strtoupper($name) == 'MIXED' )
        {
            $v->SetError('The word MIXED is reserved and cannot be used as a category name');
        }
        
        if( preg_match('~^-~', $name) )
        {
            $v->SetError('Category names cannot start with a dash (-) character');
        }
        
        if( $adding )
        {
            if( $DB->Count('SELECT COUNT(*) FROM `tlx_categories` WHERE `name`=?', array($name)) )
            {
                $v->SetError('The category '.$name.' already exists');
            }
        }
        else
        {
        }
    }
    
    return $v;
}

function StringChopTooltip($string, $length, $center = FALSE, $append = null)
{
    if( strlen($string) > $length )
    {
        $string = '<span title="'.$string.'" class="tt">' . StringChop($string, $length, $center, $append) . '</span>';
    }
    
    return $string;
}

function DirectoryFromRoot($root, $url)
{
    $parsed_url = parse_url($url);
    
    if( !IsEmptyString($parsed_url['path']) )
    {
        $root .= $parsed_url['path'];
    }
    
    return $root;
}

function PrepareMessage()
{
    UnixFormat($_REQUEST['plain']);
    UnixFormat($_REQUEST['html']);
    
    return "=>[subject]\n" .
           $_REQUEST['subject'] . "\n" .
           "=>[plain]\n" .
           trim($_REQUEST['plain']) . "\n" .
           "=>[html]\n" .
           trim($_REQUEST['html']);
}

function HandleUncheckedFields(&$fields)
{
    foreach($fields as $field)
    {
        if( $field['type'] == FT_CHECKBOX && !isset($_REQUEST[$field['name']]) )
        {
            $_REQUEST[$field['name']] = null;
        }
    }
}

function &ValidateUserDefined($defs_table, $predefined_table, $editing = FALSE)
{
    global $DB, $C;
    
    // See if field name already exists
    $field_count = $DB->Count('SELECT COUNT(*) FROM # WHERE `name`=?', array($defs_table, $_REQUEST['name']));
    
    // Get pre-defined fields so there are no duplicates
    $predefined = $DB->GetColumns($predefined_table);
    
    $v = new Validator();
    $v->Register($_REQUEST['name'], V_EMPTY, 'The Field Name must be filled in');
    $v->Register($_REQUEST['name'], V_REGEX, 'The Field Name can contain only letters, numbers, and underscores', '/^[a-z0-9_]+$/i');
    $v->Register($_REQUEST['name'], V_LENGTH, 'The Field Name can be at most 30 characters', '0,30');
    $v->Register($_REQUEST['label'], V_EMPTY, 'The Label field must be filled in');
    
    if( $_REQUEST['type'] == FT_SELECT )
        $v->Register($_REQUEST['options'], V_EMPTY, 'The Options field must be filled in for this field type');
        
    if( $_REQUEST['validation'] != V_NONE )
        $v->Register($_REQUEST['validation_message'], V_EMPTY, 'The Validation Error field must be filled in');        
    
    if( !$editing || ($_REQUEST['name'] != $_REQUEST['old_name']) )
    {    
        $v->Register(in_array($_REQUEST['name'], $predefined), V_FALSE, 'The field name you have selected conflicts with a pre-defined field name');
        $v->Register($field_count, V_ZERO, 'A field with this name already exists');
    }
    
    return $v;
}

function RecompileTemplates()
{
    $t = new Template();   
    $templates =& DirRead("{$GLOBALS['BASE_DIR']}/templates", '^(?!(email|default))[^\.]+\.tpl$');
    
    // Compile global templates first
    foreach( glob("{$GLOBALS['BASE_DIR']}/templates/global-*.tpl") as $global_template )
    {
        $t->compile_template(basename($global_template));
    }
    
    foreach( $templates as $template )
    {
        if( $template == 'default-tgp.tpl' )
        {
            continue;
        }
        
        if( strpos($template, 'global-') === FALSE )
        {
            $t->compile_template($template);
        }
    }
}

function GetValue($name)
{
    global $DB;
    
    $row = $DB->Row('SELECT * FROM `tlx_stored_values` WHERE `name`=?', array($name));
    
    if( $row )
    {
        return $row['value'];
    }
    else
    {
        return null;
    }
}

function StoreValue($name, $value)
{
    global $DB;
    
    // See if it exists
    if( $DB->Count('SELECT COUNT(*) FROM `tlx_stored_values` WHERE `name`=?', array($name)) )
    {
        $DB->Update('UPDATE `tlx_stored_values` SET `value`=? WHERE `name`=?', array($value, $name));
    }
    else
    {
        $DB->Update('INSERT INTO `tlx_stored_values` VALUES (?,?)', array($name, $value));
    }
}

function DeleteValue($name)
{
    global $DB;

    $DB->Update('DELETE FROM `tlx_stored_values` WHERE `name`=?', array($name));
}

function DoBackup($filename, &$tables)
{
    global $DB;

    $fd = fopen($filename, 'w');
    
    if( $fd )
    {
        foreach( array_keys($tables) as $table )
        {
            if( $table == 'tlx_account_fields' )
            {
                $row = $DB->Row('SHOW CREATE TABLE #', array($table));
                $create = str_replace(array("\r", "\n"), '', $row['Create Table']);
                
                fwrite($fd, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($fd, "$create;\n");
            }
            
            fwrite($fd, "DELETE FROM `$table`;\n");
            fwrite($fd, "LOCK TABLES `$table` WRITE;\n");
            fwrite($fd, "ALTER TABLE `$table` DISABLE KEYS;\n");

            $result = mysql_unbuffered_query("SELECT * FROM `$table`", $DB->handle);        
            while( $row = mysql_fetch_row($result) )
            {
                $row = array_map('PrepareRow', $row);
                fwrite($fd, "INSERT INTO `$table` VALUES (" . join(",", $row) . ");\n");
            }        
            $DB->Free($result);
            
            fwrite($fd, "UNLOCK TABLES;\n");
            fwrite($fd, "ALTER TABLE `$table` ENABLE KEYS;\n");
        }
        
        fclose($fd);
    }
}

function PrepareRow($field)
{
    global $DB;
    
    if( $field == NULL )
    {
        return 'NULL';
    }
    else
    {
        return "'" . mysql_real_escape_string($field, $DB->handle) . "'";
    }
}

function DoRestore($filename)
{
    global $DB;
    
    $buffer = '';
    $fd = fopen($filename, 'r');
    
    if( $fd )
    {
        while( !feof($fd) )
        {
            $line = trim(fgets($fd));
            
            // Skip comments and empty lines
            if( empty($line) || preg_match('~^(/\*|--)~', $line) )
            {
                continue;
            }
            
            if( !preg_match('~;$~', $line) )
            {
                $buffer .= $line;
                continue;
            }
            
            // Remove trailing ; character
            $line = preg_replace('~;$~', '', $line);
            
            $buffer .= $line;

            mysql_query($buffer, $DB->handle);
            
            $buffer = '';
        }
        
        fclose($fd);
    }
}

function GetServerCapabilities()
{
    // Handle recursion issues with CGI version of PHP
    if( getenv('PHP_REPEAT') ) return;
    putenv('PHP_REPEAT=TRUE');
    
    $GLOBALS['LAST_ERROR'] = null;
    
    $server = array('safe_mode' => TRUE,
                    'shell_exec' => FALSE,
                    'have_gd' => extension_loaded('gd'),
                    'have_magick' => FALSE,
                    'magick6' => FALSE,
                    'have_imager' => FALSE,
                    'php_cli' => null,
                    'mysql' => null,
                    'mysqldump' => null,
                    'convert' => null,
                    'composite' => null,
                    'dig' => null,
                    'tar' => null,
                    'gzip' => null,
                    'message' => array(),
                    'php_cli_safe_mode' => FALSE,
                    'php_cli_zend_optimizer' => TRUE);
    
    set_error_handler('GetServerCapabilitiesError');
    error_reporting(E_ALL);
           
    $server['safe_mode'] = @ini_get('safe_mode');
    
    if( $server['safe_mode'] === null || isset($GLOBALS['LAST_ERROR']) )
    {
        $server['safe_mode'] = TRUE;
        $server['message'][] = "The ini_get() PHP function appears to be disabled on your server\nPHP says: " . $GLOBALS['LAST_ERROR'];
    }
    else if( $server['safe_mode'] )
    {
        $server['message'][] = "Your server is running PHP with safe_mode enabled";
        
        // Do tests on safe_mode_exec_dir
    }
    else
    {
        $server['safe_mode'] = FALSE;
        
        $GLOBALS['LAST_ERROR'] = null;
        
        $open_basedir = ini_get('open_basedir');
        
        // See if shell_exec is available on the server
        @shell_exec('ls -l');        
        if( isset($GLOBALS['LAST_ERROR']) )
        {
            $server['shell_exec'] = FALSE;
            $server['message'][] = "The shell_exec() PHP function appears to be disabled on your server\nPHP says: " . $GLOBALS['LAST_ERROR'];
        }
        else
        {
            $server['shell_exec'] = TRUE;
        }
        
        if( $server['shell_exec'] )
        {   
            // Check for cli version of PHP
            $server['php_cli'] = LocateExecutable('php', '-v', '(cli)', $open_basedir);
            
            if( !$server['php_cli'] )
            {
                $server['php_cli'] = LocateExecutable('php-cli', '-v', '(cli)', $open_basedir);
            }
            
            // Check for Zend Optimizer and safe_mode
            if( $server['php_cli'] )
            {
                $cli_settings = shell_exec("{$server['php_cli']} -r \"echo serialize(array('safe_mode' => ini_get('safe_mode'), 'zend_optimizer' => extension_loaded('Zend Optimizer')));\" 2>/dev/null");
                $cli_settings = unserialize($cli_settings);
                
                if( $cli_settings !== FALSE )
                {
                    if( $cli_settings['safe_mode'] )
                    {
                        $server['php_cli_safe_mode'] = TRUE;
                        $server['message'][] = 'The CLI version of PHP is running with safe_mode enabled';
                    }
                    
                    if( !$cli_settings['zend_optimizer'] )
                    {
                        $server['php_cli_zend_optimizer'] = FALSE;
                        $server['message'][] = 'The CLI version of PHP does not have the Zend Optimizer extension enabled';
                    }
                }
            }
            
            // Check for mysql executables
            $server['mysql'] = LocateExecutable('mysql', null, null, $open_basedir);
            $server['mysqldump'] = LocateExecutable('mysqldump', null, null, $open_basedir);
            
            // Check for imagemagick executables
            $server['convert'] = LocateExecutable('convert', null, null, $open_basedir);
            $server['composite'] = LocateExecutable('composite', null, null, $open_basedir);
            
            // Check for dig
            $server['dig'] = LocateExecutable('dig', null, null, $open_basedir);
            
            // Check for archiving executables
            $server['tar'] = LocateExecutable('tar', null, null, $open_basedir);
            $server['gzip'] = LocateExecutable('gzip', null, null, $open_basedir);
        
            if( $server['convert'] && $server['composite'] )
            {
                $server['have_magick'] = TRUE;
                $server['magick6'] = FALSE;
                
                // Get version
                $output = shell_exec("{$server['convert']} -version");
                
                if( preg_match('~ImageMagick 6\.~i', $output) )
                {
                    $server['magick6'] = TRUE;
                }                
            }
        }
    }
    
    $server['have_imager'] = $server['have_magick'] || $server['have_gd'];
    
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    restore_error_handler();
    
    return $server;
}

function LocateExecutable($executable, $output_arg = null, $output_search = null, $open_basedir = FALSE)
{
    
    $executable_dirs = array('/bin',
                             '/usr/bin', 
                             '/usr/local/bin', 
                             '/usr/local/mysql/bin', 
                             '/sbin', 
                             '/usr/sbin', 
                             '/usr/lib', 
                             '/usr/local/ImageMagick/bin', 
                             '/usr/X11R6/bin');
                         
    if( isset($GLOBALS['BASE_DIR']) )
    {
        $executable_dirs[] = "{$GLOBALS['BASE_DIR']}/bin";
    }
    
    if( isset($_SERVER['DOCUMENT_ROOT']) )
    {
        $executable_dirs[] = realpath($_SERVER['DOCUMENT_ROOT'] . '/../bin/');
    }
    
    // No open_basedir restriction
    if( !$open_basedir )
    {
        foreach( $executable_dirs as $dir )
        {
            if( @is_file("$dir/$executable") && @is_executable("$dir/$executable") )
            {
                if( $output_arg )
                {
                    $output = shell_exec("$dir/$executable $output_arg");
                    
                    if( stristr($output, $output_search) !== FALSE )
                    {
                        return "$dir/$executable";
                    }
                }
                else
                {
                    return "$dir/$executable";
                }
            }
        }
    }

    $which = trim(shell_exec("which $executable"));
    
    if( !empty($which) )
    {
        if( $output_arg )
        {
            $output = shell_exec("$which $output_arg");
            
            if( stristr($output, $output_search) !== FALSE )
            {
                return $which;
            }
        }
        else
        {
            return $which;
        }
    }

    
    $whereis = trim(shell_exec("whereis -B ".join(' ', $executable_dirs)." -f $executable"));
    preg_match("~$executable: (.*)~", $whereis, $matches);        
    $whereis = explode(' ', trim($matches[1]));

    if( count($whereis) )
    {
        if( $output_arg )
        {
            foreach( $whereis as $executable )
            {
                $output = shell_exec("$executable $output_arg");
                
                if( stristr($output, $output_search) !== FALSE )
                {
                    return $executable;
                }
            }
        }
        else
        {
            return $whereis[0];
        }
    }
    
    return null;
}

function GetServerCapabilitiesError($code, $string, $file, $line)
{
    $GLOBALS['LAST_ERROR'] = $string;
}

function CheckAccessList($ajax = FALSE)
{
    global $C, $allowed_ips;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $hostname = gethostbyaddr($ip);
    $found = FALSE;

    require_once("{$GLOBALS['BASE_DIR']}/includes/access-list.php");

    if( is_array($allowed_ips) )
    {
        if( count($allowed_ips) < 1 )
        {
            return;
        }
        
        foreach( $allowed_ips as $check_ip )
        {
            $check_ip = trim($check_ip);
            $check_ip = preg_quote($check_ip);

            // Setup the wildcard items
            $check_ip = preg_replace('/\\\\\*/', '.*?', $check_ip);
            $check_ip = preg_replace('/\\\\\*/', '\\*', $check_ip);

            if( preg_match("/^$check_ip$/", $ip) || preg_match("/^$check_ip$/", $hostname)  )
            {
                $found = TRUE;
                break;
            }
        }
        
        if( !$found )
        {
            if( $ajax )
            {
                $json = new JSON();           
                echo $json->encode(array('status' => JSON_FAILURE, 
                                         'message' => "The IP address you are connecting from ({$_SERVER['REMOTE_ADDR']}) is not allowed to access this function."));
            }
            else
            {
                include_once('no-access.php');
            }            
            exit;
        }
    }
    else
    {
        $GLOBALS['no_access_list'] = TRUE;
    }
}

function CheckTemplateCode(&$code)
{
    $warnings = array();
    
    if( preg_match_all('~(\{\$.*?\})~', $code, $matches) )
    {
        foreach( $matches[1] as $match )
        {
            if( strpos($match, '$config.') || $match == '{$icon}' )
            {
                continue;
            }
            
            if( !preg_match('~\|.*?\}~', $match) )
            {
                $warnings[] = "The template value $match is not escaped with htmlspecialchars and may pose a security risk";
            }
        }
    }
    
    return join('<br />', $warnings);
}

//TODO
function AutoBlacklist(&$gallery, $reason = '')
{
    global $DB;

    // Ban URL
    if( !$DB->Count('SELECT COUNT(*) FROM `tlx_blacklist` WHERE `type`=? AND `value`=?', array('url', $gallery['gallery_url'])) )
    {
        $parsed_url = parse_url($gallery['gallery_url']);
        $DB->Update('INSERT INTO `tlx_blacklist` VALUES (?,?,?,?,?)', array(null, 'url', 0, $parsed_url['host'], $reason));
    }
                      
    // Ban IP
    if( !$DB->Count('SELECT COUNT(*) FROM `tlx_blacklist` WHERE `type`=? AND `value`=?', array('submit_ip', $gallery['submit_ip'])) )
    {
        $DB->Update('INSERT INTO `tlx_blacklist` VALUES (?,?,?,?,?)', array(null, 'submit_ip', 0, $gallery['submit_ip'], $reason));
    }
    
    // Ban e-mail
    if( !$DB->Count('SELECT COUNT(*) FROM `tlx_blacklist` WHERE `type`=? AND `value`=?', array('email', $gallery['email'])) )
    {
        $DB->Update('INSERT INTO `tlx_blacklist` VALUES (?,?,?,?,?)', array(null, 'email', 0, $gallery['email'], $reason));
    }
}

function AdminFormField(&$options)
{
    $options['tag_attributes'] = str_replace(array('&quot;', '&#039;'), array('"', "'"), $options['tag_attributes']);    
    
    switch($options['type'])
    {
    case FT_CHECKBOX:
        if( strlen($options['label']) > 70 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 70, true) . "</span>";
        }
        if( preg_match('/value\s*=\s*["\']?([^\'"]+)\s?/i', $options['tag_attributes'], $matches) )
            $options['tag_attributes'] = 'class="checkbox" value="'.$matches[1].'"';
        else
            $options['tag_attributes'] = 'class="checkbox"';
        break;
    
    case FT_SELECT:
        if( strlen($options['label']) > 20 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 20) . "</span>";
        }
        $options['tag_attributes'] = '';
        break;
    
    case FT_TEXT:
        if( strlen($options['label']) > 20 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 20) . "</span>";
        }
        $options['tag_attributes'] = 'size="70"';
        break;
    
    case FT_TEXTAREA:
        if( strlen($options['label']) > 20 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 20) . "</span>";
        }
        $options['tag_attributes'] = 'rows="5" cols="80"';
        break;
    }
}

function PageLinks($data)
{
    $html = '';
    
    if( $data['prev'] )
    {
        $html .= ' <a href="javascript:void(0);" onclick="return Search.jump(1)"><img src="images/page-first.png" border="0" alt="First" title="First"></a> ' .
                 ' <a href="javascript:void(0);" onclick="return Search.go(-1)"><img src="images/page-prev.png" border="0" alt="Previous" title="Previous"></a> ';
    }
    else
    {
        $html .= ' <img src="images/page-first-disabled.png" border="0" alt="First" title="First"> ' .
                 ' <img src="images/page-prev-disabled.png" border="0" alt="Previous" title="Previous"> ';
    }
    
    if( $data['pages'] > 2 )
    {
        $html .= ' &nbsp; <input type="text" id="_pagenum_" value="' . $data['page'] . '" size="2" class="centered pagenum" onkeypress="return event.keyCode!=13" onkeyup="Search.jump(null, event)" /> of ' . $data['fpages'] . ' &nbsp; ';
    }
    
    if( $data['next'] )
    {
        $html .= ' <a href="javascript:void(0);" onclick="return Search.go(1)"><img src="images/page-next.png" border="0" alt="Next" title="Next"></a> ' .
                 ' <a href="javascript:void(0);" onclick="return Search.jump('. $data['pages'] .')">' .
                 '<img src="images/page-last.png" border="0" alt="Last" title="Last"></a> ';
    }
    else
    {
        $html .= ' <img src="images/page-next-disabled.png" border="0" alt="Next" title="Next"> ' .
                 ' <img src="images/page-last-disabled.png" border="0" alt="Last" title="Last"> ';
    }

    return $html;
}

function CheckBox($name, $class, $value, $checked, $flag = 0)
{
    $checked_code = '';
    
    if( ($value == $checked) || ($flag & $value) )
        $checked_code = ' checked="checked"';
    
    return "<input type=\"checkbox\" name=\"$name\" id=\"$name\" class=\"$class\" value=\"$value\"$checked_code />";
}

function ValidFunction($function)
{
    return (preg_match('/^tlx[a-zA-Z0-9_]+/', $function) > 0 && function_exists($function));
}

function ValidLogin()
{
    global $DB;
    
    $error = 'Invalid username/password combination';
    
    if( isset($_POST['login_username']) && isset($_POST['login_password']) )
    {
        $_POST['login_username'] = trim($_POST['login_username']);
        $_POST['login_password'] = trim($_POST['login_password']);
        
        $administrator = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($_POST['login_username']));
        if( $administrator && $administrator['password'] == sha1($_POST['login_password']) )
        {
            $session = sha1(uniqid(rand(), true) . $_POST['login_password']);
            setcookie('toplistx', 'username=' . urlencode($_POST['login_username']) . '&session=' . $session, time() + 86400);            
            $DB->Update('UPDATE `tlx_administrators` SET ' .
                        '`session`=?,' .
                        '`session_start`=?, ' .
                        '`date_login`=?, ' .
                        '`date_last_login`=?, ' .
                        '`login_ip`=?, ' .
                        '`last_login_ip`=? ' .
                        'WHERE `username`=?', 
                        array($session, 
                              time(), 
                              MYSQL_NOW,
                              $administrator['date_login'],
                              $_SERVER['REMOTE_ADDR'],
                              $administrator['login_ip'],
                              $administrator['username']));
            
            $_SERVER['REMOTE_USER'] = $administrator['username'];
            
            return TRUE;
        }
    }
    else if( isset($_COOKIE['toplistx']) )
    {
        parse_str($_COOKIE['toplistx'], $cookie);
        
        $administrator = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($cookie['username']));
        
        if( $administrator && $cookie['session'] == $administrator['session'] )
        {
            if( $administrator['session_start'] < time() - SESSION_LENGTH )
            {
                $session = sha1(uniqid(rand(), true) . $administrator['password']);
                setcookie('toplistx', 'username=' . urlencode($administrator['username']) . '&session=' . $session, time() + 86400);
                $DB->Update('UPDATE `tlx_administrators` SET ' .
                            '`session`=?,' .
                            '`session_start`=? ' .
                            'WHERE `username`=?', 
                            array($session, 
                                  time(), 
                                  $cookie['username']));
            }
            
            $_SERVER['REMOTE_USER'] = $administrator['username'];
            
            return TRUE;
        }
        else
        {
            $error = 'Session expired or invalid username/password';
        }
    }
    else
    {
        $error = '';
    }

    return $error;
}

function VerifyPrivileges($privilege, $ajax = FALSE)
{
    global $DB;
    
    $administrator = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($_SERVER['REMOTE_USER']));
    
    if( $administrator['type'] == ACCOUNT_ADMINISTRATOR )
    {
        return;
    }
    
    if( !($administrator['rights'] & $privilege) )
    {
        if( $ajax )
        {
            $json = new JSON();
            echo $json->encode(array('status' => JSON_FAILURE, 'message' => 'You do not have the necessary privileges to access this function'));
        }
        else
        {
            $error = 'You do not have the necessary privileges to access this function';
            include_once('includes/error.php');
        }
        exit;
    }
}

function VerifyAdministrator($ajax = FALSE)
{
    global $DB;
    
    $administrator = $DB->Row('SELECT * FROM `tlx_administrators` WHERE `username`=?', array($_SERVER['REMOTE_USER']));

    if( $administrator['type'] != ACCOUNT_ADMINISTRATOR )
    {
        if( $ajax )
        {
            $json = new JSON();           
            echo $json->encode(array('status' => JSON_FAILURE, 'message' => 'This function is only available to administrator level accounts'));
        }
        else
        {
            $error = 'This function is only available to administrator level accounts';
            include_once('includes/error.php');
        }
        exit;
    }
}

function GenerateFlags(&$array, $pattern)
{
    $flags = 0x00000000;
    
    foreach($array as $name => $value)
    {
        if( preg_match("/$pattern/", $name) )
        {
            $flags = $flags | intval($value);
        }
    }
    
    return $flags;
}

function WriteConfig(&$settings)
{
    global $C;
    
    unset($settings['r']);
    unset($settings['message']);
        
    $C = array_merge($C, $settings);
    
    $fd = fopen("{$GLOBALS['BASE_DIR']}/includes/config.php", "r+");
    
    fwrite($fd, "<?PHP\n\$C = array();\n");
    
    foreach($C as $setting => $value)
    {
        if( is_numeric($value) && $setting != 'db_password' )
        {
            fwrite($fd, "\$C['$setting'] = $value;\n");
        }
        else if( IsBool($value) )
        {
            $value = $value ? 'TRUE' : 'FALSE';
            fwrite($fd, "\$C['$setting'] = $value;\n");
        }
        else
        {
            fwrite($fd, "\$C['$setting'] = '" . addslashes($value) . "';\n");
        }
    }
    
    fwrite($fd, "?>");
    ftruncate($fd, ftell($fd));
    fclose($fd);
}

function GetWhichAccounts($update = FALSE)
{
    global $DB;
    
    $result = null;
    $req = $_REQUEST;
        
    if( IsEmptyString($_REQUEST['which']) )
    {
        parse_str($_REQUEST['results'], $req);
    }
    
    switch($req['which'])
    {
    case 'specific':
        $result = $DB->Query('SELECT * FROM `tlx_accounts` WHERE `username`=?', array($req['username']));
        break;
        
    case 'matching':
        // TODO

        break;
        
    case 'all':
        $result = $DB->Query('SELECT * FROM `tlx_accounts`');
        break;
        
    default:
        if( $update )
        {
            $update->AddWhere('username', ST_IN, join(',', $req['username']));
            $result = $update;
        }
        else
        {
            $bind_list = CreateBindList($req['username']);
            $result = $DB->Query('SELECT * FROM `tlx_accounts` WHERE `username` IN (' . $bind_list . ')', $req['username']);
        }
        break;
    }
    
    return $result;
}
?>
