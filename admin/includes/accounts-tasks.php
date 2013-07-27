<?php
if( !defined('ToplistX') ) die("Access denied");

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function() { });

function submitForm(form)
{
    if( confirm('Are you sure you want to do this?') )
    {
        $('#msg').html('Processing...');
        $('#message').show();
        $('#activity').show();
        
        $.ajax({type: 'POST',
                url: 'ajax.php',
                dataType: 'json',
                data: $(form).formSerialize(),
                error: function(request, status, error)
                       {
                           $('#activity').hide();
                           $('#msg').html(error);
                       },
                success: function(json)
                         {
                             $('#activity').hide();                        
                             $('#msg').html(json.message);
                         }
            });
    }
}


function run(data)
{
    if( confirm('Are you sure you want to do this?') )
    {
        $('#msg').html('Processing...');
        $('#message').show();
        $('#activity').show();
        
        $.ajax({type: 'POST',
                url: 'ajax.php',
                dataType: 'json',
                data: data,
                error: function(request, status, error)
                       {
                           $('#activity').hide();
                           $('#msg').html(error);
                       },
                success: function(json)
                         {
                             $('#activity').hide();                        
                             $('#msg').html(json.message);
                         }
            });
    }
}
</script>

<div style="padding: 10px;">
    
    <div>
      <div style="float: right;">
        <a href="docs/accounts.html#tasks" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
    </div>
       
        <div class="notice margin-bottom" id="message" style="display: none;">
          <img src="images/activity-small.gif" id="activity"> <span id="msg"></span>
        </div>       

        <form action="index.php" method="POST" id="sarform">        
        <fieldset>
          <legend>Search and Replace</legend>
          
            <div class="fieldgroup">
                <label>Search For:</label>
                <input type="text" name="search" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label>Search Field:</label>
                <select name="field">
                    <option value="email">E-mail Address</option>
                    <option value="site_url">Site URL</option>
                    <option value="banner_url">Banner URL</option>
                    <option value="banner_height">Banner Height</option>
                    <option value="banner_width">Banner Width</option>
                    <option value="title">Title</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_activated">Date Activated</option>
                    <option value="return_percent">Return Percent</option>
                    <option value="admin_comments">Admin Comments</option>
                    <?php
                    $fields =& $DB->FetchAll('SELECT * FROM `tlx_account_field_defs`');        
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>
            </div>
            
            <div class="fieldgroup">
                <label>Replace With:</label>
                <input type="text" name="replace" size="60" />            
            </div>            
            
            <div class="fieldgroup">
                <label></label>
                <button type="button" onclick="submitForm('#sarform')">Search and Replace</button>
            </div>
            
        </fieldset>
        <input type="hidden" name="r" value="tlxAccountSearchAndReplace">
        </form>
        
        
        <form action="index.php" method="POST" id="sasform">        
        <fieldset>
          <legend>Search and Set</legend>
          
            <div class="fieldgroup">
                <label>Search For:</label>
                <input type="text" name="search" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label>Search Field:</label>
                <select name="field">
                    <option value="email">E-mail Address</option>
                    <option value="site_url">Site URL</option>
                    <option value="banner_url">Banner URL</option>
                    <option value="banner_height">Banner Height</option>
                    <option value="banner_width">Banner Width</option>
                    <option value="title">Title</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_activated">Date Activated</option>
                    <option value="return_percent">Return Percent</option>
                    <option value="admin_comments">Admin Comments</option>
                    <?php   
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>
            </div>
            
            <div class="fieldgroup">
                <label>Set Field:</label>
                <select name="set_field">
                    <option value="email">E-mail Address</option>
                    <option value="site_url">Site URL</option>
                    <option value="banner_url">Banner URL</option>
                    <option value="banner_height">Banner Height</option>
                    <option value="banner_width">Banner Width</option>
                    <option value="title">Title</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_activated">Date Activated</option>
                    <option value="return_percent">Return Percent</option>
                    <option value="admin_comments">Admin Comments</option>
                    <?php   
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>       
            </div>
            
            <div class="fieldgroup">
                <label>Set Value:</label>
                <input type="text" name="replace" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <button type="button" onclick="submitForm('#sasform')">Search and Set</button>
            </div>
            
        </fieldset>
        <input type="hidden" name="r" value="tlxAccountSearchAndSet">
        </form>
        
        
        <form>
        <fieldset>
          <legend>Other Functions</legend>
                       
            <div class="fieldgroup">
                <label></label>
                <img src="images/run.png" style="position: relative; top: 4px;" class="click" onclick="run('r=tlxAccountRemoveUnconfirmed')"> &nbsp;Remove unconfirmed accounts that are more than 48 hours old
            </div>
       
        </fieldset>
        </form>
</div>

    

</body>
</html>
