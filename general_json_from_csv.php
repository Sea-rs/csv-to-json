<?php
/**
 * TODO
 * ・読み込み元と保存先をコマンドラインから読み込むようにする（フォルダの指定）
 * ・CSVファイルの保存
 * ・readme.mdをちゃんと書く
 * ・保存機能を付ける
 * ・CSV内に保存先のパスを入れる場合
 * ・テストシートの作成
 * 
 * options
 * ・簡易モードか上級モードか判定
 * ・csvの読み込み元
 * ・保存先（ファイル名にスラッシュを入れるとフォルダ指定になる）
 *   →windows特有のバックスラッシュでもバグらないようにする
 * ・1列目は保存先のパスになっているのか？
 * ・連番で保存するか？（連番しなかったら一つしかファイル保存されないことに注意）
 */

$file_name = 'csv_sample.csv';
$dir_path = './test1/test2/';
// 後々、コマンドでパスを指定できるようにするためのもの。
// $path = '';

$fp = fopen( $file_name, 'r' );

$line = 0;
$csv_data = [];
$header = [];
$raw_header = fgetcsv( $fp );

foreach ( $raw_header as $i => $head ) {
    $properties = [];
    $header_parts = explode( '-', $head );
    $head = $header_parts === false ? [$head] : $header_parts;

    foreach ( $head as $property ) {
        $header_instance = new Header;
        $header_instance->init( $property );

        array_push( $properties, $header_instance );
    }

    array_push( $header, $properties );
}

while ( $row = fgetcsv( $fp ) ) {
    $json = [];

    foreach ( $row as $i => $data ) {
        $property_obj = null;
        $nesting_json = null;
        $json_structure = &$json;

        foreach ( $header[$i] as $property ) {
            $property_obj = $property;
            $current_property = $property_obj->get_header();

            // 一回目のループ以外であれば、$json_structureを更新する
            if ( $nesting_json !== null ) {
                $json_structure = &$nesting_json;
            }

            // keyが存在する場合は、参照可能
            if ( array_key_exists( $current_property, $json_structure ) ) {
                $nesting_json = &$json_structure[$current_property];
            } else {
                $json_structure[$current_property] = [];

                $nesting_json = &$json_structure[$current_property];
            }
        }

        $value = $data;

        if ( $property_obj !== null && $property_obj->is_array_prop() ) {
            if ( is_array( $nesting_json ) ) {
                $nesting_json[] = $value;
            } else {
                $nesting_json = array( $value );
            }
        } else {
            $nesting_json = $value;
        }

        unset( $nesting_json );
        unset( $json_structure );
    }

    mkdir( $dir_path, 0777, true );
    file_put_contents( $dir_path . 'test' . $line . '.json', json_encode( $json, JSON_UNESCAPED_UNICODE ) );

    $line++;
}

// headerの数だけインスタンスを作る。-で区切っている中身も同様に
class Header {
    /**
     * csvのヘッダー
     */
    private string $header = '';
    /**
     * 値が配列のプロパティ
     */
    private bool $is_array_prop = false;

    /**
     * 値が配列のプロパティかを判定する処理
     * 基本、インスタンス生成時のみ実行される
     */
    private function detect_is_array() {
        $discriminator = substr( $this->header, -2 );

        if ( $discriminator === '[]' ) {
            $this->header = substr( $this->header, 0, -2 );
            $this->is_array_prop = true;
        }
    }

    /**
     * 最初に実行されるコンストラクター的な処理
     * インスタンスにヘッダーの情報を登録したり、配列のプロパティかの判定などを行う
     */
    public function init( string $header ) {
        $this->header = $header;

        $this->detect_is_array();
    }

    /**
     * ヘッダーの情報を取得する
     */
    public function get_header() {
        return $this->header;
    }

    /**
     * 処理を行っているプロパティの値が配列かを判定する
     */
    public function is_array_prop() {
        return $this->is_array_prop;
    }
}