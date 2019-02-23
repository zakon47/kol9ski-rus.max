<?php
/*
 * Класс для работы со стеком
 */
class Stack_parent extends Collect{
    protected $DB;
    protected $CONFIG;
    protected $MARKETS;
    protected $PREFIX;
    //====================
    private $STACK;
    function __construct() {
        global $DB;
        $this->DB = &$DB;
        global $CONFIG;
        $this->CONFIG = &$CONFIG;
        global $MARKETS;
        $this->MARKETS = &$MARKETS;
        $this->PREFIX = $this->CONFIG['localhost']['prefix'];        //перфикс для таблиц
        //====================
    }


    #############################
    # ПРИСОЕДЕНЯЕМ НОВЫЕ ДАННЫЕ #
    ####################################################################################################################
    public function syncDB($coin=''){
        #ПОЛУЧИЛИ ДАННЫЕ
        if(!$coin) return false;
        $collect = $this->repeat_collect('getmarkethistory',['market'=>$coin]);
        #ПРЕОБРАЗОВАЛИ ДАННЫЕ
        if(empty($collect) || !$collect['success']) return false;       //если была ошибка при получении данных
        if(empty($collect['result'])) return 0;              //если новых данных не было, т.е пустые вернули numBlock=1
        $stack = $this->prepareStack($collect['result']);   //подготовительные работы

        #ПОЛУЧИТЬ НОМЕР БЛОКА ДЛЯ core НА БАЗЕ ПОЛУЧЕННЫХ ДАННЫХ
        $numBlock = ceil(($stack['o2']-$stack['o1'])/60);

        #ЗАПИСАЛИ ДАННЫЕ В ТАБЛИЦУ
        $marge = $this->marge($stack,$coin);
        if($marge){       //сихронизировали с ДБ
            return $numBlock;           //вернули число NumBlock для CORE
        }else{
            new myError('Проблемы с синхронизацией Stack_parent::syncDB',['coin'=>$coin,'marge'=>$marge,'stack'=>$stack]);
            return false;
        }
    }
        protected function groupStack(&$stack,$group='M1'){
            $group = getTime($group);
            if(empty($stack)) return false;
            $data = [
                'o1'    =>'',
                'o2'    =>'',
                'x1'    =>'',
                'x2'    =>'',
                'steep' =>$group,
                'time_o' =>'',
                'time_x' =>''
            ];
            for($i=0, $c=count($stack);$i<$c;$i++){
                $time_id = round_time($stack[$i]['time_id'],$group);
                $stack[$i]['time'] = date(DATE_FORMAT,$stack[$i]['time_id']);
                $data['blocks'][$time_id][] = $stack[$i];
            }
            $keys = array_keys($data['blocks']);
            $first = $data['blocks'][$keys[0]];
            $last = $data['blocks'][$keys[count($keys)-1]];
            $data['o1'] = $first[0]['time_id'];
            $data['x1'] = $keys[0]-$data['steep'];
            $data['o2'] = $last[count($last)-1]['time_id'];
            $data['x2'] = $keys[count($keys)-1];
            $data['time_o'] = date(DATE_FORMAT,$data['o1']).' — '.date(DATE_FORMAT,$data['o2']);
            $data['time_x'] = date(DATE_FORMAT,$data['x1']).' — '.date(DATE_FORMAT,$data['x2']);
            return $data;
        }                   //группировать СТЕК (блоки) по времени [prepareStack]
        protected function marge(&$stack,$coin){
            $tableName = $this->PREFIX.mb_strtoupper($coin);        //имя таблицы
            $sql = "SELECT `time_id` FROM `$tableName` ORDER BY time_id DESC LIMIT 1";$num = array();
            $lastID = $this->DB->select($sql,$num);
            $stackA = $this->summator($stack);     //сложили группы внутри блока

            if(isset($lastID['error'])){        //если таблица ещё не создана
                $this->createTable($tableName);                     //создаем таблицу
                return $this->insertStack($stackA,$tableName);      //тупо вставляем все записи в пустую таблицу
            }else{
                #ПРОВЕРЯЕМ БЫЛИ УЖЕ ЗАПИСИ В ТАБЛИЦЕ
                if($lastID){
                    #ПРОВЕРЯЕМ СОДЕРЖИТСЯ ЛИ ПОСЛЕДНИЙ ID ИЗ DB СРЕДИ НОВО ПОЛУЧЕННЫХ ЗАПЯСЯХ
                    if(isset($stackA[$lastID['time_id']])){
                        $update = [];
                        $insert = [];
                        $stackA = array_values($stackA);         //ПРЕОБРАЗОВАТЬ МАССИВ - получили массив не АССОЦЫАТИВНЫЙ
                        for($i=count($stackA)-1;$i>=0;$i--){
                            if($lastID['time_id']==$stackA[$i]['time_id']){      //обновить
                                $update = $stackA[$i];
                                break;
                            }
                            $insert[] = $stackA[$i];
                        }
                        #ОБНОВЛЕНИЕ
                        if(!empty($update)){
                            $sql = "UPDATE `$tableName` SET 
                                        `time_id`=:time_id,
                                        `COUNTS`=:COUNTS,
                                        `BUY`=:BUY,
                                        `BUY_price`=:BUY_price,
                                        `BUY_price_min`=:BUY_price_min,
                                        `BUY_price_max`=:BUY_price_max,
                                        `BUY_price_last`=:BUY_price_last,
                                        `BUY_total`=:BUY_total,
                                        `BUY_quantity`=:BUY_quantity,
                                        `SELL`=:SELL,
                                        `SELL_price`=:SELL_price,
                                        `SELL_price_min`=:BUY_price_min,
                                        `SELL_price_max`=:SELL_price_max,
                                        `SELL_price_last`=:SELL_price_last,
                                        `SELL_total`=:SELL_total,
                                        `SELL_quantity`=:SELL_quantity
                                        WHERE 
                                        `time_id`=:time_id";
                            $num = array(
                                ':time_id'              =>$update['time_id'],
                                ':COUNTS'               =>$update['COUNTS'],
                                ':BUY'                  =>$update['BUY'],
                                ':BUY_price'            =>str_replace(',','',$update['BUY_price']),
                                ':BUY_price_min'        =>str_replace(',','',$update['BUY_price_min']),
                                ':BUY_price_max'        =>str_replace(',','',$update['BUY_price_max']),
                                ':BUY_price_last'       =>str_replace(',','',$update['BUY_price_last']),
                                ':BUY_total'            =>str_replace(',','',$update['BUY_total']),
                                ':BUY_quantity'         =>str_replace(',','',$update['BUY_quantity']),
                                ':SELL'                 =>$update['SELL'],
                                ':SELL_price'           =>str_replace(',','',$update['SELL_price']),
                                ':SELL_price_min'       =>str_replace(',','',$update['SELL_price_min']),
                                ':SELL_price_max'       =>str_replace(',','',$update['SELL_price_max']),
                                ':SELL_price_last'      =>str_replace(',','',$update['SELL_price_last']),
                                ':SELL_total'           =>str_replace(',','',$update['SELL_total']),
                                ':SELL_quantity'        =>str_replace(',','',$update['SELL_quantity'])
                            );
                            return $this->DB->update($sql,$num);
                        }

                        #ВСТАВКА
                        if($insert){
                            return $this->insertStack($insert,$tableName);      //тупо вставляем все записи в пустую таблицу
                        }
                    }else{  //инчаче тупо вставляем
                        return $this->insertStack($stackA,$tableName);      //тупо вставляем все записи в пустую таблицу
                    }
                }else{      //если таблица была пустая
                    return $this->insertStack($stackA,$tableName);      //тупо вставляем все записи в пустую таблицу
                }
            }
        }                              #ВНЕДРИЛИ ДАННЫЕ В БД
        protected function createTable($tableName){                //создаем таблицу
            $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
                       time_id INT(11) UNSIGNED PRIMARY KEY NOT NULL,
                       COUNTS INT(3) default NULL,
                       
                       BUY INT(3) default NULL,
                       BUY_price DECIMAL (".TO4NOST.") default NULL,
                       BUY_price_min DECIMAL (".TO4NOST.") default NULL,
                       BUY_price_max DECIMAL (".TO4NOST.") default NULL,
                       BUY_price_last DECIMAL (".TO4NOST.") default NULL,
                       BUY_total DECIMAL (".TO4NOST.") default NULL,
                       BUY_quantity DECIMAL (".TO4NOST.") default NULL,
                       
                       SELL INT(3) default NULL,
                       SELL_price DECIMAL (".TO4NOST.") default NULL,
                       SELL_price_min DECIMAL (".TO4NOST.") default NULL,
                       SELL_price_max DECIMAL (".TO4NOST.") default NULL,
                       SELL_price_last DECIMAL (".TO4NOST.") default NULL,
                       SELL_total DECIMAL (".TO4NOST.") default NULL,
                       SELL_quantity DECIMAL (".TO4NOST.")default NULL
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            return !$this->DB->exec($sql);   //создали
        }                           #..MARGE -> СОЗДАТЬ ТАБЛИЦУ
        protected function insertStack($stack,$tableName,$debug=false){       //Добавили новые записи в таблицу
            $value = '';
            if(empty($stack)) return false;
            if(!isset($stack[0]['time_id'])) $stack = array_values($stack);         //ПРЕОБРАЗОВАТЬ МАССИВ - получили массив не АССОЦЫАТИВНЫЙ
            $c = count($stack);

            #ВСТАВЛЯЕМ ЭЛЕМЕНТЫ
            for($i=$c-1;$i>=0;$i--){
                $value .= ",("
                    .$stack[$i]['time_id'].","
                    .$stack[$i]['COUNTS'].","
                    .$stack[$i]['BUY'].","
                    .str_replace(',','',$stack[$i]['BUY_price']).","
                    .str_replace(',','',$stack[$i]['BUY_price_min']).","
                    .str_replace(',','',$stack[$i]['BUY_price_max']).","
                    .str_replace(',','',$stack[$i]['BUY_price_last']).","
                    .str_replace(',','',$stack[$i]['BUY_total']).","
                    .str_replace(',','',$stack[$i]['BUY_quantity']).","
                    .$stack[$i]['SELL'].","
                    .str_replace(',','',$stack[$i]['SELL_price']).","
                    .str_replace(',','',$stack[$i]['SELL_price_min']).","
                    .str_replace(',','',$stack[$i]['SELL_price_max']).","
                    .str_replace(',','',$stack[$i]['SELL_price_last']).","
                    .str_replace(',','',$stack[$i]['SELL_total']).","
                    .str_replace(',','',$stack[$i]['SELL_quantity']).")";
            }       //формируем строчку VALUE
            $value = substr($value,1);
            $sql = "INSERT INTO `$tableName` (`time_id`, `COUNTS`, `BUY`, `BUY_price`, `BUY_price_min`, `BUY_price_max`, `BUY_price_last`, `BUY_total`, `BUY_quantity`, `SELL`, `SELL_price`, `SELL_price_min`, `SELL_price_max`, `SELL_price_last`, `SELL_total`, `SELL_quantity`) VALUES $value";
            $num = array();
            $this->DB->insert($sql,$num);
            return 1;
        }       #..MARGE -> ВСТАВИТЬ В ТАБЛИЦУ
        public function sumBlock(&$a,$debug=false){          //суммировать значения в блоке
            $data = [];
            $buy_count = 0;
            $sell_count = 0;
            for($i=0;$i<count($a);$i++){
                if($i==0){
                    $data = $a[$i];
                    if($a[$i]['BUY_price']!=0){   //оно не пустое - то перезаписываем
                        $buy_count++;
                    }
                    if($a[$i]['SELL_price']!=0){   //оно не пустое - то перезаписываем
                        $sell_count++;
                    }
                    continue;
                }
                $data['COUNTS']         += $a[$i]['COUNTS'];
                $data['BUY']            += $a[$i]['BUY'];
                $data['BUY_price']      += $a[$i]['BUY_price'];
                $data['BUY_total']      += $a[$i]['BUY_total'];
                $data['BUY_quantity']   += $a[$i]['BUY_quantity'];
                if($a[$i]['BUY_price_min']!=0){   //оно не пустое и < MIN - то перезаписываем
                    if($data['BUY_price_min']!=0){
                        if($a[$i]['BUY_price_min']<$data['BUY_price_min']){
                            $data['BUY_price_min'] = $a[$i]['BUY_price_min'];
                        }
                    }else{
                        $data['BUY_price_min'] = $a[$i]['BUY_price_min'];
                    }
                }
                if($a[$i]['BUY_price_max']>$data['BUY_price_max']){   //оно > MAX - то перезаписываем
                    $data['BUY_price_max'] = $a[$i]['BUY_price_max'];
                }
                if($a[$i]['BUY_price']!=0){   //оно не пустое - то перезаписываем
                    $data['BUY_price_last'] = $a[$i]['BUY_price'];
                    $buy_count++;
                }
                //==============> ПРОДАЖА
                $data['SELL']            += $a[$i]['SELL'];
                $data['SELL_price']      += $a[$i]['SELL_price'];
                $data['SELL_total']      += $a[$i]['SELL_total'];
                $data['SELL_quantity']   += $a[$i]['SELL_quantity'];
                if($a[$i]['SELL_price_min']!=0){   //оно не пустое и < MIN - то перезаписываем
                    if($data['SELL_price_min']!=0){
                        if($a[$i]['SELL_price_min']<$data['SELL_price_min']){
                            $data['SELL_price_min'] = $a[$i]['SELL_price_min'];
                        }
                    }else{
                        $data['SELL_price_min'] = $a[$i]['SELL_price_min'];
                    }
                }
                if($a[$i]['SELL_price_max']>$data['SELL_price_max']){   //оно > MAX - то перезаписываем
                    $data['SELL_price_max'] = $a[$i]['SELL_price_max'];
                }
                if($a[$i]['SELL_price']!=0){   //оно не пустое - то перезаписываем
                    $data['SELL_price_last'] = $a[$i]['SELL_price'];
                    $sell_count++;
                }

                //===================================> ПОДВОД ИТОГОВ
                if($i==count($a)-1){    //если последий элемент
                    if($data['BUY']!=0){
                        if($buy_count!=0){
                            $data['BUY_price']         = number_format($data['BUY_price']/$buy_count,8);
                        }else{
                            $data['BUY_price']         = number_format($data['BUY_price'],8);
                        }
                    }
                    $data['BUY_price_min']     = number_format($data['BUY_price_min'],8);
                    $data['BUY_price_max']     = number_format($data['BUY_price_max'],8);
                    $data['BUY_price_last']    = number_format($data['BUY_price_last'],8);
                    $data['BUY_total']         = number_format($data['BUY_total'],8,'.','');
                    $data['BUY_quantity']      = number_format($data['BUY_quantity'],8,'.','');

                    if($data['SELL']!=0){
                        if($sell_count!=0){
                            $data['SELL_price']         = number_format($data['SELL_price']/$sell_count,8);
                        }else{
                            $data['SELL_price']         = number_format($data['SELL_price'],8);
                        }
                    }
                    $data['SELL_price_min']     = number_format($data['SELL_price_min'],8);
                    $data['SELL_price_max']     = number_format($data['SELL_price_max'],8);
                    $data['SELL_price_last']    = number_format($data['SELL_price_last'],8);
                    $data['SELL_total']         = number_format($data['SELL_total'],8,'.','');
                    $data['SELL_quantity']      = number_format($data['SELL_quantity'],8,'.','');
                }
            }
            return $data;
        }                           #..MARGE -> СУММИРОВАТЬ БЛОКИ ПО ВРЕМЕНИ
        public function summator(&$blocks,$type='DB'){
            $data = [];
            if($type=='DB'){        //суммирование для БД
                $i=0;
                foreach ($blocks['blocks'] as $k=>$v){     //суммирование данных
                    $x = $this->sumBlock($v);
                    $x['time_id'] = $k;
                    $x['time'] = date(DATE_FORMAT,$x['time_id']);
                    $data[$k] = $x;
                    $i++;
                }
            }else{
                $type = '';
                $bc = count($blocks['blocks']);
                $b = 0;
                foreach ($blocks['blocks'] as $k=>$v){
                    $x = [
                        'x1' => $k-$blocks['steep'],
                        'x2' => $k,
                        'y0' => 0,
                        'y1' => 0,
                        'y2' => 0,
                        'min' => 0,
                        'max' => 0,
                        'raznica' => 0,
                    ];
    //                if($k==$blocks['x2']) $x['x2'] = $blocks['o2']; //если это последний элемент - то берем фактический придел
                    $size = 0;
                    for($i=0,$c=count($v);$i<$c;$i++){       //перебираем внутрение элементы
                        $elem = $v[$i];
                        if($i==0){
                            if(isset($v[$i]['BUY_price'])){
                                $type = 'BUY';
                            }else{
                                $type = 'SELL';
                            }
                            $x['y1'] = $elem[$type.'_price_last'];
                        }       //опр тип BUY или SELL
                        if($b==$bc-1){  //последний блок
                            if($elem[$type.'_price_last']!=0) $x['y2'] = $elem[$type.'_price_last'];
                        }
                        if($elem[$type.'_price']>0){   //если есть что записать из этого блока
                            $x['y0'] += $elem[$type.'_price'];

                            if($x['y1']==0 && $elem[$type.'_price_last']!=0) $x['y1'] = $elem[$type.'_price_last'];
                            $size++;
                            if($x['min']==0){
                                $x['min'] = $elem[$type.'_price_min'];
                            }elseif($x['min']!=0 && $elem[$type.'_price_min']<$x['min']){
                                $x['min'] = $elem[$type.'_price_min'];
                            }
                            if($elem[$type.'_price_max']>$x['max']){
                                $x['max'] = $elem[$type.'_price_max'];
                            }
                        }
                        if($i==$c-1){    //ОФОРМЛЕНИЕ - если последний элемент
                            $x['y0'] = number_format($x['y0']/$size,8);
                        }
                    }
                    $b++;
                    $data[] = $x;
                    $last = $k;
                }
                for($i=0,$c=count($data);$i<$c;$i++){  //дописываем y2
                    if($i!=$c-1) $data[$i]['y2'] = $data[$i + 1]['y1']; //кроме последнего
                    $data[$i]['raznica'] = round((($data[$i]['y2']*100)/$data[$i]['y1'])-100,2);
                    $data[$i]['time'] = date(DATE_FORMAT,$data[$i]['x1']).' — '.date(DATE_FORMAT,$data[$i]['x2']);;
                }
    //            if(count($blocks['blocks'][$k])==1) unset($data[$b-1]);
            }
            return $data;
        }                        #..MARGE -> СУММИРОВАТЬ БЛОКИ ПО ВРЕМЕНИ



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
        protected function getStack($coin,$time,$sql='*'){                     //получить монету за определенный промежуток времениы
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