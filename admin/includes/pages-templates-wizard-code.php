=>[traditional]
<table cellspacing="0" cellpadding="0" width="100%" class="table-header">
<tr>
<td class="table-header-l">
</td>
<td style="width: 5em;">
<span style="position: relative; left: -1px;">Rank</span>
</td>
<td>
Site Information
</td>
<td style="width: 10em;">
Ranking Value
</td>
<td class="table-header-r">
</td>
</tr>
</table>

{accounts
var=$accounts
ranks=%%RANKS%%
category=%%CATEGORY%%
minhits=%%MINHITS%%
order=%%ORDER%%}

<div class="table-border">
<table cellspacing="1" cellpadding="0" width="100%">
{foreach var=$account from=$accounts}
{cycle values=row-color-a,row-color-b var=$background}
<tr>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$account.rank|htmlspecialchars}.
</td>
<td class="{$background|htmlspecialchars}" style="padding: 6px;">
<div style="float: right">
{if $account.comments}
<img src="{$config.install_url}/images/comments.png" alt="Comments" border="0" class="click comments" style="padding-right: 10px; position: relative; top: 2px;" id="{$account.username|htmlspecialchars}"/>
{/if}
{if $account.ratings}
<img src="{$config.install_url}/images/{$account.average_rating|tnearest_half}.gif" alt="Rating: {$account.average_rating|tnearest_half}" border="0" class="click rating" id="{$account.username|htmlspecialchars}" />
{else}
<img src="{$config.install_url}/images/0.gif" alt="Not Yet Rated" border="0" class="click rating" id="{$account.username|htmlspecialchars}" />
{/if}
</div>
{if $account.banner_url}
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank">
<img src="{$account.banner_url|htmlspecialchars}" border="0" alt="{$account.title|htmlspecialchars}" class="banner" />
</a><br />
{/if}
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank"><b>{$account.title|htmlspecialchars}</b></a> 
{if $account.timestamp_activated > TIME_NOW - 259200}<img src="{$config.install_url}/images/new.png" alt="New" />{/if}
{if $account.icons}{foreach var=$icon from=$account.icons}{$icon}&nbsp;{/foreach}{/if}
<br />
{$account.description|htmlspecialchars}
</td>
<td class="{$background|htmlspecialchars}" style="width: 10em; text-align: center">
{$account.sorter|tnumber_format}
</td>
</tr>
{/foreach}

{if $fillranks}
{range start=$fillranks.start end=$fillranks.end counter=$rank}
{cycle values=row-color-a,row-color-b var=$background}
<tr>
<td class="{$background|htmlspecialchars}" style="width: 5em; text-align: center">
{$rank|htmlspecialchars}.
</td>
<td class="{$background|htmlspecialchars}" style="padding: 6px;  text-align: center;" colspan="2">
<a href="{$config.install_url}/accounts.php">Add Your Site</a>
</td>
</tr>
{/range}
{/if}

</table>
</div>

=>[friends]
<div class="table-border">
<table cellspacing="1" cellpadding="0" width="100%">
<tr>

{accounts
var=$accounts
ranks=%%RANKS%%
category=%%CATEGORY%%
minhits=%%MINHITS%%
order=%%ORDER%%}

{foreach var=$account from=$accounts counter=$counter}
<td class="row-color-a" style="text-align: center">
<div style="padding: 6px;">
<a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank">
<img src="{if $account.banner_url}{$account.banner_url|htmlspecialchars}{else}{$config.install_url}/images/friends-missing-banner.png{/if}" border="0" alt="{$account.title|htmlspecialchars}" /></a>
<br /><b><a href="{$config.install_url}/out.php?id={$account.username|urlencode}" target="_blank">{$account.title|htmlspecialchars}</a></b><br />
{$account.sorter|tnumber_format}
</div>
</td>
{insert counter=$counter location=+5 max=10}
</tr><tr>
{/insert}
{/foreach}

{if $fillranks}
{range start=$fillranks.start end=$fillranks.end counter=$rank}
<td class="row-color-a" style="text-align: center">
<div style="padding: 6px;">
<a href="{$config.install_url}/accounts.php"><img src="{$config.install_url}/images/friends-site-here.png" border="0" alt="Your Site Here" /></a>
<br /><b><a href="{$config.install_url}/accounts.php">Add Your Site</a></b><br />
</div>
</td>
{insert counter=$rank location=+5 max=$fillranks.end-1}
</tr><tr>
{/insert}
{/range}
{/if}

</tr>
</table>
</div>


=>[rss_text]
{php} echo '<?xml  version="1.0" ?>'; {/php}
<rss version="2.0">
  <channel>
    <title>Your Site Title</title>
    <description>Your site description</description>
    <link>http://www.yoursite.com/</link>
    
{accounts
var=$accounts
ranks=%%RANKS%%
category=%%CATEGORY%%
minhits=%%MINHITS%%
order=%%ORDER%%}

{foreach var=$account from=$accounts}
    <item>
      <title>{$account.title|htmlspecialchars}</title>
      <link>{$config.install_url}/out.php?id={$account.username|urlencode}</link>
      <description>{$account.description|htmlspecialchars}</description>
      <pubDate>{$account.timestamp_activated|tdate::'D, d M Y H:i:s %%RSSTIMEZONE%%'}</pubDate>
    </item>
{/foreach}

  </channel>
</rss>


=>[rss_banner]
{php} echo '<?xml  version="1.0" ?>'; {/php}
<rss version="2.0">
  <channel>
    <title>Your Site Title</title>
    <description>Your site description</description>
    <link>http://www.yoursite.com/</link>
    
{accounts
var=$accounts
ranks=%%RANKS%%
category=%%CATEGORY%%
minhits=%%MINHITS%%
order=%%ORDER%%}

{foreach var=$account from=$accounts}
    <item>
      <title>{$account.title|htmlspecialchars}</title>
      <link>{$config.install_url}/out.php?id={$account.username|urlencode}</link>
      <description>{if $account.banner_url}
      &lt;a href=&quot;{$config.install_url}/out.php?id={$account.username|urlencode}&quot; title=&quot;Banner&quot;&gt;&lt;img src=&quot;{$account.banner_url|htmlspecialchars}&quot; alt=&quot;Banner&quot; border=&quot;0&quot; /&gt;&lt;/a&gt;
      &lt;br /&gt;&lt;br /&gt;
      {/if}{$account.description|htmlspecialchars}</description>
      <pubDate>{$account.timestamp_activated|tdate::'D, d M Y H:i:s %%RSSTIMEZONE%%'}</pubDate>
    </item>
{/foreach}

  </channel>
</rss>
