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

if( !is_file('scanner.php') )
{
    echo "This file must be located in the admin directory of your ToplistX installation";
    exit;
}


$replace_accounts_html = array('##Script_URL##' => '{$config.install_url}',
                               '##Overall_Rank##' => '{$account.rank|htmlspecialchars}',
                               '##Category_Rank##' => '{$account.rank|htmlspecialchars}',
                               '##Movement_Overall##' => '',
                               '##Movement_Category##' => '',
                               '##Username##' => '{$account.username|htmlspecialchars}',
                               '##Title##' => '{$account.title|htmlspecialchars}',
                               '##Site_URL##' => '{$account.site_url|htmlspecialchars}',
                               '##Out_URL##' => '{$config.install_url}/out.php?id={$account.username|urlencode}',
                               '##Description##' => '{$account.description|htmlspecialchars}',
                               '##Banner##' => '{if $account.banner_url}<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank">' .
                                               '<img src="{$account.banner_url|htmlspecialchars}" border="0" alt="{$account.title|htmlspecialchars}" class="banner" />' .
                                               '</a><br />' .
                                               '{/if}',
                               '##Category##' => '{$account.category|htmlspecialchars}',
                               '##Category_Page##' => '{$account.category_url|htmlspecialchars}',
                               '##Field_1##' => '{$account.user_field_1|htmlspecialchars}',
                               '##Field_2##' => '{$account.user_field_2|htmlspecialchars}',
                               '##Field_3##' => '{$account.user_field_3|htmlspecialchars}',
                               '##Weighted_In##' => '{$account.sorter|tnumber_format}',
                               '##Weighted_Out##' => '{$account.sorter|tnumber_format}',
                               '##Weighted_Total_In##' => '{$account.sorter|tnumber_format}',
                               '##Weighted_Total_Out##' => '{$account.sorter|tnumber_format}',
                               '##In_Per_Day##' => '{$account.sorter|tnumber_format}',
                               '##In_Per_Week##' => '{$account.sorter|tnumber_format}',
                               '##In_Per_Month##' => '{$account.sorter|tnumber_format}',
                               '##Out_Per_Day##' => '{$account.sorter|tnumber_format}',
                               '##Out_Per_Week##' => '{$account.sorter|tnumber_format}',
                               '##Out_Per_Month##' => '{$account.sorter|tnumber_format}',
                               '##Last_Sort##' => 'N/A',
                               '##Last_Hits_In##' => 'N/A',
                               '##Last_Hits_Out##' => 'N/A',
                               '##Last_Overall##' => 'N/A',
                               '##Last_Category##' => 'N/A',
                               '##Icon_HTML##' => '{if $account.icons}{foreach var=$icon from=$account.icons}{$icon}&nbsp;{/foreach}{/if}',
                               '##New_Icon##' => '{if $account.timestamp_activated > TIME_NOW - 259200}<img src="{$config.install_url}/images/new.png" alt="New" />{/if}',
                               '##Row_Color##' => '{$background|htmlspecialchars}');

    
$replace_global = array('<select name="cat">' => '<select name="c">',
                        '<option value="Mixed">Mixed</option>' => '<option value="0">All Categories</option>',
                        '<form action="##Script_URL##/search.cgi" method="GET">' => '<form action="{$config.install_url}/search.php" method="post">',
                        '<input type="text" name="key" size="20">' => '<input type="text" name="s" value="" />',
                        'accounts.cgi?login' => 'accounts.php?r=login',
                        'accounts.cgi' => 'accounts.php',
                        '##Total_Members##' => '{$total_accounts|tnumber_format}',
                        '##Last_Rebuild##' => '{date value=now format=\'m-d-Y h:ia\'}',                        
                        '##Next_Rebuild##' => 'N/A',
                        '##Last_Reset##' => 'N/A',
                        '##Next_Reset##' => 'N/A',
                        '##Script_URL##' => '{$config.install_url}',
                        '##Category_Options##' => '{categories var=$categories}' .
                                                  '{if count($categories)}' .
                                                  '{foreach var=$category from=$categories}' .
                                                  '  <option value="{$category.category_id|htmlspecialchars}">{$category.name|htmlspecialchars}</option>' .
                                                  '{/foreach}' .
                                                  '{/if}');


define('ToplistX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

$DB->Update('SET `wait_timeout`=86400');

@set_time_limit(0);

$from_shell = FALSE;
if( php_sapi_name() == 'cli' )
{
    $_REQUEST['r'] = 'ConvertData';
    $_REQUEST['directory'] = $_SERVER['argv'][1];
    $from_shell = TRUE;
}

if( isset($_REQUEST['r']) )
{
    call_user_func($_REQUEST['r']);
}
else
{
    DisplayMain();
}

$DB->Disconnect();

function ConvertData()
{
    global $C, $DB, $from_shell;
    
    $errors = array();
    if( !is_dir($_REQUEST['directory']) )
    {
        $errors[] = "The directory " . htmlspecialchars($_REQUEST['directory']) . " does not exist on your server";
        return DisplayMain($errors);
    }
    
    if( !is_file("{$_REQUEST['directory']}/arp.pl") )
    {
        $errors[] = "The arp.pl file could not be found in the " . htmlspecialchars($_REQUEST['directory']) . " directory";
        return DisplayMain($errors);
    }
    
    if( !is_readable("{$_REQUEST['directory']}/arp.pl") )
    {
        $errors[] = "The arp.pl file in the " . htmlspecialchars($_REQUEST['directory']) . " directory could not be opened for reading";
        return DisplayMain($errors);
    }
    
    
    // Check version
    $version_file_contents = file_get_contents("{$_REQUEST['directory']}/arp.pl");    
    if( preg_match('~\$VERSION\s+=\s+\'(.*?)\'~', $version_file_contents, $matches) )
    {
        list($a, $b, $c) = explode('.', $matches[1]);
        
        if( $a < 5 )
        {
            $errors[] = "Your AutoRank Pro installation is outdated; please upgrade to the 5.0.x series";
            return DisplayMain($errors);
        }
    }
    else
    {
        $errors[] = "Unable to extract version information from arp.pl; your version of AutoRank Pro is likely too old";
        return DisplayMain($errors);
    }
    
    
    // Extract variables
    $mysql_file_contents = file_get_contents("{$_REQUEST['directory']}/data/variables");
    
    if( $mysql_file_contents === FALSE )
    {
        $errors[] = "Unable to read contents of the variables file";
        return DisplayMain($errors);
    }
    
    $vars = array();
                      
    if( preg_match_all('~^\$([a-z0-9_]+)\s+=\s+\'(.*?)\';$~msi', $mysql_file_contents, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $vars[$match[1]] = $match[2];
        }
    }
    
    
    if( !$from_shell )
        echo "<pre>";
    
    
    
    
    //
    // Copy banners
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Copying member account banners...\n");
    echo "Copying member account banners...\n"; flush();
    $banners =& DirRead($vars['BANNER_DIR'], '\.(png|jpg|gif|bmp)$');
    foreach( $banners as $banner )
    {
        @copy("{$vars['BANNER_DIR']}/$banner", "{$C['banner_dir']}/$banner");
        @chmod("{$C['banner_dir']}/$banner", 0666);
    }

    
    //
    // Dump categories
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting categories...\n");
    echo "Converting categories...\n"; flush();
    $categories = array();
    $category_ids = array();
    $DB->Update('DELETE FROM `tlx_categories`');
    $DB->Update('ALTER TABLE `tlx_categories` AUTO_INCREMENT=0');
    foreach( explode(',', $vars['CATEGORIES']) as $category )
    {
        $DB->Update('INSERT INTO `tlx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $category,
                          0,
                          $vars['FORWARD_URL'],
                          null,
                          $vars['BANNER_WIDTH'],
                          $vars['BANNER_HEIGHT'],
                          $vars['BANNER_SIZE'],
                          intval($vars['O_FORCE_DIMS']),
                          intval($vars['O_CHECK_DIMS']),
                          intval($vars['O_SERVE_BANNERS']),
                          intval($vars['O_ALLOW_302']),
                          1,
                          $vars['MAX_TITLE'],
                          1,
                          $vars['MAX_DESC'],
                          intval($vars['O_REQ_RECIP'])));
                          
        $category_ids[$category] = $DB->InsertID();
    }
    
    
    
    //
    // Import icons
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting account icons...\n");
    echo "Converting account icons...\n"; flush();
    $DB->Update('DELETE FROM `tlx_icons`');
    $DB->Update('ALTER TABLE `tlx_icons` AUTO_INCREMENT=0');
    IniParse("{$_REQUEST['directory']}/data/icons", TRUE, $icons_ini);
    $icons = array();
    foreach( $icons_ini as $key => $value )
    {
        $DB->Update('INSERT INTO `tlx_icons` VALUES (?,?,?)',
                    array(null,
                          $key,
                          trim($value)));

        $icons[$key] = $DB->InsertID();
    }
    
    
    //
    // Import user defined fields
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting user defined database fields...\n");
    echo "Converting user defined database fields...\n"; flush();
    $DB->Update('DELETE FROM `tlx_account_field_defs`');
    $DB->Update('ALTER TABLE `tlx_account_field_defs` AUTO_INCREMENT=0');
    $DB->Update('DROP TABLE IF EXISTS `tlx_account_fields`');
    $DB->Update('CREATE TABLE `tlx_account_fields` (`username` CHAR(32) NOT NULL PRIMARY KEY)');
    for( $i = 1; $i <= 3; $i++ )
    {
        if( !IsEmptyString($vars["NAME_FIELD_$i"]) )
        {
            $DB->Update('INSERT INTO `tlx_account_field_defs` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                        array(null,
                              "user_field_$i",
                              $vars["NAME_FIELD_$i"],
                              FT_TEXT,
                              null,
                              null,
                              0,
                              null,
                              null,
                              1,
                              intval($vars["O_REQ_FIELD_$i"]),
                              1,
                              intval($vars["O_REQ_FIELD_$i"])));
                              
            $DB->Update("ALTER TABLE `tlx_account_fields` ADD COLUMN # TEXT", array("user_field_$i"));
        }
    }
    
    
    
    //
    // Dump account data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting account data...\n");
    echo "Converting account data...\n"; flush();
    $DB->Update('DELETE FROM `tlx_accounts`');
    $DB->Update('DELETE FROM `tlx_account_hourly_stats`');
    $DB->Update('DELETE FROM `tlx_account_daily_stats`');
    $DB->Update('DELETE FROM `tlx_account_country_stats`');
    $DB->Update('DELETE FROM `tlx_account_referrer_stats`');
    $DB->Update('DELETE FROM `tlx_account_icons`');
    $DB->Update('DELETE FROM `tlx_account_comments`');
    $DB->Update('DELETE FROM `tlx_account_ranks`');

    $accounts =& DirRead("{$_REQUEST['directory']}/data/members", '^[^.]');
    $fields = array('Current_In','Current_Out','Total_In','Total_Out','In_Weight','Out_Weight','Email','Site_URL','Banner_URL','Banner_Height','Banner_Width','Recip_URL','Title','Description','Category','Password','Icons','Signup','Suspended','Locked','Inactive','Last_Sort','Last_Hits_In','Last_Hits_Out','Last_Overall','Last_Category','Comments','Field_1','Field_2','Field_3');
    foreach( $accounts as $account_file )
    {
        $account = array();
        $file_contents = explode('|', trim(file_get_contents("{$_REQUEST['directory']}/data/members/$account_file")));
        
        for( $i = 0; $i < count($fields); $i++ )
        {
            $account[$fields[$i]] = $file_contents[$i];
        }
        
        $parsed_url = parse_url($account['Site_URL']);
        $account['Domain'] = preg_replace('~^www\.~i', '', $parsed_url['host']);        
        $account['Banner_URL'] = str_replace($vars['BANNER_URL'], $C['banner_url'], $account['Banner_URL']);
        
        $DB->Update('INSERT INTO `tlx_accounts` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array($account_file,
                          $account['Email'],
                          $account['Site_URL'],
                          $account['Domain'],
                          $account['Banner_URL'],
                          $account['Banner_URL'],
                          $account['Banner_Height'],
                          $account['Banner_Width'],
                          $account['Title'],
                          $account['Description'],
                          null,
                          date(DF_DATETIME, $account['Signup']),
                          date(DF_DATETIME, $account['Signup']),
                          null,
                          sha1($account['Password']),
                          $C['return_percent'],
                          STATUS_ACTIVE,
                          intval($account['Locked']),
                          intval($account['Suspended']),
                          0,
                          $category_ids[$account['Category']],
                          0,
                          0,
                          0,
                          0,
                          $account['Inactive'],
                          null,
                          $account['Comments']));

        $stats = array_merge(array($account_file), array_fill(0, 127, 0));        
        $DB->Update('INSERT INTO `tlx_account_hourly_stats` VALUES (' . CreateBindList($stats) . ')', $stats);
        
        $account_info = array('username' => $account_file, 'user_field_1' => $account['Field_1'], 'user_field_2' => $account['Field_2'], 'user_field_3' => $account['Field_3']);
        $insert = CreateUserInsert('tlx_account_fields', $account_info);
        $DB->Update('INSERT INTO `tlx_account_fields` VALUES ('.$insert['bind_list'].')', $insert['binds']);
        
        foreach( explode(',', $account['Icons']) as $icon_id )
        {
            if( isset($icons[$icon_id]) )
            {
                $DB->Update('INSERT INTO `tlx_account_icons` VALUES (?,?)',
                            array($account_file,
                                  $icons[$icon_id]));
            }
        }
    }

    
    //
    // Dump ranking page data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting ranking pages...\n");
    echo "Converting ranking pages...\n"; flush();
    $build_order = 1;
    $DB->Update('DELETE FROM `tlx_pages`');
    $DB->Update('ALTER TABLE `tlx_pages` AUTO_INCREMENT=0');

    $vars['MAIN_DIR'] = preg_replace('~/$~', '', $vars['MAIN_DIR']);
    $vars['MAIN_URL'] = preg_replace('~/$~', '', $vars['MAIN_URL']);
    $pages = file("{$_REQUEST['directory']}/data/dbs/pages");
    foreach( $pages as $line )
    {
        $page = array();
        list($page['filename'], $page['category']) = explode('|', trim($line));
        
        $template = file_get_contents("{$_REQUEST['directory']}/data/pages/{$page['filename']}");
        $template = ConvertTemplate($template);
        $compiled = '';
        
        $DB->Update('INSERT INTO `tlx_pages` VALUES (?,?,?,?,?,?,?)',
                    array(null,
                          str_replace("{$vars['DOCUMENT_ROOT']}/", '', "{$vars['MAIN_DIR']}/{$page['filename']}"),
                          $page['category'] == 'Mixed' ? null : $category_ids[$page['category']],
                          $build_order++,
                          null,
                          $template,
                          $compiled));
    }
    
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "\nData conversion complete!");
    echo "\nData conversion complete!\n";
    
    if( !$from_shell )
        echo "</pre>";
}

function ConvertTemplate($template)
{
    global $replace_global;

    UnixFormat($template);
    
    $template = preg_replace_callback('~<%([A-Z]+)$(.*?)%>~msi', 'ProcessDirectives', $template);
    $template = str_replace(array_keys($replace_global), array_values($replace_global), $template);

    return trim($template);
}

function ProcessDirectives($matches)
{
    global $replace_accounts_html;
    
    $directive = $matches[1];
    $options = $matches[2];
    $sub_inserts = ExtractSubs('INSERT', $options);
    $options = ExtractOptions($options);
    $output = '';
    
    switch($matches[1])
    {        
        case 'MEMBERS':
            $main_opts = ConvertMembersOptions($options, null);
            
            $colors = explode(',', $options['COLORS']);

            $html = isset($GLOBALS['HTML'][$options['HTML']]) ? $GLOBALS['HTML'][$options['HTML']] : $options['HTML'];
            $html = str_replace('>', ">\n", $html);
            $html = str_replace(array_keys($replace_accounts_html), array_values($replace_accounts_html), trim($html));
            
            $output = "{accounts\nvar=\$accounts\n" . join("\n", $main_opts) . "}\n";
                        
            $output .= "\n{foreach var=\$account from=\$accounts counter=\$counter}\n" .
                       ($options['COLORS'] ? "{cycle values={$colors[0]},{$colors[1]} var=\$background}\n" : '') .
                       $html . "\n";
                       
            foreach( $sub_inserts as $insert )
            {
                $output .= "{insert counter=\$counter location=".$insert['LOCATION']."}\n" .
                           $insert['HTML'] . "\n" .
                           "{/insert}\n";
            }
            
            $output .= "{/foreach}";
            
            if( $options['FILLER'] )
            {
                $filler = isset($GLOBALS['HTML'][$options['FILLER']]) ? $GLOBALS['HTML'][$options['FILLER']] : $options['FILLER'];
                $filler = str_replace('>', ">\n", $filler);
                $filler = str_replace(array_keys($replace_accounts_html), array_values($replace_accounts_html), trim($filler));
                
                $output .= "\n{if \$fillranks}\n{range start=\$fillranks.start end=\$fillranks.end counter=\$rank}\n" .
                           ($options['COLORS'] ? "{cycle values={$colors[0]},{$colors[1]} var=\$background}\n" : '') .
                           $filler .
                           "{/range}\n{/if}";
            }
            
            break;
            
        case 'TEMPLATE':
            $GLOBALS['HTML'][$options['NAME']] = $options['HTML'];
            break;
    }
    
    return $output;
}



function ConvertMembersOptions($options, $parent_amount)
{
    global $replace_galleries_order;
    
    $newopts = array('category=MIXED', 'order=unique_in_last_hour DESC', 'minhits=0');
    
    if( isset($options['RANKS']) )
    {
        $newopts[] = 'ranks=' . $options['RANKS'];
    }
    
    return $newopts;
}

function ExtractOptions(&$options)
{
    $opts = array();
    
    if( preg_match_all('~([A-Z]+)\s+(.*?)$~ms', $options, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $opts[trim($match[1])] = trim($match[2]);
        }
    }
    
    return $opts;
}

function ExtractSubs($directive, &$options)
{
    $sub_options = array();
    
    if( preg_match_all("~$directive\s+\{(.*?)\}~msi", $options, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $sub_options[] = ExtractOptions($match[1]);
        }
    }
    
    $options = preg_replace("~$directive\s+\{(.*?)\}~msi", '', $options);
    
    return $sub_options;
}

function DisplayMain($errors = null)
{
    global $from_shell;
    
    if( $from_shell )
    {
        if( !empty($errors) )
        {
            echo "The following errors were encountered:\n";
            foreach( $errors as $error )
            {
                echo "- $error\n";
            }
            echo "\n";
        }
    }
    else
    {
    $_REQUEST['directory'] = htmlspecialchars($_REQUEST['directory']);
    
echo <<<OUT
<html>
<head>
  <title>Convert AutoRank Pro Data</title>
  <style>
  body, form, input { font-family: Tahoma; font-size: 9pt; }
  </style>
</head>
<body>
OUT;


if( !empty($errors) )
{
    echo '<div style="font-weight: bold; color: #d52727; padding: 4px 10px 4px 10px; background-color: #FEE7E8;">' .
         'The following errors were encountered:<ol>';
    foreach( $errors as $error )
    {
        echo "<li> $error<br />";
    }
    echo "</ol></div>";
OUT;
}


echo <<<OUT
<center>
<form method="POST" action="arp-convert.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to convert this data to ToplistX format?')">
<div style="margin-bottom: 5px; font-weight: bold;">Enter the full directory path to the AutoRank Pro installation:</div>
<input type="text" name="directory" size="80" value="{$_REQUEST['directory']}"><br />
<input type="submit" value="Convert Data" style="margin-top: 10px;">
<input type="hidden" name="r" value="ConvertData">
</form>
</center>

</body>
</html>
OUT;
    }
}

?>
