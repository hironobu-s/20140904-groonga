#!/usr/bin/env php
<?php
require_once 'groonga.php';

$groonga = new Groonga('157.7.152.45', 10041);

$cond = [
  'table' => 'TableName',
  'match_columns' => 'test_col',
  'query' => 'hoge',
  'limit' => 10,
  'offset' => 0
];

$r = $groonga->execute('select', $cond);

// ヒット数
var_dump($r->count);

// 列情報
var_dumP($r->headers);

// 実行結果
var_dumP($r->results);