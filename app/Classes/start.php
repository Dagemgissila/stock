<?php
namespace App\Classes;

class start
{
    public static function checkRequirements(): array
    {
        return [
            'php_version' => ['status'=>version_compare(PHP_VERSION,'8.0.0','>='),'required'=>'8.0','current'=>PHP_VERSION],
            'pdo_mysql'   => ['status'=>extension_loaded('pdo_mysql'),  'required'=>'PDO MySQL'],
            'openssl'     => ['status'=>extension_loaded('openssl'),    'required'=>'OpenSSL'],
            'mbstring'    => ['status'=>extension_loaded('mbstring'),   'required'=>'MBString'],
            'tokenizer'   => ['status'=>extension_loaded('tokenizer'),  'required'=>'Tokenizer'],
            'json'        => ['status'=>extension_loaded('json'),       'required'=>'JSON'],
            'bcmath'      => ['status'=>extension_loaded('bcmath'),     'required'=>'BCMath'],
            'gd'          => ['status'=>extension_loaded('gd'),         'required'=>'GD Image'],
        ];
    }
}
