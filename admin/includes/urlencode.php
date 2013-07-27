<?php
if( !defined('ToplistX') ) die("Access denied");
                 
include_once('includes/header.php');
?>
<script language="JavaScript">
function urlencode() 
{
    var urls = $('#urls').val().split(/[\r\n]/);
    var newtext = '';
    
    for( var i = 0; i < urls.length; i++ )
    {
        str = escape(urls[i]);
        str = str.replace('+', '%2B', 'gi');
        str = str.replace('%20', '+', 'gi');
        str = str.replace('*', '%2A', 'gi');
        str = str.replace('/', '%2F', 'gi');
        str = str.replace('@', '%40', 'gi');
        
        newtext += str + "\n";
    }
    
    $('#urls').val(newtext);
    
    alert('URL encoding is complete, and the updated URLs are now listed in the text input box');
}
</script>

<div style="padding: 10px;">
    <div class="margin-bottom">
      Use this form to encode URLs for use in the u= attribute of the out.php links
    </div>
       
    <form>
        <fieldset>
          <legend>URL Encoder</legend>
          
            <div class="fieldgroup">
              <label for="value">URL(s) to Encode:</label>
              <textarea id="urls" rows="10" cols="110" wrap="off"></textarea>            
            </div>
        </fieldset>        
    
    <div class="centered margin-top">
      <button type="button" onclick="urlencode()">Encode URLs</button>
    </div>
    
    </form>
</div>

</body>
</html>
