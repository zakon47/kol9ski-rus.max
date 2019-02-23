<?php

namespace Brocker;

use Lib\DB;

class WalletParent{
    protected $_hash;
    protected $_sreda;
    protected $_account;
    protected $_SERVER;
    protected $_CONFIG;

    protected $OPEN_ORDERS    = false;
    protected $WALLET         = false;
    protected $HISTORY        = false;
    protected $SYNC = [];

    //==================
    public function __construct($_hash) {
        Brocker::$_hash_data[$_hash]['_WALLET'] = $this;
        $this->_SERVER = &Brocker::$_hash_data[$_hash]['_SERVER'];
        $this->_CONFIG = &Brocker::$_hash_data[$_hash]['_CONFIG'];

        $this->_hash = $_hash;
        $this->_sreda = &Brocker::$_hash_data[$_hash]['_sreda'];
        $this->_account = &Brocker::$_hash_data[$_hash]['_account'];
    }
    public function init($sync=[]): bool {
        if(!empty($sync)){
            $x1 = true;
            $x2 = true;
            $x3 = true;
            if(in_array('order',$sync)){
                $x1 = $this->syncOrders();
                $x2 = $this->syncHistory();
            }else {
                if(in_array('open',$sync)){
                    $x1 = $this->syncOrders();
                }
                if(in_array('history',$sync)){
                    $x2 = $this->syncHistory();
                }
            }
            if(in_array('wallet',$sync)){
                $x3 = $this->syncWallet();
            }else{
                $DB = $this->getWalletDB(GET_DATA_WALLET);         //показать все позиции из DB
                if(isset($DB['error']) && $DB['error']='not table') {    //если нету такой таблицы
                    $this->createTableWallet();
                    $x3 = $this->syncWallet();                      //синхронизировали данные
                }else{
                    $this->WALLET = Brocker::addKeyName($DB['data'],'cur');
                }
            }
            if(!$x1 || !$x2 || !$x3) return false;      //если что-то не синхронизировалось
        }else{
            #прссто получили кошельки
            $DB = $this->getWalletDB(GET_DATA_WALLET);         //показать все позиции из DB
            if(isset($DB['error']) && $DB['error']='not table') {    //если нету такой таблицы
                $this->createTableWallet();
                $this->syncWallet();                      //синхронизировали данные
            }else{
                $this->WALLET = Brocker::addKeyName($DB['data'],'cur');
            }
        }
        if($this->WALLET) return true;
        return false;
    }
    public function getStatusSync(){
        return $this->SYNC;
    }


    //================== SYNC
    public function syncOrders(){
        $SERVER = $this->_SERVER->repeat_collect('getopenorders');
        $this->preobrazovat_orders($SERVER);        //преобразовали ОТВЕТ
        if($SERVER===false){
            $this->OPEN_ORDERS = false;
            return false;
        }
        $DATA = [
            'uid' => [],
            'orders' => []
        ];
        #ФОРМАТИРУЕМ ПОЛУЧЕННЫЕ ДАННЫЕ И ЗАПИСЫВАЕМ ИХ В OPEN_ORDERS
        if(!empty($SERVER)){
            #ЗАКИДЫВАЕМ В СВОЙ СКЕЛЕТ
            for($i=0,$c=count($SERVER);$i<$c;$i++){
                $e = &$SERVER[$i];
                #ЕСЛИ ТАКОЙ МОНЕТЫ ЕЩЁ НЕ БЫЛО - создаем контейнер для нее [list]
                if(!isset($DATA['orders'][$e['coin']])){
                    $DATA['orders'][$e['coin']] = [
                        'coin'  => $e['coin'],
                        'count' => '',
                        'status'   => [
                            'BUY'   => 0,
                            'SELL'  => 0
                        ],
                        'list'  =>[]
                    ];
                }
                $DATA['orders'][$e['coin']]['list'][] = [
                    'coin'      => $e['coin'],
                    'status'      => $e['status'],
                    'bid_ask'      => $e['bid_ask'],
                    'kol_vo'      => $e['kol_vo'],
                    'btc'      => $e['btc'],
                    'open'      => $e['open'],
                    'close'      => $e['close'],
                    'uid'      => $e['uid'],
                ];
            }
            #ПОДЫМАЕМ ОБЩИЕ ДАННЫЕ НА УРОВЕНЬ ВЫШЕ и "СКЛАДЫВАЕМ"
            if(empty($DATA)) new myError('Как-то не понятно но данные ПУСТЫЕ перед поднятием на уровень выше в OPEN_ORDERS',['$DATA'=>$DATA,'$SERVER'=>$SERVER]);
            foreach ($DATA['orders'] as $COIN=>$ARR){         //$COIN имя монеты,  $ARR - (coin,count,uid..,list)
                $ORDERS = &$DATA['orders'];
                $ORDERS[$COIN]['count'] = count($ARR['list']);
                #ПЕРЕБИРАЕМ ВСЕ ОПЕРАЦИИ
                for($tr=0;$tr<$ORDERS[$COIN]['count'];$tr++){
                    $ob = &$ARR['list'][$tr];
                    //                        $ORDERS[$COIN]['uid'][] = $tr;       //текуший UID
                    $DATA['uid'][$ob['uid']] = $COIN.' / '.$tr;
                    if($ob['status']=='SELL'){
                        $ORDERS[$COIN]['status']['SELL'] = 1;
                    }else{
                        $ORDERS[$COIN]['status']['BUY'] = 1;
                    }
                }
            }
        }
        $this->OPEN_ORDERS = $DATA;
        $this->SYNC['OPEN_ORDERS'] = 1;      //мол синхронизировали
        return true;
    }
    public function syncWallet(){
        $SERVER = $this->_SERVER->repeat_collect('getbalances');
        $this->preobrazovat_wallet($SERVER);        //преобразовали ОТВЕТ
        if($SERVER===false){
            $this->WALLET = false;
            return false;
        }

//        unset($SERVER[count($SERVER)-1]);

        #ПОЛУЧИЛИ ТЕКУЩИЕ МОНЕТЫ
        $DB = $this->getWalletDB(GET_DATA_WALLET_UPD,false);         //показать все позиции из DB
        if(isset($DB['error']) && $DB['error']='not table') {    //если нету такой таблицы
            $this->createTableWallet();
            $DB_KEY = [];
        }else{
            $DB_KEY = Brocker::addKeyName($DB['data'],'cur');
        }

        #ПЕРЕБИРАЕМ КАЖДНЫЕ ДАННЫЕ ОТ СЕРВЕРА И ФОРМИРУЕМ $update and $insert
        $insert = [];
        $update = [];       //монеты которые изменились

        for($i=0;$i<count($SERVER);$i++){    //перебираем новые SERVER маркеры
            $e = &$SERVER[$i];        //элемент

            #ТЕПЕРЬ РАСПРЕДЕЛЯЕМ КАЖДУЮ МОНЕТУ  —  на UPDATE or INSERT
            $key = 1;   //разрешение на вставку новой записи в таблицу
            if(isset($DB_KEY[$e['cur']])){              //ЕСЛИ ЕСТЬ ЭТА МОНЕТА В DB
                if($e['md5']!=$DB_KEY[$e['cur']]['md5'] || !$DB_KEY[$e['cur']]['isA']){        // если кеш поменялся
                    $x = [
                        'id'            => $DB_KEY[$e['cur']]['id'],        //id в DB
                        'elem_id'       => $i,                                      //id в SERVER
                        'cur'           => $e['cur'],
                        'balance'       => $e['balance'],
                        'dostupno'      => $e['dostupno'],
                        'address'       => $e['address'],
                        'isA'           => 1,
                        'md5'           => $e['md5']
                    ];
                    $update[] = $x;
                }
                unset($DB_KEY[$e['cur']]);
                $key = 0;
            }
            #ПОДГОТОВКА ДЛЯ — ВСТАВКА НОВОЙ ЗАПИСИ
            if($key){   //не был найден в локальной таблице - значит эту запись надо вставить - создаем цифры
                $insert[] = [
                    ':cur'       => $e['cur'],
                    ':balance'   => $e['balance'],
                    ':dostupno'  => $e['dostupno'],
                    ':sreda'     => $this->_sreda,
                    ':account'   => $this->_account,
                    ':address'   => $e['address'],
                    ':md5'       => $e['md5']
                ];
            }
        }

//                dd('==========$DB_KEY',1);
//                dd($DB_KEY,1);
//                dd('==========insert',1);
//                dd($insert,1);
//                dd('==========update',1);
//                dd($update);

        if(!empty($DB_KEY)){
            #ВЫБИРАЕМ МОНЕТЫ КОТОРЫЕ ЕЩЁ НЕ ОТКЛЮЧИЛИ
            $list = [];
            foreach ($DB_KEY as $k=>$v){
                if($v['isA']) $list[] = $v;
            }
            #ОТКЛЮЧАЕМ МОНЕТЫ КОТОРЫЕ ЕЩЁ НЕ ОТКЛЮЧИЛИ т.к ИХ НЕТУ НА СЕРВЕРЕ ВООБЩЕ
            if(!empty($list)){
                for($i=0,$c=count($list);$i<$c;$i++){
                    $sql = "UPDATE ".TABLE_NAME_WALLET." SET :key WHERE `id`=:id";
                    $num = [
                        ':id'   => $list[$i]['id'],
                        ':key'   => [
                            ':isA'  => 0
                        ],
                    ];
                    DB::update($sql,$num);
                }
            }
        }
        if(!empty($insert)){        //если есть что вставлять -> ВСТАВЛЯЕМ
            $sql = "INSERT INTO `".TABLE_NAME_WALLET."` (`cur`,`balance`,`dostupno`,`sreda`,`account`,`address`,`md5`) VALUES :key";
            $num = [
                ':key' => $insert
            ];
            DB::insert($sql,$num);
        }
        if(!empty($update)) {        //если есть что обновить -> ОБНОВЛЯЕМ
            #ПЕРЕБИРАЕМ КАЖДЫЙ ОБНОВЛЕННЫЙ КОШЕЛЕК и НАХОДИМ НАЗВАНИЕ МОНЕТЫ КОТОРОЕ ПОМЕНЯЛОСЬ!
            for($k=0,$c=count($update);$k<$c;$k++){         //перебираем каждый кошелек, который изменился
                #ВСЛЕПУЮ ПЕРЕЗАПИСЫВАЕМ ДАННЫЕ НА НОВЫЕ
                $num = [
                    ':id' => $update[$k]['id'],
                    ':key' => [
                        ':balance'  => $update[$k]['balance'],
                        ':dostupno'  => $update[$k]['dostupno'],
                        ':address'  => $update[$k]['address'],
                        ':isA'  => $update[$k]['isA'],
                        ':md5'  => $update[$k]['md5'],
                    ]
                ];
                $sql = "UPDATE ".TABLE_NAME_WALLET." SET :key WHERE `id`=:id";
                dd($sql,1);
                DB::update($sql,$num,1);
            }
        }

        //ТУТ НАДО ОБНОВИТЬ ДАННЫЕ КОШЕЛЬКА НА НОВЫЕ! - потому что из него получаем БАЛАНС
        $DB = $this->getWalletDB(GET_DATA_WALLET);
        if(!isset($DB['data'])){
            $this->WALLET = false;
            return false;
        }else{
            $this->SYNC['WALLET'] = 1;      //мол синхронизировали
            $this->WALLET = Brocker::addKeyName($DB['data'],'cur');
            return true;
        }

        //=============================ТЕПЕРЬ ОБНОВЛЯЕМ STATUS and isO для монет которые получили обновление
    }
    public function syncHistory(){
        $SERVER = $this->_SERVER->repeat_collect('getorderhistory');
        $this->preobrazovat_orders($SERVER);        //преобразовали ОТВЕТ
        if($SERVER===false){
            $this->HISTORY = false;
            return false;
        }
        $HISTORY_AND_OPEN = [];
        $select_uid = [];       //данные которые синхронизуем с БД - тут UID's
        if(!empty($SERVER)){
            $end_time_history = time()-TIME_VYBORKI_HISTORY;
            for ($i=0,$c = count($SERVER);$i<$c;$i++){
                $e = &$SERVER[$i];       //один элемент истории
                if($e['close']<$end_time_history) continue; //Если ордер более 7дней - пропускаем
                $select_uid[] = $e['uid'];     //строка для выборки из бд
                $HISTORY_AND_OPEN[] = $e;               //массив с данными которые > 7 дней
            }
        }
        #ДОБАВЛЯЕМ К ЭТОЙ ИСТОРИИ ОТКРЫТЕ ОРДЕРА если они есть
        if(!empty($this->OPEN_ORDERS['uid'])){
            foreach ($this->OPEN_ORDERS['uid'] as $UID=>$LINK){
                $LINK = explode(' / ',$LINK);
                $HISTORY_AND_OPEN[] = $this->OPEN_ORDERS['orders'][$LINK[0]]['list'][$LINK[1]];
            }
        }

        #ПОЛУЧИЛИ ТЕКУЩИЕ ЗАКРЫТЫЕ ОРДЕРА ИЗ БД по их uid
        $DB_HISTORY = $this->getHistoryDB('*',$select_uid);         //показать все позиции из DB
        if(isset($DB_HISTORY['error']) && $DB_HISTORY['error']='not table') {    //если нету такой таб
            $this->createHistoryTable();
            $DB_HISTORY = [];         //показать все позиции из DB
        }else{
            $DB_HISTORY = $DB_HISTORY['data'];
        }

        #ПЕРЕБИРАЕМ ВСЕ ВОЗМОЖНЫЕ СЛУЧАИ
        $INSERT = [];
        $UPDATE = [];
        if(empty($DB_HISTORY) && !empty($HISTORY_AND_OPEN)){    //ДБ пусто - СЕРВЕР есть = ВСТАВЛЯЕМ
            $INSERT = &$HISTORY_AND_OPEN;
        }else if(!empty($DB_HISTORY) && empty($HISTORY_AND_OPEN)){  //ДБ есть- СЕРВЕР пусто = УДАЛЯЕМ
            $DELETE = &$DB_HISTORY;
        }else{                                                  //ДБ есть - СЕРВЕР есть = АНАЛИЗ
            for($i=0;$i<count($HISTORY_AND_OPEN);$i++){
                $SERV = &$HISTORY_AND_OPEN[$i];          //одно значение от сервера
                $ID = false;
                #ОПРЕДЕЛЯЕМ ОБЩИЙ ID если он ЕСТЬ!
                foreach ($DB_HISTORY as $KEY=>$DB){     //KEY=[1]..[10]  $DB-однозначение из БД
                    if($SERV['uid']==$DB['uid']){
                        $ID = $KEY;
                        break;
                    }
                }
                #ЕСЛИ ОРДЕРА ВЕЗДЕ ПРИСУТСТВУЮТ
                if($ID!==false){
                    #ЕСЛИ ОН ЗАКРЫЛСЯ - проверяем данные для ОБНОВЛЕНИЯ - если не равны - ОБНОВЛЯЕМ
                    if(!empty($SERV['close']) && ($SERV['kol_vo']!=$DB_HISTORY[$ID]['kol_vo'] || $DB_HISTORY[$ID]['isA'])){
                        $SERV['id'] = $DB_HISTORY[$ID]['id'];
                        $UPDATE[] = $SERV;
                    }
                    unset($DB_HISTORY[$ID]);
                }else{      //в локалке нету этого UID = ВСТАВЛЯЕМ
                    $INSERT[] = $SERV;
                }
            }
        }

//        dd('$INSERT',1);
//        dd($INSERT,1);
//        dd('$UPDATE',1);
//        dd($UPDATE,1);
//        dd('$DELETE',1);
//        dd($DB_HISTORY);

        #ВСТАВЛЯЕМ НОВЫЕ ДАННЫЕ
        if(!empty($INSERT)){
            $_insert = [];
            foreach ($INSERT as $KEY=>$SERV){
                if(empty($SERV['close'])){
                    $isA = '1';
                    $SERV['close'] = NULL;
                }else{
                    $isA = '0';
                }
                $_insert[] = [
                    ':coin' => $SERV['coin'],
                    ':status' => $SERV['status'],
                    ':isA' => $isA,
                    ':uid' => $SERV['uid'],
                    ':open' => $SERV['open'],
                    ':close' => $SERV['close'],
                    ':sreda' => $this->_sreda,
                    ':account' => $this->_account,
                    ':bid_ask' => $SERV['bid_ask'],
                    ':btc' => $SERV['btc'],
                    ':kol_vo' => $SERV['kol_vo'],
                ];
                Brocker::$_hash_data[$this->_hash]['FOR_MARKETS'][$SERV['coin']] = 1;          //добавли чтобы обновить монету
            }
            $sql = "INSERT INTO `".TABLE_NAME_HISTORY."` (`coin`,`status`,`isA`,`uid`,`open`,`close`,`sreda`,`account`,`bid_ask`,`btc`,`kol_vo`) VALUES :key";
            $num = [
                ':key' => $_insert
            ];
            DB::insert($sql,$num);
        }
        #ОБНОВЛЯЕМ ДАННЫЕ
        if(!empty($UPDATE)){
            $upd = [];
            //каждую запись отдельно
            foreach ($UPDATE as $KEY=>$SERV){
                if(empty($SERV['close'])){
                    $isA = '1';
                    $SERV['close'] = 'NULL';
                }else{
                    $isA = '0';
                }
                $upd = [
                    ':isA'  => $isA,
                    ':close'  => $SERV['close'],
                    ':btc'  => $SERV['btc'],
                    ':kol_vo'  => $SERV['kol_vo'],
                ];
//                $upd = str_replace('\'NULL\'','NULL',$upd);
//                $upd = substr($upd,0,strlen($upd)-1);
                $sql = "UPDATE ".TABLE_NAME_HISTORY." SET :upd WHERE `id`=:id";
                $num = [
                    ':id' => $SERV['id'],
                    ':upd'=> $upd
                ];
                DB::update($sql,$num);
                Brocker::$_hash_data[$this->_hash]['FOR_MARKETS'][$SERV['coin']] = 1;          //добавли чтобы обновить монету
            }
        }
        #УДАЛЯЕМ ДАННЫЕ
        if(!empty($DB_HISTORY)){
            $del_id = [];
            foreach ($DB_HISTORY as $KEY=>$DB){
                $del_id[] = $DB['id'];
                Brocker::$_hash_data[$this->_hash]['FOR_MARKETS'][$DB['coin']] = 1;          //добавли чтобы обновить монету
            }
            $sql = "DELETE FROM `".TABLE_NAME_HISTORY."` WHERE `id` in (:del)";
            $num = [
                ':del' => $del_id
            ];
            DB::query($sql,$num);
        }

        $this->SYNC['HISTORY'] = 1;      //мол синхронизировали
        return true;
    }
    //================== END-SYNC


    protected function getHistoryDB($sql='*',$uid=[]){       //получить все MARKETS из таблицы
        if(!empty($uid))    $sql = "SELECT {$sql} FROM `".TABLE_NAME_HISTORY."` WHERE `uid` IN (:key) OR isA=1";
        else                $sql = "SELECT {$sql} FROM `".TABLE_NAME_HISTORY."` WHERE isA=1";
        $num = [
            ':sreda' => $this->_sreda,
            ':key' => $uid
        ];
        return DB::selectAll($sql,$num);
    }
    protected function getWalletDB($sql='*',$active=true){       //получить все MARKETS из таблицы
        if($active) $sql = "SELECT {$sql} FROM `".TABLE_NAME_WALLET."` WHERE `isA`='1' AND `sreda`=:sreda AND `account`=:account";
        else        $sql = "SELECT {$sql} FROM `".TABLE_NAME_WALLET."` WHERE `sreda`=:sreda AND `account`=:account";
        $num = [
            ':sreda' => $this->_sreda,
            ':account' => $this->_account
        ];
        return DB::selectAll($sql,$num);
    }



    public function getBalance($coin1=''){
        if($this->WALLET===false){
            $DB = $this->getWalletDB(GET_DATA_WALLET);         //показать все позиции из DB
            if(isset($DB['error']) && $DB['error']='not table') {    //если нету такой таблицы
                $this->createTableWallet();
                $this->syncWallet();                      //синхронизировали данные
            }else{
                $this->WALLET = Brocker::addKeyName($DB['data'],'cur');
            }
        }
        if(strpos($coin1,'-')!==false){
            $coin1 = explode('-',$coin1);
            $coin1 = $coin1[1];
        }
        if($coin1){
            return (isset($this->WALLET[$coin1]))?$this->WALLET[$coin1]:['cur'=>$coin1,'balance'=>0,'dostupno'=>0,'address'=>''];
        }else{
            return $this->WALLET;
        }
    }
    public function getOrder($coin=''){
        if($coin){
            return (isset($this->OPEN_ORDERS['orders'][$coin]))?$this->OPEN_ORDERS['orders'][$coin]:false;
        }else{
            return $this->OPEN_ORDERS;
        }
    }


    /**
     * Получить STATUS + isTop для монеты
     * @coin = BTC-1ST
     * @min = 47.8456151  минимальная сумма транзакции
     * @return [stutas,isOpen] = [BUY,1]
     *
     * @DESC
     * 1)ПОЛУЧИЛИ БАЛАНС КОШЕЛЬКА 2 монеты
     * 2)РАВЕН ЛИ БАЛАНС = 0? [$isO=0]
     * 3)РАВЕН ЛИ БАЛАНС = ДОСТУПНО? [$isO=0]
     * 4)НЕ-РАВЕН ЛИ БАЛАНС != ДОСТУПНО? [$isO=1]
     */
    public function getStatusCoin($coin,$min,$debug=''){      //минималка ОБЯЗАТЕЛЬНА!!!
        #ПОЛУЧИЛИ БАЛАНС
        $balance = $this->getBalance($coin);
//        if($debug) dd($this->OPEN_ORDERS);


        if(isset($this->OPEN_ORDERS['orders'][$coin])){
            $isO = 1;
        }else{
            $isO = 0;
        }
        if($balance['balance']==0){
            if(!isset($isO)) $isO = 0;
            return ['BUY',$isO];
        }else{
            #ПРОВЕРЯЕМ РАВЕН ЛИ БАЛАНС = ДОСТУПНО?
            if($balance['balance']==$balance['dostupno']){      //если равны - то ордера нету
                #ЕСЛИ ЕСТЬ ОТКРЫТЫЕ ОРДЕРА
                if(!isset($isO)) $isO = 0;
                if($balance['balance']>$min){
                    return ['SELL',$isO];
                }else{
                    return ['BUY',$isO];
                }
            }else{
                if($balance['balance']>$min){           //если общий баланс меньше минималки - никак не может быть ПРОДАЖИ!
                    return ['SELL',$isO];
                }else{
                    return ['BUY',$isO];
                }
            }
        }
    }
    /**
     * Создать пустую таблицу в БВ
     * @return int|string
     */
    protected function createTableWallet(){          //создать таблицу если ее нету
        $sql = "CREATE TABLE IF NOT EXISTS `".TABLE_NAME_WALLET."` (
                   id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                   cur varchar(10) NOT NULL,
                   balance DECIMAL(".TO4NOST.") NOT NULL,
                   dostupno DECIMAL(".TO4NOST.") NOT NULL,
                   sreda varchar(50) NOT NULL,
                   account varchar(50) NOT NULL,
                   address varchar(60) default NULL,
                   isA tinyint(1) default 1,
                   md5 varchar(32) default NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                   ALTER TABLE `".TABLE_NAME_WALLET."` ADD INDEX(`wallet`);";
        return DB::query($sql);   //создали
    }

    protected function createHistoryTable(){          //создать таблицу если ее нету
        $sql = "CREATE TABLE IF NOT EXISTS `".TABLE_NAME_HISTORY."` (
                   id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                   coin varchar(20) NOT NULL,
                   status varchar(20) NOT NULL,
                   isA tinyint(1) NOT NULL,
                   uid varchar(50) NOT NULL,
                   dates datetime default CURRENT_TIMESTAMP,
                   open INT(11) default NULL,
                   close INT(11) default NULL,
                   sreda varchar(50) NOT NULL,
                   account varchar(50) NOT NULL,
                   bid_ask DECIMAL(".TO4NOST.") default NULL,
                   btc DECIMAL(".TO4NOST.") NOT NULL,
                   kol_vo DECIMAL(".TO4NOST.") NOT NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                   ALTER TABLE `".TABLE_NAME_HISTORY."` ADD INDEX(`uid`);";
        return DB::query($sql);   //создали
    }



    /**
     * получить актуальные зачения BID-ASK-LAST монеты
     * @param $coin - BTC-1ST
     * @param string $type ask,bid,last
     * @return bool
     */
    public function getTicker($coin){
        #ПОЛУЧАЕМ ЦЕНЫ
        $SERVER = $this->_SERVER->repeat_collect('getticker',['market'=>$coin]);
        $this->preobrazovat_ticket($SERVER);        //преобразовали ОТВЕТ
        if($SERVER===false){
            return false;
        }
        return $SERVER;
    }

}