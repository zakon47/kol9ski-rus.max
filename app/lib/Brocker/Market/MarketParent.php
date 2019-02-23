<?php

namespace Brocker;

use Lib\DB;

class MarketParent{
    protected $_hash;
    protected $_sreda;
    protected $_account;
    protected $_SERVER;
    protected $_WALLET;
    protected $_CONFIG;

    protected $MARKET = false;
    protected $SYNC = [];

    //==================
    public function __construct($_hash) {
        Brocker::$_hash_data[$_hash]['_MARKET'] = $this;
        $this->_SERVER = &Brocker::$_hash_data[$_hash]['_SERVER'];
        $this->_WALLET = &Brocker::$_hash_data[$_hash]['_WALLET'];
        $this->_CONFIG = &Brocker::$_hash_data[$_hash]['_CONFIG'];

        $this->_hash = $_hash;
        $this->_sreda = &Brocker::$_hash_data[$_hash]['_sreda'];
        $this->_account = &Brocker::$_hash_data[$_hash]['_account'];
    }


    public function init($sync=[]): bool {
        $x = $this->init_code($sync);        //синхронизируем

        #если есть монеты которым надо статус перепроверить и изменить
        if(isset(Brocker::$_hash_data[$this->_hash]['FOR_MARKETS'])){
            #ПОМЕНЯЛИ ЗНАЧЕНИЯ В MARKETS
            $this->updMarketStatusAll(Brocker::$_hash_data[$this->_hash]['FOR_MARKETS']);

            #ОБНОВИЛИ ЗНАЧЕНИЯ ИЗ БД
            $DB = $this->getMarketDB(GET_DATA_MARKETS);         //показать все позиции из DB
            $this->MARKET = Brocker::addKeyName($DB['data'],'coin');
        }
        Brocker::$coin_all = count($this->MARKET);
        return $x;
    }
    public function getMarket($coin_array=''){
	    if($this->MARKET===false) dd('Не активирован MARKET!');
    	if(!is_array($coin_array)) $coin_array = [$coin_array];
    	$return = [];
	    for($i=0,$c=count($coin_array);$i<$c;$i++){
	    	$coin = &$coin_array[$i];
		    $coin = strtoupper($coin);
		    if(!$coin){
			    return $this->MARKET;
		    }else{
			    if(isset($this->MARKET[$coin])){
				    $return[] = $this->MARKET[$coin];
			    }else{
				    return dd("Отсутствует запрашиваемая монета!!!");
			    }
		    }
	    }
	    if(count($return)==1) return $return[0];
	    return $return;
    }
    public function getStatusSync(){
        return $this->SYNC;
    }

    private function updMarketStatusAll(&$arr){
        foreach ($arr as $coin=>$v){
            $market = $this->getMarket($coin);
            $min = $market['min'];
            $STATUS = $this->_WALLET->getStatusCoin($coin,$min,1);

            #ЕСЛИ БЫЛИ ИЗМЕНЕНИЯ - то сохраняем их
            if($STATUS[0]!=$market['status'] || $STATUS[1]!=$market['isOpen']){
                $num = [
                    ':id' => $market['id'],
                    ':key' => [
                        ':status'  => $STATUS[0],
                        ':isOpen'  => $STATUS[1],
                    ]
                ];
                $sql = "UPDATE ".TABLE_NAME_MARKETS." SET :key WHERE `id`=:id";
                DB::update($sql,$num);
            }
        }
    }
    private function init_code($sync=[]){
        if(!empty($sync)){
            if(in_array('market',$sync)){
                #ТЕПЕРЬ ОБНОВЛЯЕМ МАРКЕРЫ новыми значениями
                $x1 = true;
                $x1 = $this->syncMarket();
                if(!$x1) return false;      //если что-то не синхронизировалось

                #А ТЕПЕРЬ ОБНОВЛЯЕМ У ВСЕХ МОНЕТ СТАТУСЫ!
                $DB = $this->getMarketDB(GET_DATA_MARKETS);         //показать все позиции из DB
                $this->MARKET = Brocker::addKeyName($DB['data'],'coin');
                $this->updMarketStatusAll($this->MARKET);
            }
        }
        #ОБНОВИЛИ ДАННЫЕ В КЛАССЕ
        $DB = $this->getMarketDB(GET_DATA_MARKETS);         //показать все позиции из DB
        if(isset($DB['error']) && $DB['error']='not table') {    //если нету такой таблицы
            $this->createTableMarket();
            $this->syncMarket();                      //синхронизировали данные
        }else{
            $this->MARKET = Brocker::addKeyName($DB['data'],'coin');
        }
        if($this->MARKET) return true;
        return false;
    }

    protected function syncMarket(): bool {
        #ЕСЛИ МЫ НЕ СИНХРОНИЗОВАЛИ ОРДЕРА - то надо их досинхронизовать!
        $sync = $this->_WALLET->getStatusSync();
        if(!isset($sync['OPEN_ORDERS']) || !isset($sync['HISTORY'])) $this->_WALLET->init(['order']);

        $SERVER = $this->_SERVER->repeat_collect('getmarkets');
        $this->preobrazovat_market($SERVER);        //преобразовали ОТВЕТ
        if($SERVER===false){
            $this->MARKET = false;
            return false;
        }

        #ПОЛУЧИЛИ ТЕКУЩИЕ МОНЕТЫ
        $DB = $this->getMarketDB(GET_DATA_MARKETS_UPD,false);         //показать все позиции из DB
        if(isset($DB['error']) && $DB['error']='not table') {    //если нету такой таблицы
            $this->createTableMarket();
            $DB_KEY = [];
        }else{
            $DB_KEY = Brocker::addKeyName($DB['data'],'coin');
        }

        #ПЕРЕБИРАЕМ МОНЕТЫ
        $insert = [];
        $update = [];

        #ПЕРЕБИРАЕМ СПИСОК ПОЛУЧЕННЫХ МОНЕТ
        for($i=0,$c=count($SERVER);$i<$c;$i++){
            $e = $SERVER[$i];      //элемент только что с сервера
            #ТЕПЕРЬ РАСПРЕДЕЛЯЕМ ЭТУ МОНЕТУ  —  на UPDATE or INSERT
            $key = 1;   //разрешение на вставку новой записи в таблицу
            if(isset($DB_KEY[$e['coin']])){
                $key = 0;
                if($e['md5']!=$DB_KEY[$e['coin']]['md5']){      //если кеш поменял → обновляем контент!
                    $x = [
                        'id'            => $DB_KEY[$e['coin']]['id'],      //id в DB
                        'elem_id'       => $i,                             //id в SERVER
                        'min'           => $e['min'],
                        'isA'           => $e['isA'],
                        'logo'          => $e['logo'],
                        'notice'        => $e['notice'],                          //какие-то сообщения
                        'md5'           => $e['md5']
                    ];
                    $update[] = $x;
                }
                unset($DB_KEY[$e['coin']]);
            }
            #ПОДГОТОВКА ДЛЯ — ВСТАВКА НОВОЙ ЗАПИСИ
            if($key){   //не был найден в локальной таблице - значит эту запись надо вставить - создаем цифры
                $s = $this->_WALLET->getStatusCoin($e['coin'],$e['min']);  //получаем статус монеты + её активность [BUY,1]
                $status = $s[0];        //BUY
                $isOpen = $s[1];        //1
                $insert[] = [
                    ':coin'     => $e['coin'],
                    ':cur'      => $e['cur'],
                    ':base'     => $e['base'],
                    ':curN'     => $e['curN'],
                    ':baseN'    => $e['baseN'],
                    ':min'      => $e['min'],
                    ':isA'      => $e['isA'],
                    ':status'   => $status,
                    ':isOpen'   => $isOpen,
                    ':notice'   => $e['notice'],
                    ':open'     => $e['open'],
                    ':logo'     => $e['logo'],
                    ':md5'      => $e['md5'],
                    ':sreda'    => $this->_sreda,
                    ':account'  => $this->_account,
                ];
            }
        }
//
//                        dd('==========$DB_KEY',1);
//                        dd($DB_KEY,1);
//                        dd('==========insert',1);
//                        dd($insert,1);
//                        dd('==========update',1);
//                        dd($update,1);

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
                    $sql = "UPDATE ".TABLE_NAME_MARKETS." SET :key WHERE `id`=:id";
                    $num = [
                        ':id'   => $list[$i]['id'],
                        ':key'   => [
                            ':isA'  => 0,
                            ':md5'  => md5('0'.$list[$i]['min'].$list[$i]['notice']),
                        ],
                    ];
                    DB::update($sql,$num);
                }
            }
        }
        if(!empty($insert)){        //если есть что вставлять -> ВСТАВЛЯЕМ
            $sql = "INSERT INTO `".TABLE_NAME_MARKETS."` (`coin`,`cur`,`base`,`curN`,`baseN`,`min`,`isA`,`status`,`isOpen`,`notice`,`open`,`logo`,`md5`,`sreda`,`account`) VALUES :key";
            $num = [
                ':key'  => $insert
            ];
            $x = DB::insert($sql,$num);
        }
        if(!empty($update)) {        //если есть что обновить -> ОБНОВЛЯЕМ
            for($k=0,$c=count($update);$k<$c;$k++){
                $num = [
                    ':id' => $update[$k]['id'],
                    ':key' => [
                        ':min'  => $update[$k]['min'],
                        ':isA'  => $update[$k]['isA'],
                        ':logo'  => $update[$k]['logo'],
                        ':notice'  => $update[$k]['notice'],
                        ':md5'  => $update[$k]['md5'],
                    ]
                ];
                $sql = "UPDATE ".TABLE_NAME_MARKETS." SET :key WHERE `id`=:id";
                DB::update($sql,$num);
            }
        }
        $this->SYNC['MARKET'] = 1;      //мол синхронизировали
        return true;
    }                       //СИНХРОНИЗАЦИЯ
    protected function getMarketDB($sql='*',$active=true){       //получить все MARKETS из таблицы
        if($active) $sql = "SELECT {$sql} FROM `".TABLE_NAME_MARKETS."` WHERE `base` IN (:key) AND `isA`='1' AND `sreda`=:sreda AND `account`=:account ORDER BY `coin`";
        else        $sql = "SELECT {$sql} FROM `".TABLE_NAME_MARKETS."` WHERE `sreda`=:sreda AND `account`=:account ORDER BY `coin`";
        $num = [
            ':key' => Brocker::$_CONFIG['sreda'][$this->_sreda]['anal_coin'],
            ':sreda' => $this->_sreda,
            ':account' => $this->_account,
        ];
        return DB::selectAll($sql,$num);
    }     //Загрузить маркеры из БД (ВСЕ или ТОЛЬКО АКТИВНЫЕ маркеры)
    protected function createTableMarket(){          //создать таблицу если ее нету
        $sql = "CREATE TABLE `".TABLE_NAME_MARKETS."` (
                   id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                   coin varchar(20) NOT NULL UNIQUE KEY,
                   cur varchar(10) NOT NULL,
                   base varchar(10) default NULL,
                   curN varchar(30) NOT NULL,
                   baseN varchar(30) default NULL,
                   min DECIMAL(".TO4NOST.") default NULL,
                   isA tinyint(1) NOT NULL,
                   
                   status varchar(30) NOT NULL,
                   isOpen tinyint(1) NOT NULL,
                   opt varchar(255) default NULL,
                   
                   notice varchar(255) default NULL,
                   
                   open datetime default NULL,
                   logo varchar(250) default NULL,
                   md5 varchar(32) default NULL,
                   sreda varchar(50) NOT NULL,
                   account varchar(50) NOT NULL,
                   strategy text default NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ALTER TABLE `".TABLE_NAME_MARKETS."` ADD INDEX(`market`);";
        return DB::query($sql);   //создали
    }                    //Создать пустую таблицу в БВ
}