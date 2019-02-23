<?php

#ОБРАБОТЧИК ОТЛОЖЕННЫХ ЗАДАЧ
class Aks extends Buffer{
    function __construct(){
        parent::__construct(TEMP, 'ASK.txt');
    }
    public function command($COM,$VAR){
        switch($COM){
            case 'upd':
                $SREDA = $VAR['SREDA'];
                $TYPE = $VAR['TYPE'];
                #НАЧАЛО РАБОТЫ
                $WALLET = new Wallet();
                $MARKETS = new Markets();
                $CORE = new Core($SREDA);
                #====================
                $WALLET->init(1);        //синхронизировали кошельки и ордерс
                $MARKETS->init();       //получили значения маркеров из БД
                if($CORE->update()){
                    echo "[$SREDA] ".date(DATE_FORMAT,time())." {$TYPE} update\n";
                }else{
                    echo "[$SREDA] ".date(DATE_FORMAT,time())." {$TYPE} update ERROR\n";
                }
                break;
            default:
                new myError('Нету данного обработчика для команды ASK',['COM'=>$COM,'VAR'=>$VAR]);
                return false;
        }
    }
}