<?php

namespace Brocker;

class Server extends ServerParent{
    const APIADRESS = [
        'bittrex' => 'https://bittrex.com/api/v1.1',
    ];

    protected function collect($command,$myquery=[]){
        Brocker::$count++;
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
            'apikey' =>$this->_api_key,
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
        $uri = self::APIADRESS[$this->_sreda].$api_query[$command].$qlink;
        $sign=hash_hmac('sha512',$uri,$this->_api_secret);
        $option = [
            CURLOPT_HTTPHEADER => array('apisign:'.$sign),
        ];
        $newStack_json = sendRequest($uri,$query,'get',$option);        //
        if(substr($newStack_json,0,1) == '{'){
            return json_decode($newStack_json,1);
        }else{
            return false;       //возвращаем отказ
        }
    }
}