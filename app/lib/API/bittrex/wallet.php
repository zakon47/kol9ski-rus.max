<?php defined('_3AKOH') or die(header('/'));

include API.'wallet_class.php';
class Wallet extends Wallet_parent{
    /**
     * Синхронизировать СЕРВЕР с БД
     * @DESC
     * 1)ПОЛУЧАЕМ ДАННЫЕ С СЕРВЕРА (Кол-во денег в кошельках)
     * 2)ПОДГРУЗИЛИ ТЕКУЩИЕ ДАННЫЕ ИЗ БД ($DB)
     * 3)ОТСОРТИРОВАЛИ ДАННЫЕ ИЗ БД ПО КЛЮЧУ ($DB_KEY)
     * 4)ТЕПЕРЬ РАСПРЕДЕЛЯЕМ КАЖДУЮ МОНЕТУ  —  на UPDATE or INSERT
     * 5)ОБРАБОТЧИКИ МАССИВОВ
     *      — Если удалил какую-то монету с СЕРВЕРА {не пустой $DB_KEY} - то делаем неактивным кошелек
     *      — Если появились новые монеты в строке {$insert} - то вставляем новые монеты
     *      — Если какие-то кошельки монет изменились {$update} - то обрабатываем их
     */
    protected function syncDB(){
        #ПОЛУЧАЕМ ДАННЫЕ С СЕРВЕРА (Кол-во денег в кошельках)
        $SERVER= $this->repeat_collect('getbalances');      //данные ссервера
        if(!empty($SERVER) && $SERVER['success']){
            $insert = '';
            $update = [];       //монеты которые изменились
            $insert2 = [];      //новые кошельки которые надо записать в БД
            #ПОДГРУЗИЛИ ТЕКУЩИЕ ДАННЫЕ ИЗ БД
            $DB = $this->getDB('id,cur,balance,dostupno,wallet,address,isA,md5',false);         //показать все позиции из DB
            if(isset($DB['error'])){
                $this->createTable();
                $DB = [];
            }         //если таблица ещё не создана
            if(empty($DB)){
                $DB = [];
            }
            #ОТСОРТИРОВАЛИ ДАННЫЕ ИЗ БД ПО КЛЮЧУ
            $DB_KEY = $this->addKeyName($DB,'cur');     //отсортировали данные по ключу из БД

            #ПЕРЕБИРАЕМ КАЖДНЫЕ ДАННЫЕ ОТ СЕРВЕРА И ФОРМИРУЕМ $update and $insert2
            for($i=0;$i<count($SERVER['result']);$i++){    //перебираем новые SERVER маркеры
                $elem = &$SERVER['result'][$i];        //элемент
                $md5 = md5($elem['Balance'].$elem['Available'].$elem['CryptoAddress']);     //md5 from SERVER
//                if($elem['Currency'] =='SNT'){
//                    dd($elem,1);
//                    dd($DB_KEY[$elem['Currency']],1);
//                    dd('============================',1);
//                }

                #ТЕПЕРЬ РАСПРЕДЕЛЯЕМ КАЖДУЮ МОНЕТУ  —  на UPDATE or INSERT
                $key = 1;   //разрешение на вставку новой записи в таблицу
                if(isset($DB_KEY[$elem['Currency']])){              //ЕСЛИ ЕСТЬ ЭТА МОНЕТА В DB
                    if($md5!=$DB_KEY[$elem['Currency']]['md5'] || !$DB_KEY[$elem['Currency']]['isA']){        // если кеш поменялся
                        $x = [
                            'id'            => $DB_KEY[$elem['Currency']]['id'],        //id в DB
                            'elem_id'       => $i,                                      //id в SERVER
                            'cur'           => $elem['Currency'],
                            'balance'       => $elem['Balance'],
                            'dostupno'      => $elem['Available'],
                            'address'       => $elem['CryptoAddress'],
                            'isA'           => 1,
                            'md5'           => $md5
                        ];
                        $update[] = $x;
                    }
                    unset($DB_KEY[$elem['Currency']]);
                    $key = 0;
                }
                #ПОДГОТОВКА ДЛЯ — ВСТАВКА НОВОЙ ЗАПИСИ
                if($key){   //не был найден в локальной таблице - значит эту запись надо вставить - создаем цифры
                    $insert2[] = $elem['Currency'];
                    $insert .= ",('"
                        .$elem['Currency']."','"
                        .$elem['Balance']."','"
                        .$elem['Available']."','"               //dostupno
                        .$elem['CryptoAddress']."','"
                        .$this->api_name."','"
                        .md5($elem['Balance'].$elem['Available'].$elem['CryptoAddress'])."')";
                }
            }
//            dd('==========$DB_KEY',1);
//            dd($DB_KEY,1);
//            dd('==========insert',1);
//            dd($insert,1);
//            dd('==========update',1);
//            dd($update,1);
//            dd('==========insert2',1);
//            dd($insert2);
            #ОБРАБОТКА МОНЕТ КОТОРЫЕ УДАЛИЛИ НА СЕРВЕРЕ а в DB они остались
            if(!empty($DB_KEY)){
                #ВЫБИРАЕМ МОНЕТЫ КОТОРЫЕ ЕЩЁ НЕ ОТКЛЮЧИЛИ
                $list = [];
                foreach ($DB_KEY as $k=>$v){
                    if($v['isA']) $list[] = $v;
                }
                #ОТКЛЮЧАЕМ МОНЕТЫ КОТОРЫЕ ЕЩЁ НЕ ОТКЛЮЧИЛИ т.к ИХ НЕТУ НА СЕРВЕРЕ ВООБЩЕ
                if(!empty($list)){
                    for($i=0,$c=count($list);$i<$c;$i++){
                        $e = &$list[$i];    //1 элемент
                        $upd = '';      //проверям изменились ли значения - если изменили -> ОБНОВЛЯЕМ
                        $num = array(':id'=>$e['id']);

                        $upd .= "`isA`=:isA,";
                        $num[':isA'] = '0';

                        $upd = substr($upd,0,strlen($upd)-1);
                        $sql = "UPDATE ".$this->TABLE_NAME." SET $upd WHERE `id`=:id";
                        $this->DB->update($sql,$num);
                    }
                }
            }
            if(!empty($insert)){        //если есть что вставлять -> ВСТАВЛЯЕМ
                $insert = substr($insert,1);
                $sql = "INSERT INTO `$this->TABLE_NAME` (`cur`,`balance`,`dostupno`,`address`,`wallet`,`md5`) VALUES $insert"; $num = array();
                $x = $this->DB->insert($sql,$num);
                if(isset($x['error'])){
                    $this->createTable();
                    $x = $this->DB->insert($sql,$num);
                    if(isset($x['error'])) dd('Не вставил новый элемент MARKETS ['.$sql.'] - была ошибка::bittrex — '.$x['error']);
                }
            }
            if(!empty($update)) {        //если есть что обновить -> ОБНОВЛЯЕМ
                $DB_ID = $this->addKeyName($DB,'id');     //отсортировали данные по ID из БД
                #ПЕРЕБИРАЕМ КАЖДЫЙ ОБНОВЛЕННЫЙ КОШЕЛЕК и НАХОДИМ НАЗВАНИЕ МОНЕТЫ КОТОРОЕ ПОМЕНЯЛОСЬ!
                for($k=0,$c=count($update);$k<$c;$k++){         //перебираем каждый кошелек, который изменился
                    if(0){
                        $upd_data = &$update[$k];                   //новые данные КОШЕЛЬКА с сервера
                        $old_data = &$DB_ID[$update[$k]['id']];     //старые данные КОШЕЛЬКА с БД
                        $nashli = false;
                        #ЕСЛИ БАЛАНСЫ НЕ РАВНЫ - ЗНАЧИТ ОТКРЫТ ОРДЕР!
                        if($upd_data['balance']!=$upd_data['dostupno']){
                            $delta = $upd_data['balance']-$upd_data['dostupno'];
                            #ПРОВЕРЯЕМ ЭТО И УЗНАЕМ ИМЯ МОНЕТЫ
                            if(isset($this->OPEN_ORDERS['orders']) && !empty($this->OPEN_ORDERS['orders'])){
                                #ПЕРЕБИРАЕМ КАЖДЫЙ ОТКРЫТЫЙ ОРДЕР ПО МОНЕТАМ и ищем свою монету
                                foreach ($this->OPEN_ORDERS['orders'] as $C=>$B){         //$C - coinName, $B - тело и данные ордера
                                    #ДОЛЖНО СОБЛЮДАТЬСЯ 2 УСЛОВИЯ:
                                    #1)НАЗВАНИЕ МОНЕТЫ ДОЛЖНО СОДЕРЖАТЬ ИМЯ КОШЕЛЬКА
                                    if(strpos($C,$old_data['cur'])===false) continue;       //если не содержит - берем следующий ордер
                                    #2)СУММА ТРАНЗАКЦИЙ ДОЛЖНА РОВНЯТЬСЯ = БАЛАНСУ
                                    $sum = 0;
                                    for ($li=0;$li<$B['count'];$li++){
                                        $sum += $B['list'][$li]['kol_vo'];
                                    }
                                    #ТЕПЕРЬ ЕСЛИ СУММА ОТКРЫТЫХ ОРДЕРОВ РАВНА ДЕЛЬТЕ (БАЛАНСА И ДОСТУПНО) - то это 100% наша монета!
                                    if(ceil($delta)==ceil($sum)){
                                        $this->FOR_MARKETS[$C] = 1;
                                        $nashli = 1;
                                        break;
                                    }
                                    #СУММИРУЕМ СРЕДСТВА В ОРДЕРЕ
                                }
                            }
                        }
                        #ЕСЛИ НЕ ОПРЕДЕЛИЛИ К КАКОЙ ПАРЕ ОТНОСИТСЯ КОШЕЛЕК! - добавляем все ВОЗМОЖНЫЕ СВЯЗКИ!
                        if($nashli){
                            $cur = $old_data['cur'];    //имя кошелька к которому не подобрали ПАРУ!
                            $market_list = $this->MERKET->get('list');
                            if(empty($market_list)) new myError('Почему-то нету маркеров!?');
                            for ($m=0,$c=count($market_list);$m<$c;$m++){
                                if($cur==$market_list[$m]['cur']){
                                    if(!isset($this->FOR_MARKETS[$market_list[$m]['market']])) $this->FOR_MARKETS[$market_list[$m]['market']] = 0;
                                }
                            }
                        }
//                    dd('МОНЕТА ИЗМЕНИЛАСЬ?',1);
//                    dd($upd_data,1);
//                    dd($old_data,1);
//                    dd($this->FOR_MARKETS);
//                    dd($this->OPEN_ORDERS);
                    }

                    #ВСЛЕПУЮ ПЕРЕЗАПИСЫВАЕМ ДАННЫЕ НА НОВЫЕ
                    $upd = '';
                    $num = array(':id'=>$update[$k]['id']);
                    $upd .= "`balance`=:balance,";
                    $upd .= "`dostupno`=:dostupno,";
                    $upd .= "`address`=:address,";
                    $upd .= "`isA`=:isA,";
                    $upd .= "`md5`=:md5,";
                    $num[':balance'] = $update[$k]['balance'];
                    $num[':dostupno'] = $update[$k]['dostupno'];
                    $num[':address'] = $update[$k]['address'];
                    $num[':isA'] = $update[$k]['isA'];
                    $num[':md5'] = $update[$k]['md5'];

                    $upd = substr($upd,0,strlen($upd)-1);
                    $sql = "UPDATE ".$this->TABLE_NAME." SET $upd WHERE `id`=:id";
                    $this->DB->update($sql,$num);
                }
            }

            //ТУТ НАДО ОБНОВИТЬ ДАННЫЕ КОШЕЛЬКА НА НОВЫЕ! - потому что из него получаем БАЛАНС
            $this->KEY = $this->addKeyName($this->getDB(GET_DATA_WALLET),'cur');
//            dd('ПРОСТО НОВЫЙ БАЛАНС ИЗ БД',1);
//            $x = $this->getBalance('SNT');
//            dd($x,1);


            //ТУТ НАДО ПОЛНОСТЬЮ ЗАКРЫТЬ $this->FOR_MARKETS
//            $x = $this->FOR_MARKETS;
//            dd($x,1);

            #СИНХРОНИЗОВАЛИ ИСТОРИИ
            $this->syncHistoryOrders();     //синхронизировали ордера ИСТОРИИ

            #ТЕПЕРЬ ОБНОВЛЯЕМ STATUS and isO для монет которые получили обновление
//            dd($this->FOR_MARKETS,1);
            if(!empty($this->FOR_MARKETS)){
                foreach ($this->FOR_MARKETS as $coin=>$v){
                    $market = $this->MERKET->get('key')[$coin];
//                    dd('=================',1);
//                    dd($market,1);
                    $min = $market['min'];
                    $STATUS = $this->getStatusCoin($coin,$min);
//                    dd($STATUS,1);
                    #ЕСЛИ БЫЛИ ИЗМЕНЕНИЯ - то сохраняем их
                    if($STATUS[0]!=$market['status'] || $STATUS[1]!=$market['isOpen']){
                        $upd = '';
                        $num = array(':id'=>$market['id']);
                        $upd .= "`status`=:status,";
                        $upd .= "`isOpen`=:isOpen,";
                        $num[':status'] = $STATUS[0];
                        $num[':isOpen'] = $STATUS[1];

                        $upd = substr($upd,0,strlen($upd)-1);
                        $sql = "UPDATE ".TABLE_NAME_MARKETS." SET $upd WHERE `id`=:id";
                        $x = $this->DB->update($sql,$num);
//                        dd($x,1);
                    }
                }
                #ПОСЛЕ ЧЕГО ОБНОВЛЯЕМ ЗНАЧЕНИЯ В КЛАССЕ!
                $m = $this->MERKET->getDB(GET_DATA_MARKETS);     //загрузили таблицу
                #ЗАПИСАЛИ ИНФУ ИЗ BD в локалку
                $this->MERKET->_list = $m;
                $this->MERKET->_key = $this->addKeyName($m,'market');
                unset($m);
            }
        }else{
            new myError('ошибка - при получение данных с сервера new Wallet::syncDB()',['server'=>$SERVER]);
        }
    }

    /**
     * Добавляем ордес от СЕРВЕРА локально в БД
     * @param $order
     */
    protected function addOrders($order){
        dd($order);
    }
    /**
     * Получить список открытых ордеров || 1 ордер по NAME[BTC-1ST]
     * @return массив или список массивов
     *
     * @DESC
     * 1)ОТПРАВИТЬ ЗАПРОС БАЛАНСА
     * 2)ЕСЛИ ЧТО_ТО ВЕРНУЛОСЬ ФОРМАТИРУЕМ ДАННЫЕ ПОД СВОЙ ФОРМАТ
     *      — ФОРМАТИРУЕМ
     *      — ПОДЫМАЕМ ОБЩИЕ ДАННЫЕ НА УРОВЕНЬ ВЫШЕ и "СКЛАДЫВАЕМ"
     * 3)ТЕПЕРЬ СИНХРОНИЗИРУЕМ ДАННЫЕ С БД
     *      — ПОДГРУЗИЛИ ТЕКУЩИЕ ДАННЫЕ ИЗ БД
     *      — ОТСОРТИРОВАЛИ ДАННЫЕ ИЗ БД ПО КЛЮЧУ uid
     *      — ПЕРЕБИРАЕМ КАЖДНЫЕ ДАННЫЕ ОТ СЕРВЕРА И ФОРМИРУЕМ $update and $insert2
     * 4)СОХРАНЯЕМ РЕЗУЛЬТАТ В OPEN_ORDERS
     */
    public function syncOrders(){
        $SERVER = $this->repeat_collect('getopenorders');
        $DATA = [
            'uid' => [],
            'orders' => []
        ];
        if(!empty($SERVER) && $SERVER['success']){
            #ФОРМАТИРУЕМ ПОЛУЧЕННЫЕ ДАННЫЕ И ЗАПИСЫВАЕМ ИХ В OPEN_ORDERS
            if(!empty($SERVER['result'])){
                #ФОРМАТИРУЕМ
                $SERVER = $this->preobrazovat_order($SERVER['result']);
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
                        'limits'      => $e['limits'],
                        'kol_vo'      => $e['kol_vo'],
                        'price'      => $e['price'],
                        'open'      => $e['open'],
                        'close'      => $e['close'],
                        'uid'      => $e['uid'],
                    ];
                }
                #ПОДЫМАЕМ ОБЩИЕ ДАННЫЕ НА УРОВЕНЬ ВЫШЕ и "СКЛАДЫВАЕМ"
                if(empty($DATA)) new myError('Как-то не понятно но данные ПУСТЫЕ перед поднятием на уровень выше в OPEN_ORDERS',['$DATA'=>$DATA,'$orders'=>$SERVER]);
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
        }else{
            $DATA = false;        //записали false
            new myError('Сервер вернул отрицательно Wallet::syncOrders()',['orders'=>$SERVER]);
        }
        $this->OPEN_ORDERS = $DATA;
    }

    /**
     * ПРЕОБРАЗОВАНИЕ ОРДЕРОВ — ДАННЫХ ПОЛУЧЕНЫХ  ОТ СЕРВЕРА
     * @param $order
     * @return array
     */
    protected function preobrazovat_order($order){
        $new_order = [];
        if(!empty($order)){
            if(!isset($_COOKIE['sreda'])) new myError('Отстуствут кука со средой запуска!');
            for($i=0,$c=count($order);$i<$c;$i++){
                $e = &$order[$i];
                $open = (isset($e['TimeStamp']))?$e['TimeStamp']:$e['Opened'];
                $new_order[] = [
                    'coin'  => $e['Exchange'],
                    'status'  => $this->getTypeCoin($e['OrderType']),
                    'limits'  => number_format($e['Limit'],8,'.',''),
                    'kol_vo'  => number_format($e['Quantity'],8,'.',''),
                    'price'  => number_format($e['Price'],8,'.',''),
                    'open'  => getLocalTime($open,$this->CONFIG['sreda'][$_COOKIE['sreda']]['timezone2']),
                    'close'  => ($e['Closed'])?getLocalTime($e['Closed'],$this->CONFIG['sreda'][$_COOKIE['sreda']]['timezone2']):'',
                    'uid'  => $e['OrderUuid'],
                ];
            }
        }
        return $new_order;
    }
}