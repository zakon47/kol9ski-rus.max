<?php
/*
 * Класс для работы с кошельком
 */


class Wallet_parent extends Collect{
    protected $DB;
    protected $CONFIG;
    protected $TABLE_NAME = TABLE_NAME_WALLET;
    //=============
    public $OPEN_ORDERS = false;      //список всех открытых ордеров  [false иЛи array()]
    public $MERKET = false;
    public $FOR_MARKETS = [];
    protected $KEY = [];
    function __construct($cron=''){
        parent::__construct();          //активируем нужный кошелек
        global $DB;
        $this->DB = &$DB;
        global $CONFIG;
        $this->CONFIG = &$CONFIG;

        #ЕСЛИ НЕТУ ИНФЫ ВО ВНУТРЕНЕМ СТЕКЕ - БЕРЕМ ИНФУ ИЗ БД (не сервер!)
        if(!isset($GLOBALS['MARKETS']) || !$this->MERKET){
            $GLOBALS['MARKETS'] = new Markets();
            $GLOBALS['MARKETS']->init();
        }
        $this->MERKET = &$GLOBALS['MARKETS'];
    }

    /**
     * Инициализировать условия для CRON обработчика
     */
    public function init($sync=1){
        if($sync){
            $this->syncOrders();        //инициализировали переменную с ОТКРЫТЫМИ ОРДЕРАМИ - открытые ордера
            $this->syncDB();                //синхронизировали DB и SERVER  - баланс на кошельках
        }
        $DB = $this->getDB(GET_DATA_WALLET);     //загрузили таблицу
        #ОТРАБАТЫВАЕМ ЗАЩИТУ - ЕСЛИ ТАБЛИЦЫ НЕТУ ИЛИ ОНА ПУСТАЯ
        if(isset($DB['error']) || empty($DB)){
            if(empty($DB)) {       //таблица пуста
                $this->syncDB();                      //синхронизировали данные
                $DB = $this->getDB(GET_DATA_WALLET);     //загрузили таблицу
            }else if($DB['error']=='not table'){       //если таблица не существует -> создаем ее
                $this->createTable();                 //создали таблицу
                $this->syncDB();                      //синхронизировали данные
                $DB = $this->getDB(GET_DATA_WALLET);     //загрузили таблицу
            }      //если таблица пустая или ее нету
            #ПОВТОРНЫЙ ЗАПРОС - окончательный
            if(isset($DB['error']))  new myError('фатальная ошибка::Wallet',['db'=>$DB]);   //отсутствует таблица
            if(empty($DB))           new myError('получили пустые данные с таблицы::Wallet',['db'=>$DB]);      //отсутствуют данные
        }  //если ОШИБКА при получение данных

        #ЗАПИСАЛИ ИНФУ ИЗ BD в локалку
        $this->KEY = $this->addKeyName($DB,'cur');
        unset($DB);
    }

    //======================ПОСРЕДСТВЕННЫЕ========================

    /**
     * Создать пустую таблицу в БВ
     * @return int|string
     */
    public function createTable(){          //создать таблицу если ее нету
        $sql = "CREATE TABLE IF NOT EXISTS `$this->TABLE_NAME` (
                   id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                   cur varchar(10) NOT NULL,
                   balance DECIMAL(".TO4NOST.") NOT NULL,
                   dostupno DECIMAL(".TO4NOST.") NOT NULL,
                   wallet varchar(50) NOT NULL,
                   address varchar(60) default NULL,
                   isA tinyint(1) default 1,
                   md5 varchar(32) default NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                   ALTER TABLE `{$this->TABLE_NAME}` ADD INDEX(`wallet`);";
        return $this->DB->exec($sql);   //создали
    }
    /**
     * Создать пустую таблицу в ДБ для ORDERS
     * @return int|string
     */
    public function createOrdersTable(){          //создать таблицу если ее нету
        $sql = "CREATE TABLE IF NOT EXISTS `".TABLE_NAME_ORDERS."` (
                   id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                   coin varchar(20) NOT NULL,
                   status varchar(20) NOT NULL,
                   isA tinyint(1) NOT NULL,
                   uid varchar(50) NOT NULL,
                   dates datetime default CURRENT_TIMESTAMP,
                   open INT(11) default NULL,
                   close INT(11) default NULL,
                   wallet varchar(50) NOT NULL,
                   limits DECIMAL(".TO4NOST.") default NULL,
                   price DECIMAL(".TO4NOST.") NOT NULL,
                   kol_vo DECIMAL(".TO4NOST.") NOT NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                   ALTER TABLE `".TABLE_NAME_ORDERS."` ADD INDEX(`uid`);";
        return $this->DB->exec($sql);   //создали
    }
    /**
     * Загрузить КОШЕЛЬКИ из БД (ВСЕ или ТОЛЬКО АКТИВНЫЕ монеты)
     * @param string $sql
     * @return bool|mixed
     */
    protected function getDB($sql='*',$active=true){
        if($active) $sql = "SELECT {$sql} FROM `$this->TABLE_NAME` WHERE `wallet`=:wallet AND `isA`='1'";
        else        $sql = "SELECT {$sql} FROM `$this->TABLE_NAME` WHERE `wallet`=:wallet";
        $num = array(':wallet'=>$this->api_name);
        $markets = $this->DB->select($sql,$num,1);
        return $markets;
    }
    /**
     * ПОЛУЧИТЬ ИНФУ ПО МОНЕТЕ    $this->getBalance('BTG')
     * @param string $market
     * @return array
     */
    public function getBalance($coin1=''){
        //Если вдруг передали не кошелек а монету!
        if(strpos($coin1,'-')!==false){
            $coin1 = explode('-',$coin1);
            $coin1 = $coin1[1];
        }
        if($coin1){
            return (isset($this->KEY[$coin1]))?$this->KEY[$coin1]:['cur'=>$coin1,'balance'=>0,'dostupno'=>0,'address'=>''];
        }else{
            return $this->KEY;
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
//        dd('БАЛАНС',1);
//        dd($balance,1);
//        dd($this->OPEN_ORDERS,1);

        if(isset($this->OPEN_ORDERS['orders'][$coin])) $isO = 1;

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

    public function syncHistoryOrders(){
        #ПОЛУЧАЕМ ИСТОРИЮ МОНЕТ ОТ СЕРВЕРА!
        $HISTORY = $this->repeat_collect('getorderhistory');
        $HISTORY_AND_OPEN = [];
        $select_uid = [];       //данные которые синхронизуем с БД
        if(empty($HISTORY) || !$HISTORY['success']) new myError('Не пришли данные закрытой истории!',['$HISTORY'=>$HISTORY]);
        if(!empty($HISTORY['result'])){
            $end_time_history = time()-TIME_VYBORKI_HISTORY;
            $HISTORY = $this->preobrazovat_order($HISTORY['result']);
            for ($i=0,$c = count($HISTORY);$i<$c;$i++){
                $e = &$HISTORY[$i];       //один элемент истории
                if($e['close']<$end_time_history) continue; //Если ордер более 7дней - пропускаем
                $select_uid[] = "'".$e['uid']."'";     //строка для выборки из бд
                $HISTORY_AND_OPEN[] = $e;               //массив с данными которые > 7 дней
            }
            $select_uid = implode(',',$select_uid);
        }
        #ДОБАВЛЯЕМ К ЭТОЙ ИСТОРИИ ОТКРЫТЕ ОРДЕРА если они есть
        if(!empty($this->OPEN_ORDERS['uid'])){
            foreach ($this->OPEN_ORDERS['uid'] as $UID=>$LINK){
                $LINK = explode(' / ',$LINK);
                $HISTORY_AND_OPEN[] = $this->OPEN_ORDERS['orders'][$LINK[0]]['list'][$LINK[1]];
            }
        }
//        dd($HISTORY_AND_OPEN);
        #ЕСЛИ ЕСТЬ ДАННЫЕ КОТОРЫЕ НАДО ПРОВЕРИТЬ В БД - получаем эти данные из БД
        $DB_HISTORY = [];
        if(!empty($select_uid)){
            $sql = "SELECT * FROM `".TABLE_NAME_ORDERS."` WHERE `uid` IN ($select_uid) OR isA=1";
        }else{
            $sql = "SELECT * FROM `".TABLE_NAME_ORDERS."` WHERE isA=1";
        }
        $DB_HISTORY = $this->DB->select($sql,array(),1);
        if(isset($DB_HISTORY['error'])){
            $x = $this->createOrdersTable();
            $DB_HISTORY = [];
        }         //если таблица ещё не создана
        if(!$DB_HISTORY) $DB_HISTORY = [];

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
            $_insert = '';
            foreach ($INSERT as $KEY=>$SERV){
                if(empty($SERV['close'])){
                    $isA = '1';
                    $SERV['close'] = 'NULL';
                }else{
                    $isA = '0';
                }
                $_insert .= ",('"
                    .$SERV['coin']."','"
                    .$SERV['status']."','"
                    .$isA."','"               //dostupno
                    .$SERV['uid']."','"
                    .$SERV['open']."','"
                    .$SERV['close']."','"
                    .$this->api_name."','"
                    .$SERV['limits']."','"
                    .$SERV['price']."','"
                    .$SERV['kol_vo']."')";
                $this->FOR_MARKETS[$SERV['coin']] = 1;          //добавли чтобы обновить монету
            }
            $_insert = str_replace('\'NULL\'','NULL',$_insert);
            $_insert = substr($_insert,1);
            $sql = "INSERT INTO `".TABLE_NAME_ORDERS."` (`coin`,`status`,`isA`,`uid`,`open`,`close`,`wallet`,`limits`,`price`,`kol_vo`) VALUES $_insert"; $num = array();
            $x = $this->DB->insert($sql,$num);
//            if($x) dd('Что-то вставили в БД: '.$x,1);
        }
        #ОБНОВЛЯЕМ ДАННЫЕ
        if(!empty($UPDATE)){
            $upd = '';
            //каждую запись отдельно
            foreach ($UPDATE as $KEY=>$SERV){
                if(empty($SERV['close'])){
                    $isA = '1';
                    $SERV['close'] = 'NULL';
                }else{
                    $isA = '0';
                }
                $num = array(':id'=>$SERV['id']);
                $upd .= "`isA`=:isA,";
                $upd .= "`close`=:close,";
                $upd .= "`price`=:price,";
                $upd .= "`kol_vo`=:kol_vo,";
                $num[':isA'] = $isA;       //завершено
                $num[':close'] = $SERV['close'];       //завершено
                $num[':price'] = $SERV['price'];       //завершено
                $num[':kol_vo'] = $SERV['kol_vo'];       //завершено

                $upd = str_replace('\'NULL\'','NULL',$upd);
                $upd = substr($upd,0,strlen($upd)-1);
                $sql = "UPDATE ".TABLE_NAME_ORDERS." SET $upd WHERE `id`=:id";
                $x = $this->DB->update($sql,$num);
//                if($x) dd('ОБНОВИЛИ '.$SERV['uid'],1);
                $this->FOR_MARKETS[$SERV['coin']] = 1;          //добавли чтобы обновить монету
            }
        }
        #УДАЛЯЕМ ДАННЫЕ
        if(!empty($DB_HISTORY)){
            $del_id = [];
            foreach ($DB_HISTORY as $KEY=>$DB){
                $del_id[] = $DB['id'];
                $this->FOR_MARKETS[$DB['coin']] = 1;          //добавли чтобы обновить монету
            }
            $del_id = implode(',',$del_id);
            $sql = "DELETE FROM `".TABLE_NAME_ORDERS."` WHERE `id` in ({$del_id})";
            $update = $this->DB->delete($sql);
//            if($update) dd('Удалили что-то из бд',1);
        }
    }


    /**
     * Сохранили $status = ['BUY',1] в определеную ID МОНЕТЫ
     * @param $coin
     * @param $status
     */
    public function saveStatus($id,$status){
        if(!is_array($status) || count($status)!=2) return false;
        $upd = '';
        $num = array(':id'=>$id);
        $upd .= "`status`=:status,";
        $upd .= "`isOpen`=:isOpen,";
        $num[':status'] = $status[0];
        $num[':isOpen'] = $status[1];

        $upd = substr($upd,0,strlen($upd)-1);
        $sql = "UPDATE ".TABLE_NAME_MARKETS." SET $upd WHERE `id`=:id";
        $x = $this->DB->update($sql,$num);
        return $x;
    }
    /**
     * ПРОВЕРИТЬ SELL МАРКЕРЫ У КОТОРЫХ НЕТУ ОРДЕРА и ДОБАВЛЯЕМ ЕГО
     *
     * @DESC
     * #ПЕРЕБИРАЕМ ВСЕ МОНЕТЫ ИЗ ДБ И ВЫБИРАЕМ ТОЛЬКО СО СТАТУСОМ "SELL" и isA=1 (актуальные монеты)
     *      #ПРОВЕРЯЕМ ЧТОБЫ ЕЕ BALANCE > MIN - мол есть что продавать
     *      #ПЕРЕБИРАЕМ КАЖДУЮ МОНЕТУ - ПОЛУЧАЕМ ЕЕ STOP_LOSS
     *      #ПРОВЕРЯЕМ ОТКРЫТЫЕ ОРДЕРА isA=1 И ЕСЛИ ТАМ НЕТУ ОРДЕРА ПОД ЭТУ МОНЕТУ И ЭТОТ STOP_LOSS - ставим ВСЕ НА ПРОДАЖУ!
     */
    public function addSellOrder(){
        #ПЕРЕБИРАЕМ ВСЕ МОНЕТЫ ИЗ ДБ И ВЫБИРАЕМ ТОЛЬКО СО СТАТУСОМ "SELL" и записываем в $sell_market
        $sell_market = [];
        $markets = $this->MERKET->get('list');
        if(!empty($markets)){
            for ($i=0,$c=count($markets);$i<$c;$i++){
                if($markets[$i]['status']=='SELL' AND $markets[$i]['isA']){
                    #ПРОВЕРЯЕМ СТАТУС ЭТОЙ МОНЕТЫ
                    $status = $this->getStatusCoin($markets[$i]['market'],$markets[$i]['min']);
                    #ЕСЛИ СТАТУС НЕ ПРАВИЛЬНЫЙ - ТО ПЕРЕЗАПИСЫВАЕМ ЕГО
                    if($status[0]=='BUY'){
                        $this->saveStatus($markets[$i]['id'],$status);
                    }else{
                        $sell_market[] = $markets[$i];
                    }
                }
            }
        }
    }



    //============================
    /**
 * получить актуальные зачения BID-ASK-LAST монеты
 * @param $coin - BTC-1ST
 * @param string $type ask,bid,last
 * @return bool
 */
    public function getTicker($coin){
        #ПОЛУЧАЕМ ЦЕНЫ
        $res = $this->repeat_collect('getticker',['market'=>$coin]);
        if(!$res['success']) return false;      //если не смогли получить данные - отмена
        #ФОРМАТИРУЕМ ПОД УСРЕДНЁННЫЙ ТИП
        global $getTicker;
        $result = [];
        foreach ($getTicker as $K=>$V) {
            if(isset($res['result'][$V])){
                $result[$K] = $res['result'][$V];
            }
        }
        return $result;
    }
    /**
     * проверка - есть ли такой баланс на счету
     * @param $coin - BTC
     * @param $otdat - 0.16548154
     * @return bool
     */
    private function exist($coin,$otdat,$komissia=0){
        //Если вдруг передали не кошелек а монету!
        if(strpos($coin,'-')!==false){
            $coin = explode('-',$coin);
            $coin = $coin[1];
        }
        if($komissia){
            $komissia = $this->CONFIG['sreda'][SREDA]['komissia'][$coin];
            $otdat += $komissia;
        }
        #НАДО УЧИТЫВАТЬ КОМИССИЮ!
        $res = $this->getBalance($coin);
        if(isset($res['dostupno']) && $res['dostupno']>=$otdat){
            return true;
        }else{
            return false;
        }
    }
    /**
     * покупаем монету
     * @param $coin - BTC-1ST
     * @param $otdat - 0.1684894
     * @param $price - по какой цене покупаем - если пусто берется с сервера
     * @param $delta - надо ли уменьшать цену покупки на дельту?
     *
     * @DESC
     * $ticket = $WALLET->buy($coin, 0.01);            //первая покупка
     * $ticket = $WALLET->buy($coin, 0.01,0.000001,0); //update покупка
     */
    public function buy($coin,$otdat,$price='',$delta=1){
        if(!$this->CONFIG['sreda'][SREDA]['action']['buy']) return ['msg'=>'Стоит запрет на покупку','return'=>0];
        $coin1 = explode('-',$coin);         // [0]=>BTC, [1]=>1ST
        #ПРОВЕРЯЕМ ЕСТЬ ЛИ НА БАЛАНСЕ ТРЕБУЕМАЯ СУММА
        if($this->exist($coin1[0],$otdat,1)){
            $ticket = $this->getTicker($coin);
            $price = (empty($price))?$ticket['BUY']:$price*1;    //получаем цену по которой будем покупать - [для BTC]
            #ПОНИЖАЕМ ЦЕНУ НА ДАЛЬТУ если требуется
            if($delta) $price = ($price*(100-DELTA_BUY_SELL))/100;
            $count = $otdat/$price;                              //получаем кол-во монет, которое сейчас купим
            $res = $this->repeat_collect('buylimit',['market'=>$coin,'quantity'=>$count,'rate'=>$price]);      //покупаем
            if($res['success']){
                #ДОБАВЛЯЕМ В БД ОРДЕР
                $close = 'NULL';
                $time = time();
                $_insert = ",('"
                    .$coin."','"
                    ."BUY','"
                    ."1','"               //dostupno
                    .$res['result']['uuid']."','"
                    .$time."','"
                    .$close."','"
                    .$this->api_name."','"
                    .number_format($price,8,'.','')."','"
                    .number_format($otdat,8,'.','')."','"
                    .number_format($count,8,'.','')."')";
                $_insert = str_replace('\'NULL\'','NULL',$_insert);
                $_insert = substr($_insert,1);
                $sql = "INSERT INTO `".TABLE_NAME_ORDERS."` (`coin`,`status`,`isA`,`uid`,`open`,`close`,`wallet`,`limits`,`price`,`kol_vo`) VALUES $_insert"; $num = array();
                $this->DB->insert($sql,$num);

                return ['msg'=>'Успех!','return'=>1];                                            //если всё удачно - то TRUE
            } else {
                new myError('Ошибка при покупке монеты?!',['$coin'=>$coin,'$price'=>$price,'$count'=>$count,'$res'=>$res]);
            }
        }else{
            return ['msg'=>'Отстутствует запрашиваемая сумма','return'=>0];
        }
    }
    /**
     * продаем монету
     * @param $coin - BTC-1ST
     * @param $otdat - 35.44
     * @param $price - price -> ask,bid,last
     * @param string $type - ask,bid,last
     *
     * @DESC
     * $ticket = $WALLET->sell($coin);                     //продали все что есть
     * $ticket = $WALLET->sell($coin,100);                 //продали только 100 ед. по актуальной цене
     * $ticket = $WALLET->sell($coin,100,0.00005,0);       //продали только 100 ед. за определенную цену без ДЕЛЬТЫ
     */
    public function sell($coin,$otdat='',$price='',$delta=1){
        if(!$this->CONFIG['sreda'][SREDA]['action']['sell']) return ['msg'=>'Стоит запрет на продажу','return'=>0];
        $coin1 = explode('-',$coin);         // [0]=>BTC, [1]=>1ST
        if(empty($otdat)){
            $otdat = $this->getBalance($coin)['dostupno'];        //если продаем все что есть
        }else{
            if(!$this->exist($coin1[1],$otdat)) return ['msg'=>'Отстутствует запрашиваемая сумма','return'=>0];     //если нету этого кол-ва запрашиваемой монеты
        }
        #ПРОВЕРЯЕМ НА МИНИМАЛКУ транзакции
        $b = $this->MERKET->get($coin)['min'];
        if($otdat<$this->MERKET->get($coin)['min']) return ['msg'=>'Кол-во монет меньше минималки','return'=>0];         //если кол-во монет меньше минимальной транзакции - отмена

        #ПОЛУЧАЕМ ЦЕНУ
        $ticket = $this->getTicker($coin);
        $price = (empty($price))?$ticket['BUY']:$price*1;    //получаем цену по которой будем покупать - [для BTC]

        #ПОВЫШАЕМ ЦЕНУ НА ДАЛЬТУ если требуется
        if($delta) $price = ($price*(100+DELTA_BUY_SELL))/100;

        $res = $this->repeat_collect('selllimit',['market'=>$coin,'quantity'=>$otdat,'rate'=>$price]);      //продаем
        if($res['success']){
            return ['msg'=>'Успех!','return'=>1];                                            //если всё удачно - то TRUE
        } else {
            new myError('Ошибка при продаже монеты?!',['$coin'=>$coin,'$price'=>$price,'$otdat'=>$otdat,'$res'=>$res]);
        }
    }


    /**
     * ПОЛУЧИТЬ ИНФУ ОТКРЫТОГО ОРДЕРА по его UID
     * @param $uid
     * @return array
     */
    public function getUID_ORDER($uid){
        if(!isset($this->OPEN_ORDERS['uid'][$uid])) return [];
        $link = explode(' / ',$this->OPEN_ORDERS['uid'][$uid]);
        return $this->OPEN_ORDERS['orders'][$link[0]]['list'][$link[1]];
    }
    public function getUID_DB($uid){
        $sql = "SELECT * FROM `".TABLE_NAME_ORDERS."` WHERE `uid`=:uid";
        $num = array(':uid'=>$uid);
        $markets = $this->DB->select($sql,$num);
        return $markets;
    }

    /**
     * ОБНОВИТЬ ОТКРЫТЫЙ ОРДЕР - закончить итерацию
     * @param $uid
     */
    public function updatePrice($uid){
        #ПОЛУЧИЛИ ОТКРЫТЫЙ ОРДЕР
        $order = $this->getUID_DB($uid);
        dd('$order',1);
        dd($order,1);
        if(empty($order)){
            $order = $this->getUID_ORDER($uid);       //получили открытый ордер по его UID
            dd('$order',1);
            dd($order,1);
            if(empty($order)){
                new myError('Запрошенные UID на обновления отсутствует в БД',['$uid'=>$uid,'$order'=>$order]);
                return ['msg'=>'Запрошенные UID на обновления отсутствует в БД','return'=>0];
            }
            $order['price'] = $order['kol_vo']*$order['limits'];//+KOMISSIA;
        }
        #ПОЛУЧИЛИ ИНФУ ПО МОНЕТЕ
        $coin = $order['coin'];

        #ОТМЕНИТЬ ОРДЕР
        $cancel = $this->repeat_collect('cancel',['uuid'=>$uid]);      //покупаем
        if(!$cancel['success']) return ['msg'=>'Не смогли отменить ордер',['$uid'=>$uid,'$order'=>$order,'$cancel'=>$cancel]];

        #УДАЛЯЕМ СТАРУЮ ЗАПИСЬ
        $sql = "DELETE FROM `".TABLE_NAME_ORDERS."` WHERE `uid`='{$uid}'";
        $update = $this->DB->delete($sql);
        dd($sql,1);
        dd($update,1);
        dd('========',1);


        #ЗАПУСТИТЬ ОРДЕР С ЭТИМИ ЖЕ ПАРАМЕТРАМИ
        if($order['status']=='SELL'){          //продажа по кол-ву
            $res = $this->sell($coin,$order['kol_vo']);                 //продали только 100 ед. по актуальной цене
        }else{                  //покупка по цене
            $res = $this->buy($coin, $order['price']);            //первая покупка
        }
        if($res['return']){
            return ['msg'=>'Обновили!','return'=>1];                                            //если всё удачно - то TRUE
        } else {
            new myError('Ошибка при обновление монеты?!',['$coin'=>$coin,'$res'=>$res]);
        }
    }
}