<?php
if( !defined('ToplistX') ) die("Access denied");

$hours = array();
$today = gmdate(DF_DATE, TIME_NOW);
$yesterday = FALSE;

foreach( range(0,23) as $i )
{
    $timestamp = TIME_NOW - ($i * 3600);

    if( !$yesterday && $today != gmdate(DF_DATE, $timestamp) )
    {
        $yesterday = TRUE;
    }

    $hours[gmdate('G', $timestamp)] = gmdate('h:00a ' , $timestamp) . ($yesterday ? 'Yesterday' : 'Today');
}

$dates = $DB->Row('SELECT MIN(`date_stats`) AS `min`,MAX(`date_stats`) AS `max` FROM `tlx_account_daily_stats` WHERE `username`=?', array($_REQUEST['username']));

if( !empty($dates['min']) )
{
    $min_ts = strtotime("{$dates['min']} 00:00:00");
    $max_ts = strtotime("{$dates['max']} 23:59:59");
    $date_options = array();

    while( $max_ts >= $min_ts )
    {
        $date_options[date(DF_DATE, $max_ts)] = date($C['date_format'], $max_ts);
        $max_ts -= 86400;
    }
}

if( $_SERVER['REQUEST_METHOD'] == 'GET' )
{
    $_REQUEST['hour_start'] = gmdate('G', TIME_NOW - 3600 * 23);
    $_REQUEST['hour_end'] = gmdate('G', TIME_NOW);
    $_REQUEST['date_start'] = $dates['min'];
    $_REQUEST['date_end'] = $dates['max'];
}

include_once('includes/header.php');
?>

<script language="JavaScript">
function checkInput()
{
    if( $('#hour_start')[0].selectedIndex < $('#hour_end')[0].selectedIndex )
    {
        alert('Hourly Stats Error: The "From" hour must come before the "To" hour');
        return false;
    }

    if( $('#date_start')[0] )
    {
        if( $('#date_start')[0].selectedIndex < $('#date_end')[0].selectedIndex )
        {
            alert('Daily Stats Error: The "From" date must come before the "To" date');
            return false;
        }
    }
}
</script>

<style>
body, html {
    height: auto;
}
</style>

<div style="padding: 0px 10px 10px 10px;">

  <form action="index.php" method="POST" onSubmit="return checkInput()">
  <fieldset>
    <legend>Hourly Account Stats</legend>

    <div style="text-align: center">
    <b>From:</b>
    <select name="hour_start" id="hour_start">
      <?php echo OptionTags($hours, $_REQUEST['hour_start']); ?>
    </select>

    <b style="padding-left: 20px;">To:</b>
    <select name="hour_end" id="hour_end">
      <?php echo OptionTags($hours, $_REQUEST['hour_end']); ?>
    </select>

    <button type="submit" style="margin-left: 20px;">View</button>
    </div>

    <?php

    $hours = array('raw_in' => array(),
                   'unique_in' => array(),
                   'raw_out' => array(),
                   'unique_out' => array(),
                   'clicks' => array());

    for( $i = $_REQUEST['hour_end']; $hour != $_REQUEST['hour_start']; $i-- )
    {
        $hour = ($i % 24 < 0 ?  24 - abs($i % 24) : $i % 24);

        $hours['raw_in'][] = '`raw_in_' . $hour . '`';
        $hours['unique_in'][] = '`unique_in_' . $hour . '`';
        $hours['raw_out'][] = '`raw_out_' . $hour . '`';
        $hours['unique_out'][] = '`unique_out_' . $hour . '`';
        $hours['clicks'][] = '`clicks_' . $hour . '`';
    }

    $hourly_stats = $DB->Row('SELECT ' .
                             join('+', $hours['raw_in']) . ' AS `raw_in`,' .
                             join('+', $hours['unique_in']) . ' AS `unique_in`,' .
                             join('+', $hours['raw_out']) . ' AS `raw_out`,' .
                             join('+', $hours['unique_out']) . ' AS `unique_out`,' .
                             join('+', $hours['clicks']) . ' AS `clicks` ' .
                             'FROM `tlx_account_hourly_stats` WHERE `username`=?', array($_REQUEST['username']));

    foreach( $hourly_stats as $name => $value ): ?>

    <div class="fieldgroup">
      <label class="lesspad"><?php echo ucfirst(str_replace('_', ' ', $name)); ?>:</label> <?php echo number_format($value, 0, $C['dec_point'], $C['thousands_sep']); ?>
    </div>

    <?php endforeach; ?>

  </fieldset>

  <fieldset>
    <legend>Daily Account Stats</legend>

    <?php
    if( $dates['min'] ):

        if( $dates['min'] == $_REQUEST['date_start'] && $dates['max'] == $_REQUEST['date_end'] )
        {
            $daily_stats = $DB->Row('SELECT ' .
                                    'SUM(`raw_in`) AS `raw_in`,' .
                                    'SUM(`unique_in`) AS `unique_in`,' .
                                    'SUM(`raw_out`) AS `raw_out`,' .
                                    'SUM(`unique_out`) AS `unique_out`,' .
                                    'SUM(`clicks`) AS `clicks` ' .
                                    'FROM `tlx_account_daily_stats` WHERE `username`=?',
                                    array($_REQUEST['username']));
        }
        else
        {
            $daily_stats = $DB->Row('SELECT ' .
                                    'SUM(`raw_in`) AS `raw_in`,' .
                                    'SUM(`unique_in`) AS `unique_in`,' .
                                    'SUM(`raw_out`) AS `raw_out`,' .
                                    'SUM(`unique_out`) AS `unique_out`,' .
                                    'SUM(`clicks`) AS `clicks` ' .
                                    'FROM `tlx_account_daily_stats` WHERE `username`=? AND `date_stats` BETWEEN ? AND ?',
                                    array($_REQUEST['username'], $_REQUEST['date_start'], $_REQUEST['date_end']));
        }
    ?>


    <div style="text-align: center">
    <b>From:</b>
    <select name="date_start" id="date_start">
      <?php echo OptionTags($date_options, $_REQUEST['date_start']); ?>
    </select>

    <b style="padding-left: 20px;">To:</b>
    <select name="date_end" id="date_end">
      <?php echo OptionTags($date_options, $_REQUEST['date_end']); ?>
    </select>

    <button type="submit" style="margin-left: 20px;">View</button>
    </div>

    <?php foreach( $daily_stats as $name => $value ): ?>

    <div class="fieldgroup">
      <label class="lesspad"><?php echo ucfirst(str_replace('_', ' ', $name)); ?>:</label> <?php echo number_format($value, 0, $C['dec_point'], $C['thousands_sep']); ?>
    </div>

    <?php endforeach; ?>

    <?php else: ?>
    <div class="notice">
    This account does not yet have any daily stats recorded
    </div>
    <?php endif; ?>

  </fieldset>


  <fieldset>
    <legend>Referrer Stats</legend>

    <?php
    $result = $DB->Query('SELECT * FROM `tlx_account_referrer_stats` WHERE `username`=? ORDER BY `raw_in` DESC', array($_REQUEST['username']));

    if( $DB->NumRows($result) ):
        while( $referrer = $DB->NextRow($result) ):
            ArrayHSC($referrer);
    ?>
    <div style="clear: both; margin-bottom: 5px;">
    <div style="float: left; width: 80px; text-align: right; padding-right: 10px">
    <?php echo number_format($referrer['raw_in'], null, $C['dec_point'], $C['thousands_sep']); ?>
    </div>
    <?php if( $referrer['referrer'] == '-' ): ?>
    No Referrer
    <?php else: ?>
    <a href="<?php echo $referrer['referrer']; ?>" target="_blank"><?php echo $referrer['referrer']; ?></a>
    <?php endif; ?>
    </div>
    <?php
        endwhile;
    else:
    ?>
    <div class="notice">
    This account does not yet have any referrer stats recorded
    </div>
    <?php
    endif;
    ?>
  </fieldset>

  <input type="hidden" name="username" value="<?php echo htmlspecialchars($_REQUEST['username']); ?>">
  <input type="hidden" name="r" value="tlxShAccountStats">
  </form>

</div>

</body>
</html>
