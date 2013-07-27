<?PHP
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
require_once('includes/functions.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

// Check consistency of last_updates
$last_updates = unserialize(GetValue('last_updates'));

if( $DB->Count('SELECT COUNT(*) FROM `tlx_daily_stats` WHERE `date_stats`=?', array($last_updates['daily'])) )
{
    // Update the daily value
    $today = gmdate('Y-m-d', TIME_NOW);
    $last_updates['daily'] = $today;
    StoreValue('last_updates', serialize($last_updates));
}


// Load table data
IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $table_defs);

// Load existing tables
$tables = $DB->GetTables();


// Create the tlx_account_ranks table
if( !isset($tables['tlx_account_ranks']) )
{
    $DB->Update("CREATE TABLE IF NOT EXISTS `tlx_account_ranks` ( {$table_defs['tlx_account_ranks']} ) TYPE=MyISAM");
}


// Create the tlx_account_ranks table
if( !isset($tables['tlx_account_category_ranks']) )
{
    $DB->Update("CREATE TABLE IF NOT EXISTS `tlx_account_category_ranks` ( {$table_defs['tlx_account_category_ranks']} ) TYPE=MyISAM");
}


// Update the tlx_categories table
$columns = $DB->GetColumns('tlx_categories', TRUE);
if( !isset($columns['page_url']) )
{
    $DB->Update('ALTER TABLE `tlx_categories` ADD COLUMN `page_url` TEXT AFTER `forward_url`');
}

// Update the tlx_daily_stats table
$columns = $DB->GetColumns('tlx_daily_stats', TRUE);
if( !isset($columns['clicks']) )
{
    $DB->Update('ALTER TABLE `tlx_daily_stats` ADD COLUMN `clicks` INT UNSIGNED NOT NULL');
}


// Update the tlx_ip_log_ratings table
$columns = $DB->GetColumns('tlx_ip_log_ratings', TRUE);
if( !isset($columns['last_rating']) )
{
    $DB->Update('DROP TABLE `tlx_ip_log_ratings`');
    $DB->Update("CREATE TABLE IF NOT EXISTS `tlx_ip_log_ratings` ( {$table_defs['tlx_ip_log_ratings']} ) TYPE=MyISAM");
}


// Update the tlx_accounts table
$columns = $DB->GetColumns('tlx_accounts', TRUE);
if( !isset($columns['last_rank']) )
{
    $DB->Update('ALTER TABLE `tlx_accounts` ADD COLUMN `last_rank` INT AFTER `category_id`');
    $DB->Update('ALTER TABLE `tlx_accounts` ADD COLUMN `last_category_rank` INT AFTER `last_rank`');
}


// Insert values into tlx_skim_ratio if they don't already exist
if( $DB->Count('SELECT COUNT(*) FROM `tlx_skim_ratio`') < 1 )
{
    $DB->Update('INSERT INTO `tlx_skim_ratio` VALUES (0,0)');
}


$DB->Disconnect();


echo "Patching has been completed successfully";

?>