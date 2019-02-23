<?php defined('_3AKOH') or die(header('/'));


include API.'stack_class.php';
class Stack extends Stack_parent{
    /**
     * ПОДГОТОВИТЬ СТЕК ДЛЯ ГРУППИРОВКИ - ПЕРЕВЕРНУЛИ И ДОПОЛНИЛИ
     * @param $stack
     * @return array|bool
     */
    public function prepareStack(&$stack){
        if(empty($stack)) return [];
        $new_stack = [];
        $tpl = array(
            'time_id' => 0,
            'COUNTS' => 0,
            'BUY' => 0,
            'BUY_price' => 0,
            'BUY_price_min' => 0,
            'BUY_price_max' => 0,
            'BUY_price_last' => 0,
            'BUY_total' => 0,           //цена в BTC
            'BUY_quantity' => 0,        //кол-во
            'SELL' => 0,
            'SELL_price' => 0,
            'SELL_price_min' => 0,
            'SELL_price_max' => 0,
            'SELL_price_last' => 0,
            'SELL_total' => 0,
            'SELL_quantity' => 0,
        );
        for($i=count($stack)-1;$i>=0;$i--){
            $time_id = strtotime($stack[$i]['TimeStamp'])+OFFSET_TIME;
            $new_stack[] = $tpl;
            $last = count($new_stack)-1;
            $new_stack[$last]['time_id']    = $time_id;
            $new_stack[$last]['COUNTS']    = 1;
            if($stack[$i]['OrderType']=='BUY'){     //покупка
                $new_stack[$last]['BUY']    = 1;
                $new_stack[$last]['BUY_price']    = $stack[$i]['Price'];
                $new_stack[$last]['BUY_price_min']    = $stack[$i]['Price'];
                $new_stack[$last]['BUY_price_max']    = $stack[$i]['Price'];
                $new_stack[$last]['BUY_price_last']    = $stack[$i]['Price'];
                $new_stack[$last]['BUY_total']    = $stack[$i]['Total'];
                $new_stack[$last]['BUY_quantity']    = $stack[$i]['Quantity'];
            }else{      //продажа
                $new_stack[$last]['SELL']    = 1;
                $new_stack[$last]['SELL_price']    = $stack[$i]['Price'];
                $new_stack[$last]['SELL_price_min']    = $stack[$i]['Price'];
                $new_stack[$last]['SELL_price_max']    = $stack[$i]['Price'];
                $new_stack[$last]['SELL_price_last']    = $stack[$i]['Price'];
                $new_stack[$last]['SELL_total']    = $stack[$i]['Total'];
                $new_stack[$last]['SELL_quantity']    = $stack[$i]['Quantity'];
            }
        }
        return $this->groupStack($new_stack);
    }
}
//dd(date(DATE_FORMAT,getTime('1514014680')));
//$g['grafic'] = ['t/M1/M10',['name'=>1]];
//$path = $STACK->enter('BTC-2GIVE',$g,'1514014680');
//$x = $STACK->syncDB('BTC-PPC');
//dd($x);preobrazovat_stack