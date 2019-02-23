<?php

namespace Brocker;

use Lib\DB;

class StackParent{
    protected $_hash;
    protected $_sreda;
    protected $_account;
    protected $_SERVER;
    protected $_WALLET;
    protected $_CONFIG;

    protected $STACK = false;
    protected $SYNC = [];

    //==================
    public function __construct($_hash) {
        Brocker::$_hash_data[$_hash]['_STACK'] = $this;
        $this->_SERVER = &Brocker::$_hash_data[$_hash]['_SERVER'];
        $this->_CONFIG = &Brocker::$_hash_data[$_hash]['_CONFIG'];

        $this->_hash = $_hash;
        $this->_sreda = &Brocker::$_hash_data[$_hash]['_sreda'];
        $this->_account = &Brocker::$_hash_data[$_hash]['_account'];
    }


    public function init($sync=[]): bool {
        if(!empty($sync)){
            $x1 = true;
            if(in_array('stack',$sync)){
                $x1 = $this->syncSatck();
            }
            if(!$x1) return false;      //если что-то не синхронизировалось
        }
        if($this->STACK) return true;
        return false;
    }
    public function getStackAnalize($coin=''){
        dd($coin);
        //$BUF = new \Buffer(TEMP,'STACK');                               //НАДО УДАЛИТЬ! ЗАГЛУШКА!
        //$this->STACK = $BUF->get('STACK');

        if($this->STACK===false){
            dd('Стека нету!');
        }
        if(!$coin){
            return $this->STACK;
        }else{
            if(isset($this->STACK[$coin])){
                return $this->STACK[$coin];
            }else{
                return false;
            }
        }
    }
    public function getStatusSync(){
        return $this->SYNC;
    }

    protected function syncSatck(): bool {
        $SERVER = $this->_SERVER->repeat_collect('getmarketsummaries');
        $this->preobrazovat_stack($SERVER);        //преобразовали ОТВЕТ
        if($SERVER===false){
            $this->MARKET = false;
            return false;
        }

        if(empty($SERVER)) dd('Что-то херня какая-то почему-то пусто');
        $this->STACK = [];
        foreach ($SERVER as $COIN=>$VAL){
            //if($COIN!='BTC-2GIVE') continue;              ..ЗАГЛУШКА
            $insert = [
                ':high'         => $VAL['high'],
                ':low'          => $VAL['low'],
                ':volume'       => $VAL['volume'],
                ':base_volume'  => $VAL['base_volume'],
                ':last'         => $VAL['last'],
                ':sell'         => $VAL['sell'],
                ':buy'          => $VAL['buy'],
                ':buy_orders'   => $VAL['buy_orders'],
                ':sell_orders'  => $VAL['sell_orders'],
                ':dates'        => $VAL['dates'],
            ];
            $sql = "INSERT INTO `".PREFIX_DB.$COIN."` (`high`,`low`,`volume`,`base_volume`,`last`,`sell`,`buy`,`buy_orders`,`sell_orders`,`dates`) VALUES :key";
            $num = [
                ':key'  => $insert
            ];
            $DB = DB::insert($sql,$num);
            if(isset($DB['error'])) {    //если нету такой таблицы
                if($DB['error']=='not table'){      //такой записи ещё нету
                    $create = $this->createTableStack($COIN);
                    if(!isset($create['error'])){
                        $DB = DB::insert($sql,$num);
                        $this->STACK[$COIN] = $VAL;
                    }
                }else{      //типо уже такая запись есть
                    unset($SERVER[$COIN]);      //удаляем запись, т.к не вставили ее
                }
            }else{
                $this->STACK[$COIN] = $VAL;
            }
        }
//        $this->STACK = $SERVER;
        $this->SYNC['STACK'] = 1;      //мол синхронизировали
        Brocker::$coin_add = count($this->STACK);
        return true;
    }                       //СИНХРОНИЗАЦИЯ
    protected function createTableStack($COIN){          //создать таблицу если ее нету
        $sql = "CREATE TABLE `".PREFIX_DB.$COIN."` (
                   dates INT(10) UNSIGNED NOT NULL UNIQUE KEY,
                   high DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   low DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   volume DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   base_volume DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   last DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   sell DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   buy DECIMAL(".TO4NOST.") UNSIGNED default NULL,
                   buy_orders INT(7) UNSIGNED default NULL,
                   sell_orders INT(7) UNSIGNED default NULL
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
//                    ALTER TABLE `".PREFIX_DB.$COIN."` ADD INDEX(`buy_orders`)";
        return DB::query($sql);   //создали
    }                    //Создать пустую таблицу в БВ

    ##########################
    # ОБРАБОТКА ДЫННЫХ ИЗ БД #
    ####################################################################################################################
    /**
     * ШПАРГАЛКА
     * [5]  = 5/M10             — прото группировка
     * [t]  = t/M10/H1          — для графиков, где H1 предел
     * [s]  = s/M10 M20 M30     — просто группировка от предыдущего
     * [p]  = p/M10 M20 M30     — группировка по точкам от начала
     *
     * ОПЦИИ
     * [block]  — представить в блочном режиме      def=0
     * [name]   — показать имя блока M1,M2..        def=0
     * [dop]    — посчитать дополнительно RAZ+PROC  def=0
     */
    public function enter($coin,$strategy,$date='now',$debug=false){
        if(empty($strategy)) dd('НЕ передан параметр <b><u>$strategy</u></b> для STACK::enter()');
        #ПОЛУЧАЕМ СТАТУС МОНЕТЫ
        $status = $this->MARKETS->get($coin);             //получили тип монеты - BUY/SELL
        //ЕСЛИ ДАННЫХ ИЗ БД НЕТУ --> ЗАПОЛНЯЕМ МАССИВЫ ПУСТЫШКАМИ
        if(!$status){
            $new_d = [];
            foreach ($strategy as $GOUP_K=>$GROUP_V){
                $new_d[$GOUP_K] = '';
            }
            $new_d['error'] = 'Запрашиваемая монета не существует в БД: '.$coin;
            new myError('Запрашиваемая монета не существует в БД - ENTER',['$coin'=>$coin,'$strategy'=>$strategy,'$date'=>$date,'$status'=>$status]);
            return $new_d;
        }
        $status = $status['status'];
        if(!$status){
            new myError('У монеты нету статуса... ENTER',['$coin'=>$coin,'$strategy'=>$strategy,'$date'=>$date,'$status'=>$status]);
            return [];
        }

        #ПРЕОБРАЗУЕМ $strategy В $data[group] + МЕТАДАННЫЕ + ПОЛУЧАЕМ МАКСИМАЛЬНУЮ ДЛИНУ ПОЛУЧАЕММЫХ ДАННЫХ —> $data
        $x2 = getTime($date);     //готовим дату X2 - от которой будем считать
        $data = [
            'x1' => 99999999999999,
            'x2' => $x2,
        ];
        $opts = [];         //скелет маски


        #ФОРМИРУЕМ СКЕЛЕТНУЮ-ВРЕМЕНУЮ МАСКУ ДЛЯ БЛОКОВ
        foreach ($strategy as $k=>$v){          //пробегаемся по стратегии $k=>[line,block,stat..]  $v=>[[0]=>5/M30,[1]=>dop,block]
            $x = [];
            $x['block']['v'] = 0;
            $opts[$k] = [                    //значения по умолчанию
                'block' =>'line',        //по какому принципу группируем
                'dop'   =>0,               //нужна ли доп инфа?
                'name'  =>0,               //нужно ли выводить имя блока?
            ];
            $opt = &$opts[$k];
            if(isset($v[1]) && !empty($v[1])){  //перезапиываем св-ва
                //                dd($v[1]);
                foreach ($v[1] as $key=>$val){
                    if($key=='block'){
                        $opt[$key] = ($val)?$key:$opt[$key];
                    }elseif ($key=='dop'){
                        $opt[$key] = ($val)?$val:$opt[$key];
                    }elseif ($key=='name'){
                        $opt[$key] = ($val)?$val:$opt[$key];
                    }
                    if(is_string($val)) $opt[$key] = $val;
                }
            }
            $blocks = explode('/',trim($v[0]));
            $group = explode(' ',$blocks[1]);       //группировка
            $type = $blocks[0];                              //тип группировки t,s,p
            $tt = [$x2];
            $dop = getTime('M20');   //дополнительная выборка для точности
            $steep = getTime($group[0]);
            $opt['mask_name'] = [];
            $mask_name = &$opt['mask_name'];
            if(is_numeric($type)){   //это тип '5/M5'
                if($opt['block']=='block'){     //для блока добавляем брейкпоинт
                    $xlast = round_time($x2,$group[0]);
                    if($xlast!=$x2){
                        $tt[] = $xlast;
                        $mask_name[] = 'dop';
                    }
                }
                for($i=1;$i<=$type;$i++){
                    $tt[] = $tt[count($tt)-1]-$steep;
                    $mask_name[] = $group[0];
                }
            }elseif($type=='t'){     //это тип 't/M5',0,'D5'    — конкретный промежуток времени с определенной группировкой
                if($opt['block']=='block'){     //для блока добавляем брейкпоинт
                    $xlast = round_time($x2,$group[0]);
                    if($xlast!=$x2){
                        $tt[] = $xlast;
                        $mask_name[] = 'dop';
                    }
                }
                $last = $tt[count($tt)-1]-getTime($blocks[2]);       //последняя точка
                while($tt[count($tt)-1]>$last){
                    $tt[] = $tt[count($tt)-1]-getTime($group[0]);
                    $mask_name[] = $group[0];
                }
            }elseif($type=='p'){     //это тип 'p/M5 M10 M15'    — конкретные break points
                if($opt['block']=='block'){     //для блока добавляем брейкпоинт
                    $xlast = round_time($x2,$group[0]);
                    if($xlast!=$x2){
                        $tt[] = $xlast;
                        $mask_name[] = 'dop';
                    }
                }else{
                    $xlast = $x2;
                }
                for($i=0,$c=count($group);$i<$c;$i++){
                    $tt[] = $xlast-pTime($group[$i]);
                    $mask_name[] = $group[$i];
                }
                //                dd($tt);
            }elseif($type=='s'){     //это тип 's/M5 M10 M15'    — поледовательность блоков
                if($opt['block']=='block'){     //для блока добавляем брейкпоинт
                    $xlast = round_time($x2,$group[0]);
                    if($xlast!=$x2){
                        $tt[] = $xlast;
                        $mask_name[] = 'dop';
                    }
                }
                for($i=0,$c=count($group);$i<$c;$i++){
                    $tt[] = $tt[count($tt)-1]-pTime($group[$i]);
                    $mask_name[] = $group[$i];
                }
            }else{
                return false;
            }
            if($tt[count($tt)-1]<$data['x1']) $data['x1'] = $tt[count($tt)-1]; //определяем предел до куда вытаскивать из БД
            $data['group'][$k] = $tt;
        }
        $data['x1'] -= $dop;    //вытаскиваем дополнительные поля для точности
        #ПОЛУЧАЕМ МАСИМАЛЬНЫЕ ДАННЫЕ ИЗ БД, УДОВЛЕТВОРЯЮЩИЕ ВСЕ $opt —> $stack
        $stack = $this->getStack($coin,['x1'=>$data['x1'],'x2'=>$data['x2']],"time_id,{$status}_price,{$status}_price_min,{$status}_price_max,{$status}_price_last");
        //        if($debug) dd($stack);
        //ЕСЛИ ДАННЫХ ИЗ БД НЕТУ --> ЗАПОЛНЯЕМ МАССИВЫ ПУСТЫШКАМИ
        if(isset($stack['error'])){
            $new_d = [];
            foreach ($data['group'] as $GOUP_K=>$GROUP_V){
                $new_d[$GOUP_K] = '';
            }
            $new_d['error'] = 'Отсутствует таблица для: '.$coin;
            return $new_d;
        }
        //        if($debug) dd($stack);
        if(empty($stack)){
            $new_d = [];
            foreach ($data['group'] as $GOUP_K=>$GROUP_V){
                $new_d[$GOUP_K] = '';
            }
            $new_d['error'] = 'STACK пустой на этом промежутке для: '.$coin;
            return $new_d;
        }

        #ДОПИСАЛИ ВРЕМЯ ДЛЯ ПОНИМАНИЯ? [DEL]
        if(!empty($stack)){
            foreach($stack as $k=>$v){
                $stack[$k]['tt'] = date(DATE_FORMAT,$v['time_id']);
            }
        }

        #3 живых сущности - $strategy — $stack — $data — $opts — $opt
        //        if($debug) dd($strategy);
        #ПЕРЕБИРАЕМ СТЕК И ЗАПОЛНЯЕМ БЛОКИ СТЕКОМ + ОПТИМИЗАЦИЯ


        foreach ($data['group'] as $k=>$v){                     //$k=>gragic   $v=>[1527084360,1527084360,...]
            $opt = &$opts[$k];                          //скелет
            $type = $strategy[$k][0][0];                //тип стратегии - p,s,num,p - см ШПАРГАЛКУ - Берем 0 символ
            $xa = [];                                   // переменная которая идет на конвеер - с ней будем работать

            #ПРЕДВАРИТЕЛЬНЫЕ РАБОТЫ - для блокок впрописываем начало и конеч + [tt] время - все включительно
            for($i=0,$c=count($v);$i<$c;$i++){
                if($i==$c-1) continue;  //если последний элемент
                if($opt['name']) $xa[$i]['name'] = $opt['mask_name'][$i];
                $xa[$i]['x1'] = $v[$i+1];
                if($type=='p'){
                    $xa[$i]['x2'] = $v[0];
                }else{
                    $xa[$i]['x2'] = $v[$i];
                }
                $xa[$i]['time'] = date(DATE_FORMAT,$xa[$i]['x1']).' — '.date(DATE_FORMAT,$xa[$i]['x2']);
            }                   //формируем блоки перед заполнением

            //            if($debug) dd($stack);
            #ДОПИСЫВАЕМ НЕДОСТАЮЩИЕ ВРЕМЕННЫЕ ТОЧКИ + ГРУППИРОВКА ПО ВРЕМЕНИ
            $in_key = false;            //ключ - чтобы понять были ли хоть какие-то данные

            for($s=count($stack)-1;$s>=0;$s--){        //заполняем блоки стеками
                if($stack[$s][$status.'_price']>0){
                    for($i=0,$c=count($xa);$i<$c;$i++){
                        //                        if($debug) echo date(DATE_FORMAT,$stack[$s]['time_id']).' = ';
                        //                        if($debug) echo date(DATE_FORMAT,$xa[$i]['x1'])."\n";
                        if($stack[$s]['time_id']>$xa[$i]['x1'] && $stack[$s]['time_id']<=$xa[$i]['x2']){            //не проходит
                            $in_key = true;
                            //                            if($debug) dd(21111111111111111111111111111111);
                            $new = [
                                'time_id'   => $stack[$s]['time_id'],
                                'y0'        => (isset($stack[$s]['BUY_price']))?$stack[$s]['BUY_price']:$stack[$s]['SELL_price'],
                                'y2'        => (isset($stack[$s]['BUY_price_last']))?$stack[$s]['BUY_price_last']:$stack[$s]['SELL_price_last'],
                                'min'        => (isset($stack[$s]['BUY_price_min']))?$stack[$s]['BUY_price_min']:$stack[$s]['SELL_price_min'],
                                'max'        => (isset($stack[$s]['BUY_price_max']))?$stack[$s]['BUY_price_max']:$stack[$s]['SELL_price_max'],
                            ];
                            if($type=='p'){
                                $new['id'] = $s;
                            }
                            $xa[$i]['b'][] = $new;
                            //                            if($debug) dd($xa);
                        }
                    }
                }
            }
            if(!$in_key){
                $data['group'][$k] = '';
                continue;
            }


            #ЕСЛИ ЭТО ГРАФИК - ТО ДОПИСЫВАЕМ ПОСЛЕДНЮЮ ТОЧКУ
            if($type=='p'){                 //дописываем y0 для type==p
                for($i=0,$c=count($xa);$i<$c;$i++){
                    if(isset($xa[$i]['b'])){
                        $l = count($xa[$i]['b']);
                        $last_id = $xa[$i]['b'][$l-1]['id'];
                        $num = $this->getLast($stack,$last_id,'up');
                        if($num===false) $num = $this->getLast($stack,$last_id,'down');
                        if($num===false) return false;  //вернул ID
                        $new = [
                            'time_id'   => $stack[$num]['time_id'],
                            'y0'        => (isset($stack[$num]['BUY_price']))?$stack[$num]['BUY_price']:$stack[$num]['SELL_price'],
                            'y2'        => (isset($stack[$num]['BUY_price_last']))?$stack[$num]['BUY_price_last']:$stack[$num]['SELL_price_last'],
                            'min'        => (isset($stack[$num]['BUY_price_min']))?$stack[$num]['BUY_price_min']:$stack[$num]['SELL_price_min'],
                            'max'        => (isset($stack[$num]['BUY_price_max']))?$stack[$num]['BUY_price_max']:$stack[$num]['SELL_price_max'],
                        ];
                        $xa[$i]['b'][$l] = $new;
                        $xa[$i]['y1'] = $new['y2'];
                    }
                }
            }

            #СУММИРУЕМ БЛОКИ
            for ($b=0;$b<$c;$b++){
                $this->summaBlocks($xa[$b]);         //суммируем блоки
            }
            //            if($debug) dd($xa);

            #ИЗБАВЛЯЕМСЯ ОТ НУЛЕВЫХ ЗНАЧЕНИЙ
            for($i=0,$c=count($xa);$i<$c;$i++) {
                $e = &$xa[$i];
                if ($e['y2'] == 0) {
                    $num = $this->getNotNull_y1($xa, $i, 'y1', 'up');
                    if ($num !== false) {
                        $e['y2'] = $xa[$num]['y1'];
                    } else {
                        $num = $this->getNotNull_y1($xa, $i, 'y2', 'up');
                        if ($num !== false) {
                            $e['y2'] = $xa[$num]['y2'];
                        } else {
                            $num = $this->getNotNull_y1($xa, $i, 'y2');
                            if ($num !== false){
                                $e['y2'] = $xa[$num]['y2'];
                            }else{
                                $xa = [];
                            }
                        }
                    }
                    //                    if(!$y2) $y2 = $e['y2'];      //только для опр типа - p
                }
                if (!isset($e['y1']) || $e['y1'] == 0) {
                    $num = $this->getNotNull_y1($xa, $i, 'y2');
                    if ($num !== false) {
                        $e['y1'] = $xa[$num]['y2'];
                    } else {
                        $e['y1'] = $e['y2'];
                        //                        $e['min'] = $e['y2'];
                        //                        $e['max'] = $e['y2'];
                        //                        $e['y0'] = $e['y2'];
                    }
                    if ($e['y1'] < $e['min']){
                        $e['min'] = $e['y1'];
                        //                        $e['y0'] = number_format(($e['y2']+$e['y1'])/2,8);
                    }
                    if ($e['y1'] > $e['max']){
                        $e['max'] = $e['y1'];
                        //                        $e['y0'] = number_format(($e['y2']+$e['y1'])/2,8);
                    }
                }
                if($e['y0']==0){
                    if($e['y1']!=0 && $e['y1']==$e['y2']){
                        $e['y0'] = $e['y1'];
                        $e['min'] = $e['y1'];
                        $e['max'] = $e['y1'];
                    }else{
                        $e['y0'] = number_format(($e['y2']+$e['y1'])/2,8);
                        $e['min'] = min($e['y1'],$e['y2']);
                        $e['max'] = max($e['y1'],$e['y2']);
                    }
                }
                if($i==$c-1 && $type=='t'){         //дописываем для графика - дополнительный блок
                    $xa[] = [
                        'x2' => $xa[$i]['x1'],
                        'y2' => $xa[$i]['y1'],
                    ];
                }
            }

            //ПОДСЧЕТ RAZNICA И PROC
            if($opt['dop']){
                for($i=0,$c=count($xa);$i<$c;$i++) {
                    if(isset($xa[$i]['y1'])){
                        $xa[$i]['raz'] = round((($xa[$i]['y2']*100)/$xa[$i]['y1'])-100,2);
                        $xa[$i]['razX'] = round((($xa[0]['y2']*100)/$xa[$i]['y1'])-100,2);
                    }
                    if(isset($xa[$i+1]['y0'])){
                        $xa[$i]['proc'] = round((($xa[$i]['y0']*100)/$xa[$i+1]['y0'])-100,2);
                        $xa[$i]['procX'] = round((($xa[0]['y0']*100)/$xa[$i+1]['y0'])-100,2);
                    }
                }
            }


            #ПЕРЕЗАПИСАЛИ НОВЫМИ ДАННЫМИ
            $data['group'][$k] = $xa;
        }

        #ДОПИСЫВАЕМ ОБЩИЕ ПРОМЕЖУТКИ ПОЛУЧЕННОГО СТЕКА СТЕКА !!!!!! Информационный запрос
        if(count($stack)>1){
            $_zapros = [
                'x1'=>$data['x1'],
                'x2'=>$data['x2'],
                'time'=>date(DATE_FORMAT,$data['x1']).' — '.date(DATE_FORMAT,$data['x2'])
            ];
            $_stack = [
                'x1'=>$stack[0]['time_id'],
                'x2'=>$stack[count($stack)-1]['time_id'],
                'time'=>date(DATE_FORMAT,$stack[0]['time_id']).' — '.date(DATE_FORMAT,$stack[count($stack)-1]['time_id'])
            ];
            $data['group']['_timeinfo'] = ['zapros'=>$_zapros,'stack'=>$_stack];
        }
        return $data['group'];
    }
        protected function getStack1($coin,$time,$sql='*'){                     //получить монету за определенный промежуток времениы
            $sql = "SELECT {$sql} FROM `".$this->PREFIX.$coin."` WHERE time_id>={$time['x1']} AND time_id<={$time['x2']}";$num = array();
            $r = $this->DB->select($sql,$num,1);
            if(is_string($r) || empty($r)) return false; //типо была ошибка
            return $r;
        }                    //получить данные из БД
        protected function summaBlocks(&$b){
            $b['y0']  = 0;
            //        $b['y1']  = 0;
            $b['y2']  = 0;
            $b['min'] = 0;
            $b['max'] = 0;
            $size = 0;
            if(isset($b['b'])){
                $c=count($b['b']);
                if($c==1){
                    $b['y0'] = $b['b'][0]['y0'];
                    $b['y2'] = $b['b'][0]['y2'];
                    $b['min'] = $b['b'][0]['min'];
                    $b['max'] = $b['b'][0]['max'];
                }else{
                    for($i=0;$i<$c;$i++){       //перебираем внутрение элементы
                        $elem = &$b['b'][$i];                      //внутренний элемент который проверяем и SUM
                        if($elem['y0']>0){   //если есть что записать из этого блока
                            $b['y0'] += $elem['y0'];
                            if($b['y2']==0) {           //запиываем первое попавщееся значние для блока в y2 - last price
                                $b['y2'] = $elem['y2'];
                            }
                            if($b['min']==0){                       //записали Min
                                $b['min'] = $elem['min'];
                            }elseif($b['min']!=0 && $elem['min']<$b['min']){
                                $b['min'] = $elem['min'];
                            }
                            if($elem['max']>$b['max']){             //записали Max
                                $b['max'] = $elem['max'];
                            }
                            $size++;
                        }
                        if($i==$c-1){    //ОФОРМЛЕНИЕ - если последний элемент
                            if($size) $b['y0'] = number_format($b['y0']/$size,8,'.','');
                        }
                    }
                }
                unset($b['b']);
            }
        }                                  //суммируем блоки
        protected function getLast(&$stack,$last_id,$type='up'){       //вернул ID первого попавщегося элемента
            if(isset($stack[$last_id]['BUY_price_last'])){
                $T = 'BUY';
            }else{
                $T = 'SELL';
            }
            if($type=='up'){
                for($s=$last_id-1;$s>=0;$s--){
                    if(isset($stack[$s]) && $stack[$s][$T.'_price_last']>0){
                        return $s;
                    }
                }
            }else{
                $count = count($stack);
                for($s=$last_id+1;$s<=$count;$s++){
                    if(isset($stack[$s]) && $stack[$s][$T.'_price_last']>0){
                        return $s;
                    }
                }
            }
            return false;
        }              //для ENTER и для type==p ищем y1
        protected function getNotNull_y1(&$stack,$i,$box,$type='down'){
        if($type=='down')   $i++;
        else                $i--;
        if(!isset($stack[$i])) return false;
        if($stack[$i][$box]==0){
            return $this->getNotNull_y1($stack,$i,$box,$type);
        }else{
            return $i;
        }
    }       //показать не нулевой соседний блок
}