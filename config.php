<?php defined('_3AKOH') or die(header('/'));

$BList = [        // первая цифра - частота обновления в минутах, вторая диапазон приходящих данных в минутах
    'B1'=>[1,   [0,4]],
    'B2'=>[2,   [5,6]],
    'B3'=>[4,   [7,10]],
    'B4'=>[8,   [11,14]],
    'B5'=>[12,   [15,999]],
];

$CONFIG = [
    'custom' => [
        'myerror'       => 1,           //включить или отключить MyError
        'cron'          => 1,           //включить или отключить CRON команды
        'show_error'    => 1,           //отображать ошибки на сайте
        'report_error'  => E_ALL,           //каие ошибки отображать
        'log'           => 0,           //логирование ошибок локально
        'send'          => 0,           //отправлять ли ошибки на Телеграмм
    ],
    'server' => [
        'timezone'      =>  'Europe/Moscow',    //зона локального сервера
        'url'           =>  'vavilon-center.ru',           //имя текущего сайта
        'protocol'      =>  'http://',             //протокол текущего сайта
    ],
    'block_coins'   => [        //Запретные "РУКИ" - руки которые не учавствуют в торгах и сборе монет!
        'USDT-BTC',
        'ETH-BTC',
        'USD-BTC',
    ],
    'min_orders'   => 0.0005,           //минимальный размер ОРДЕРА!
    'ANAL'   => [                   //МОНЕТЫ КОТОРЫЕ АНАЛИЗИРУЕМ!
        'ETH','POWR'
    ],
    'sreda' => [
        'bittrex'       => [
            'timezone'  => 3,                   //разница во времени с сервером
            'timezone2'  => 'H3',               //разница во времени с сервером
            'maxcpu'    => 30,                  //максимльно обрабатываемых монет
            'action'    => [                    //какие операции можно делать
                'buy'  => 1,
                'sell' => 1,
            ],
            'komissia'    => [                    //Комисси - учитывать для точности
                'BTC'  => 0.00004,
            ],
            'coins'         => "'BTC','ETH','USDT'",     //монеты которые надо парсить
            'anal_coin'         => ['BTC','ETH','USDT'],     //монеты которые надо анализивароть
            'body' => [
                'v1' => &$BList,    //название ядра
                'v2' => &$BList,    //название ядра
                'v3' => &$BList,    //название ядра
                'v4' => &$BList,    //название ядра
            ]
        ]
    ],
    'localhost' => [
        'typedb'        => 'mysql',
        'hostname'      => 'localhost',
        'username'      => '046003566_br2',
        'password'      => '26kxRRNUP47',
        'database'      => '9313000353_bittrex2',
        'prefix'        => 'dmb__',
        'dbcollat'      => 'UTF8',
    ],
    'socket'    => [
        'web'   => '//127.0.0.47:8000',
        'local' => 'tcp://127.0.0.47:1234'
    ],
    'account' => [
        'x0_16'         => '0128068a15aa493496d9ee46be90c2d6|f98cbb7a2b2a4b4fa2f5a09d760dabc3',
        'x0_20'         => '777bd349f419430b82c4ce22072abaf3|fdbe40b0a5054dec9011de2e244c1ea5',
    ]
];

//$argv = ['xxx','login','upd','da'];
#ОБРАБОТЧИК URL
function getRequest($argv=''){

//    define('AUTH',false);           //можно ли авторизоваться
    $url = [];
    if($argv){      //если это крон
        if(!defined('AUTH')) define('AUTH',false);           //Запрет авторизации
        if(count($argv)>1){
            for($i=1;$i<count($argv);$i++){
                $url[] = $argv[$i];
            }
        }else{
            $url = [''];
        }
    }else{
        if(!defined('AUTH')) define('AUTH',true);           //Разрешаем авторизации
        $str = (substr($_SERVER['REQUEST_URI'],-1)=='/')?substr($_SERVER['REQUEST_URI'],0,strlen($_SERVER['REQUEST_URI'])-1):$_SERVER['REQUEST_URI'];       //отрезаем последний флеш
        $str = substr($str,0,(strripos($str,'?')!==false)?strripos($str,'?'):strlen($str));     //обрезаем GET параметры
        if(!$str) $str = 'index';
        $str = ($str[0]=='/')?substr($str,1):$str;
        if(!empty($str)) $url = explode('/',$str);
        else $url = [''];
    }
    return $url;
}