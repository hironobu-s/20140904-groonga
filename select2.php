#!/usr/bin/env php
<?php

/**
 * 引数に渡されたキーワードでGroongaにselectコマンドを投げるスクリプト。
 * キーワードの出現数を日別に集計する。
 */

require_once 'groonga.php';

$query = $argv[1];
if(strlen($query) == 0) {
    throw new Exception('argv[1] is required.');
}

$g = new Groonga('157.7.152.45', 10041);
$params = [
    'limit' => 0,
];

$dates = [
    '2014-08-24',
    '2014-08-25',
    '2014-08-26',
    '2014-08-27',
    '2014-08-28',
    '2014-08-29',
    '2014-08-30',
    '2014-08-31',
    '2014-09-01',
];

foreach($dates as $date) {
    $retval = $g->selectByFilter('TwSource', "text @ '$query' && date == '$date'", $params);
    echo $date . ": " . $retval->count . "\n";
}

