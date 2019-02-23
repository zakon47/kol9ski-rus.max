<?php

namespace Brocker;

use Lib\DB;

class Wallet extends WalletParent{





    protected function preobrazovat_wallet(&$SERVER){
        switch ($this->_sreda){
            case "bittrex":
                $this->preobrazovat_wallet_Bittrex($SERVER);
                break;
            default: dd('Wallet:: При синхронизации кошелька для среды: '.$this->sreda." отсутствует обработчик");
        }
    }
    private function preobrazovat_wallet_Bittrex(&$SERVER){
        if(empty($SERVER) && !$SERVER['success']) return false;
        $data = [];
        if(!empty($SERVER['result'])) {      //если есть данные которые пришли ссервере → преобразовываем их в наш вид
            for($i=0,$c=count($SERVER['result']);$i<$c;$i++){
                $e = &$SERVER['result'][$i];
                $data[] = [
                    'cur'  => $e['Currency'],
                    'balance'  => Brocker::num_format($e['Balance']),
                    'dostupno'  => Brocker::num_format($e['Available']),
                    'address'  => $e['CryptoAddress'],
                    'md5'  => md5($e['Balance'].$e['Available'].$e['CryptoAddress'])
                ];
            }
        }
        $SERVER = $data;
    }


    protected function preobrazovat_orders(&$SERVER){
        switch ($this->_sreda){
            case "bittrex":
                $this->preobrazovat_orders_Bittrex($SERVER);
                break;
            default: dd('Orders:: При синхронизации ордера для среды: '.$this->sreda." отсутствует обработчик");
        }
    }
    private function preobrazovat_orders_Bittrex(&$SERVER){
        if(empty($SERVER) && !$SERVER['success']) return false;
        $data = [];
        if(!empty($SERVER['result'])){      //если есть данные которые пришли ссервере → преобразовываем их в наш вид
            for($i=0,$c=count($SERVER['result']);$i<$c;$i++){
                $e = &$SERVER['result'][$i];
                $open = (isset($e['TimeStamp']))?$e['TimeStamp']:$e['Opened'];
                $data[] = [
                    'coin'  => $e['Exchange'],
                    'status'  => Brocker::getTypeCoin($e['OrderType']),
                    'bid_ask'  => Brocker::num_format($e['Limit']),
                    'kol_vo'  => Brocker::num_format($e['Quantity']),
                    'btc'  => Brocker::num_format($e['Price']),
                    'open'  => getLocalTime($open,$this->_CONFIG['sreda'][$this->_sreda]['timezone2']),
                    'close'  => ($e['Closed'])?getLocalTime($e['Closed'],$this->_CONFIG['sreda'][$this->_sreda]['timezone2']):'',
                    'uid'  => $e['OrderUuid'],
                ];
            }
        }
        $SERVER = $data;
    }


    protected function preobrazovat_ticket(&$SERVER){
        switch ($this->_sreda){
            case "bittrex":
                $this->preobrazovat_ticket_Bittrex($SERVER);
                break;
            default: dd('Orders:: При синхронизации тикета для среды: '.$this->sreda." отсутствует обработчик");
        }
    }
    private function preobrazovat_ticket_Bittrex(&$SERVER){
        if(empty($SERVER) && !$SERVER['success']) return false;
        $getTicker = [
            'buy'  => 'Ask',
            'sell' => 'Bid',
            'last' => 'Last',
        ];              //имя ячейки для опр монеты - получить актуальные значения
        $result = [];
        foreach ($getTicker as $K=>$V) {
            if(isset($SERVER['result'][$V])){
                $result[$K] = $SERVER['result'][$V];
            }
        }
        $SERVER = Brocker::num_format($result);
    }
}