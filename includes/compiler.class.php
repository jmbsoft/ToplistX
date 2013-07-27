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

define('S_PHP', '<?PHP');
define('E_PHP', '?>');
define('NEWLINE', "\n");


if( !class_exists('selectbuilder') )
{
    require_once('common.php');
}

class Compiler
{
    var $current_line = 1;
    var $current_file = null;
    var $left_delimiter = '{';
    var $right_delimiter = '}';
    var $tag_stack = array();
    var $capture_stack = array();
    var $syntax_ok = TRUE;
    var $errors = array();
    var $defines = array();
    var $from_count = 0;
    var $nocache_buffer = '';
    var $nocache_token = '';
    var $template_dir = '';
    var $compile_dir = '';
    var $flags = array();

    function Compiler()
    {
        $this->template_dir = realpath(dirname(__FILE__) . '/../templates');
        $this->compile_dir = $this->template_dir . '/compiled';    
    }

    function compile($source, &$compiled)
    {
        $this->current_line = 1;

        $ldq = preg_quote($this->left_delimiter, '~');
        $rdq = preg_quote($this->right_delimiter, '~');
        
        $source = str_replace(array("\r\n", "\r"), "\n", $source);

        $this->locate_multiline($source);
        
        // Process each line of the file
        foreach( explode("\n", $source) as $line )
        {
            $line = "$line\n";
            
            // Extract and parse all template tags
            $generated_code = preg_replace_callback("~{$ldq}\s*(.*?)\s*{$rdq}~s", array(&$this, 'compile_tag'), $line);
            
            $compiled .= $generated_code;
            $this->current_line++;
        }
                
        // Process nocache placeholders
        if( preg_match_all("~{$ldq}nocache ([a-z0-9]+){$rdq}(.*?){$ldq}/nocache ([a-z0-9]+){$rdq}~msi", $compiled, $matches, PREG_SET_ORDER) )
        {
            foreach( $matches as $match )
            {
                $cache_id = $match[1];
                $code = $match[2];                
                $compiled = str_replace(array("{codecache $cache_id}", "{nocache $cache_id}", "{/nocache $cache_id}"), array(base64_encode($code), '', ''), $compiled); 
            }
        }

        // Check for unclosed tag(s)
        if( count($this->tag_stack) > 0 )
        {
            $last_tag = end($this->tag_stack);
            $this->syntax_error("unclosed tag \{{$last_tag[0]}} (opened on line {$last_tag[1]}).");
            return;
        }

        $this->code_cleanup($compiled);
        
        return $this->syntax_ok;
    }

    function locate_multiline(&$source)
    {
        $source = preg_replace_callback('~{(accounts|categories)(.*?)}~msi', array(&$this, 'convert_multiline'), $source);
        $source = preg_replace_callback('~{\*(.*?)\*}~msi', array(&$this, 'convert_multiline'), $source);
    }

    function convert_multiline($matches)
    {
        if( $matches[0][1] == '*' )
        {
            return '';
        }
        
        $tag = $matches[0];
        $tag = preg_replace('~\r\n|\n|\r~', ' ', $tag);

        return $tag;
    }

    function code_cleanup(&$compiled)
    {
        // remove unnecessary close/open tags          
        $compiled = preg_replace('~\?> +<\?php~i', ' echo " "; ', $compiled);
        $compiled = preg_replace('~\?><\?php~i', ' ', $compiled);
        
        // Add extra newline for php closing tags that are at the end of the line
        $compiled = preg_replace('~\?>$~im', "?>\n", $compiled);        
        $compiled = trim($compiled);
    }

    function compile_file($filename, &$compiled)
    {
        $this->current_file = basename($filename);
        $source = file_get_contents($filename);        
        return $this->compile($source, $compiled);
    }

    function compile_tag(&$matches)
    {        

         
        // Parse tag into command, modifiers, and arguments;
        $tag = $this->parse_tag($matches[1]);

        // Don't monkey with stuff when we're inside a {literal} or {php} tag
        list($open_tag) = end($this->tag_stack);
        if( $open_tag == 'literal' && $tag['tag'] != '/literal' )
            return $matches[0];
        if( $open_tag == 'php' && $tag['tag'] != '/php' )
            return $matches[0];


        // Tag name is a variable
        if( $tag['tag'][0] == '$' )
        {            
            $_return = $this->parse_vars($tag['tag'] . ($tag['modifiers'] ? '|' . $tag['modifiers'] : ''));
            return S_PHP . " echo $_return; " . E_PHP;
        }


        // Determine what to do with this tag
        switch($tag['tag'])
        {
            case 'if':
            {
                $this->push_tag('if');
                return $this->compile_if_tag($tag['attributes']);
            }

            case 'else':
            {
                list($open_tag) = end($this->tag_stack);
                if( $open_tag != 'if' && $open_tag != 'elseif' )
                    $this->syntax_error('unexpected {else}');
                else
                    $this->push_tag('else');

                return S_PHP . ' else: ' . E_PHP;
            }

            case 'elseif':
            {
                list($open_tag) = end($this->tag_stack);
                if( $open_tag != 'if' && $open_tag != 'elseif' )
                    $this->syntax_error('unexpected {elseif}');
                if( $open_tag == 'if' )
                    $this->push_tag('elseif');

                return $this->compile_if_tag($tag['attributes'], true);
            }

            case '/if':
            {
                $this->pop_tag('if');
                return S_PHP . ' endif; ' . E_PHP;
            }

            case 'capture':
            {
                $this->push_tag('capture');
                return $this->compile_capture_tag(true, $tag['attributes']);
            }

            case '/capture':
            {
                $this->pop_tag('capture');
                return $this->compile_capture_tag(false);
            }
            
            case 'nocache':
            {
                $this->push_tag('nocache');
                $this->nocache_token = md5(uniqid(rand(), true));
                return $this->nocache_token . S_PHP . ' ob_start(); ' . E_PHP . "{nocache {$this->nocache_token}}";
            }
            
            case '/nocache':
            {
                $this->pop_tag('nocache');
                $serialized = base64_encode($this->nocache_buffer);
                return "{/nocache {$this->nocache_token}}" . S_PHP . " \$this->nocache['{$this->nocache_token}'] = ob_get_contents(); ob_end_clean(); " .
                       "\$this->codecache['{$this->nocache_token}'] = '{codecache {$this->nocache_token}}'; " . E_PHP;
            }
            
            case 'ldelim':
                return $this->left_delimiter;

            case 'rdelim':
                return $this->right_delimiter;

            case 'literal':
            {
                $this->push_tag('literal');
                return '';
            }

            case '/literal':
            {
                $this->pop_tag('literal');
                return '';
            }
    
            case 'foreach':
            {
                $this->push_tag('foreach');
                return $this->compile_foreach_start($tag['attributes']);
            }

            case 'foreachelse':
            {
                $this->push_tag('foreachelse');
                return S_PHP . ' endforeach; else: ' . E_PHP;
            }

            case '/foreach':
            {
                $fromvar = $this->from_count--;
                $open_tag = $this->pop_tag('foreach');
                if( $open_tag == 'foreachelse' )
                    return S_PHP . ' endif; ' . E_PHP;
                else
                    return S_PHP . ' endforeach; unset($from'.$fromvar.'); endif; ' . E_PHP;
            }
            
            case 'range':
            {
                $this->push_tag('range');
                return $this->compile_range_start($tag['attributes']);
            }

            case '/range':
            {
                $this->pop_tag('range');
                return S_PHP . ' endforeach; ' . E_PHP;
            }

            case 'php':
            {
                $this->push_tag('php');
                return S_PHP;
            }

            case '/php':
            {
                $this->pop_tag('php');
                return E_PHP;
            }
            
            case 'phpcode':
            {
                $this->push_tag('phpcode');
                return S_PHP . " echo ' <?PHP '; " . E_PHP;
            }

            case '/phpcode':
            {
                $this->pop_tag('phpcode');
                return S_PHP. " echo ' ?> '; " . E_PHP;
            }
            
            case 'insert':
            {
                $this->push_tag('insert');
                return $this->compile_insert_tag($tag['attributes']);
            }
                
            case '/insert':
            {
                $this->pop_tag('insert');
                return S_PHP . ' endif; ' . E_PHP;
            }
            
            case 'define':
                return $this->compile_define_tag($tag['attributes']);
                
            case 'categories':
                return $this->compile_categories_tag($tag['attributes']);
                
            case 'accounts':
                return $this->compile_accounts_tag($tag['attributes']);
                
            case 'featured':
                return $this->compile_featured_tag($tag['attributes']);
            
            case 'pages':
                return $this->compile_pages_tag($tag['attributes']);
                    
            case 'file':
                return $this->compile_file_tag($tag['attributes']);
                
            case 'include':
                return $this->compile_include_tag($tag['attributes']);                

            case 'cycle':
                return $this->compile_cycle_tag($tag['attributes']);

            case 'options':
                return $this->compile_options_tag($tag['attributes']);
                
            case 'assign':
                return $this->compile_assign_tag($tag['attributes']);
                
            case 'field':
                return $this->compile_field_tag($tag['attributes']);
            
            case 'locale':
                return $this->compile_locale_tag($tag['attributes']);
            
            case 'date':
                return $this->compile_date_tag($tag['attributes']);
            
            case 'datelocale':
                return $this->compile_datelocale_tag($tag['attributes']);

            default:
            {
                // Return value unchanged
                return $matches[0];
            }
        }
    }

    function compile_insert_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);
        
        if( empty($attrs['counter']) )
            return $this->syntax_error("insert: missing 'counter' attribute");
            
        if( empty($attrs['location']) )
            return $this->syntax_error("insert: missing 'location' attribute");
    
        if( !preg_match('~^[$\w]+$~', $attrs['counter']) )
            return $this->syntax_error("insert: 'counter' must be a variable name (literal string)");
            
        $attrs['counter'] = $this->parse_vars($attrs['counter']);        
        
        
        // Format: +5        
        if( preg_match('~\+(\d+)~', $attrs['location'], $matches) )
        {
            return S_PHP . " if( {$attrs['counter']} % {$matches[1]} == 0 " .
                   (isset($attrs['max']) && is_numeric($attrs['max']) ? "&& {$attrs['counter']} <= {$attrs['max']} " : '') .
                   "): " . E_PHP . NEWLINE;
        }
        
        // Format: 5
        else if( is_numeric($attrs['location']) )
        {
            return S_PHP . " if( {$attrs['counter']} == {$attrs['location']} ): " . E_PHP . NEWLINE;
        }
        
        // Format: 5,10,15
        else if( preg_match_all('~(\d+)\s*,?~', $attrs['location'], $matches) )
        {
            return S_PHP . " if( strstr(',".join(',', $matches[1]).",', ','.{$attrs['counter']}.',') ): " . E_PHP . NEWLINE;
        }
    }

    function compile_datelocale_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);
        
        if( empty($attrs['value']) )
            return $this->syntax_error("datelocale: missing 'value' attribute");
            
        if( empty($attrs['format']) )
            return $this->syntax_error("datelocale: missing 'format' attribute");
        
        $attrs['format'] = addslashes($attrs['format']);
        $attrs['value'] = addslashes($attrs['value']);
        
        $strtotime = "MYSQL_NOW . ' {$attrs['value']}'";
        if( $attrs['value'] == 'today' || $attrs['value'] == 'now' )
        {
            $strtotime = 'MYSQL_NOW';
        }
        
        if( !empty($attrs['var']) )
        {
            $attrs['var'] = $this->parse_vars($attrs['var']);        
            return S_PHP . " {$attrs['var']} = ucwords(strftime('{$attrs['format']}', strtotime($strtotime))); " . E_PHP;
        }
        else
        {
            return S_PHP . " echo ucwords(strftime('{$attrs['format']}', strtotime($strtotime))); " . E_PHP;
        }
    }

    function compile_date_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);
        
        if( empty($attrs['value']) )
            return $this->syntax_error("date: missing 'value' attribute");
            
        if( empty($attrs['format']) )
            return $this->syntax_error("date: missing 'format' attribute");
        
        $attrs['format'] = addslashes($attrs['format']);
        $attrs['value'] = addslashes($attrs['value']);
        
        $strtotime = "MYSQL_NOW . ' {$attrs['value']}'";
        if( $attrs['value'] == 'today' || $attrs['value'] == 'now' )
        {
            $strtotime = 'MYSQL_NOW';
        }
        
        if( !empty($attrs['var']) )
        {
            $attrs['var'] = $this->parse_vars($attrs['var']);        
            return S_PHP . " {$attrs['var']} = date('{$attrs['format']}', strtotime($strtotime)); " . E_PHP;
        }
        else
        {
            return S_PHP . " echo date('{$attrs['format']}', strtotime($strtotime)); " . E_PHP;
        }
    }

    function compile_locale_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);
        
        if( empty($attrs['value']) )
            return $this->syntax_error("locale: missing 'value' attribute");
                
        return S_PHP . " setlocale(LC_TIME, '{$attrs['value']}'); " . E_PHP;
    }

    function compile_define_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);
        
        if( empty($attrs['name']) )
            return $this->syntax_error("define: missing 'name' attribute");
            
        if( empty($attrs['value']) )
            return $this->syntax_error("define: missing 'value' attribute");
            
        $this->defines[$attrs['name']] = $attrs['value'];
        
        return '';
    }

    function compile_pages_tag($tag_args)
    {
        global $DB;

        $attrs = $this->parse_attributes($tag_args);        
        
        if( empty($attrs['var']) )
            return $this->syntax_error("pages: missing 'var' attribute");
        
        $s = new SelectBuilder('*,`tlx_categories`.`name` AS `category`', 'tlx_pages');
        
        $s->AddJoin('tlx_pages', 'tlx_categories', 'LEFT', 'category_id');
        
        if( isset($attrs['tags']) )
        {
            $s->AddFulltextWhere('tags', $attrs['tags']);
        }
        
        if( isset($attrs['category']) )
        {
            if( is_numeric($attrs['category']) )
            {
                if( $attrs['category'] == 0 )
                {
                    $s->AddWhere('tlx_pages.category_id', ST_NULL, null);
                }
                else
                {
                    $s->AddWhere('tlx_pages.category_id', ST_MATCHES, $attrs['category']);
                }
            }
            else if( $attrs['category']{0} == '$' )
            {
                $attrs['category'] = $this->parse_vars($attrs['category']);
                $s->AddWhere('tlx_pages.category_id', ST_MATCHES, '%CATEGORY_ID%');
            }
            else
            {
                $category = $DB->Row('SELECT * FROM `tlx_categories` WHERE `name`=?', array($attrs['category']));
                
                if( !$category )
                {
                    return $this->syntax_error("pages: 'category' attribute has an invalid category name specified");
                }
                
                $s->AddWhere('tlx_pages.category_id', ST_MATCHES, $category['category_id']);
            }
        }
        
        if( isset($attrs['filecontains']) )
        {
            $s->AddWhere('filename', ST_CONTAINS, $attrs['filecontains']);
        }
        
        if( isset($attrs['amount']) )
        {
            $s->SetLimit($attrs['amount']);
        }
        
        $s->AddOrder('build_order', 'ASC');
        
        $query = $DB->Prepare($s->Generate(), $s->binds);
        $query = str_replace("='%CATEGORY_ID%'", "\".({$attrs['category']} ? \"='\".{$attrs['category']}.\"'\" : ' IS NULL').\"", $query);
        $attrs['var'] = $this->parse_vars($attrs['var']);
        
        return S_PHP . " {$attrs['var']} =& \$GLOBALS['DB']->FetchAll(\"$query\"); " . E_PHP;
    }

    function compile_featured_tag($tag_args)
    {
        global $DB;
        
        $defaults = array('category' => 'MIXED',
                          'ranks' => '1-50');
        
        $attrs = $this->parse_attributes($tag_args);        
        $attrs = array_merge($defaults, $attrs);
        
        if( empty($attrs['var']) )
            return $this->syntax_error("featured: missing 'var' attribute");
            
        if( !preg_match('~^\d+-\d+$~', $attrs['ranks']) )
            return $this->syntax_error("featured: the 'ranks' attribute must be in START-END format");
                        
        $attrs['var'] = $this->parse_vars($attrs['var']);
        
        $s = new SelectBuilder('*', 'tlx_accounts');            

        $attrs['category'] = FormatCommaSeparated($attrs['category']);
        
        if( $this->flags['category_id'] )
        {                
            $s->AddWhere('category_id', ST_MATCHES, $this->flags['category_id'], TRUE);
        }
        else if( strtoupper($attrs['category']) != 'MIXED' )
        {
            $category_not_in = array();
            $category_in = array();
            
            if( !isset($GLOBALS['CATEGORY_CACHE']) )
            {
                $GLOBALS['CATEGORY_CACHE'] =& $DB->FetchAll('SELECT * FROM `tlx_categories`', null, 'name');
            }
            
            foreach( explode(',', $attrs['category']) as $category )
            {
                switch($category)
                {
                    case 'MIXED':
                    case 'mixed':
                    case 'Mixed':
                        break;
                    
                    default:
                    {
                        $minus = FALSE;
                        if( preg_match('~^-(.*)~i', $category, $matches) )
                        {
                           $minus = TRUE;
                           $category = $matches[1];
                        }
                        
                        if( $GLOBALS['CATEGORY_CACHE'][$category] )
                        {
                            if( $minus )
                            {
                                $category_not_in[] = $GLOBALS['CATEGORY_CACHE'][$category]['category_id'];
                            }
                            else
                            {
                                $category_in[] = $GLOBALS['CATEGORY_CACHE'][$category]['category_id'];
                            }
                        }
                    } 
                }
            }
            
            $s->AddWhere('category_id', ST_IN, join(',', $category_in), TRUE);
            $s->AddWhere('category_id', ST_NOT_IN, join(',', $category_not_in), TRUE);
            $s->AddWhere('last_category_rank', ST_BETWEEN, str_replace('-', ',', $attrs['ranks']));
        }
        else
        {
            $s->AddWhere('last_rank', ST_BETWEEN, str_replace('-', ',', $attrs['ranks']));
        }
        
        $s->AddWhere('disabled', ST_MATCHES, 0);
        $s->AddWhere('status', ST_MATCHES, STATUS_ACTIVE);
        $s->SetLimit('1');
        $s->AddOrder('RAND()');
        
        // Generate the SQL query to pull accounts from the database
        $query = $DB->Prepare($s->Generate(), $s->binds);

        return S_PHP . " {$attrs['var']} = \$GLOBALS['DB']->Row(\"$query\");\n" .
               "if( {$attrs['var']} )\n{\n".
               "PopulateAccountInfo({$attrs['var']}, \$empty);\n} " . E_PHP;
    }

    function compile_accounts_tag($tag_args)
    {
        global $DB;
        $defaults = array('category' => 'MIXED',
                          'ranks' => '1-25',
                          'storeranks' => 'false',
                          'storecatranks' => 'false',
                          'minhits' => 0,
                          'order' => 'unique_in_last_hour DESC');
        
        $attrs = $this->parse_attributes($tag_args);        
        $attrs = array_merge($defaults, $attrs);
        
        if( empty($attrs['var']) )
            return $this->syntax_error("accounts: missing 'var' attribute");
            
        if( !preg_match('~^\d+-\d+$~', $attrs['ranks']) )
            return $this->syntax_error("accounts: the 'ranks' attribute must be in START-END format");
                        
        $attrs['var'] = $this->parse_vars($attrs['var']);
        
        $attrs['storeranks'] = $this->to_bool($attrs['storeranks']);
        $attrs['storecatranks'] = $this->to_bool($attrs['storecatranks']);
        
        // Prepare RAND() values in order attribute
        $attrs['order'] = preg_replace('~rand\(\)~i', 'RAND(%RAND%)', $attrs['order']);
        
        // Pulling accounts from database using user-specified SELECT statements
        if( isset($attrs['select']) )
        {            
            // TODO: User specified select statement
        }
        
        // Pulling accounts from database using settings
        else
        {
            $s = new SelectBuilder('*,%SORTER% AS `sorter`,`tlx_accounts`.`username` AS `username`', 'tlx_accounts');            

            $attrs['category'] = FormatCommaSeparated($attrs['category']);
            
            if( $this->flags['category_id'] )
            {                
                $s->AddWhere('category_id', ST_MATCHES, $this->flags['category_id'], TRUE);
            }
            else if( strtoupper($attrs['category']) != 'MIXED' )
            {
                $category_not_in = array();
                $category_in = array();
                
                if( !isset($GLOBALS['CATEGORY_CACHE']) )
                {
                    $GLOBALS['CATEGORY_CACHE'] =& $DB->FetchAll('SELECT * FROM `tlx_categories`', null, 'name');
                }
                
                foreach( explode(',', $attrs['category']) as $category )
                {
                    switch($category)
                    {
                        case 'MIXED':
                        case 'mixed':
                        case 'Mixed':
                            break;
                        
                        default:
                        {
                            $minus = FALSE;
                            if( preg_match('~^-(.*)~i', $category, $matches) )
                            {
                               $minus = TRUE;
                               $category = $matches[1];
                            }
                            
                            if( $GLOBALS['CATEGORY_CACHE'][$category] )
                            {
                                if( $minus )
                                {
                                    $category_not_in[] = $GLOBALS['CATEGORY_CACHE'][$category]['category_id'];
                                }
                                else
                                {
                                    $category_in[] = $GLOBALS['CATEGORY_CACHE'][$category]['category_id'];
                                }
                            }
                        } 
                    }
                }
                
                $s->AddWhere('category_id', ST_IN, join(',', $category_in), TRUE);
                $s->AddWhere('category_id', ST_NOT_IN, join(',', $category_not_in), TRUE);
            }
        }
                
        // Handle the order attribute
        $order = trim($attrs['order']);
        $direction = null;
        $sorter = null;

        if( strpos($order, ' ') )
        {
            list($order, $direction) = explode(' ', $order);
        }
        
        switch( strtolower($order) )
        {
            case 'ratings':
            case 'date_added':
            case 'date_activated':
            case 'inactive':
                $sorter = "`$order`";
                break;
                
            case 'average_rating':
                $sorter = '`ratings_total`/`ratings`';
                break;

            default:
                if( preg_match('~^(.*?)_(last|this|yesterday)_?(\d+)?_?(.*)?$~', $order, $matches) )
                {
                    list($full, $field, $type, $amount, $period) = $matches;
                    $join = 'tlx_account_daily_stats';
                    
                    if( $type == 'yesterday' )
                    {
                        $type = 'last';
                        $amount = 1;
                        $period = 'day';
                    }
                    
                    if( empty($amount) )
                    {
                        $amount = 1;
                    }
                    
                    if( stristr($period, 'hour') )
                    {
                        $s->AddJoin('tlx_accounts', 'tlx_account_hourly_stats', 'LEFT', 'username');
                        
                        if( $field == 'productivity' )
                        {
                            $sorter = ($amount == 24 ? "`clicks_total`/`unique_in_total`" : "(\" . SorterLastHours('clicks_%%', $amount) . \")/(\" . SorterLastHours('unique_in_%%', $amount) . \")");
                        }
                        else
                        {
                            $sorter = ($amount == 24 ? "`$field"."_total`" : "\" . SorterLastHours('$field"."_%%', $amount) . \"");
                        }
                        
                        if( !empty($attrs['minhits']) && is_numeric($attrs['minhits']) )
                        {
                            $s->AddWhereString("($sorter) >= {$attrs['minhits']}");
                        }
                    }
                    else if( stristr($period, 'day') )
                    {
                        $s->AddJoin('tlx_accounts', 'tlx_account_daily_stats', 'LEFT', 'username');
                        $s->AddGroup('tlx_accounts.username');
                        
                        if( $field == 'productivity' )
                        {
                            $sorter = ($amount >= 365 ? "SUM(`clicks`)/SUM(`unique_in`)" : "SUM(IF(`date_stats` >= DATE_ADD('%TODAY%', INTERVAL -$amount DAY), `clicks`, 0))/SUM(IF(`date_stats` >= DATE_ADD('%TODAY%', INTERVAL -$amount DAY), `unique_in`, 0))");
                        }
                        else
                        {
                            $sorter = ($amount >= 365 ? "SUM(`$field`)" : "SUM(IF(`date_stats` >= DATE_ADD('%TODAY%', INTERVAL -$amount DAY), `$field`, 0))");
                        }
                        
                        if( !empty($attrs['minhits']) && is_numeric($attrs['minhits']) )
                        {
                            $s->AddHavingString("`sorter` >= {$attrs['minhits']}");
                        }
                    }
                    else
                    {
                        $sorter = '`unique_in_last_hour`';
                        $direction = 'DESC';
                    } 
                }
                else
                {
                    $sorter = '`unique_in_last_hour`';
                    $direction = 'DESC';
                }

                break;
        }
        
        $s->AddWhere('disabled', ST_MATCHES, 0);
        $s->AddWhere('status', ST_MATCHES, STATUS_ACTIVE);
        $s->AddOrder('sorter', $direction);
        $s->AddOrder('tlx_accounts.username');
        
        // Set the range of accounts to select
        list($start, $end) = explode('-', $attrs['ranks']);
        $s->SetLimit(($start-1).','.($end-$start+1));
        
        // Generate the SQL query to pull accounts from the database
        $query = $DB->Prepare($s->Generate(), $s->binds);
        
        // Query replacements
        $replacements = array('%SORTER%' => $sorter,
                              '%TODAY%' => '" . MYSQL_CURDATE . "');
                              
        foreach( $replacements as $find => $replace )
        {
            $query = str_replace($find, $replace, $query);
        }
        
        if( isset($attrs['stats']) )
        {
            $attrs['stats'] = FormatCommaSeparated($attrs['stats']);
        }

        return S_PHP . " {$attrs['var']} =& LoadAccounts(\"$query\", '{$attrs['ranks']}', \$this->vars['fillranks'], " . 
               ($attrs['storeranks'] === TRUE ? 'TRUE' : 'FALSE') . ", " . ($attrs['storecatranks'] === TRUE ? 'TRUE' : 'FALSE') . ", '{$attrs['stats']}'); " . E_PHP;
    }

    function compile_categories_tag($tag_args)
    {
        global $DB;
        
        $defaults = array('amount' => 'all',
                          'order' => 'name');
        
        $attrs = $this->parse_attributes($tag_args);        
        $attrs = array_merge($defaults, $attrs);
        
        if( empty($attrs['var']) )
            return $this->syntax_error("categories: missing 'var' attribute");
            
        $s = new SelectBuilder('*', 'tlx_categories_build');
        
        if( strtolower($attrs['amount']) != 'all' )
            $s->SetLimit($attrs['amount']);
        
        if( $attrs['exclude'] )
        {
            $attrs['exclude'] = FormatCommaSeparated($attrs['exclude']);            
            $s->AddWhere('name', ST_NOT_IN, $attrs['exclude']);
        }
        
        if( $attrs['startswith'] )
        {
            $s->AddWhere('name', ST_STARTS, $attrs['startswith']);
        }
        
        $s->SetOrderString($attrs['order'], $DB->GetColumns('tlx_categories_build'));
        
        $query = $DB->Prepare($s->Generate(), $s->binds);
        
        $attrs['var'] = $this->parse_vars($attrs['var']);
        
        return S_PHP . " if( !isset(\$GLOBALS['_prep_category_build']) )" . NEWLINE .
               "{" . NEWLINE .
               "PrepareCategoriesBuild();" . NEWLINE .
               "}" . NEWLINE .
               " {$attrs['var']} =& \$GLOBALS['DB']->FetchAll(\"$query\"); " . E_PHP;
    }

    function compile_field_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['from']) )
            return $this->syntax_error("field: missing 'from' attribute");

        if( empty($attrs['value']) )
            return $this->syntax_error("field: missing 'value' attribute");

        $from = $this->parse_vars($attrs['from']);
        $value = $this->parse_vars($attrs['value']);        

        return S_PHP . " echo FormField($from, $value); " . E_PHP;
    }

    function compile_options_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['from']) )
            return $this->syntax_error("options: missing 'from' attribute");

        if( empty($attrs['key']) )
            return $this->syntax_error("options: missing 'key' attribute");

        if( empty($attrs['value']) )
            return $this->syntax_error("options: missing 'value' attribute");

        $from = $this->parse_vars($attrs['from']);
        $plain_key = $this->parse_vars(str_replace('$', '$_options_.', preg_replace('~\|.*~', '', $attrs['key'])));
        $key = $this->parse_vars(str_replace('$', '$_options_.', $attrs['key']));
        $value = $this->parse_vars(str_replace('$', '$_options_.', $attrs['value']));
        $selected = $this->parse_vars($attrs['selected']);

        return S_PHP . " foreach( $from as \$this->vars['_options_'] ): " . NEWLINE .
               "echo \"<option value=\\\"\" . htmlspecialchars(\$this->vars['_options_']['$key']) .  \"\\\"" .
               (!empty($attrs['selected']) ? "\" . ($selected == \$this->vars['_options_']['$plain_key'] ? ' selected' : '') . \"" : '') .
               ">\" . htmlspecialchars(\$this->vars['_options_']['$value']) . \"</option>\\n\";" . NEWLINE .
               'endforeach;  ' . E_PHP;
    }

    function compile_assign_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['var']) )
            return $this->syntax_error("assign: missing 'var' attribute");

        if( empty($attrs['value']) )
            return $this->syntax_error("options: missing 'value' attribute");

        $var = $this->parse_vars($attrs['var']);
        $value = $this->parse_vars($attrs['value']);

        
        if( strpos($value, '$this->vars') === FALSE && !is_numeric($value) )
        {
            $value = '"' . $value . '"';
        }        
        
        return S_PHP . " $var = $value; " . E_PHP;
    }

    function compile_cycle_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['values']) )
            return $this->syntax_error("cycle: missing 'values' attribute");

        list($first, $second) = explode(',', $attrs['values']);

        if( empty($attrs['var']) )
        {
            return S_PHP . " \$tmp_cycle = (\$tmp_cycle == '$first') ? '$second' : '$first'; echo \$tmp_cycle; " . E_PHP;
        }
        else
        {
            $var = $this->parse_vars($attrs['var']);
            return S_PHP . " $var = ($var == '$first') ? '$second' : '$first'; " . E_PHP;
        }
    }

    function compile_file_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['filename']) )
            return $this->syntax_error("file: missing 'filename' attribute");

        return S_PHP . " readfile('{$attrs['filename']}'); " . E_PHP;
    }

    function compile_include_tag($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['filename']) )
            return $this->syntax_error("include: missing 'filename' attribute");
            
        if( !preg_match('~^global-~', $attrs['filename']) )
            return $this->syntax_error("include: only global templates can be included");
            
        if( !file_exists($this->compile_dir . '/' . $attrs['filename']) )
            return $this->syntax_error("include: compiled template '".$this->compile_dir . '/' . $attrs['filename']."' does not exist");

        return file_get_contents($this->compile_dir . '/' . $attrs['filename']);
    }

    function compile_if_tag($tag_args, $elseif = false)
    {
        // make sure we have balanced parenthesis
        $token_count = count_chars($tag_args);
        if( isset($token_count['(']) && $token_count['('] != $token_count[')'] )
            $this->syntax_error("unbalanced parenthesis in if statement");

        $tag_args = $this->parse_vars($tag_args);

        if( $elseif )
            return S_PHP . " elseif( $tag_args ): " . E_PHP;
        else
            return S_PHP . " if( $tag_args ): " . E_PHP;
    }

    function compile_range_start($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['start']) )
            return $this->syntax_error("range: missing 'start' attribute");

        if( empty($attrs['end']) )
            return $this->syntax_error("range: missing 'end' attribute");
            
        if( empty($attrs['counter']) )
            return $this->syntax_error("range: missing 'counter' attribute");
            
        if( isset($attrs['counter']) && !preg_match('~^[$\w]+$~', $attrs['counter']) )
            return $this->syntax_error("range: 'counter' must be a variable name (literal string)");

        $attrs['start'] = $this->parse_vars($attrs['start']);
        $attrs['end'] = $this->parse_vars($attrs['end']);
        $attrs['counter'] = $this->parse_vars($attrs['counter']);
               
        return S_PHP . " foreach( range({$attrs['start']}, {$attrs['end']}) as {$attrs['counter']} ): " . E_PHP;
    }

    function compile_foreach_start($tag_args)
    {
        $attrs = $this->parse_attributes($tag_args);

        if( empty($attrs['from']) )
            return $this->syntax_error("foreach: missing 'from' attribute");

        if( empty($attrs['var']) )
            return $this->syntax_error("foreach: missing 'var' attribute");

        if( !preg_match('~^[$\w]+$~', $attrs['var']) )
            return $this->syntax_error("'foreach: var' must be a variable name (literal string)");
            
        if( isset($attrs['counter']) && !preg_match('~^[$\w]+$~', $attrs['counter']) )
            return $this->syntax_error("'foreach: counter' must be a variable name (literal string)");

        $attrs['from'] = $this->parse_vars($attrs['from']);
        $attrs['var'] = $this->parse_vars($attrs['var']);
        $attrs['counter'] = $this->parse_vars($attrs['counter']);

        $key = null;
        $key_part = '';
        if( isset($attrs['key']) )
        {
            if( !preg_match('~^[$\w]+$~', $attrs['key']) )
                return $this->syntax_error("foreach: 'key' must to be a variable name (literal string)");

            $attrs['key'] = $this->parse_vars($attrs['key']);
            
            $key_part = "{$attrs['key']} => ";
        } 

        $fromcount = ++$this->from_count;
        
        $output = S_PHP . " \$from$fromcount = {$attrs['from']};" . NEWLINE;
        $output .= "if( is_array(\$from$fromcount) ):" . NEWLINE;
        
        if( $attrs['counter'] )
            $output .= "    {$attrs['counter']} = 0;" . NEWLINE;
        
        $output .= "    foreach (\$from$fromcount as $key_part{$attrs['var']}):" . NEWLINE;
        
        if( $attrs['counter'] )
            $output .= "    {$attrs['counter']}++;" . NEWLINE;
            
        $output .= E_PHP;

        return $output;
    }

    function compile_capture_tag($start, $tag_args = '')
    {
        $attrs = $this->parse_attributes($tag_args);

        if( $start )
        {
            if( empty($attrs['var']) )
            {
                return $this->syntax_error("capture: missing 'var' attribute");
            }

            $name = $attrs['var'];
            $output = S_PHP . " ob_start(); " . E_PHP;
            $this->capture_stack[] = $name;
        } 
        else
        {
            $name = $this->parse_vars(array_pop($this->capture_stack));
            $output = S_PHP . " $name = ob_get_contents(); ob_end_clean(); " . E_PHP;
        }

        return $output;
    }

    function syntax_error($error_msg)
    {
        $this->errors[] = "[line {$this->current_line}] $error_msg";
        $this->syntax_ok = FALSE;
    }

    function push_tag($tag)
    {
        array_push($this->tag_stack, array($tag, $this->current_line));
    }

    function pop_tag($tag)
    {
        if( count($this->tag_stack) > 0 )
        {
            list($open_tag, $line) = array_pop($this->tag_stack);

            if( $tag == $open_tag )
            {
                return $open_tag;
            }


            if( $tag == 'if' && ($open_tag == 'else' || $open_tag == 'elseif') )
            {
                return $this->pop_tag($tag);
            }
     

            if( $tag == 'foreach' && $open_tag == 'foreachelse' )
            {
                $this->pop_tag($tag);
                return $open_tag;
            }


            if( $open_tag == 'else' || $open_tag == 'elseif' )
            {
                $open_tag = 'if';
            }
            elseif( $open_tag == 'foreachelse' )
            {
                $open_tag = 'foreach';
            }

            $message = " expected {/$open_tag} (opened on line $line).";
        }

        $this->syntax_error("mismatched tag {/$tag}.$message");
    }

    function dequote($string)
    {
        if( (substr($string, 0, 1) == "'" || substr($string, 0, 1) == '"') && substr($string, -1) == substr($string, 0, 1) )
        {
            return substr($string, 1, -1);
        }
        else
        {
            return $string;
        }
    }

    function parse_tag($tag)
    {
        $parsed_tag = FALSE;
        
        if( $tag{0} == '$' )
        {
            $parsed_tag = array();
            $parsed_tag['tag'] = $tag;
            $parsed_tag['modifiers'] = '';
            $parsed_tag['attributes'] = '';
            
            // Check for tag modifiers
            if( preg_match('~([^|]+)\|(.*)$~s', $parsed_tag['tag'], $matches) )
            {
                $parsed_tag['tag'] = $matches[1];
                $parsed_tag['modifiers'] = $matches[2];
            }
        }
        else
        {
            // Separate the tag name from it's attributes
            if( preg_match('~([^\s]+)(\s+(.*))?$~s', $tag, $matches) )
            {
                $parsed_tag = array();
                $parsed_tag['tag'] = $matches[1];
                $parsed_tag['modifiers'] = '';
                $parsed_tag['attributes'] = $matches[3];
            }
        }

        return $parsed_tag;
    }

    function parse_attributes($attributes)
    {
        $parsed = array();

        if( preg_match_all('~([a-z_ ]+=.*?)(?=(?:\s+[a-z_]+\s*=)|$)~i', $attributes, $matches) )
        {
            foreach( $matches[1] as $match )
            {
                $equals = strpos($match, '=');
                $attr_name = $this->dequote(trim(substr($match, 0, $equals)));
                $attr_value = $this->dequote(trim(substr($match, $equals + 1)));

                $parsed[strtolower($attr_name)] = $attr_value; 
            }
        }

        return $parsed;
    }

    function parse_vars($input)
    {
        return preg_replace_callback('~(\$[a-z0-9._\[\]]+)(\|{1}.*)?~i', array(&$this, 'parse_vars_callback'), $input);
    }

    function parse_vars_callback($matches)
    {
        $variable = $matches[1];
        $modifiers = substr($matches[2], 1);
        $dot = strpos($variable, '.');
        $parsed_var = '';

        if( $dot !== FALSE )
        {
            $parsed_var = preg_replace('~\$([a-z0-9_]+)\.([a-z0-9_]+)~i', '$this->vars[\'\1\'][\'\2\']', $variable);
        }
        else
        {
            $parsed_var = preg_replace('~\$([a-z0-9_]+)~i', '$this->vars[\'\1\']', $variable);
        }


        // Process modifiers
        if( !empty($modifiers) )
        {
            foreach( explode('|', $modifiers) as $modifier )
            {
                if( preg_match('~^([a-z0-9_\->\$]+)(::)?(.*)$~i', $modifier, $grabbed) )
                {
                    $function = $grabbed[1];
                    $args = $grabbed[3];
                    
                    if( $function == 'htmlspecialchars' && empty($args) )
                        $args = 'ENT_QUOTES';

                    $args = $this->parse_vars($args);
                    
                    if( !empty($args) )
                        $args = ", " . preg_replace('~::~', ', ', $args);                   
                    
                    $parsed_var = "$function($parsed_var$args)";
                }
            }
        }

        return $parsed_var;
    }

    function get_error_string()
    {
        return join("\n", $this->errors);
    }

    function to_bool($value)
    {
        if( is_numeric($value) )
        {
            if( $value == 0 )
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }
        else if( preg_match('~^any$~i', $value) )
        {
            return 'any';
        }
        else if( preg_match('~^true$~i', $value) )
        {
            return TRUE;
        }
        else if( preg_match('~^false$~i', $value) )
        {
            return FALSE;
        }
        
        return FALSE;
    }
}


?>