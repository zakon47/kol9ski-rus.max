<?php

namespace Lib;
use Brocker\Brocker;
use \PDO;


class DB{
    static protected $db;  //подключение
    public function __construct($c) {
        define("aPREFIX_DB",$c['prefix']);
        try{
            static::$db = new PDO("{$c['typedb']}:host={$c['hostname']};port=3306;dbname={$c['database']}",$c['username'],$c['password']);
            static::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch (PDOException $e){
            switch ($e->getCode()){
                case 0: dd("Неправильный тип подключения к БД");
                case 2002: dd("Отсутствует подключаемый сервер");
                case 1045: dd("Ошибка в связке Логин/Пароль");
                default: dd($e->getCode()." → ".$e->getMessage());
            }
        }
        //переводим кодировку
//        static::$db("SET NAMES ".$c['dbcollat']);
    }
    /**
     * Если есть параметры как массив - то записываем их как переменные
     * @param $sql
     * @param $params
     */
    static private function insertArray(&$sql,&$params){
        foreach ($params as $k=>$v) {        //биндим значение из массива
            if(is_array($v)){
                $str = [];
                $skobki = false;
                if(!empty($v)){
                    foreach ($v as $key=>$val){
                        if(is_array($val)){
                            $in_str = [];
                            foreach ($val as $kk=>$vv){
                                $name = substr($kk,1);
                                $in_str[] = $k.$key.$name;
                                $params[$k.$key.$name] = $vv;
                            }
                            $in_str = '('.implode(',',$in_str).')';
                            $str[] = $in_str;
                        }else{
                            $skobki = true;
                            $name = substr($key,1);
                            $str[] = $k.$name;
                            $params[$k.$name] = $val;
                        }
                    }
                }
                if($skobki){
                    $str = '('.implode(',',$str).')';
                }else{
                    $str = implode(',',$str);
                }
                #Заменяем строку $sql
                $sql = str_replace($k,$str,$sql);
                unset($params[$k]);
            }
        }
    }
    static private function otherArray(&$sql,&$params){
        foreach ($params as $k=>$v) {        //биндим значение из массива
            if(is_array($v)){
                $str = [];
                if(!empty($v)){
                    foreach ($v as $key=>$val){
                        $str[] = $k.$key;
                        $params[$k.$key] = $val;
                    }
                }
                $str = implode(',',$str);
                #Заменяем строку $sql
                $sql = str_replace($k,$str,$sql);
                unset($params[$k]);
            }
        }
    }
    static private function updateArray(&$sql,&$params){
        foreach ($params as $k=>$v) {        //биндим значение из массива
            if (is_array($v)) {
                $str = [];
                if(!empty($v)){
                    foreach ($v as $key=>$val){
                        if(is_numeric($key)){   //если это число
                            $str[] = $k.$key;
                            $params[$k.$key] = $val;
                        }else{
                            $name = substr($key,1);
                            $str[] = $name.'='.$k.$name;
                            $params[$k.$name] = $val;
                        }
                    }
                }
                $str = implode(',',$str);
                #Заменяем строку $sql
                $sql = str_replace($k,$str,$sql);
                unset($params[$k]);
            }
        }
    }

    static private function exec(&$sql,$params=[],$type='',$debug=false){
        if($debug){
            dd($params,1);
            dd($sql,1);
        }
        #ДЕЛАЕМ ПРЕ-ОБРАБОТКУ $sql ДЛЯ Array()
        if(!empty($params)){
            if($type=='update') {
                static::updateArray($sql,$params);
            }else if($type=='insert') {
                static::insertArray($sql,$params);
            }else {
                static::otherArray($sql,$params);
            }
        }
        if($debug){
            dd($params,1);
            dd($sql);
        }

        #ПРОГОНЯЕМ ПРЕПРОЦЕССОРНУЮ ПОДГОТОВКУ ДАННЫХ
        $smtp = static::$db->prepare($sql);
        if(!empty($params)){
            foreach ($params as $k=>$v){        //биндим значение из массива
                if(strpos($sql,$k)!==false){
                    $smtp->bindValue($k,$v);
                }
            }
        }
        #ВЫПОЛНЯЕМ ЗАПРОС
        try{        //если успешно
        	$r = "/^([a-zA-Z]+).+(".PREFIX_DB."[a-zA-Z]+)/";
        	preg_match_all($r,$sql,$out);
	        Brocker::$count_dbn[] = $out[1][0].' '.$out[2][0];
            Brocker::$count_db++;
            $smtp->execute();       //выполнить запрос
        }catch(\PDOException $e){
            switch ($e->errorInfo[0]){
                case '42S02': {return ['error'=>'not table']; break;}
                case '23000': {return ['error'=>'unique']; break;}
                case '21S01': {return ['error'=>'Количество столбцов не совпадает с количеством значений в строке 1']; break;}
                case 'HY093': {return ['error'=>'Недопустимые параметры']; break;}
                default: return ['error'=>$e->getMessage()];
            }
        }
        return $smtp;
    }
    //ЗАПРОС
    static public function query($sql,$params=[]){
        $smtp = static::exec($sql,$params);
        if(is_array($smtp)) return $smtp;
        return ['data'=>1];
    }
    //ВСТАВКА
    static public function update($sql,$params=[],$debug=false){
        $smtp = self::exec($sql,$params,'update',$debug);
        if(is_array($smtp)) return $smtp;
        return ['data'=>1];
    }
    //ВСТАВКА
    static public function insert($sql,$params=[],$debug=false){
        $smtp = self::exec($sql,$params,'insert',$debug);
        if(is_array($smtp)) return $smtp;
        return ['data'=>static::$db->lastInsertId()];
    }
    //ВЫБОРОКА
    static public function select($sql,$params=[],$debug=false){
        $smtp = static::exec($sql,$params,'',$debug);
        if(is_array($smtp)) return $smtp;
        return ['data'=>$smtp->fetch(PDO::FETCH_ASSOC)];
    }
    //ВЫБОРОКА ВСЕГО
    static public function selectAll($sql,$params=[],$debug=false){
        $smtp = static::exec($sql,$params,'',$debug);
        if(is_array($smtp)) return $smtp;
        return ['data'=>$smtp->fetchAll(PDO::FETCH_ASSOC)];
    }
    public function __destruct(){
        if(static::$db) static::$db = null;
    }
}
