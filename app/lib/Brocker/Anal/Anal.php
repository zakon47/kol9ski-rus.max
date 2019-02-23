<?php

namespace Brocker;

use Lib\DB;

#ШАБЛОНЫ ВЫБОРОК ИЗ БД


class Anal{
    protected $_hash;
    protected $_sreda;
    protected $_account;
    protected $_SERVER;
    protected $_WALLET;
    protected $_MARKET;
    protected $_STACK;
    protected $_CONFIG;
//    protected $TEMPLATE = [
//        'BUY'   =>[
//            'BUY_UGOL'  =>[
//                'opt'   => ['dop'=>'[1-2,1]','mom'=>2],
//                'str'   => [
//                    's/M[1-5,1] H10 D4',
//                    's/M[2-10,1] M30 H1'
//                ],
//                'ugol'  => '[5-15,1]'
//            ]
//        ],
//        'SELL'  => [
//            'LOSS'  => [
//                'opt'   => ['dop'=>1],
//                'str'   => '3/M5',
//                'stop'  => '[3-10,1]',
//            ]
//        ]
//    ];
    protected $TEMPLATE = [
        'BUY'   =>[
            'BUY_UGOL'  =>[
                'opt'   => ['dop'=>'1','mom'=>2],
                'str'   => [
	                's/M5 M30 H1',
                    's/M5 H10 D4',
                ],
                'ugol'  => '[0.1-0.3,0.1]'
            ]
        ],
        'SELL'  => [
            'LOSS'  => [
                'opt'   => ['dop'=>1],
                'str'   => '3/M5',
                'stop'  => '3',
            ]
        ]
    ];

    //==================
    public function __construct($_hash) {
        Brocker::$_hash_data[$_hash]['_ANAL'] = $this;
        $this->_SERVER = &Brocker::$_hash_data[$_hash]['_SERVER'];
        $this->_WALLET = &Brocker::$_hash_data[$_hash]['_WALLET'];
        $this->_MARKET = &Brocker::$_hash_data[$_hash]['_MARKET'];
        $this->_STACK = &Brocker::$_hash_data[$_hash]['_STACK'];
        $this->_CONFIG = &Brocker::$_hash_data[$_hash]['_CONFIG'];

        $this->_hash = $_hash;
        $this->_sreda = &Brocker::$_hash_data[$_hash]['_sreda'];
        $this->_account = &Brocker::$_hash_data[$_hash]['_account'];
    }

    public function analize(){
        $list_stack = $this->_STACK->getStackAnalize();        //список проанализированных монет
        if(empty($list_stack)) return false;

        #АНАЛИЗИРУЕМ КАЖДУЮ МОНЕТУ
        foreach ($list_stack as $COIN=>$VAL){
            #ПРОАНАЛИЗИРОВАЛИ ТЕКУЩУЮ МОНЕТУ
            $xx = $this->go($COIN,$VAL['dates']);
            dd($xx,1);
            dd('Конец анализа монеты → нужна обработка анализа!');
        }
        return true;
    }                                       //ЗАПУСКАЕМ АНАЛИЗ ВСЕХ МОНЕТ, КОТОРЫЕ "ОБНОВИЛИ"

    public function go($coin,$date,$DATA=false,$STACK=false,$debug=false){
        #ПОЛУЧАЕМ ИНФУ ПО МОНЕТЕ
        $market = $this->_MARKET->getMarket($coin);

        #ПОЛУЧАЕМ $DATA
        if(!$DATA){
            $DATA = $this->getStrategy($coin);            //после этого момента $markets не актуален
        }
        if(!isset($DATA['status'])) $DATA['status'] = $market['status'];

	    $DATA['new'] = 0;

        #ОБРАБОТКА ИСКЛЮЧЕНИЙ
        //1) Есть активный ордер!
	    $order = $this->_WALLET->getOrder($coin);
	    if(isset($order['status'][$DATA['status']])) return ['status'=>1,'msg'=>$DATA['status'].' → It has open order'];
        //2) DATA по умолчанию и status=BUY
	    if($DATA['status']=='BUY' && $DATA['new']) return ['status'=>1,'msg'=>$DATA['status'].' → DATA is default for BUY'];
	    //3) ЕСЛИ STATUS=BUY а у нас стратегия для BUY пустая!
	    if($DATA['status']=='BUY' && empty($DATA['BUY'])) return ['status'=>1,'msg'=>$DATA['status'].' → DATA[BUY] is empty!'];

	    $LIST_STR = $this->LIST_STR($DATA);
	    $MAX_STR = $this->MAX_STR($LIST_STR,$date);
//	    if($debug) dd($MAX_STR);

	    if(!$STACK){
		    #ПОЛУЧИЛИ СТЕК
		    $STACK = $this->GET_STACK($coin,$MAX_STR,$date,$DATA['status']);
		    if(empty($STACK)) return ['status'=>1,'msg'=>'$STACK → empty!'];
	    }
	    #ПОЛУЧИЛИ $ENTER
	    $ENTER = $this->ENTER($coin,$LIST_STR,$date,$STACK,$MAX_STR);
	    //$ENTER = $this->FastENTER($coin,$LIST_STR,$date,$STACK,$MAX_STR);

        return true;
//	    return ['status'=>1,'msg'=>$DATA['status'].' → It has open order'];

	    #ПОЛУЧИЛИ ТЕКУЩИЙ $PRICE
	    $getStack = FALSE;
	    if($getStack){
	    	if($DATA['status']=='BUY'){
			    $PRICE = $getStack['buy'];
		    }else{
			    $PRICE = $getStack['sell'];
		    }
	    }else{
	    	$k = 0;
	    	for($i=0,$c=count($STACK);$i<$c;$i++){
	    		if($STACK[$i]['d']>$MAX_STR['first_date']){
				    break;
			    }
			    $k = $i;
		    }
		    $PRICE = $STACK[$k]['y2'];
	    }

        if($DATA['status']=='BUY'){
            return $this->anal_BUY($PRICE,$DATA,$ENTER);
        }else{
            return $this->anal_SELL($PRICE,$DATA,$ENTER);
        }
    }       //эта штука анализирует и возвращает состояние - надо ли покупать или продавать сейчас?!

    public function FastENTER(&$coin,&$LIST_STR,&$date,&$STACK=false,&$MAX_STR=FALSE,&$STATUS=FALSE,$debug=FALSE){
        if(!$MAX_STR) $MAX_STR = $this->MAX_STR($LIST_STR,$date);

        #ПОЛУЧИТЬ СТЕК - если нету
        if(!$STACK){
            $STACK = $this->GET_STACK($coin,$MAX_STR,$date,$STATUS);
        }


        if(0){
            #ЗАПИСЫВАЕМ ДАННЫЕ В ФАЙЛ
            //file_put_contents(TEMP.'FastENTER_IN',json_encode(['list'=>$MAX_STR['data'],'stack'=>$STACK]));
            foreach ($MAX_STR['data'] as $K=>$V){
                $MAX_STR['data'][$K]['str'] = str_replace(" ",":",$MAX_STR['data'][$K]['str']);
                for ($i=0,$c=count($V['segment']);$i<$c;$i++){
                    $MAX_STR['data'][$K]['segment'][$i]['d'] = [];
                }
            }
//        var_dump($STACK[0]["min"]);
//        var_dump($STACK[0]["max"]);
//        var_dump($STACK[0]["y2"]);
//        dd($STACK);
            file_put_contents(TEMP.'FastENTER_IN',json_encode(['list'=>$MAX_STR['data'],'stack'=>$STACK]));
//        dd(json_decode($json,1));
//        file_put_contents(TEMP.'FastENTER_IN',json_encode($STACK));
            //file_put_contents(TEMP.'FastENTER_IN',$json);
            //file_put_contents(TEMP.'FastENTER_IN','{"hello":"world","t":true,"f":false,"n":null,"i":123,"pi":3.1416,"a":[47]}');

            #ЗАПУСК СКРИПТА!
            system(EXE."EmptyProject2.exe ../temp/",$return);
            if($return) dd('При запуске EXE произошла ошибка! Код: '.$return,1);

            #ОБРАБАТЫВАЕМ ДАННЫЕ
            $itog_fast = file_get_contents(TEMP.'FastENTER_OUT');
            $R = json_decode($itog_fast,1);
            $MAX_STR['data'] = $R['list'];
//            return $r;
//            dd($r);
        }else{
            //dd($MAX_STR);
            for($i=0,$c=count($STACK);$i<$c;$i++){
                $eStack = &$STACK[$i];
                #Перебираем каждый из секгемнтов
                foreach ($MAX_STR['data'] as $NAME=>$BODY){
                    for($j=0;$j<$BODY['count'];$j++){
                        //$eSegment = &$BODY['segment'][$i];
                        if($eStack['d']>=$BODY['segment'][$j]['x1'] AND $eStack['d']<=$BODY['segment'][$j]['x2']){
                            $MAX_STR['data'][$NAME]['segment'][$j]['g'][] = $eStack;
                        }
                    }
                }
            }
        }
        //dd($MAX_STR);

        return false;
        dd('ЗАГЛУШКА');

        #ПРИВЯЗАТЬ СТЕК К ВРЕМЕННЫМ ОТРЕЗКАМ
        foreach ($MAX_STR['data'] as $NAME=>$STR){     //общий массив стретегий → одна стратегия
            $ELEM = &$MAX_STR['data'][$NAME];          //ОДНА ИЗ СТРАТЕГИЙ
            $empty = true;     //ключ - мол пока не нашли данные, подходящие стратегии
            #ПЕРЕБИРАЕМ СТЕК И ЗАПОЛНЯЕМ СТРАТЕГИЮ
            if(!empty($STACK)){
                for ($i=0,$c=count($STACK);$i<$c;$i++){
                    $e = &$STACK[$i];
                    #ПЕРЕБИРАЕМ СТРАТЕГИЮ
                    if(empty($ELEM['segment'])) dd('ПОЧЧЕМУТО! ПУСТО в стратегии - в точках останова!');
                    for ($str=0,$str_c=count($ELEM['segment']);$str<$str_c;$str++){
                        $path = &$ELEM['segment'][$str];
                        #ПРОВЕРКА УСЛОВИЯ
                        if($e['d']>=$path['x1'] AND $e['d']<=$path['x2']){
                            $path['g'][] = $e;
                            $empty = false;     //есть данные для этой стратегии
                        }
                    }
                    #ЕСЛИ ДЛЯ ДАННОЙ СТРАТЕГИИ НЕ БЫЛО ДАННЫХ из СТЕКА!
                }
            }
            if($empty) $ELEM['error'] = 'Отсутствуют значения STACK';

            continue;
            #ЕСЛИ НЕТУ ОШИБОК!
            if(!isset($ELEM['error'])){
                $count_seg = count($ELEM['segment']);
                #СКЛАДЫВАЕМ ОБЩИЕ ТОЧКИ
                for ($i=0;$i<$count_seg;$i++){
                    $seg = &$ELEM['segment'][$i];
                    if(isset($seg['g'])){
                        $seg['g'] = $this->GROUP_SUM($seg['g']);
                    }
                }

                #ДОПОЛНЯЕМ НЕДОСТАЮЩИЕ ТОЧКИ
                for ($i=0;$i<$count_seg;$i++){
                    $seg = &$ELEM['segment'][$i];
                    if(!isset($seg['g'])){
                        #ПРЯМАЯ СБОРКА
                        $ADD_G = $this->ADD_G($i,$ELEM['segment']);
                        if($ADD_G){
                            $seg['g'] = $ADD_G;
                        }else{
                            #ОБРАТНАЯ СБОРКА
                            $ADD_G = $this->ADD_G($i,$ELEM['segment'],true);
                            if($ADD_G) $seg['g'] = $ADD_G;
                        }
                    }
                }
                #ДОРИСОВЫВАЕМ Y1 + СКЛАДЫВАЕМ ЗНАЧЕНИЯ НА УРОВЕНЬ ВЫШЕ
                for ($i=0;$i<$count_seg;$i++){
                    #на уровень выше
                    $seg = &$ELEM['segment'][$i];
                    $seg = array_merge($seg,$seg['g']);
                    unset($seg['g']);
                    #дописываем y1
                    if(isset($ELEM['segment'][$i+1])){
                        $seg['y1'] = $ELEM['segment'][$i+1]['g']['y2'];
                    }else{
                        $seg['y1'] = $ELEM['segment'][$i]['y2'];
                    }
                }
                if($ELEM['opt']['dop']){        //если есть запрос на ДОПОЛНИТЕЛЬНЫЕ РАСЧЕТЫ
                    for ($i=0;$i<$count_seg;$i++) {
                        $seg = &$ELEM['segment'][$i];
                        $seg['raz'] = round((($seg['y2']*100)/$seg['y1'])-100,2);
                        $seg['razX'] = round((($ELEM['segment'][0]['y2']*100)/$seg['y1'])-100,2);
                        if($i==$count_seg-1){       //на последнем элементе
                            $seg['proc'] = 0;
                            $seg['procX'] = 0;
                        }else{
                            $seg['proc'] = round((($seg['y0']*100)/$ELEM['segment'][$i+1]['y0'])-100,2);
                            $seg['procX'] = round((($ELEM['segment'][0]['y2']*100)/$ELEM['segment'][$i+1]['y0'])-100,2);
                        }
                    }
                }
                //                dd($ELEM);
                #ВЫПОЛНЯЕМ ВЫЧИСЛЕНИЯ
            }
        }

        return $MAX_STR;
    }           //а эта штука просто группирует стек(свой или переданный) по определенным правилам
    public function ENTER($coin,$LIST_STR,$date,$STACK=false,$MAX_STR=FALSE,$STATUS=FALSE,$debug=FALSE){
        if(!$MAX_STR) $MAX_STR = $this->MAX_STR($LIST_STR,$date);

        #ПОЛУЧИТЬ СТЕК - если нету
        if(!$STACK){
            $STACK = $this->GET_STACK($coin,$MAX_STR,$date,$STATUS);
        }
        //dd($MAX_STR);

        #ПРИВЯЗАТЬ СТЕК К ВРЕМЕННЫМ ОТРЕЗКАМ
        foreach ($MAX_STR['data'] as $NAME=>$STR){     //общий массив стретегий → одна стратегия
            $ELEM = &$MAX_STR['data'][$NAME];          //ОДНА ИЗ СТРАТЕГИЙ
            $empty = true;     //ключ - мол пока не нашли данные, подходящие стратегии
            #ПЕРЕБИРАЕМ СТЕК И ЗАПОЛНЯЕМ СТРАТЕГИЮ
            if(!empty($STACK)){
                for ($i=0,$c=count($STACK);$i<$c;$i++){
                    $e = &$STACK[$i];
                    #ПЕРЕБИРАЕМ СТРАТЕГИЮ
                    if(empty($ELEM['segment'])) dd('ПОЧЧЕМУТО! ПУСТО в стратегии - в точках останова!');
                    for ($str=0,$str_c=count($ELEM['segment']);$str<$str_c;$str++){
                        $path = &$ELEM['segment'][$str];
                        #ПРОВЕРКА УСЛОВИЯ
                        if($e['d']>=$path['x1'] AND $e['d']<=$path['x2']){
                            $path['g'][] = $e;
                            $empty = false;     //есть данные для этой стратегии
                        }
                    }
                    #ЕСЛИ ДЛЯ ДАННОЙ СТРАТЕГИИ НЕ БЫЛО ДАННЫХ из СТЕКА!
                }
            }
            if($empty) $ELEM['error'] = 'Отсутствуют значения STACK';

            //continue;
            #ЕСЛИ НЕТУ ОШИБОК!
            if(!isset($ELEM['error'])){
                $count_seg = count($ELEM['segment']);
                #СКЛАДЫВАЕМ ОБЩИЕ ТОЧКИ
                for ($i=0;$i<$count_seg;$i++){
                    $seg = &$ELEM['segment'][$i];
                    if(isset($seg['g'])){
                        $seg['g'] = $this->GROUP_SUM($seg['g']);
                    }
                }

                #ДОПОЛНЯЕМ НЕДОСТАЮЩИЕ ТОЧКИ
                for ($i=0;$i<$count_seg;$i++){
                    $seg = &$ELEM['segment'][$i];
                    if(!isset($seg['g'])){
                        #ПРЯМАЯ СБОРКА
                        $ADD_G = $this->ADD_G($i,$ELEM['segment']);
                        if($ADD_G){
                            $seg['g'] = $ADD_G;
                        }else{
                            #ОБРАТНАЯ СБОРКА
                            $ADD_G = $this->ADD_G($i,$ELEM['segment'],true);
                            if($ADD_G) $seg['g'] = $ADD_G;
                        }
                    }
                }
                #ДОРИСОВЫВАЕМ Y1 + СКЛАДЫВАЕМ ЗНАЧЕНИЯ НА УРОВЕНЬ ВЫШЕ
                for ($i=0;$i<$count_seg;$i++){
                    #на уровень выше
                    $seg = &$ELEM['segment'][$i];
                    $seg = array_merge($seg,$seg['g']);
                    unset($seg['g']);
                    #дописываем y1
                    if(isset($ELEM['segment'][$i+1])){
                        $seg['y1'] = $ELEM['segment'][$i+1]['g']['y2'];
                    }else{
                        $seg['y1'] = $ELEM['segment'][$i]['y2'];
                    }
                }
                if($ELEM['opt']['dop']){        //если есть запрос на ДОПОЛНИТЕЛЬНЫЕ РАСЧЕТЫ
                    for ($i=0;$i<$count_seg;$i++) {
                        $seg = &$ELEM['segment'][$i];
                        $seg['raz'] = round((($seg['y2']*100)/$seg['y1'])-100,2);
                        $seg['razX'] = round((($ELEM['segment'][0]['y2']*100)/$seg['y1'])-100,2);
                        if($i==$count_seg-1){       //на последнем элементе
                            $seg['proc'] = 0;
                            $seg['procX'] = 0;
                        }else{
                            $seg['proc'] = round((($seg['y0']*100)/$ELEM['segment'][$i+1]['y0'])-100,2);
                            $seg['procX'] = round((($ELEM['segment'][0]['y2']*100)/$ELEM['segment'][$i+1]['y0'])-100,2);
                        }
                    }
                }
//                dd($ELEM);
                #ВЫПОЛНЯЕМ ВЫЧИСЛЕНИЯ
            }
            break;
        }
        //dd($ELEM);

        return $MAX_STR;
    }           //а эта штука просто группирует стек(свой или переданный) по определенным правилам

    ######################################## ОБРАБОТЧИКИ ##############################################

    /**
     * АНАЛИЗИРУЕМ МОНЕТУ ДЛЯ ПОКУПКИ
     * @param $DATA
     * @return array
     *
     * @DESC
     * 1)ПОЛУЧАЕМ СТРАТЕГИИ
     * 2)СОБИРАЕМ ИНФУ ДЛЯ ENTER и записываем в $X
     * 3)ЗАПУСКАЕМ ENTER
     * 4)ЕСЛИ ЕСТЬ ДАННЫЕ КОТОРЫЕ МОЖНО ПРОАНАЛИЗИРОВАТЬ + ДАННЫХ БОЛЬШЕ ЧЕМ ~ 3 дня -> АНАЛИЗ
     */
    private function anal_BUY(&$PRICE,&$DATA,&$ENTER){
//    	dd($ENTER);
	    $BUY_Result = FALSE;       //если не было покупки
	    foreach($DATA['BUY'] as $BUY=>$OPT){
	    	if($BUY_Result) break;
	    	#Формируем линки на РЕЗУЛЬТАТЫ промежутков СТРАТЕГИИ
		    if(is_array($OPT['str'])){
			    $LINK = [];
		    	for($i=0,$c=count($OPT['str']);$i<$c;$i++){
		    		$LINK[] = 'BUY:'.$BUY.':'.$i;
			    }
		    }else{
			    $LINK = ['BUY:'.$BUY];
		    }
	    	switch($BUY){
			    case 'BUY_UGOL':
			    	if(!isset($ENTER['data'][$LINK[0]])) dd('Критическая ошибка! Почему-то для стратегии не найден ENTER!');
				    $BUY_Result = $this->BUY_UGOL($PRICE,$OPT,$LINK,$ENTER['data']);
			    	break;
			    default: dd('Не распознан обработчик для BUY→'.$BUY);
		    }
	    }
	    #ЕСЛИ ПОСЛЕ ПРОХОДА ВСЕХ СТРАТЕГИЙ $BUY=true, то покупаем!
	    if($BUY_Result){
		    return [
		    	'status'=>'BUY',
			    'msg'=>'КУПИЛИ',
			    'x2'=>$BUY_Result['x2'],
			    'y2'=>$BUY_Result['y2'],
			    'stretagy'=>$BUY_Result['stretagy']
		    ];
	    }
	    return ['status'=>1,'msg'=>'BUY —'];


	    $BUY_Result = FALSE;       //если не было покупки
	    if(!$BUY_Result && isset($BUY['BUY_UGOL']) && !empty($analize['BUY_UGOL'])){
		    $BUY_Result = $this->BUY_UGOL($BUY['BUY_UGOL'],$analize['BUY_UGOL']);
	    }

        return ['status'=>1,'msg'=>'BUY —'];
    }

	######################################## СТРАТЕГИИ ##############################################
	//Очень резкий угол подъема графика
    private function BUY_UGOL(&$PRICE,&$STR,$LINK,&$ENTER_STR){
	    $ENTER_STR = $ENTER_STR[$LINK[0]];
    	if(!isset($ENTER_STR['segment'][0])) return FALSE;
	    if($ENTER_STR['segment'][0]['raz']>=$STR['ugol']){
		    return ['x2'=>$ENTER_STR['segment'][0]['x2'],'y2'=>$ENTER_STR['segment'][0]['y2'],'stretagy'=>'BUY_UGOL'];
	    }
	    return FALSE;
    }

    /**
     * АНАЛИЗИРУЕМ МОНЕТУ ДЛЯ ПРОДАЖИ
     * @param $DATA
     * @return array
     *
     * @DESC
     * 1)ПОЛУЧАЕМ НАШ LOSS
     * 2)ПОЛУЧАЕМ ПОСЛЕДНЮЮ ЦЕНУ $last_price
     * 3)АНАЛИЗИРУЕМ СДВИГ ТЕКУЩЕЙ ЦЕНЫ
     *      ВЫШЕ ВЕРХНЕЙ ГРАНИЦЫ
     *      — Получаем дельту и прибавляем ее к текущим показателям
     *      НИЖЕ НИЖНЕЙ ГРАНИЦЫ
     *      — Продали
     */
    private function anal_SELL(&$PRICE,&$DATA,&$ENTER){
	    $LOSS = &$DATA['SELL']['LOSS'];     //наша стратегия
	    $top = (float) $LOSS['calc']['top'];
	    $bot = (float) $LOSS['calc']['bot'];
//	    dd($ENTER);
	    #АНАЛИЗИРУЕМ СДВИГ ТЕКУЩЕЙ ЦЕНЫ
	    if($PRICE>$top){   //новая цена - выше верхней границы
		    #Получаем дельту и прибавляем ее к текущим показателям
		    $delta = $PRICE-$LOSS['calc']['top'];
		    $LOSS['calc']['top'] += $delta;
		    $LOSS['calc']['bot'] += $delta;
		    #Небольшие преобразования
		    $LOSS['calc']['top'] = number_format($LOSS['calc']['top'],8,'.','');
		    $LOSS['calc']['bot'] = number_format($LOSS['calc']['bot'],8,'.','');
		    //                    $this->saveStrategy($DATA['coin'],$DATA['strategy']);
		    return ['status'=>'UPD','msg'=>'Обновить DATA->StopLoss','DATA'=>$DATA];
	    }elseif($PRICE<=$bot){       //ниже нижней границы
		    return [
		    	'status'=>'SELL',
			    'msg'=>'ПРОДАЛИ',
			    'x2'=>$ENTER['first_date'],
			    'y2'=>$PRICE,
			    'LOSS'=>[
			    	'top'=>$LOSS['calc']['top'],
				    'bot'=>$LOSS['calc']['bot']
			    ]
		    ];
	    }else{
		    return ['status'=>1,'msg'=>'SELL —'];
	    }
    }


    public function simulator($coin,$date_limit,$DATA=FALSE,$STACK=false){
//dd($DATA);
	    #ПОЛУЧАЕМ ВРЕМЯ
	    $x1 = getTime($date_limit['x1']);
	    $x2 = getTime($date_limit['x2']);

	    #ПОЛУЧИЛИ СТРАТЕГИЮ МОНЕТЫ
	    if(!$DATA) $DATA = $this->getStrategy($coin);
	    #ЗАПИСАЛИ СВОЮ СТРАТЕГИЮ
//	    $DATA['BUY']['BUY_UGOL'] = [
//		    'opt'   =>  ['dop'=>1],
//		    'str'   =>  's/M5 M30 H1',
//		    'ugol'  =>  0.1
//	    ];
	    #ОДИНАКОВЫЕ УСЛОВИЯ ДЛЯ ВСЕХ ВАРИАНТОВ
	    $DATA['status'] = 'BUY';

	    #ЕСЛИ НЕТУ СТЕКА
	    if(!$STACK){
		    $STACK = $this->getStackDB($coin,$date_limit,$DATA);
	    }

//	    dd($DATA);

	    #ЗАГОТОВКА для return
	    $itog = [
		    'count' => 0,
		    'BUY'   => 0,
		    'SELL'  => 0,
		    'kpd'   => 0,
		    'act'   => [],
	    ];

	    #ПЕРЕБИРАЕМ ВРЕМЯ И ПОЛУЧАЕМ АНАЛИЗ ДЛЯ КАЖДОЙ ТОЧКИ
	    $steep = 60;        //шаг операции
//	    $K = 0;
        dd(pTime('D1'),1);
	    //for($i=$x1;$i<=$x2;$i+=$steep){     //Где $i - временая метка  19544845615
	    for($i=0;$i<=1000;$i++){     //Где $i - временая метка  19544845615
		    $res = $this->go($coin,$x1,$DATA,$STACK[$DATA['status']],1);
            continue;
//		    dd($i.' → '.$x2,1);
//		    if($K==2) break;
//		    $K++;
//		    dd($res,1);
		    #ЕСЛИ БЫЛИ ПРАВКИ В $DATA — или — ЕСЛИ БЫЛА ВЫПОЛНЕНА ОПЕРАЦИЯ
		    if($res['status']=='UPD'){              //поправили значения STOP_LOSS
			    $DATA['SELL'] = $res['DATA']['SELL'];
		    }elseif ($res['status']=='BUY' || $res['status']=='SELL'){  //SELL получается если GO вернул SELL!!!
//			    dd($res);
			    $LOSS = &$DATA['SELL']['LOSS'];         //ссылка на стоп-лосс
			    if($res['status']=='BUY'){
				    $itog['BUY']++;                                                 //купили
				    $DATA['status'] = 'SELL';                                       //поменяли статус
				    $LOSS['calc'] = $this->getStopLoss($res['y2'],$LOSS['stop']);           //пересчитали стоп-лосс
				    $res['LOSS'] = ['top'=>$LOSS['calc']['top'],'bot'=>$LOSS['calc']['bot']]; //дописали актуальный стоплосс для вывода на графике
			    }else{
				    $itog['SELL']++;
				    $DATA['status'] = 'BUY';

				    $res['LOSS'] = ['top'=>$LOSS['calc']['top'],'bot'=>$LOSS['calc']['bot']]; //дописали актуальный стоплосс для вывода на графике
				    $LOSS['calc'] = '';                                //перезаписали СТОП_ЛОСС на пустой
			    }
			    $X = 1;
			    $itog['count']++;
			    $itog['act'][] = $res;      //записали в $itog in [act]
		    }else{
			    $X = 0;
		    }
	    }

//	    dd($date_limit,1);
	    #СЧИТАЕМ КПД
	    if(!empty($itog['act'])){
		    $BUY = false;
		    for($i=0;$i<count($itog['act']);$i++){
			    $e = &$itog['act'][$i];
			    if($e['status']=='SELL' && $BUY){
				    $sum = $e['y2']-$BUY;
				    $itog['kpd'] += $sum;       //суммируем КПД
			    }else{
				    $BUY = $e['y2'];
			    }
		    }
		    $itog['kpd'] = number_format($itog['kpd'],8,'.','');
	    }
	    return $itog;
    }
    private function ADD_G($iteration,$arr,$reverse=false){
        $iteration = (!$reverse)?$iteration+1:$iteration-1;
        if(!isset($arr[$iteration])) return false;
        if(isset($arr[$iteration]['g'])) return $arr[$iteration]['g'];
        return $this->ADD_G($iteration,$arr,$reverse);
    }               //ДОПОЛНЯЕМ НЕДОСТАЮЩИЕ ТОЧКИ
    private function GROUP_SUM($segment){
        if(empty($segment)) dd('КРИТИЧЕСКАЯ ОШИБКА - откуда-то получился пусто ммассив!');
        $etalon = $segment[0];
        $etalon['y0'] = 0;
        unset($etalon['d']);

        #ПЕРЕБИРАЕМ И ГРУППИРУЕМ
        $count_seg = count($segment);
        if($count_seg>1){
            for ($i=0;$i<$count_seg;$i++){
                $e = &$segment[$i];     //элемент
                if($e['max']>$etalon['max']) $etalon['max'] = $e['max'];
                if($e['min']<$etalon['min']) $etalon['min'] = $e['min'];
                $etalon['y2'] = $e['y2'];
                $etalon['y0'] += $e['y2'];
            }
            $etalon['y0'] = Brocker::num_format($etalon['y0']/$count_seg);
        }else{
            $etalon['y0'] = $etalon['y2'];
        }
        return $etalon;
    }                                 //СКЛАДЫВАЕМ ОБЩИЕ ТОЧКИ → ГРУППИРОВКА

	public function getStackDB($coin,$date,$DATA=false,$STATUS=false,$MAX_STR=false){
    	if(!$DATA) $this->getStrategy($coin);       //если нету $DATA

		if(!$MAX_STR){
			$LIST_STR = $this->LIST_STR($DATA);
			$MAX_STR = $this->MAX_STR($LIST_STR,$date);
		}
		return $this->GET_STACK($coin,$MAX_STR,$date,$STATUS);
	}
    public function GET_STACK_FOR_ENTER($coin,$DATA,$date){
        $LIST_STR = $this->LIST_STR($DATA);
        $MAX_STR = $this->MAX_STR($LIST_STR,$date);
        return $this->GET_STACK($coin,$MAX_STR,'BUY',$date);
    }
    private function LIST_STR($str){
        $data = $this->LIST_STR_RECURSE($str);
        if(!empty($data)){
            $new_date = [];
            for ($i=0,$c=count($data);$i<$c;$i++){
                $e = &$data[$i];
                $new_date[$e[0]] = [$e[1],$e[2]];
            }
            $data = $new_date;
        }
        return $data;
    }                                     //ПОЛУЧИТЬ СПИСОК СТРАТЕГИЙ
    private function MAX_STR($LIST_STR,$date){
    	if(is_array($date)){
		    $date = $date['x1'];
        }
	    $date = (!is_numeric($date))?getTime($date):$date;

        $max = 0;
        foreach ($LIST_STR as $NAMA=>$ARR){
            //            $ARR[0] = "5/M2";

            #СФОРМИРОВАЛИ ПОЛНОЦЕННЫЕ ОПЦИИ
            $elem = &$LIST_STR[$NAMA];
            $elem = [];
            $elem['str'] = $ARR[0];
            $elem['opt'] = array_merge([                    //значения по умолчанию
                'block' =>0,        //по какому принципу группируем
                'dop'   =>0,        //нужна ли доп инфа?
            ],$ARR[1]);
            $opt = &$elem['opt'];                //link

            #СОЗДАЕМ РАЗБИТЫЙ МАССИВ ДАННЫХ     → $LIST_STR[$NAMA]['exp']
            $exp = explode('/',trim($ARR[0]),2);
            $elem['exp'] = [
                'type' => $exp[0],
                'group' => explode(' ',$exp[1]),
            ];
            $type = &$elem['exp']['type'];       //link
            $group = &$elem['exp']['group'];     //link
            #НУЖНО ПОЛУЧИТЬ ВРЕМЕННЫЕ ОТРЕЗКИ
            $point = &$elem['point'];
            $segment_name = &$elem['segment_name'];
            $point = [];
            $segment_name = [];
            $point[] = $date;       //устанавливаем всем начальную ТОЧКУ!
            switch ($type){
                case is_numeric($type):       //→ поледовательность блоков
                    if($opt['block']){
                        $Xpoint = round_time($date,$group[0]);
                        if($Xpoint!=$date){
                            $point[] = $Xpoint;
                            $segment_name[] = 'dop';
                        }
                    }
                    for($i=1;$i<=$type;$i++){
                        $point[] = $point[count($point)-1]-pTime($group[0]);
                        $segment_name[] = $group[0];
                    }
                    break;
                case 's':       //→ поледовательность блоков
                    if($opt['block']){
                        $Xpoint = round_time($date,$group[0]);
                        if($Xpoint!=$date){
                            $point[] = $Xpoint;
                            $segment_name[] = 'dop';
                        }
                    }
                    for($i=0,$c=count($group);$i<$c;$i++){
                        $point[] = $point[count($point)-1]-pTime($group[$i]);
                        $segment_name[] = $group[$i];
                    }
                    break;  //→ поледовательность блоков
                case 'g':       //→ интервал с шагом → для ГРАФИКОВ
                    $interval_ex = explode('/',$group[0]);  //получаем интервалы
                    $interval = [
                        'min' => pTime($interval_ex[0]),
                        'max' => pTime($interval_ex[1]),
                    ];
                    if($opt['block']){
                        $Xpoint = round_time($date,$interval['min']);
                        if($Xpoint!=$date){
                            $point[] = $Xpoint;
                            $segment_name[] = 'dop';
                        }
                    }
                    $iteracia = $interval['min'];
                    while($iteracia<=$interval['max']){
                        $point[] = $point[count($point)-1]-$interval['min'];

                        $segment_name[] = $interval_ex[0];      //записали имя
                        $iteracia+=$interval['min'];            //увеличили число
                    }
                    break;  //→ интервал с шагом → для ГРАФИКОВ
                case 'p':     //→ конкретные break points → для СТАТИКИ
//                    $interval = explode('/',$group[0]);
                    $interval = $group;
//                    dd($interval);
                    $last_point = $point[0];
                    if($opt['block']){
                        $Xpoint = round_time($date,$interval[0]);
                        if($Xpoint!=$date){
                            $point[] = $Xpoint;
                            $segment_name[] = 'dop';
                            $last_point = $Xpoint;
                        }
                    }
                    for($i=0,$c=count($interval);$i<$c;$i++){
                        $point[] = $last_point-pTime($interval[$i]);
                        $segment_name[] = $interval[$i];
                    }
                    break;
                default:
                    dd('НЕИЗВЕСТНЫЙ ТИП: '.$type);
            }
            #ВЫЧИТАЕМ МАКСИМАЛЬНЫЕ ОТСКОКИ
            //            $elem['max'] = $point[0]-$point[count($point)-1];
            $max_elem = $point[0]-$point[count($point)-1];
            if($max_elem>$max) $max = $max_elem;

            #Записываем общее кол-во сегментов
            $elem['count'] = count($elem['segment_name']);

            #ФОРМИРУЕМ СЕГМЕНТЫ
            for ($i=1,$c=$elem['count'];$i<=$c;$i++){
                $elem['segment'][] = [
                    'name'  => $segment_name[$i-1],
                    'x1'    => $point[$i],
                    'x2'    => $point[$i-1]
                ];
            }

            #ЗАТИРАЕМ СТАРОЕ
            //            unset($elem['opt']);
            unset($elem['point']);
            unset($elem['segment_name']);
            unset($elem['exp']);
            //            dd($elem);
        }
        return [
            'max'=>$max,
            'first_date'=>$date,      //точка относительно чего считаем MAX_DATE
            'first_date_get'=>date(DATE_FORMAT,$date),      //точка относительно чего считаем MAX_DATE
            'data'=>$LIST_STR
        ];
    }                          //ПЕРЕХОДНЫЙ $DATA - считаем МАХ стратегию + строим промежутку
    private function GET_STACK($coin,$MAX_STR,$date=false,$STATUS=false){
    	if(!is_array($date)) $date = false;     //если передали просто число - а не промежуток

        #ФОРМИРУЕМ ЛИМИТ
        $limit = [
            'x1' => ($date)?getTime($date['x1']):$MAX_STR['first_date'],
            'x2' => ($date)?getTime($date['x2']):$MAX_STR['first_date'],
        ];

        #ВЫЧИТАЕМ MAX
        $limit['x1']-=$MAX_STR['max'];
        #ВЫЧИТАЕМ ДЕЛЬТУ
        $limit['x1']-=pTime('H3');  //→ дельта

        #ДЕЛАЕМ ЗАПРОС В БД
        $sql = "SELECT ".GET_DATA_STACK." FROM `".PREFIX_DB.$coin."` WHERE dates>=:x1 AND dates<=:x2";
        $num = [
            ':x1' => $limit['x1'],
            ':x2' => $limit['x2']
        ];
	    $DB = DB::selectAll($sql,$num);

        if(isset($DB['error'])) dd('Почему появилась ошибка!');
        if(empty($DB['data'])) return [];
        $stack = ['BUY'=>[],'SELL'=>[]];
        for($i=0,$c=count($DB['data']);$i<$c;$i++){
        	$e = &$DB['data'][$i];
            $e['d']*=1;
        	$head = [];
	        $buy = '';
	        $sell = '';
		    foreach($e as $K=>$V){
		    	if($K=='buy'){
				    $buy = $V;
			    }else if($K=='sell'){
				    $sell = $V;
			    }else{
				    $head[$K] = $V;
			    }
		    }
	        $head_buy = array_merge($head,['y2'=>$buy]);
	        $head_sell = array_merge($head,['y2'=>$sell]);
	        $stack['BUY'][] = $head_buy;
	        $stack['SELL'][] = $head_sell;
	    }
	    if($STATUS && isset($stack[$STATUS])){
        	return $stack[$STATUS];
	    }else{
		    return $stack;
	    }
    }              //ПОЛУЧИТЬ СТЕК

    private function LIST_STR_RECURSE($str,$name='',&$data=[]){
        foreach ($str as $KEY=>$VAL){
            if(is_array($VAL)){
                if(isset($VAL['str'])){
                    $opt = $VAL['opt'];
                    if(is_array($VAL['str'])){
                        foreach ($VAL['str'] as $X=>$Y){
                            $data[] = [$name.':'.$KEY.':'.$X,$Y,$opt];
                        }
                    }else{
                        $data[] = [$name.':'.$KEY,$VAL['str'],$opt];
                    }
                    continue;
                }else{
                    if(empty($name)){
                        $data = $this->LIST_STR_RECURSE($VAL,$KEY,$data);
                    }else{
                        $data = $this->LIST_STR_RECURSE($VAL,$name.':'.$KEY,$data);
                    }
                }
            }
        }
        return $data;
    }          //РЕКУРСИВНЫЙ МАССИВ

    public function optimizator(){
        #ПОЛУЧИЛИ СТРАТЕГИЮ ДЛЯ ЭТОЙ МОНЕТЫ
        $DATA = $this->generateStrategy([]);
        $DATA = $DATA[count($DATA)-1];       //максимальная выборка
    }

    public function getStrategy($coin){
        $market = $this->_MARKET->getMarket($coin);       //получили маркер и всю его инфу
        #ПОЛУЧАЕМ РАСКОДИРОВАННУЮ СТРАТЕГИЮ - ЕСЛИ НЕТУ СТРАТЕГИИ - ТО СОЗДАЕМ ЕЕ и ЗАПИСЫВАЕМ + получаем
        if(empty($market['strategy'])){             //если ещё нету стратегии
            $DATA = $this->createStrategy($coin);
        }else{                      //если есть стратегия - просто вернуть ее
            $DATA = json_decode($market['strategy'],1);     //раскодировали
        }
        if(!$DATA){
            $this->saveStrategy($coin,NULL);
            return false;
        }
        return $DATA;
    }                                   //ПОЛУЧИТЬ ОБЪЕКТ СТРАТЕГИИ ДЛЯ ОПРЕДЕЛЕННОЙ МОНЕТЫ ИЗ БД :: Типо скелет - если нету - то создаем
    private function saveStrategy($coin,$strategy){
        #ЕСЛИ ПЕРЕДАЛИ НЕ JSON а массив - то преобразуем его в JSON
        if(is_array($strategy)){
            $strategy_js = json_encode($strategy);
        }else{
            $strategy_js = $strategy;
        }
        $num = [
            ':coin' => $coin,
            ':upd'  => [
                ':strategy' => $strategy_js
            ]
        ];

        $sql = "UPDATE ".TABLE_NAME_MARKETS." SET :upd WHERE `coin`=:coin";
        $DB = DB::update($sql,$num);
        if(!isset($DB['error'])){
            return $strategy;
        }else{
            return false;
        }
    }                       //ПЕРЕЗАПИСЫВАЕМ ДЛЯ МОНЕТЫ НОВУЮ СТРАТЕГИЮ
    private function createStrategy($coin){
        #ПОЛУЧАЕМ АКТУАЛЬНУЮ ЦЕНУ - берем из последнего запроса или делаем запрос на сервер
        $_stack = $this->_STACK->getStackAnalize($coin);
        if(isset($_stack['sell'])){
            $price = $_stack['sell'];
        }else{
            $price = $this->_WALLET->getTicker($coin);
            if(!$price) return false;
            $price = $price['sell'];
        }
        $strategy = [
            'new'   => 1,               //если 1 - то эта стратегия сгенерирована по умолчанию
            'upd'   => time(),          //время последнего обновления стратегии
            'BUY'   => [],              //стратегии покупки
            'SELL'  => [
                'LOSS'  => [
                    'opt'   =>  ['dop'=>1],                 //параметры для ENTER
                    'str'   =>  '3/M5',                     //стратегия для ENTER
                    'stop'   =>  DEFAULT_LOSS_BOTTOM,       //актуальный процент СТОП_ЛОСС
                    'calc'   =>  $this->getStopLoss($price,DEFAULT_LOSS_BOTTOM)       //получить КАЛЬКУЛЯЦИЮ СТОП-ЛОСС
                ]
            ]            //стратегии продажи
        ];
        #СОХРАНЯЕМ
        return $this->saveStrategy($coin,$strategy);
    }                               //ПОЛУЧИТЬ ПУСТУЮ ШАБЛОННУЮ СТРАТЕГИЮ
    private function getStopLoss($price,$proc){
        $bottom = ((100-$proc)*$price)/100;
        return [
            'price'     => $price,                                       //цена по которой была инициализация
            'stop'      => $proc,                                        //процент инициализации
            'top'       => $price,         //изменяемая часть
            'bot'       => Brocker::num_format($bottom,8,'.',''),        //изменяемая часть
        ];
    }                           //ПОЛУЧАЕМ STOP-LOSS для данной цены и %процента%




    /**
     * Получает текущую и шаблонную стратегию
     * @param $strategy - текущая стратегия
     * @param $tpl      - шаблон стратегий с промежутками
     * @param $elements - элементы для которых надо применить/обновить стратегию
     * @return array    - массив возможных вариантов стратегий
     */
    public function generateStrategy($cur_strategy,$tpl=''){
        if(!empty($cur_strategy)) $strategy = $cur_strategy;
        else $strategy = [];
        $TEMPLATE = $this->TEMPLATE;
        if(empty($tpl)) $tpl = $TEMPLATE;
        $new_gen = $this->preobrazovatel_arr($tpl);
        $new_strategy = [];
        for ($i=0;$i<count($new_gen);$i++){
            $x = $cur_strategy;
            $x = $this->replace_strategy($strategy,$new_gen[$i]);
            $new_strategy[] = $x;
        }
        return $new_strategy;
    }
        protected function preobrazovatel_str($tpl){
        if(is_array($tpl)) return [$tpl];
        $REG_TPL = "/(\[([0-9]*[.])?[0-9]+-([0-9]*[.])?[0-9]+\,([0-9]*[.])?[0-9]+\])+/";    //Шаблон для обучения
        $result = [$tpl];
        preg_match_all($REG_TPL, $tpl, $out);
        if(!isset($out[0]) || empty($out[0])) return [$tpl];      //если через легулярку ничего не нашли - то возвращаем только проверяемое.
        #ФОРМИРУЕМ СПИСОК ЗАМЕН в массиве — $zameny
        $zameny = [];           //список заменяемых элементов
        for($i=0;$i<count($out[0]);$i++){
            $e = &$out[0][$i];      //одиночный элемент
            $zameny[] = $e;
        }
        #ПЕРЕБИРАЕМ ЗАМЕНЫ
        for ($i=0;$i<count($zameny);$i++){                   //перебираем отрезки, из которых получаем цифры
            $e = trim($zameny[$i]);                          //[10-15,2]
            $dlina_zameny = strlen($e);
            #ПРЕОБРАЗУЕМ ШАБЛОННУЮ СТРОЧКУ В МАССИВ
            $e = substr($e,1,strlen($e)-2);     //-2 буквы  10-15,2
            $e = explode(',',$e);                   //обрезали запятую
            $e[0] = explode('-',$e[0]);             //обрезали
            $e = [
                '0'     => $zameny[$i],
                'x1'    => round($e[0][0],1),
                'x2'    => round($e[0][1],1),
                's'     => $e[1],
                'r'     => []       //результат
            ];
            #РЕНДЕРИМ И ПОЛУЧАЕМ КОНЕЧНЫЕ ЗНАЧЕНИЯ - т.е вычисляем список из скобок
            for($t=$e['x1'];$t<=$e['x2'];$t=round($t+$e['s'],1)){
                $e['r'][] = $t;
            }
            if(empty($e['r'])) $e['r'][] = $e['x1'];

            #ЕСЛИ ЕСТЬ ЧТО ЗАМЕНЯТЬ
            if(!empty($e['r'])){
                #ПРОИЗВОДИМ СОБСТВЕННО ЗАМЕНУ
                $new_result = [];
                for($t=0;$t<count($result);$t++){           //перечисляем список заменяемых строк
                    $str_tpl = $result[$t];                 //заменяемая строка —  s/M[0-10,2] M[5-10,2] M10
                    for($r=0;$r<count($e['r']);$r++){       //а теперь перечисляем список заменяемых значений
                        $zna4enie = $e['r'][$r];
                        #ЗАМЕНА
                        $new_result[] = str_replace($e['0'],$zna4enie,$str_tpl);
                    }
                }
                $result = $new_result;
            }
        }
        return $result;
    }           //Получаем из СТРАТЕГИИ (шаблона) - кучу стратегий - передаём - s/M[1-10,1] M2 M10
        protected function &getLINK($STR,&$TEST){
        if(isset($Y)) unset($Y);
        $EX = explode(':',$STR);
        $Y = '';
        for ($j=0;$j<count($EX);$j++){
            $ex = &$EX[$j];
            if(empty($Y)){
                $Y = &$TEST[$ex];    //куда вставляем
            }else{
                $Y = &$Y[$ex];       //куда вставляем
            }
        }
        return $Y;
    }               //ПОЛУЧИТЬ ССЫЛКУ НА ЗАМЕНУ ПЕРЕМЕННОЙ
        protected function preobrazovatel_arr($TEST){
        $ZAMENY = [];                                   //элементы которые уже проработали и на что заменять
        #ПЕРЕБИРАЕМ ЭЛЕМЕНТЫ МАССИВА
        foreach ($TEST as $BUY=>$STRATEGY) {          //перебираем
            if($BUY!='BUY' && $BUY!='SELL') continue;       //если не надо это перебирать
            if(empty($BUY)) continue;                       //если массив пустой
            foreach ($STRATEGY as $str=>$opt){              //перебираем СТРАТЕГИИ
                foreach ($opt as $key=>$val){           //тут конечные опции стратегии str=... stop=...
                    #ПОЛУЧИЛИ УНИКАЛЬНЫЙ АДРЕС + добавили его
                    $ADDRES = $BUY.':'.$str.':'.$key;       //сформировали адрес
                    #ПРОГОНЯЕМ ЧЕРЕЗ ПРЕОБРАЗОВАТЕЛЬ
                    if(is_array($val)){
                        foreach ($val as $X=>$Y){
                            $res = $this->preobrazovatel_str($Y);
                            if(count($res)==1) continue;        //это если заменять нечего
                            $ZAMENY[$ADDRES.':'.$X] = $res;              //добавили
                        }
                    }else{
                        $res = $this->preobrazovatel_str($val);
                        if(count($res)==1) continue;        //это если заменять нечего
                        $ZAMENY[$ADDRES] = $res;              //добавили
                    }
                }
            }
        }
//        dd($ZAMENY);
        #ТЕПЕРЬ ЗАМЕНЯЕМ ЗАМЕНЫ
        $result = [$TEST];
        if(!empty($ZAMENY)){
            $DA = 0;
            foreach ($ZAMENY as $STR=>$NEW_STR){
                $LIST = [];
                //        $NEW_STR = [5,47];        - на что менять
                //        $STR = 'BUY:XAXA:DADA';   - где менять
                #ПЕРЕБИРАЕМ $RESULT
                for($i=0;$i<count($result);$i++){           //перебираем стратегии
                    $TEST = $result[$i];
                    #ПЕРЕБИРАЕМ СПИСОК НОВЫХ ЗАМЕН
                    for($k=0;$k<count($NEW_STR);$k++){          //перебираем замены
                        $e = $NEW_STR[$k];         //тут новое значение
                        #ТЕПЕРЬ ПОЛУЧАЕМ ССЫЛКУ КУДА ДЕЛАЕМ ЗАМЕНУ
                        $Y = &$this->getLINK($STR,$TEST);
                        #ДЕЛАЕМ ЗАМЕНУ ЗНАЧЕНИЯ
                        $Y = $e;
                        #ДОБАВЛЯЕМ НОВУЮ ФИШКУ В МАССИВ
                        $LIST[] = $TEST;            //записали новые фишки - т.е заменили
                        unset($Y);      //удаляем ссылку
                    }
                }
                $result = $LIST;
                $DA++;
            }
        }
        return $result;
    }
        protected function replace_strategy($new,$list){
        foreach ($list as $KEY=>$VAL){
            #ЕСТЬ ЛИ НЕТУ ТАКОГО КЛЮЧА В ИСХОДНИКЕ!
            if(!isset($new[$KEY])){     //нету ключа
                $new[$KEY] = $VAL;
            }else{          //если есть ключ
                #ЕСЛИ ЭТО НЕ МАССИВ
                if(!is_array($VAL)){
                    $new[$KEY] = $VAL;
                }else{
                    $new[$KEY] = $this->replace_strategy($new[$KEY],$VAL);
                }
            }
        }
        return $new;
    }       //ЗАМЕНА КОНТЕНТА ИЗ ОДНОЙ СТРАТЕГИИ В ДРУГУЮ - по факту перемножение!
}