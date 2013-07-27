{assign var=$page_title value="Search Results"}
{include filename="global-header.tpl"}

<script language="JavaScript" type="text/javascript">
function jumpPage(offset)
{
    $('#p').val(parseInt($('#p').val()) + offset);
    $('#search_form').submit();
    return false;
}
</script>


{if $search_too_short}
<div class="error">
The search term you entered is too short, it must be at least 4 characters
</div>
{else}

<table align="center" width="100%" cellpadding="5" cellspacing="0">
<tr>
<td width="100">
{if $pagination.prev}
<a href="" onclick="return jumpPage(-1)" class="link" style="text-decoration: none;">
<img src="{$config.install_url}/images/prev.png" border="0" alt="" style="position: relative; top: 5px;"> <b>Previous</b></a>
&nbsp;
{/if}
</td>
<td align="center">
<b style="font-size: 14pt;">Search results {$pagination.start|htmlspecialchars} - {$pagination.end|htmlspecialchars} of {$pagination.total|htmlspecialchars}</b>
</td>
<td align="right" width="100">
{if $pagination.next}
&nbsp;
<a href="" onclick="return jumpPage(1)" class="link" style="text-decoration: none;">
<b>Next</b> <img src="{$config.install_url}/images/next.png" border="0" alt="" style="position: relative; top: 5px;"></a>
{/if}
</td>
</tr>

<tr>
<td colspan="3">
<div style="padding-top: 10px; padding-bottom: 10px; border-top: 2px solid #333; border-bottom: 2px solid #333;">
<ol start="{$pagination.start|htmlspecialchars}">
{foreach var=$account from=$results}
<li> <a href="{$config.install_url}/out.php?id={$account.username|urlencode}">{$account.title|htmlspecialchars|thilite}</a><br />{$account.description|htmlspecialchars|thilite}<br /><br />
{/foreach}
</ol>
</div>
</td>
</tr>

<tr>
<td width="100">
{if $pagination.prev}
<a href="" onclick="return jumpPage(-1)" class="link" style="text-decoration: none;">
<img src="{$config.install_url}/images/prev.png" border="0" alt="" style="position: relative; top: 5px;"> <b>Previous</b></a>
&nbsp;
{/if}
</td>
<td align="center">
</td>
<td align="right" width="100">
{if $pagination.next}
&nbsp;
<a href="" onclick="return jumpPage(1)" class="link" style="text-decoration: none;">
<b>Next</b> <img src="{$config.install_url}/images/next.png" border="0" alt="" style="position: relative; top: 5px;"></a>
{/if}
</td>
</tr>

</td>
</tr>
</table>
{/if}

</div>

{include filename="global-footer.tpl"}