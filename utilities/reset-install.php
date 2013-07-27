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
    echo "This file must be located in the admin directory of your TGPX installation";
    exit;
}

define('ToplistX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

@set_time_limit(0);

if( $_SERVER['REQUEST_METHOD'] == 'POST' )
{
    ResetInstall();
}
else
{
    DisplayMain();
}

$DB->Disconnect();

function ResetInstall()
{
    global $DB, $C;

    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    foreach( $tables as $table => $create )
    {
        $DB->Update('DROP TABLE IF EXISTS #', array($table));
    }

    FileWrite("{$GLOBALS['BASE_DIR']}/includes/config.php", "<?php\n\$C = array();\n?>");

    echo "Your ToplistX installation has been reset<br />" .
         "Upload the install.php script and access it through your browser to re-initialize the software";
}

function DisplayMain()
{

echo <<<OUT
<html>
<head>
  <title>Reset ToplistX Installation</title>
  <style>
  body, form, input { font-family: Tahoma; font-size: 9pt; }
  </style>
</head>
<body>
<center>
<b>Press the button below to reset your ToplistX installation to it's default state.<br />
This will delete all of the data and settings that you have configured up to this point.</b>
<form method="POST" action="reset-install.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to reset your ToplistX installation?')">
<input type="submit" value="Reset ToplistX Installation" style="margin-top: 10px;">
</form>
</center>

</body>
</html>
OUT;
}

?>
