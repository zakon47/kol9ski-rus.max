<?php defined('_3AKOH') or die(header('/'));

class Collect_parent{
    protected $api_name = '';
    protected $api_key = '';
    protected $api_secret = '';
    public $FOR_MARKETS = [];
    public function __construct(){
        if(empty($this->api_key) || empty($this->api_secret)) $this->init_secret_key();     //инициализируем API
    }
    private function init_secret_key(){     //инициализируем API
        global $CONFIG;
        if(!isset($CONFIG['account']) || !count($CONFIG['account'])){      //если нету подключаемых кошельков
            dd('отсутствуют wallet::Collects');
            die();
        }
        if(isset($_COOKIE['account']) && isset($CONFIG['account'][$_COOKIE['account']])){
            $wallet = $_COOKIE['account'];
        }else{
            $wallet = array_keys($CONFIG['account'])[0];
            setcookie('account',$wallet, time() + 3600*24,'/');      //записали номер кошелька
        }
        $wal = explode('|',$CONFIG['account'][$wallet]);
        $this->api_name = $wallet;
        $this->api_key = $wal[0];
        $this->api_secret = $wal[1];
    }
    private function proverka_myquery($list,$myquery){  //$list - обязательные, $myquery - то что у меня
        $list_arr = explode(',',$list);
        $error = [];
        if(!empty($list_arr)){
            for($i=0;$i<count($list_arr);$i++){
                if(!isset($myquery[$list_arr[$i]])){
                    $error[] = $list_arr[$i];
                }
            }
        }
        if(!empty($error)){
            dd('<u>Укажите</u>: <b>'.implode('</b>, <b>',$error).'</b>');
        }
        return '';
    }


    protected function collect($command,$myquery=[]){
        if(empty($this->api_key) || empty($this->api_secret)) $this->init_secret_key();     //инициализируем API
        //https://support.bittrex.com/hc/en-us/articles/115003723911-Developer-s-Guide-API
        $api_query = [
            'getbalances'           => '/account/getbalances',             //остатки всех монет                                        [apikey=API_KEY]
            'getbalance'            => '/account/getbalance',              //тоже само для 1 монеты                                       [apikey=API_KEY + currency=BTC + quantity=20.40 + address=EAC_ADDRESS]
            'getorder'              => '/account/getorder',                //вся инфа по ордеру?                                        [uuid=0cb4c4e4-bdc7-4e13-8c13-430e587d2cc1]

            'getdeposithistory'     => '/account/getdeposithistory',       //история депозита                                        [apikey=API_KEY + currency=BTC]
            'getdepositaddress'     => '/account/getdepositaddress',       //получить адрес кошелька                                     [apikey=API_KEY + currency=BTC]
            'withdraw'              => '/account/withdraw',                //вывести средства                                       [uuid=0cb4c4e4-bdc7-4e13-8c13-430e587d2cc1]
            'getorderhistory'       => '/account/getorderhistory',         //извлечение истории заказов                                    [market=BTC-LTC]
            'getwithdrawalhistory'  => '/account/getwithdrawalhistory',    //история вывода средств                                      [currency=BTC]

            //================
            'buylimit'              => '/market/buylimit',                 //выписать орден на покупку                                  [apikey=API_KEY + market=BTC-LTC + quantity=1.2 + rate=1.3]
            'selllimit'             => '/market/selllimit',                //выписать ордер на продажу                                  [apikey=API_KEY + market=BTC-LTC + quantity=1.2 + rate=1.3]
            'cancel'                => '/market/cancel',                   //отменить ордер                                             [apikey=API_KEY + uuid=ORDER_UUID]
            'getopenorders'         => '/market/getopenorders',            //список всех АКТИВНЫХ ордеров                               [apikey=API_KEY + market=BTC-LTC]
            //=============
            'getorderbook'          => '/public/getorderbook',             //СТАКАНЫ СДЕЛОК                                             [market=BTC-LTC + type=both|buy|sell]
            'getticker'             => '/public/getticker',                //текущие значение тикетов рынка - 3шт / Last-Big-Ask        [market=btc-ltc]

            'getmarkets'            => '/public/getmarkets',               //получить список всех доступных ПАР-монет
            'getcurrencies'         => '/public/getcurrencies',            //получить список торгуемых монет для биржи
            'getmarketsummaries'    => '/public/getmarketsummaries',       //Сводка за 24 часа - инфа на главной сранице
            'getmarketsummary'      => '/public/getmarketsummary',         //тоже самое только для 1 пары                               [market=btc-ltc]
            'getmarkethistory'      => '/public/getmarkethistory',         //УСПЕШНЫЕ СДЕЛКИ сделки за последние 6ч                     [market=BTC-LTC]
        ];
        if(!array_key_exists($command,$api_query)){
            return 'Нет такой команды';
        }
        $nonce=time();
        $query = [
            'apikey' =>$this->api_key,
            'nonce' =>$nonce,
        ];
        //указываем обязательные поля
        $valid = [
            'getticker'             => 'market',
            'getmarketsummary'      => 'market',
            'getorderbook'          => 'market,type',
            'getmarkethistory'      => 'market',
            'buylimit'              => 'market,quantity,rate',
            'selllimit'             => 'market,quantity,rate',
            'cancel'                => 'uuid',
            'getbalance'            => 'currency',
            'getdepositaddress'     => 'currency',
            'withdraw'              => 'currency,quantity,address',
            'getorder'              => 'uuid',
            'getwithdrawalhistory'  => 'currency',
            'getdeposithistory'     => 'currency',
        ];
        if(array_key_exists($command,$valid)){       //проверяем
            $error = $this->proverka_myquery($valid[$command],$myquery);
            if(!empty($error)){ return $error;}
        }
        if(!empty($myquery)){
            $query = array_merge($query,$myquery);
        }
        if(!empty($query)){
            $qlink = '?'.http_build_query($query);
        }else{
            $qlink = '';
        }
        $uri = APIADRESS.$api_query[$command].$qlink;
        $sign=hash_hmac('sha512',$uri,$this->api_secret);
        $option = [
            CURLOPT_HTTPHEADER => array('apisign:'.$sign),
        ];
        $newStack_json = sendRequest($uri,$query,'get',$option);        //
        if(substr($newStack_json,0,1) == '{'){
            return json_decode($newStack_json,1);
        }else{
//            new myError('Сервер вернул что-то не то',['uri'=>$uri,'newStack_json'=>$newStack_json,'query'=>$query]);
            return false;       //возвращаем отказ
        }
    }                   //отпраивли запрос

    /**
     * ОТСОРТИРОВАЛИ МАССИВ ПО 1 КЛЮЧУ? - сделали ассоциативный
     * @param array $arr
     * @param string $name
     * @return array
     */
    protected function addKeyName($arr,$name){
        $keyName = [];
        if(!empty($arr)){
            for($i=0;$i<count($arr);$i++){
                $keyName[$arr[$i][$name]] = $arr[$i];
            }
        }
        return $keyName;
    }
    //перевести KEY в название
    /**
     * ВОЗВРАЩАЕМ ИМЯ ТЕУЩЕГО СТАТУСА - BUY или SELL
     * @param $type
     * @return string
     */
    protected function getTypeCoin($type){
        return (strpos($type,'BUY')!==false)?'BUY':'SELL';
    }



    public function repeat_collect($command,$myquery=[]){
        $steep = explode(' ','1 1 1');    //схема повторных запросов
        $i=0;
        $key = true;
        do{
            if(!isset($steep[$i])){
                $key=false;        //если попытки закончились - выходим
            }else{
                $q = $this->collect($command,$myquery);    //делаем запрос
                if(!empty($q) && $q['success']){
                    $key=false;
                }else{
                    sleep($steep[$i]);                   //выдерживаем паузу
                    $i++;
                }
            }
        }while($key);   //выполняем запрос
        if(!isset($q) || empty($q) || !$q['success']) $q = false;
        return $q;
    }

}