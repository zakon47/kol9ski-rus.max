<?php

namespace Brocker;

class ServerParent{
    protected $_sreda;
    protected $_account;
    protected $_api_key;
    protected $_api_secret;
    //==================

    public function __construct($_hash){
        Brocker::$_hash_data[$_hash]['_SERVER'] = $this;
        $this->_sreda = &Brocker::$_hash_data[$_hash]['_sreda'];
        $this->_account = &Brocker::$_hash_data[$_hash]['_account'];

        $wallet = explode('|',Brocker::$_CONFIG['account'][$this->_account]);
        $this->_api_key = $wallet[0];
        $this->_api_secret = $wallet[1];
    }


    protected function proverka_myquery($list,$myquery){  //$list - обязательные, $myquery - то что у меня
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
    }       //для обязательных параметров в запросе
    public function repeat_collect($command,$myquery=[]){
        $steep = explode(' ','1 1 1');    //схема повторных запросов
        $i=0;
        $key = true;
        do{
            Brocker::$count_all++;
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