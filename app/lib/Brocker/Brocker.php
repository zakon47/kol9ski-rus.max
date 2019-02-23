<?php

namespace Brocker;

use Brocker\Server;
use Brocker\Wallet;
use Brocker\Market;
use Brocker\Stack;
use Brocker\Anal;
use Lib\myError;

class Brocker implements BrockerInterface{
    private $_hash;
    private $_sreda;
    private $_account;
    private $time_start;
    //==================
    private $_SERVER;
    private $_WALLET;
    private $_MARKET;
    private $_STACK;
    //==================
    static $_time_zone;         //кол-во секунд - разница между серверами
    static $_CONFIG;
    static $_hash_data = [];
    static $count = 0;
    static $count_all = 0;
    static $count_db = 0;
    static $count_dbn = [];
    static $coin_add = 0;
    static $coin_all = 0;


    public function __construct($sreda,$account) {
        $this->time_start = time();
        //ЗАГРУЗИЛИ КОНФИГ
        global $CONFIG;
        static::$_CONFIG = $CONFIG;
        //ЗАГРУЗИЛИ СРЕДУ
        if(!isset($CONFIG['sreda'][$sreda])) dd('Отсутствует выбранная среда!');
        $this->_sreda = $sreda;
        //загрузили аккаунт
        if(!empty($account)){
            if(!isset($CONFIG['account'][$account])) dd('Отсутствует выбранный аккаунт!');
            $this->_account = $account;
        }

        #ПОЛУЧАЕМ ХЕШ ОБЪЕКТА
        $this->_hash = spl_object_hash($this);
        #ЗАПИСЫВАЕМ ОБХЕКТЫ В НУЖНЫЙ КЕШ ФАЙЛ
        static::$_hash_data[$this->_hash] = [
            '_sreda'     => $this->_sreda,
            '_account'   => $this->_account,
            '_CONFIG'    => $CONFIG
        ];
        static::$_time_zone = pTime('H'.$CONFIG['sreda'][$this->_sreda]['timezone']);
        #СОЗДАЕМ ОБЪЕКТЫ
        $this->_SERVER = new Server($this->_hash);
        $this->_WALLET = new Wallet($this->_hash);
        $this->_MARKET = new Market($this->_hash);
        $this->_STACK = new Stack($this->_hash);
        $this->_ANAL = new Anal($this->_hash);
    }
    public function init($sync=[]):bool {
        $this->_WALLET->init($sync);         //получили данные про ORDERS
        $this->_MARKET->init($sync);         //получили MARKETS
        $this->_STACK->init($sync);          //получили СТЕК
        return true;
    }
    public function getBalance($coin='') {
        return $this->_WALLET->getBalance($coin);
    }
    public function getMarket($coin='') {
        return $this->_MARKET->getMarket($coin);
    }
    public function getStackAnalize($coin='') {
        return $this->_STACK->getStackAnalize($coin);
    }
    public function analize() {
        return $this->_ANAL->analize();
    }
    public function enter($coin,$LIST_STR,$date,$STACK=false,$MAX_STR=FALSE,$STATUS=FALSE){
        return $this->_ANAL->ENTER($coin,$LIST_STR,$date,$STACK,$MAX_STR,$STATUS);
    }
	public function simulator($coin,$date_limit,$DATA=FALSE,$STACK=false){
		return $this->_ANAL->simulator($coin,$date_limit,$DATA,$STACK);
	}

	public function getStrategy($coin){
        return $this->_ANAL->getStrategy($coin);
    }
    public function generateStrategy($strategy,$tpl=''){
        return $this->_ANAL->generateStrategy($strategy,$tpl);
    }

    public function getStackDB($coin,$date,$DATA=false,$STATUS=false){
        return $this->_ANAL->getStackDB($coin,$date,$DATA,$STATUS);
    }

    //===================== СТАТИКА
        /**
     * ОТСОРТИРОВАЛИ МАССИВ ПО 1 КЛЮЧУ? - сделали ассоциативный
     * @param array $arr
     * @param string $name
     * @return array
     */
    static function addKeyName($arr,$name){
        $keyName = [];
        if(!empty($arr)){
            for($i=0;$i<count($arr);$i++){
                $keyName[$arr[$i][$name]] = $arr[$i];
            }
        }
        return $keyName;
    }
        /**
     * ВОЗВРАЩАЕМ ИМЯ ТЕУЩЕГО СТАТУСА - BUY или SELL
     * @param $type
     * @return string
     */
    static function getTypeCoin($type){
        return (strpos($type,'BUY')!==false)?'BUY':'SELL';
    }
    static function num_format($e){
        if(is_array($e)){
            foreach ($e as $K=>$V){
                $e[$K] = number_format($V,8,'.','');
            }
            return $e;
        }
        return number_format($e,8,'.','');
    }

    public function getStatistic(){
        return [
            'date'=>time(),
            'send_server'=>static::$count,
            'send_server_all'=>static::$count_all,
            'send_db'=>static::$count_db,
            'send_dbn'=>static::$count_dbn,
            'coin_add'=>static::$coin_add,
            'coin_all'=>static::$coin_all,
            'time_script'=>time()-$this->time_start,      //время выполнения скрипта
        ];
    }
    public function getStatusSync(){
        $wallet = $this->_WALLET->getStatusSync();
        $market = $this->_MARKET->getStatusSync();
        $stack = $this->_STACK->getStatusSync();
        $wallet = array_merge($wallet,$market);
        return array_merge($wallet,$stack);
    }







    public function getTest(){

    }

    public function saveStatistics() {
        $STATISTIC = new \Buffer(TEMP,'statistic');
        $STATISTIC->add('sync',$this->getStatusSync());
        $STATISTIC->add('all',$this->getStatistic());
    }
}