<?php
if( !defined('ToplistX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<style>
.plain-link {
  font-weight: bold;
  text-decoration: none;
}

.divlabel {
  width: 180px; 
  float: left; 
  font-weight: bold; 
  text-align: right;
  margin-right: 6px;
}

fieldset {

  padding-bottom: 10px;
  font-family: Tahoma;
  font-size: 8pt;
  border: 1px solid #999;
}

legend {
  color: #555;
  margin: 0px 0px 5px 0px;
  padding: 0px 5px;
  font-size: 8pt;
  font-weight: bold;
}
</style>

<div id="main-content">
  <div id="centered-content" class="max-width">

  
  <table width="100%" cellpadding="5">
    <tr>
      <td width="50%" valign="top">      
        <fieldset>
          <legend>Overall Stats</legend>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Total Accounts</div> 
            <?php echo number_format($DB->Count('SELECT COUNT(*) FROM `tlx_accounts`'), 0, $C['dec_point'], $C['thousands_sep']); ?>
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Pending Accounts</div>         
            <?php echo $new_accts; ?>
            &nbsp;
            <a href="index.php?r=tlxShAccountSearch&new=true"><img src="images/go.png" border="0"></a>
          </div>     
                
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Edited Accounts</div> 
            <?php echo $edited_accts; ?>
            &nbsp;
            <a href="index.php?r=tlxShAccountSearch&edited=true"><img src="images/go.png" border="0"></a>
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Pending Comments</div> 
            <?php echo $pending_comments; ?>
            &nbsp;
            <a href="index.php?r=tlxShComments&pending=true"><img src="images/go.png" border="0"></a>
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Total Categories</div> 
            <?php echo number_format($DB->Count('SELECT COUNT(*) FROM `tlx_categories`'), 0, $C['dec_point'], $C['thousands_sep']); ?>
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Last Page Rebuild</div> 
            <?php $last_rebuild = GetValue('last_rebuild'); echo $last_rebuild ? gmdate(DF_SHORT, $last_rebuild) : '-'; ?>
          </div>
        </fieldset>
      </td>
      <td width="50%" valign="top">

        <fieldset>
          <legend>Software Information</legend>
          
          <div style="margin-bottom: 6px;">
          <div class="divlabel">Last Backup</div> 
          <?php
          $last_backup = GetValue('last_backup');
          
          echo empty($last_backup) ? '-' : date(DF_SHORT, strtotime($last_backup));      
          ?>
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Installed Version</div> 
            <?php echo $GLOBALS['VERSION']; ?>
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">Release Date</div> 
            <?php echo $GLOBALS['RELEASE']; ?>
          </div>
                    
          <div style="margin-bottom: 6px;">
            <div class="divlabel">&nbsp;</div>
            &nbsp;
          </div>
          
          <div style="margin-bottom: 6px;">
            <div class="divlabel">&nbsp;</div>
            &nbsp;
          </div>
          
          <table width="100%">
            <tr>
              <td align="center" width="50%">
                <a href="docs/" target="_blank" class="plain-link">Documentation</a>
              </td>
              <td align="center" width="50%">
                <a href="http://www.jmbsoft.com/support/" target="_blank" class="plain-link">Tech Support</a>
              </td>
            </tr>
          </table>

        </fieldset>
      </td>
    </tr>
    
    <tr>
      <td colspan="2">

        <fieldset>
          <legend>JMB Software News and Updates</legend>
          
          <iframe src="http://www.jmbsoft.com/software/toplistx/news/" style="width: 95%; margin-left: 10px; margin-right: 10px;" frameborder="0"></iframe>
        </fieldset>

      </td>
    </tr>
  </table>
    
    <div class="page-end" style="margin-top: 10px;"></div>

  </div>
</div>

</body>
</html>
