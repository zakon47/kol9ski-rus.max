<?php

namespace Brocker;

class Market extends MarketParent{





    protected function preobrazovat_market(&$SERVER){
        switch ($this->_sreda){
            case "bittrex":
                $this->preobrazovat_market_Bittrex($SERVER);
                break;
            default: dd('Orders:: При синхронизации маркера для среды: '.$this->sreda." отсутствует обработчик");
        }
    }
    private function preobrazovat_market_Bittrex(&$SERVER){
        if(empty($SERVER) && !$SERVER['success']) return false;
        $data = [];
        if(!empty($SERVER['result'])){      //если есть данные которые пришли ссервере → преобразовываем их в наш вид
            for($i=0,$c=count($SERVER['result']);$i<$c;$i++){
                $e = &$SERVER['result'][$i];
                $isA = (empty($e['IsActive']))?'0':$e['IsActive'];            //если пустое значение
                $md5 = md5($isA.$e['MinTradeSize'].$e['Notice']);         //md5 from SERVER
                $data[] = [
                    'coin'  => $e['MarketName'],
                    'cur'  => $e['MarketCurrency'],
                    'base'  => $e['BaseCurrency'],
                    'curN'  => $e['MarketCurrencyLong'],
                    'baseN'  => $e['BaseCurrencyLong'],
                    'min'  => $e['MinTradeSize'],
                    'isA'  => $isA,
                    'notice'  => $e['Notice'],
                    'open'  => $e['Created'],
                    'logo'  => $e['LogoUrl'],
                    'md5'  => $md5,
                ];
            }
        }
        $SERVER = $data;
    }
}