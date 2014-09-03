<?php

/**
 * Groongaに接続する簡易クラス
 *
 * // 使い方
 * $groonga = new Groonga('localhost', 10041);
 *
 * $cond = [
 *   'table' => 'TableName',
 *   'match_columns' => 'test_col',
 *   'query' => 'hoge'
 * ];
 * $result = $groonga->execute('select', $cond);
 * var_dump($result);
 * 
 */

class Groonga
{
    private $host = 'localhost';
    private $post = 10041;

    /**
     * コンストラクタ
     * @param string $host 接続先ホスト
     * @param string $port 接続先ポート
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Groongaのコマンドを実行する
     * 
     * see http://groonga.org/ja/docs/reference/command.html
     *
     * @param string $cmd コマンド名
     * @param array  $params コマンドに渡すパラメータ
     * @return array
     */
    public function execute($cmd, $params = [])
    {
        return $this->sendRequest($cmd, $params);
    }

    /**
     * Groongaサーバにリクエストを送信して、結果をJSONで返す
     *
     * @param string $cmd コマンド名
     * @param array  $params コマンドに渡すパラメータ配列
     * @return array
     */
    private function sendRequest($cmd, $params = [])
    {
        $url = sprintf('http://%s:%d/d/%s', $this->host, $this->port, $cmd);
        $url .= '?' . http_build_query($params);
        
        // リクエストを送信
        $curl = $this->initializeCurl();
        curl_setopt($curl, CURLOPT_URL, $url);
        
        $body = curl_exec($curl);
        if( ! $body) {
            throw new RuntimeException('HTTP Request fail.');
        }
        
        // TwitterなどはUTF8外？のよくわからない文字が含まれていることがある？
        // json_decodeが失敗するので、mb_convert_encodingで無理矢理除去する。
        $body = mb_convert_encoding($body, 'UTF-8', 'auto');
        
        // レスポンスをデコードする
        $json = json_decode($body, true);
        if( ! $json) {
            throw new RuntimeException('Incorrect datatype for JSON.');
        }

        // この要素が0未満の場合はコマンド実行失敗で、[0][3]にメッセージが入るっぽい。
        if($json[0][0] < 0) {
            throw new RuntimeException($json[0][3]);
        }
        
        return $json;
    }

    /**
     * queyrパラメータでselectコマンドを実行する便利メソッド
     *
     * @param string $query
     * @param array $match_column
     * @param array $additional 追加パラメータ(limitとかoffsetとか)
     * @return array
     */
    public function selectByQuery($table, $query = null, array $match_columns = [], array $additionals = [])
    {
        $params = [
            'table' => $table
        ];

        if($query) {
            $params['query'] = strtolower($query);
        }

        if(count($match_columns) > 0) {
            $params['match_columns'] = join(',', $match_columns);
        }
        
        $params = array_merge($params, $additionals);
        
        $cmd_result = $this->execute('select', $params);
        return $this->processSelectResult($cmd_result);
    }

    /**
     * filterパラメータでselectコマンドを実行する便利メソッド
     *
     * @param string $filter
     * @param array $additional 追加パラメータ(limitとかoffsetとか)
     * @return array
     */
    public function selectByFilter($table, $filter = null, array $additionals = [])
    {
        $params = [
            'table' => $table
        ];

        if($filter) {
            $params['filter'] = $filter;
        }

        $params = array_merge($params, $additionals);
        
        $cmd_result = $this->execute('select', $params);
        return $this->processSelectResult($cmd_result);
    }

    /**
     * selectコマンドの結果を処理してGroongaSearchResultオブジェクトを返す
     *
     * @param array $cmd_result selectコマンドの実行結果
     * @return object
     */
    private function processSelectResult($cmd_result)
    {
        $r = new GroongaSearchResult();
        $r->count = $cmd_result[1][0][0][0];

        // カラム情報取得
        $r->columns = $cmd_result[1][0][1];
        $columns = [];
        foreach($cmd_result[1][0][1] as $col) {
            $columns[] = $col[0];  // カラム名を取得しておく。
        }
        
        $results = [];
        for($i = 2; $i < count($cmd_result[1][0]) ; $i++) {
            $results[] = array_combine($columns, $cmd_result[1][0][$i]);
        }

        $r->results = $results;
        return $r;
    }

    /**
     * cURLの初期化
     *
     * @return curl instance.
     */
    private function initializeCurl()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, false);
    
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3600);

        curl_setopt($curl, CURLOPT_HTTPGET, true);
        
        return $curl;
    }
}
    

/**
 * selectコマンドの結果を格納するオブジェクト
 */
class GroongaSearchResult
{
    // 検索ヒット数
    public $count   = -1;

    // カラム情報
    public $columns = [];
    
    // 検索結果
    public $results = [];
}