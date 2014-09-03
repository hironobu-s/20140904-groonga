#!/usr/bin/env php
<?php

// see https://github.com/fennb/phirehose
require_once 'phirehose/lib/Phirehose.php';
require_once 'phirehose/lib/OauthPhirehose.php';

require_once 'groonga.php';

// OAuth
$consumer_key = '';
$consumer_secret = '';
$access_token = '';
$access_token_secret = '';

/**
 * StreamAPIからツイートを取得してGroongaにストアするやっつけスクリプト
 */
class TestStream extends OauthPhirehose
{
    // Groongaオブジェクト
    private $groonga;
    
    public function __construct($username, $password, $method = Phirehose::METHOD_SAMPLE, $format = self::FORMAT_JSON, $lang = FALSE)
    {
        parent::__construct($username, $password, $method, $format, $lang);

        $this->groonga = new Groonga('localhost', 10041);
    }
    
    public function enqueueStatus($status)
    {
        static $counter = 0;
        
        $data = json_decode($status);
        
        if (is_object($data) && isset($data->user->screen_name)) {
            
            // 日付を変換
            $time = strtotime($data->created_at);
            
            // Groongaにデータを保存
            $tweet = [
                '_key' => $data->id_str,
                'text' => $data->text,
                'source' => $data->source,
                'name' => $data->user->name,
                'screen_name' => $data->user->screen_name,
                'created_at' => $time
            ];

            if($data->geo != null) {
                $geo = $data->geo->coordinates[0] . 'x' . $data->geo->coordinates[1];
                $tweet['geo'] = $geo;
            }

            $data = [
                'table' => 'TwStream',
                'values' =>
                json_encode([ $tweet ])
            ];
            
            $r = $this->groonga->execute('load', $data);

            if($counter++ % 10000 == 0) {
                echo "$counter tweet saved.\n";
            }
            
        }  else {
            
        }
    }
}

$s = new TestStream($access_token, $access_token_secret);
$s->setLang('ja');
$s->consume();
