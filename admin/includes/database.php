<?php
if( !defined('ToplistX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
function executeQuery()
{
    if( confirm('Are you sure you want to execute this MySQL query?') )
    {
        infoBarAjax({data: 'r=tlxDatabaseRawQuery&' + $('#query').serialize()}, false);
    }
    
    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=tlxDatabaseOptimize" class="window {title: 'Database Repair and Optimize', height: 300}">
        <img src="images/repair.png" border="0" alt="Repair and Optimize" title="Repair and Optimize"></a>
        &nbsp;
        <a href="docs/database-tools.html#backup" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Database Backup and Restore
    </div>
        
    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-top">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>
    
    <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to do this?')">
    
    <div class="centered margin-top" style="font-weight: bold">
      <b>Filename</b> &nbsp; <input type="text" name="filename" id="filename" size="30" value="backup.txt" /><br />
      <div style="margin-top: 8px;">
      <button type="submit" onclick="$('#r').val('tlxDatabaseBackup')">Backup</button>
      &nbsp;&nbsp;&nbsp;
      <button type="submit" onclick="$('#r').val('tlxDatabaseRestore')">Restore</button>
      </div>
    </div>
    
    <input type="hidden" name="r" id="r" value="">
    </form>
    
    <br />    
    
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/database-tools.html#raw" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Raw Database Query
    </div>
       
    <form id="query_form">
    
    <div class="centered margin-top" style="font-weight: bold">
      <b>Query</b> <input type="text" name="query" id="query" size="100" value="" onkeypress="return event.keyCode!=13" /> &nbsp; 
      <button type="button" id="execute_button" onclick="executeQuery()">Execute</button>
    </div>

    </form>
    
    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
