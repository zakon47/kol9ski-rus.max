<?php

namespace Brocker;

class Stack extends StackParent{

    protected function preobrazovat_stack(&$SERVER){
        switch ($this->_sreda){
            case "bittrex":
                $this->preobrazovat_stack_Bittrex($SERVER);
                break;
            default: dd('Orders:: При синхронизации маркера для среды: '.$this->sreda." отсутствует обработчик");
        }
    }
    private function preobrazovat_stack_Bittrex(&$SERVER){
        if(empty($SERVER) && !$SERVER['success']) return false;
        $data = [];
        if(!empty($SERVER['result'])){      //если есть данные которые пришли ссервере → преобразовываем их в наш вид
            for($i=0,$c=count($SERVER['result']);$i<$c;$i++){
                $e = &$SERVER['result'][$i];
                $time = round_time2(strtotime($e['TimeStamp'])+Brocker::$_time_zone);   //форматируем время
                $data[$e['MarketName']] = [
                    'high'           => Brocker::num_format($e['High']),
                    'low'           => Brocker::num_format($e['Low']),
                    'volume'        => $e['Volume'],
                    'base_volume'   => $e['BaseVolume'],
                    'last'           => Brocker::num_format($e['Last']),
                    'sell'           => Brocker::num_format($e['Bid']),
                    'buy'           => Brocker::num_format($e['Ask']),
                    'buy_orders'    => $e['OpenBuyOrders'],
                    'sell_orders'   => $e['OpenSellOrders'],
                    'dates'         => $time
                ];
            }
        }
        $SERVER = $data;
    }
}