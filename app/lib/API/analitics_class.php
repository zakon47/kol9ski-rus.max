<?php



#ШАБЛОНЫ ВЫБОРОК ИЗ БД
$TEMPLATE = [
    'BUY'   =>[
        'BUY_UGOL'  =>[
            'opt'   => ['dop'=>1],
            'str'   => 's/M[1-5,1] M30 H1',
            'ugol'  => '[5-15,1]'
        ]
    ],
    'SELL'  => [
        'LOSS'  => [
            'opt'   => ['dop'=>1],
            'str'   => '3/M5',
            'stop'  => '[3-10,1]',
        ]
    ]
];


/*
 * Класс для работы с аналитикой стратегий
 */
class Analitics{
    private $DB;
    private $STACK;
    private $WALLET;
    private $MARKETS;
    public function __construct(){
        global $DB;
        $this->DB = $DB;
        global $STACK;
        $this->STACK = $STACK;
        global $WALLET;
        $this->WALLET = $WALLET;
        global $MARKETS;
        $this->MARKETS = $MARKETS;
    }

    /**
     * ТОЛЬКО ПОЛУЧАЕМ АНАЛИЗ БД относительно определенного времени — Создает дефолтную стратегию онлай — Меняем статус снаружи
     * @param $coin
     * @param string $date - точка времени к которой применяем анализ
     * @param string $status [не обязательно]
     * @return array
     *
     * ШПОРА (код статусу)
     * 0 - Была ошибка
     * 1 - Все ОК
     * BUY/SELL - произвели операцию
     *
     * @DESC
     * 1) ПОДГОТОВИЛИ ВРЕМЯ
     * 2) ПОЛУЧИЛИ МАРКЕР И ВСЮ ЕГО ИНФУ
     * 3) ПРОВЕРЯЕМ МАРКЕР НА НАЛИЧИЕ  - return
     * 4) ПРОВЕРЯЕМ НА НАЛИЧИЕ ОТКРЫТОГО ОРДЕРА - return
     * 5) Получили СКЕЛЕТ-стратегию ($DATA) из маркера или ИСПОЛЬЗУЕМ ПЕРЕДАННЫЙ
     * 6) Запустили АНАЛИЗ -> BUY/SELL от статуса - return РЕЗУЛЬТАТ
     */
    public function go($coin,$date='now',$DATA=''){
        #ПОДГОТОВИЛИ ВРЕМЯ
        if($date=='now') $date = time();            //временая точка относительно, которой мы ведем отсчет
        else $date = getTime($date);

        #ПОЛУЧИЛИ МАРКЕР И ВСЮ ЕГО ИНФУ
        $market = $this->MARKETS->get($coin);

        #ПРОВЕРЯЕМ МАРКЕР НА НАЛИЧИЕ
        if(!isset($market) || empty($market)){
            return ['status'=>0,'msg'=>'Монета '.$coin.' отсутствует в списке MARKETS'];
        }

        #ПОЛУЧИЛИ СКЕЛЕТ ($DATA) — СТРАТЕГИЮ ЕСЛИ НЕ БЫЛО ПЕРЕДАНО СВОЕЙ
        if(empty($DATA)){
            $DATA = $this->getStrategy($coin,$date);            //после этого момента $markets не актуален
        }
        $DATA['date'] = $date;      //указали временую точку АНАЛИЗА
//        dd($DATA);
        #ЗАПУСТИЛИ АНАЛИЗ
//        $DATA['status'] = 'BUY';
//        if(!isset($DATA['status'])){
//            dd($DATA);
//        }
        if($DATA['status']=='BUY'){
            return $this->anal_BUY($DATA);
        }elseif($DATA['status']=='SELL'){
            return $this->anal_SELL($DATA);
        }else{
            new myError('Беда со статусом монеты..',['coin'=>$coin,'status'=>$DATA['status']]);
            return ['status'=>0,'msg'=>'Беда со статусом монеты..'];
        }
    }

    ######################################## ЯДРО ##############################################
    /**
     * Получает текущую и шаблонную стратегию
     * @param $strategy - текущая стратегия
     * @param $tpl      - шаблон стратегий с промежутками
     * @param $elements - элементы для которых надо применить/обновить стратегию
     * @return array    - массив возможных вариантов стратегий
     */
    public function generateStrategy($cur_strategy,$tpl='',$elements=[]){
        $strategy = $cur_strategy['strategy'];
        global $TEMPLATE;
        if(empty($tpl)) $tpl = $TEMPLATE;
        $new_gen = $this->preobrazovatel_arr($tpl);
        $new_strategy = [];
        for ($i=0;$i<count($new_gen);$i++){
            $x = $cur_strategy;
            $x['strategy'] = $this->replace_strategy($strategy,$new_gen[$i]);
            $new_strategy[] = $x;
        }
        return $new_strategy;
    }

    /**
     * ЗАМЕНА КОНТЕНТА ИЗ ОДНОЙ СТРАТЕГИИ В ДРУГУЮ - по факту перемножение!
     * @param $new          - текущая стратегия
     * @param $list         - новая сгенерированная стретегия
     * @return mixed
     */
    public function replace_strategy($new,$list){
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
    }

    /**
     * ПОЛУЧИТЬ ОБЪЕКТ СТРАТЕГИИ ДЛЯ ОПРЕДЕЛЕННОЙ МОНЕТЫ ИЗ БД :: Типо скелет - если нету - то создаем
     * @param $coin - BTC-BCC
     * @return $DATA
     *
     * @DESC
     * 1) ПОЛУЧАЕМ ВСЮ ИНФУ ИЗ МАРКЕТА
     * 2) ПОЛУЧАЕМ РАСКОДИРОВАННУЮ СТРАТЕГИЮ - ЕСЛИ НЕТУ СТРАТЕГИИ - ТО СОЗДАЕМ ЕЕ
     *      ЕСЛИ НЕТУ -> создаем через $this->createStrategy($coin)
     *      ЕСТЬ -> декодируем json_decode($market['strategy'],1)
     * 3) Если в итоге ПУСТО $DATA['strategy'] -> вызываем ошибку и записываем NULL
     */
    public function getStrategy($coin,$t=false){
//        if($t) dd(1);
        $market = $this->MARKETS->get($coin);       //получили маркер и всю его инфу
        $DATA = [
            'coin'=>$coin,                  //монета
            'date'=>'',                     //точка времени к которой применяем анализ (записывается "выше")
            'status'=>$market['status']     //текущий статус
        ];  //готовим заготовку - типо "скелет"
        #ПОЛУЧАЕМ РАСКОДИРОВАННУЮ СТРАТЕГИЮ - ЕСЛИ НЕТУ СТРАТЕГИИ - ТО СОЗДАЕМ ЕЕ и ЗАПИСЫВАЕМ + получаем
        if(empty($market['strategy'])){             //если ещё нету стратегии
            $new_str = $this->createStrategy($coin);
            if(!isset($new_str['json'])) return ['status'=>0,'msg'=>'Почему-то была ошибка при создание новой стратегии: '.$coin];
            $DATA['strategy'] = json_decode($new_str['json'],1);
        }else{                      //если есть стратегия - просто вернуть ее
            $DATA['strategy'] = json_decode($market['strategy'],1);     //раскодировали
        }
        #ЕСЛИ ПРИ РАСКОДИРОВАНИЕ СТРАТЕГИИ БЫЛА ОШИБКА и вернули пустую строку - то стратегию затираем
        if(!$DATA['strategy']){
            $this->saveStrategy($coin,NULL);
            new myError('Почему-то была ошибка при раскодирование JSON',['coin'=>$coin,'strategy'=>$market['strategy']]);
            return ['status'=>0,'msg'=>'Почему-то была ошибка при раскодирование JSON'];
        }
        return $DATA;
    }
    /**
     * ПЕРЕЗАПИСЫВАЕМ ДЛЯ МОНЕТЫ НОВУЮ СТРАТЕГИЮ
     * @param $coin
     * @param $strategy [array] or [string]
     */
    private function saveStrategy($coin,$strategy){
        #ЕСЛИ ПЕРЕДАЛИ НЕ JSON а массив - то преобразуем его в JSON
        if(is_array($strategy)){
            $strategy_js = json_encode($strategy);
            if(empty($strategy_js)){
                new myError('При конвертации массива произошла ошибка..',['coin'=>$coin,'strategy'=>$strategy]);
                return 0;
            }
            $strategy = &$strategy_js;
        }
        $upd = '';      //проверям изменились ли значения - если изменили -> ОБНОВЛЯЕМ
        $num = array(':coin'=>$coin);

        $upd .= "`strategy`=:strategy,";
        $num[':strategy'] = $strategy;

        $upd = substr($upd,0,strlen($upd)-1);
        $sql = "UPDATE ".TABLE_NAME_MARKETS." SET $upd WHERE `market`=:coin";
        if($this->DB->update($sql,$num)){
            return $strategy;
        }else{
            return false;
        }
    }
    /**
     * ПОЛУЧИТЬ ПУСТУЮ ШАБЛОННУЮ СТРАТЕГИЮ
     * @param $price
     * @return array
     *
     * @DESC
     * 1) ПОЛУЧАЕМ АКТУАЛЬНУЮ ЦЕНУ С БИРЖИ - нижнюю цену, т.е BID,
     * 2) НА ФОНЕ ЭТОЙ ЦЕНЫ ФОРМИРУЕМ СТРАТЕГИЮ
     * 3) СОХРАНЯЕМ
     */
    private function createStrategy($coin){
        #ПОЛУЧАЕМ ПОСЛЕДНЮЮ ТОРГУЕМУ ЦЕНУ С БИРЖИ
        $price = $this->WALLET->getTicker($coin);
        if(!$price){
            new myError('Сервер вернул пустые актуальные цены...',['coin'=>$coin,'price'=>$price]);
            return ['status'=>0,'msg'=>'Сервер вернул пустые актуальные цены...'];
        }
        $strategy = [
            'temp'  => 1,               //если 1 - то эта стратегия сгенерирована по умолчанию
            'upd'   => time(),          //время последнего обновления стратегии
            'BUY'   => [],              //стратегии покупки
            'SELL'  => [
                'LOSS'  => [
                    'opt'   =>  ['dop'=>1],                 //параметры для ENTER
                    'str'   =>  '3/M10',                     //стратегия для ENTER
                    'stop'   =>  DEFAULT_LOSS_BOTTOM,       //актуальный процент СТОП_ЛОСС
                    'calc'   =>  $this->getStopLoss($price['SELL'],DEFAULT_LOSS_BOTTOM)       //получить КАЛЬКУЛЯЦИЮ СТОП-ЛОСС
                ]
            ]            //стратегии продажи
        ];
        #СОХРАНЯЕМ
        $res = $this->saveStrategy($coin,$strategy);
        if($res){
            return ['status'=>1,'msg'=>'Создали шаблон','json'=>$res];
        }else{
            return ['status'=>0,'msg'=>'При сохранение в БД произошла ошибка'];
        }
    }
    /**
     * ПОЛУЧАЕМ STOP-LOSS для данной цены и %процента%
     * @param $price - текущая цена
     * @param string $proc - процент стоплосса
     * @return array
     */
    protected function getStopLoss($price,$proc){
        $bottom = ((100-$proc)*$price)/100;
        return [
            'price'     => $price,                                                              //цена по которой была инициализация
            'stop'      => $proc,                                                               //процент инициализации
            'top'       => number_format($price,8,'.',''),      //изменяемая часть
            'bot'       => number_format($bottom,8,'.','')      //изменяемая часть
        ];
    }

    /**
     * Получаем из СТРАТЕГИИ (шаблона) - кучу стратегий - передаём - s/M[1-10,1] M2 M10
     * @param $tpl
     * @return array
     */
    public function preobrazovatel_str($tpl){
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
                'x1'    => $e[0][0],
                'x2'    => $e[0][1],
                's'     => $e[1],
                'r'     => []       //результат
            ];
            #РЕНДЕРИМ И ПОЛУЧАЕМ КОНЕЧНЫЕ ЗНАЧЕНИЯ - т.е вычисляем список из скобок
            for($t=$e['x1'];$t<=$e['x2'];$t+=$e['s']){
                $e['r'][] = $t;
            }
//            dd($e);
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
    }

    /**
     * ПОЛУЧИТЬ ССЫЛКУ НА ЗАМЕНУ ПЕРЕМЕННОЙ
     * @param $STR - строка 'BUY:XAXA:DADA';
     * @param $TEST - $result = [$TEST];
     * @return string - ссылку
     */
    public function &getLINK($STR,&$TEST){
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
    }
    public function preobrazovatel_arr($TEST){
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
                    $res = $this->preobrazovatel_str($val);
                    if(count($res)==1) continue;        //это если заменять нечего
                    $ZAMENY[$ADDRES] = $res;              //добавили
                }
            }
        }
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
    private function anal_BUY(&$DATA){
        #ПОЛУЧАЕМ СТРАТЕГИИ
        $stretagy = &$DATA['strategy']['BUY'];   //ссылка на BUY раздел
//        dd($stretagy);
        if(!empty($stretagy)){
            #СОБИРАЕМ ИНФУ ДЛЯ ENTER и записываем в $X
            $X = [];        //тут хранится объект для ENTER
            foreach ($stretagy as $K=>$V){      // $K=>BUY_UGOL,     $V=>Параметры
                if(empty($V['str'])) continue;  //если выборка отсутствует локально
                $X[$K] = [$V['str'],$V['opt']];
            }
            #ТЕПЕРЬ АНАЛИЗИРУЕМ
            $analize = $this->STACK->enter($DATA['coin'],$X,$DATA['date']);
//            dd($analize);
            if(!isset($analize['error'])){
                if(!isset($analize['_timeinfo'])) return ['status'=>1,'msg'=>'Отсутствует $analize[\'_timeinfo\']'];
                #ЕСЛИ ЕСТЬ ДАННЫЕ КОТОРЫЕ МОЖНО ПРОАНАЛИЗИРОВАТЬ + ДАННЫХ БОЛЬШЕ ЧЕМ ~ 3 дня
                $delta_day = $analize['_timeinfo']['stack']['x2']-$analize['_timeinfo']['stack']['x1'];     //дельта разницы для данных стека
//                dd($delta_day);
                if($delta_day>=GO_STACK_DAY){
                    #НУ ТЕПЕРЬ, СОБСТВЕННО, АНАЛИЗ МОНЕТЫ ПО СТРАТЕГИИ, data=$analize[name], cnf=$stretagy
                    $BUY = 0;       //если не было покупки
                    if(!$BUY && isset($stretagy['BUY_UGOL']) && !empty($analize['BUY_UGOL'])){
                        $BUY = $this->BUY_UGOL($stretagy['BUY_UGOL'],$analize['BUY_UGOL']);
                    }
                    #ЕСЛИ ПОСЛЕ ПРОХОДА ВСЕХ СТРАТЕГИЙ $BUY=true, то покупаем!
                    if($BUY){
//                        $loss = &$DATA['strategy']['SELL']['loss'];
                        return ['status'=>'BUY','msg'=>'КУПИЛИ','x2'=>$BUY['x2'],'y2'=>$BUY['y2'],'stretagy'=>$BUY['stretagy']];
                    }
                }
            }else{
                return ['status'=>1,'msg'=>$analize['error']];
            }
        }
        return ['status'=>1,'msg'=>'BUY —'];
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
    private function anal_SELL(&$DATA){
        $stretagy = &$DATA['strategy']['SELL'];
        #ПОЛУЧАЕМ НАШ LOSS
        if(isset($stretagy['LOSS'])){
            $LOSS = &$stretagy['LOSS'];     //наша стратегия

            #ПОЛУЧАЕМ ПОСЛЕДНЮЮ ЦЕНУ
            $X['stat'] = [$LOSS['str'],$LOSS['opt']];       //формируем запрос для ENTER
            $analize = $this->STACK->enter($DATA['coin'],$X,$DATA['date'])['stat'];

            #ЕСЛИ ЕСТЬ КАКАЯ_ТО ВЫБОРКА
            if(!empty($analize) && isset($analize[0]['y2']) && !empty($analize[0]['y2'])){
                $last_price = $analize[0]['y2'];        //последняя актуальная цена
//                $last_price = 0.00004038;
                $top = (float) $LOSS['calc']['top'];
                $bot = (float) $LOSS['calc']['bot'];
                #АНАЛИЗИРУЕМ СДВИГ ТЕКУЩЕЙ ЦЕНЫ
                if($last_price>$top){   //новая цена - выше верхней границы
                    #Получаем дельту и прибавляем ее к текущим показателям
                    $delta = $last_price-$LOSS['calc']['top'];
                    $LOSS['calc']['top'] += $delta;
                    $LOSS['calc']['bot'] += $delta;
                    #Небольшие преобразования
                    $LOSS['calc']['top'] = number_format($LOSS['calc']['top'],8,'.','');
                    $LOSS['calc']['bot'] = number_format($LOSS['calc']['bot'],8,'.','');
//                    $this->saveStrategy($DATA['coin'],$DATA['strategy']);
                    return ['status'=>'UPD','msg'=>'Обновить DATA->StopLoss','DATA'=>$DATA];
                }elseif($last_price<=$bot){       //ниже нижней границы
                    return ['status'=>'SELL','msg'=>'ПРОДАЛИ','x2'=>$analize[0]['x2'],'y2'=>$analize[0]['y2'],'LOSS'=>['top'=>$LOSS['calc']['top'],'bot'=>$LOSS['calc']['bot']]];
                }
            }
        }
        return ['status'=>1,'msg'=>'SELL —'];
    }
//$TEMP_STRETAGY = [
//    'BUY'   =>[
//        'BUY_UGOL'      => ['s/M[1-10,1] M2 M10',           ['dop'=>1]      ,['ugol'=>'[1-10,1]']],            //Резкий угол
//        'BUY_DA'        => ['s/M[1-2,1] D10',               ['block'=>1]    ,['x'=>5]],
//    ],
//    'SELL'  =>[
//        'LOSS'          => ['3/M5',                         ['dop'=>1]      ,['stop'=>'[1-10,1]']],
//    ]
//];






    ######################################## СТРАТЕГИИ ##############################################
    //Очень резкий угол подъема графика
    private function BUY_UGOL($param,$data){
//        dd($param,1);
        if($data[0]['raz']>=$param['ugol']){
            return ['x2'=>$data[0]['x2'],'y2'=>$data[0]['y2'],'stretagy'=>'BUY_UGOL'];
        }
        return 0;
    }


    ######################################## СИМУЛЯТОР ##############################################


    /**
     * @param $coin
     * @param $time
     * @param int $show_act
     * @param int $steep
     * @return array
     *
     * @DESC
     * 1)ПОЛУЧАЕМ ВРЕМЯ
     * 2)ПОЛУЧИЛИ СТРЕТЕГИЮ
     * 3)ПЕРЕБИРАЕМ ВРЕМЯ И ПОЛУЧАЕМ АНАЛИЗ ДЛЯ КАЖДОЙ ТОЧКИ
     *      ЕСЛИ БЫЛА ВЫПОЛНЕНА ОПЕРАЦИЯ
     * 4)ПОДСЧИТЫВАЕМ КПД
     */
    public function simulator2($coin,$time,$DATA='',$debug=0){
        $steep=60;
        #ПОЛУЧАЕМ ВРЕМЯ
        $x1 = getTime($time['x1']);
        $x2 = getTime($time['x2']);
        #ПОЛУЧИЛИ СТРАТЕГИЮ МОНЕТЫ
        if(empty($DATA)) $DATA = $this->getStrategy($coin);
//        if($debug) dd($DATA);
        #ЗАПИСАЛИ СВОЮ СТРАТЕГИЮ
        $DATA['strategy']['BUY']['BUY_UGOL'] = [
            'opt'   =>  ['dop'=>1],
            'str'   =>  's/M5 M30 H1',
            'ugol'  =>  5
        ];
//        $DATA['strategy']['SELL']['LOSS']['stop'] = 3;
//        $this->saveStrategy($coin,$DATA['strategy']);
//        dd($DATA);
//        if($debug) dd($DATA,1);
        if($DATA['status']=='SELL'){
            $DATA['status'] = 'BUY';
        }
//        if($debug) dd($DATA);
        #ЗАГОТОВКА для return
        $itog = [
            'count' => 0,
            'BUY'   => 0,
            'SELL'  => 0,
            'kpd'   => 0,
            'act'   => [],
        ];
//        return $DATA['strategy'];
//        dd($DATA['strategy']);
        #ПЕРЕБИРАЕМ ВРЕМЯ И ПОЛУЧАЕМ АНАЛИЗ ДЛЯ КАЖДОЙ ТОЧКИ
        for($i=$x1;$i<=$x2;$i+=$steep){     //Где $i - временая метка  19544845615
            $res = $this->go($coin,$i,$DATA);
            #ЕСЛИ БЫЛИ ПРАВКИ В $DATA — или — ЕСЛИ БЫЛА ВЫПОЛНЕНА ОПЕРАЦИЯ
            if($res['status']=='UPD'){              //поправили значения STOP_LOSS
                $DATA['strategy'] = $res['DATA']['strategy'];
            }elseif ($res['status']=='BUY' || $res['status']=='SELL'){  //SELL получается если GO вернул SELL!!!
//                dd($res,1);
//                dd($DATA);
                $LOSS = &$DATA['strategy']['SELL']['LOSS'];         //ссылка на стоп-лосс
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












    private function getLengthBlocks($block){
        $block = explode('/',$block);
        if(count($block)!=2) return false;
        $dop = 10; //10% от всего блока - для запаса
        $block[1] = pTime($block[1]);
        $block = [
            '0' => $block[0],
            '1' => $block[1],
            'itog' => $block[0]*$block[1],
            'dop' => $block[0]*$block[1],
        ];
        if($dop) $block['dop'] += ($dop*$block['itog'])/100;
        return $block;
    }                   //посчитать общее кол-во необзодимых минут для выборки из БД
    public function getCountBlock(&$stack,$time){
        $data = [];
        $otse4ka = $stack['o2']-$time;
        $key = 0;
        $n = 0;
        for($i=0,$c=count($stack['blocks']);$i<$c;$i++){
            $e = $stack['blocks'][$i];
            if($key){
                if($n==0){
                    $stack['x1'] = $e['x1'];
                    $stack['o1'] = $e['x1'];
                    $stack['time_o'] = date(DATE_FORMAT,$stack['o1']).' — '.date(DATE_FORMAT,$stack['o2']);
                    $stack['time_x'] = date(DATE_FORMAT,$stack['x1']).' — '.date(DATE_FORMAT,$stack['x2']);
                }
                $data[] = $e;
                $n++;
            }else{
                if($e['x1']<=$otse4ka && $e['x2']>=$otse4ka){
                    $key = 1;
                }elseif($e['x1']>=$otse4ka){
                    $key = 1;
                    if($n==0){
                        $stack['x1'] = $e['x1'];
                        $stack['o1'] = $e['x1'];
                        $stack['time_o'] = date(DATE_FORMAT,$stack['o1']).' — '.date(DATE_FORMAT,$stack['o2']);
                        $stack['time_x'] = date(DATE_FORMAT,$stack['x1']).' — '.date(DATE_FORMAT,$stack['x2']);
                    }
                    $data[] = $e;
                    $n++;
                }
            }
        }
        return $data;
    }               //обрезать блоки - по кол-ву 3,5...
    public function getXY(&$stack){
        $type = $stack['status'];
        $x = [];
        for($i=0,$c=count($stack['blocks']);$i<$c;$i++){
            $e = $stack['blocks'][$i];
            $x[] = [
                'x1'        =>$e['time_id']-$stack['steep'],
                'x2'        =>$e['time_id'],
                'y0'        =>$e[$type.'_price'],
                'y1'        =>'',
                'y2'        =>$e[$type.'_price_last'],
                'min'       =>$e[$type.'_price_min'],
                'max'       =>$e[$type.'_price_max']
            ];
        }
        for($i=0,$c=count($x);$i<$c;$i++){
            if($i!=0){
                $x[$i]['y1'] = $x[$i-1]['y2'];
                $x[$i]['raznica'] = round((($x[$i]['y2']*100)/$x[$i]['y1'])-100,2);
            }
            $x[$i]['time'] = date(DATE_FORMAT,$x[$i]['x1']).' — '.date(DATE_FORMAT,$x[$i]['x2']);
        }
        return $x;
    }
    public function getMask($coin,$block,$date='now'){
        $orderType = $this->MARKETS->get($coin)['status'];
        $date = getTime($date);
        $block = $this->getLengthBlocks($block);
        if(empty($block)) return false;  //не верно задана группировка
        $stack = $this->STACK->getPath($coin,$block['dop'],$block[1],$date);
        if(!$stack) return false;
        $stack['blocks'] = $this->getXY($stack);
        $stack['blocks'] = $this->getCountBlock($stack,$block['itog']);     //делаем выборку - только посл 5 блоков
        return $stack;
        dd($stack);

        $stack = $this->STACK->getStack($coin,interval($block['dop'],$date),'time_id,'.$orderType.'_price,'.$orderType.'_price_min,'.$orderType.'_price_max,'.$orderType.'_price_last');
        $stack = $this->STACK->groupStack($stack,$block[1]);
        $stack['status'] = $orderType;
        $stack['blocks'] = $this->STACK->summator($stack,1);
        $stack['blocks'] = $this->getCountBlock($stack,$block['itog']);     //делаем выборку - только посл 5 блоков
        return $stack;
    }
    public function getM(&$group,$raznica){
//        dd($group,1);
        if($group[0]['raznica']>=$raznica){
            if(isset($group[2]) && $group[2]['procX']>.5){
                return $group[0];
            }
        }
        return false;
    }


    //стратегии
    public function B_P(&$analize,$custom){
        if(!empty($analize['M10']) && $analize['M10'][0]['proc']>0 && $analize['M10'][1]['y0']<$analize['B_P'][0]['y2']){
            for($i=0;$i<$custom[1]['b']-1;$i++){
                if($analize['B_P'][$i][$custom[1]['t']]<$custom[1]['n']){
                    return false;
                }
            }
            return $analize['B_P'][0];
        }
        return false;
    }
    public function S_S(&$analize,$custom,&$stoploss,&$max_price){
        $elem = $analize[0];
        if($elem['y2']>$max_price){  //сдвигаем стоп-лосс если цена поднялась
            $stoploss += round($elem['y2']-$max_price,8);
            $max_price = $elem['y2'];
        }else{
            if($elem['y2']<=$stoploss){ //продаем если ниже стоп-лосса
                return $elem;
            }
        }
        return false;
    }
}