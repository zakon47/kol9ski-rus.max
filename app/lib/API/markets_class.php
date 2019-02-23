<?php

/*
 * Класс для работы с парами BTS-1ST
 */
class Markets_parent extends Collect{
    protected $DB;
    protected $CONFIG;
    protected $WALLET;
    protected $TABLE_NAME = TABLE_NAME_MARKETS;
    //====================
    public $_list;               //список маркеров
    public $_key = [];           //вроде тоже самое только через ключ
    public function __construct() {    //подкчлюсили ДБ, КОНФИГ, КОШЕЛЕК? + заполнили данными из таблицы++ _markets[list/key]
        global $DB;
        $this->DB = &$DB;
        global $CONFIG;
        $this->CONFIG = &$CONFIG;
        global $WALLET;
        $this->WALLET = &$WALLET;
        //====================
        //self::power();     //Получили данные из BD и запихнули их в [list/key]
    }

    public function init(){      //активировать _markets[list/key]
        $t_sql = GET_DATA_MARKETS;           //какие элементы загружаем из таблицы
        $m = $this->getDB($t_sql);     //загрузили таблицу

        #ЕСЛИ УЖЕ WALLET Обработал монеты и обновил их - то просто получаем их
        if(isset($this->WALLET->MERKET->_list) && !empty($this->WALLET->MERKET->_list)){
            $this->_list = $this->WALLET->MERKET->_list;
            $this->_key = $this->WALLET->MERKET->_key;

        }else{  #ОТРАБАТЫВАЕМ ЗАЩИТУ - ЕСЛИ ТАБЛИЦЫ НЕТУ ИЛИ ОНА ПУСТАЯ
            if(isset($m['error']) || empty($m)){
                if(empty($m)) {       //таблица пуста
                    $this->syncDB();                      //синхронизировали данные
                    $m = $this->getDB($t_sql);     //загрузили таблицу
                }else if($m['error']=='not table'){       //если таблица не существует -> создаем ее
                    $this->createTable();                 //создали таблицу
                    $this->syncDB();                      //синхронизировали данные
                    $m = $this->getDB($t_sql);     //загрузили таблицу
                }      //если таблица пустая или ее нету
                #ПОВТОРНЫЙ ЗАПРОС - окончательный
                if(isset($m['error']))  dd('фатальная ошибка::MARKETS');   //отсутствует таблица
                if(empty($m))           dd('получили пустые данные с таблицы::MARKETS');      //отсутствуют данные
            }  //если ОШИБКА при получение данных

            #ЗАПИСАЛИ ИНФУ ИЗ BD в локалку
            $this->_list = $m;
            $this->_key = $this->addKeyName($m,'market');
            unset($m);
        }
    }

    //======================ПОСРЕДСТВЕННЫЕ========================
    /**
     * Создать пустую таблицу в БВ
     * @return int|string
     */
    protected function createTable(){          //создать таблицу если ее нету
        $sql = "CREATE TABLE `$this->TABLE_NAME` (
                   id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                   market varchar(20) NOT NULL UNIQUE KEY,
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
                   strategy text default NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ALTER TABLE `{$this->TABLE_NAME}` ADD INDEX(`market`);";
        return $this->DB->exec($sql);   //создали
    }
    /**
     * Загрузить маркеры из БД (ВСЕ или ТОЛЬКО АКТИВНЫЕ маркеры)
     * @param string $sql
     * @return bool|mixed
     */
    public function getDB($sql='*',$active=true){       //получить все MARKETS из таблицы
        if($active) $sql = "SELECT {$sql} FROM `$this->TABLE_NAME` WHERE `base` IN (".LOAD_COINS.") AND `isA`='1' ORDER BY `market`";
        else        $sql = "SELECT {$sql} FROM `$this->TABLE_NAME` WHERE `base` IN (".LOAD_COINS.") ORDER BY `market`";
        $markets = $this->DB->select($sql,[],1);
        if(is_array($markets) && !empty($markets)){ //если все прошло удачно - удаляем ЗАПРЕТНЫЕ МОНЕТЫ
            $new_m = [];
            for ($i=0,$c=count($markets);$i<$c;$i++){
                if(!in_array($markets[$i]['market'],$this->CONFIG['block_coins'])){
                    $new_m[] = $markets[$i];
                }
            }
            return $new_m;
        }else{
            return $markets;
        }
    }
    /**
     * Проверяем - активна ли данная монета в торгах? Есть ли на нее открытый ордер?
     */
    public function isOpen($coin){
        if(isset($this->get($coin)['isOpen']) && $this->get($coin)['isOpen']==true) return 1;
        return 0;
    }
    /**
     * Получить список актуальных маркеров из КЛАССА
     * @param $market — ''|list|key| + BTC-1ST
     * @return array|mixed
     */
    public function get($market=false,$type=''){       //получить все MARKETS из таблицы
        if($market){                          //если забросили определенную пару
            if($market=='list'){
                return $this->_list;
            }else if($market=='key'){
                return $this->_key;
            }else{                          //BTC-1ST
                if(empty($this->_key)){
                    $this->_key = $this->addKeyName($this->_list,'market');
                }
                return (isset($this->_key[$market]))?$this->_key[$market]:false;
            }
        }else{      // показать иерархией от BTC.. USDT..
            $data = [];
            for($i=0;$i<count($this->_list);$i++){
                $data[$this->_list[$i]['base']][] = $this->_list[$i];
            }
            return $data;
        }
    }










    protected function getBUYaSELL(){       //получить и зафикстировать информацию по монете - на каком статусе находится BUY and SELL
        if(!empty($this->_list)){
            for($i=0;$i<count($this->_list);$i++){
                $el = $this->_list[$i];
                if(empty($el['status'])){   //если монета не имеет статус
                    $w = $this->WALLET->get($el['marketCurrency']);
                    if($w['dostupno'] >= $el['minTradeSize']){          //если есть что продавать -> ПРОДАЕМ
                        $status = 'SELL';
                    }else{          // ПОКУПАЕМ
                        $status = 'BUY';
                    }
                    $this->_list[$i]['status'] = $status;
                    //тут устанавливаем новый статус там где его нету!
                    $sql = "UPDATE ".$this->TABLE_NAME." SET `status`=:status WHERE `id`=:id"; $num = array(':id'=>$el['id'],':status'=>$status);
                    $this->DB->update($sql,$num);
                }
            }
        }
    }
}