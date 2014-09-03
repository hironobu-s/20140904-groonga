#!/usr/bin/env php
<?php

/**
 * 引数に渡されたキーワードでGroongaにselectコマンドを投げる
 * 出現数と結果を適当に出力する。
 */

require_once 'groonga.php';

$query = $argv[1];
if(strlen($query) == 0) {
    throw new Exception('argv[1] is required.');
}

$g = new Groonga('157.7.152.45', 10041);

$params = [
    'limit' => 10,
];

$retval = $g->selectByFilter('TwSource', "text @ '$query'", $params);

var_dump($retval->count);
var_dump($retval->results);


