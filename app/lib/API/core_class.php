<?php defined('_3AKOH') or die(header('/'));
/*
 * Класс для CORE многоядерной системом
 */
class Core{
    protected $MARKETS;     //подгрузили new MARKETS
    protected $WALLET;     //подгрузили new MARKETS
    protected $STACK;     //подгрузили new STACK
    protected $ANAL;     //подгрузили new STACK
    //===============
    private $DOM;
    private $SREDA;     //bittrex
    private $TYPE;      //cpu
    private $CORE;      //v1
    private $path = TEMP.'core/';
    private $tpl = '';          //[bittrex] CPU
    private $format_file = '.txt';
    function __construct($SREDA,$TYPE='',$CORE=''){       //SREDA = bittrex,  TYPE=CPU,  CORE=v1
        global $CONFIG;
        global $MARKETS;
        $this->MARKETS = &$MARKETS;         //подгрузили $MARKETS
        global $WALLET;
        $this->WALLET = &$WALLET;           //подгрузили $WALLET
        global $STACK;
        $this->STACK = &$STACK;             //подгрузили $STACK
        global $ANAL;
        $this->ANAL = &$ANAL;             //подгрузили $STACK
        //==============
        $this->SREDA = $SREDA;
        if(!empty($TYPE)) $this->TYPE = $TYPE;
        if(!empty($CORE)) $this->CORE = $CORE;
        $this->DOM['custom'] = $CONFIG['sreda'][$SREDA]['body'];           //сохранили тело конфига
        $this->CPU_Processor();
//        dd('zza');
        if($TYPE=='GPU') $this->GPU_Processor();
    }

    /**
     * Получаем файлы из директории - для данной среды, по шаблону $this->TPL
     * @param $tpl
     * @return array
     */
    private function _scanfile($tpl){
        $list = [];
        $files = scandir_zak($this->path);        //читаем все ядра
        if(!empty($files)){
            for($i=0,$c=count($files);$i<$c;$i++){
                if(substr($files[$i],0,strlen($tpl))==$tpl && substr($files[$i],-strlen($this->format_file))==$this->format_file){
                    $list[] = $files[$i];
                }
            }
        }                   //выбираем только => [CORE_SREDA] CORE_TYPE && с окончание .TXT
        return $list;
    }
    /**
     * получить структуру CORE файлами на базе конфига
     * @return array
     */
    private function _getStructure($tpl){
        #ПОЛУЧАЕМ СПИСОК ФАЙЛОВ ДАННОЙ среды
        $list = $this->_scanfile($tpl);
        #РАСПРЕДЕЛЯЕМ ФАЙЛЫ ПО ЯДРАМ
        $structure = [];
        foreach ($this->DOM['custom'] as $k=>$v){   //$key=v1    $val=B1,B2
            if(!isset($structure[$k])) $structure[$k] = [];
            if(empty($v)) continue;
            $name_file = $tpl.'_'.$k.$this->format_file;      //получаем имя файла    [bittrex] CPU_v1.txt
            $num = in_array_num($name_file,$list);  //получаем номер совпадения в массиве
            if(!!~$num){  //если есть для ядра уже файл - то удаляем его из списка или создаем файл
                unset($list[$num]);         //удалили его из списка
            }else{        //если нету - создаем такой (пустой) файл
                file_put_contents($this->path.$name_file,'');
            }
            $structure[$k] = $name_file;       //добавляем имя файла в структуру CORE (Распределяем файлы по ядрам)
        }
        #ЕСЛИ ЕСТЬ МУСОРНЫЕ ФАЙЛЫ - ТО УДАЛЯЕМ ИХ
        if(!empty($list)){
            foreach ($list as $k=>$v){
                unlink($this->path.$v);
            }
            unset($list);
        }
        return $structure;
    }
    /**
     * ОБРАБОТЧИК ядра типа - CPU
     * @return bool
     */
    private function CPU_Processor(){
        $this->DOM['file'] = $this->_getStructure('['.$this->SREDA.'] CPU');        //получить структуру CORE файлами на базе конфига - создаем недостающие файлы

        #ПРОВЕРЯЕМ ЦЕЛОСТНОСТЬ ФАЙЛА
        if(empty($this->DOM['file'])) return false;
        foreach ($this->DOM['file'] as $key=>$val){ //где key=v1, val=[bittrex] CPU_v1.txt
            #ПОЛУЧИЛИ ДАННЫЕ
            $file_a = $this->fopen($key);
//            dd('zakon');
            $file = &$file_a[0];
            #РЕДАКТИРУЕМ ДАННЫЕ
            if(empty($file) || !isset($file['MD5']) || !isset($file['BODY']) || $file['MD5']!=md5(json_encode($file['BODY']))){
                $file = [
                    'MD5' => '',
                    'TIME' => 0,
                    'BODY' => [
                        'CPU' => 0,
                        'NAME' => $key,
                        'CURRENT' => 0,
                        'GIVING' => [],
                        'BLOCKS' => []
                    ]
                ];
                $tmp = &$this->DOM['custom'][$key];     //получаем настройки для текущего ядра
                if (!empty($tmp)){
                    foreach ($tmp as $k=>$v){
                        $file['BODY']['BLOCKS'][$k] = [];   //будет массив
                        $file['BODY']['GIVING'][$k] = '';   //будет цифра
                    }
                }
//                if($key=='v1'){
//                    $file['BODY']['BLOCKS']['B1']['BTC-THT0'] = '';
//                    $file['BODY']['BLOCKS']['B1']['BTC-THT2'] = '';
//                }
//                if($key=='v2'){
//                    $file['BODY']['BLOCKS']['B1']['BTC-THT3'] = '';
//                    $file['BODY']['BLOCKS']['B1']['BTC-THT2'] = '';
//                }
            }       //если файл поврежден -> создаем новый
            $file_a[0] = $file;
//            dd($file_a);
            $file = $this->fwrite($file_a);     //записали изменения
//            dd(1);
            $this->DOM['core'][$key] = $file;                  //записали в ДОМ
            $this->fclose($file_a);     //закрыли соединение
        }       //формируем [core]
        #СИНХРОНИЗИРУЕМ ВСЕ МОНЕТЫ
        $this->DOM['has'] = [];
        //Собираем все монеты в один массив -> [has]
        foreach ($this->DOM['core'] as $key=>$val){   //где key=v1, val=BODY+MD5
            $BLOCKS = &$val['BODY']['BLOCKS'];
            if(!empty($BLOCKS)){    //если ядро содержит обрабатываемы значения - фиксируем их
                foreach ($BLOCKS as $K=>$V){    //$K=B1, $V=[BTC-HTH],[BTC-THT]
                    if(!empty($V)){ //если есть пары в данном блоке
                        foreach ($V as $COIN_NAME=>$COIN_VAL){  //пробегаемся по блокам которые не пустые
                            if($this->hasCoin($COIN_NAME)){   //если есть повторная монета в ядрах - удаляем ее в старом месте
                                new myError('Появилась повторная монета и мы ее удалили',['coin'=>$COIN_NAME,'DOM'=>$this->DOM]);
                                $this->removeCoin($COIN_NAME);        //удалить v1_B1
                            }
                            $this->DOM['has'][$COIN_NAME] = $key.'_'.$K;
                        }
                    }
                }
            }
        }
        #ПОДСЧЕТ ОБЩЕЙ СТАТИСТИКИ
        $this->run_stat();
        #=====================================
//        $c = $this->DOM['core']['v1'];
//        $this->updGiving($c);       //обновили МЕТА ядра
//        dd($c);
//        dd($this->hasCoin('BTC-ZAK48'));
//        $this->removeCoin('BTC-THT0');
//        $this->addCoin('BTC-NXT');
//        $this->removeCoin('BTC-THT16');
    }
    /**
     * ОБРАБОТЧИК ядра типа - ACV
     * @return bool
     */
    private function GPU_Processor(){
        #ПОЛУЧИЛИ СПИСОК МОНЕТ КОТОРЫЕ С ОТКРЫТЫМИ ОРДЕРАМИ (в БВ)
        $DB = $this->MARKETS->get('key');
        $open_list = [];        //список монет которые имею активные ордера
        foreach ($DB as $k=>$v){    //$k=coin   $v=array(содержимое)
            if($DB[$k]['isOpen']) $open_list[] = $DB[$k]['market'];     //заполняем список монет которые надо проверить
        }
        #ПРОВЕРИЛИ ИХ НА АКТУАЛЬНОСТЬ
        if(empty($open_list)) return false;     //если нет монет которые надо анализировать
        $orders = $this->WALLET->OPEN_ORDERS;
        for ($i=0,$c=count($open_list);$i<$c;$i++){
            $coin = &$open_list[$i];       //берем монету BTC-1ST
        }
        dd($open_list);
    }

    //======================FILE========================
    /**
     * @param $v1 - название ядра
     * @return array [json_code, Resource ID, $path]
     */
    public function fopen($v1){

//
//        $path = TEMP.'wallet-cash.txt';
//        dd($path,1);
//        $file_o = fopen($path, "r+");            //создали и подготовились ЧИТАТЬ
//        if(flock($file_o,LOCK_EX)){
//            dd('ЗАБЛОКИРОВАЛИ');
//        }else{
//            dd('КОСЯКИ');
//        }
// 
//
//        dd('ЗАГЛУШКА');
        $path = $this->path.$this->DOM['file'][$v1];
        if (!file_exists($path)) touch($path);          //если нету файла - то создали его
        $file_o = fopen($path, "r+");            //создали и подготовились ЧИТАТЬ
        if(!$file_o) return 'xaxa';
        $key = false;                                   //ключ блокировки файла
        $file = 'Превысил предел';
//        dd(flock($file_o, LOCK_EX));
//        dd(111);
        clearstatcache();
        while (!$key && flock($file_o, LOCK_EX)) {        //пока не заблокируется -> БЛОКИРУЕМ ФАЙЛ
            clearstatcache();                      // скинуликеш
            $filesize = filesize($path);                            //размер файла
            if($filesize){
                $file = fread($file_o, $filesize);  //содержимое файла, если есть что читать
                $file_r = $file;
                $file = json_decode($file,1);
                if(empty($file)){
                    new myError('хахах тут ошибка',['file_r'=>$file_r,'filesize'=>$filesize]);
                }
            }else{
                new myError('хахах тут ошибка x2',['file_r'=>$file_o,'filesize'=>$filesize]);
            }
            $key = true;
        }
        return [$file, $file_o, $path];
    }
    public function fwrite($file_o){
        if(!isset($file_o[0]['BODY']['BLOCKS']) || empty($file_o[0]['BODY']['BLOCKS'])){
            new myError('Надо проверить file',['$file_o'=>$file_o]);
            $this->fclose($file_o);
            return false;
        }
        $file_o[0] = $this->updGiving($file_o[0]);       //обновили мета заголовки
        $file = $file_o[0];
        $file_o[0] = json_encode($file_o[0],JSON_UNESCAPED_UNICODE);
        if(is_array($file_o[0])){
            dd('Core::fwrite получил для записи не строку!!!');
            return false;
        }
        ftruncate($file_o[1], 0);           // очищаем файл
        rewind($file_o[1]);                      //сбросили указатель
        if(fwrite($file_o[1], $file_o[0]) === FALSE) return false;       // если не записалось - FALSE
        return $file;
    }
    public function fclose($fp){
        if(!isset($fp[1])){
            new myError('Отсутствует закрываемый файл!',['file_a'=>$fp]);
            return false;
        }
        fflush($fp[1]);                        // очищаем вывод перед отменой блокировки
        flock($fp[1], LOCK_UN);      // снимаем БЛОКИРОВКУ
        fclose($fp[1]);                        // закрыли файл
    }

    //======================ПОСРЕДСТВЕННЫЕ========================
    /**
     * Получить DOM-дерево
     * @return mixed
     */
    public function getDOM($name=''){
        if(!empty($name)){
            $m = explode(' ',$name);
            if(count($m) == 1 && isset($this->DOM[$name])){
                return $this->DOM[$name];
            }else{
                $list = [];
                for ($i=0,$c=count($m);$i<$c;$i++){
                    if(isset($this->DOM[$m[$i]])) $list[$m[$i]] = $this->DOM[$m[$i]];
                }
                if(empty($list)) return $this->DOM;
                return $list;
            }
        }
        return $this->DOM;
    }
    /**
     * РАЗВОРАЧИВАНИЯ СРЕДЫ
     */
    public function update(){
        if(!$this->MARKETS->syncDB()) return false;     //создаем таблицу для MARKETS и заполняем ее
        $this->syncCoin();                              //заполняем ядра монетами из MARKETS
        return true;
    }
    /**
     * ИНИЦИАЛИЗАЦИЯ КРОН
     * @DESC
     * 1)ПОЛУЧИЛИ СПИСОК МОНЕТ
     * 2)ОПЕРАЦИИ НАД КАЖДОЙ МОНЕТОЙ
     *      — СИНХРОНИЗИРУЕМ КАЖДУЮ МОНЕТУ С БД
     *      — ПРОАНАЛИЗИРОВАЛИ МОНЕТУ
     *      — ОБНОВИЛИ ПОЛОЖЕНИЕ В CORE
     */
    public function cron(){

        if(!$this->CORE) dd('Для Cron=>'.$this->SREDA.' не указан номер ядра');
        $coins = $this->getCoin($this->CORE);        //получаем список монет из текущего CORE
        $coins = ['BTC-BLOCK'];
        #ЕСЛИ СПИСОК ВДРУГ ПУСТ!
        if(empty($coins)){
            $this->update();
            $coins = $this->getCoin($this->CORE);      //получаем список монет из текущего CORE
        }                       //инициализировали список монет в CORE
        if(empty($coins)){
            new myError('Проблема - почему-то список монет в ядрах ПУСТ!',['CORE_v1'=>$this->CORE,'SREDA'=>$this->SREDA]);
            return false;
        }
        for($i=0,$c=count($coins);$i<$c;$i++){
            $coin = &$coins[$i];
            $numBlock = $this->STACK->syncDB($coin);                //синхронизировали свежие данные по монете
//            $analitic = $this->ANAL->go('BTC-BLOCK','28-05-2018 12:12');    //BUY
            $analitic = $this->ANAL->go('BTC-BLOCK','28-05-2018 15:30');    //SELL
            dd($analitic);
            dd('ЗАГЛУШКА');
            if($numBlock!==FALSE) $this->updCoin($coin,$numBlock);  //обновили монету в CORE
        }

        #ПОДСЧЕТ ВРЕМЕНИ ВЫПОЛНЕНИЯ СКРИПТА через CRON
        #ФИКСИРЕМ ВРЕМЯ В ЯДРЕ
        if($this->CORE){
            $BOT = new Bot();
            $finish = microtime(true);       //время конца выполнения скрипта
            $delta = $finish - START;                  //ИТОГО
            $file_a = $this->fopen($this->CORE);        //открыи ядро
            if(!isset($file_a[0]['BODY']['BLOCKS']) || empty($file_a[0]['BODY']['BLOCKS'])){
                $BOT->sendMessage('br.max',$this->CORE.' '.$delta.'JS22222::   '.json_encode($file_a));
            }
            $file_a_old = $file_a;
            $file_a[0]['TIME'] = round($delta,2);                //SAVE
            if(!isset($file_a[0]['BODY']['BLOCKS']) || empty($file_a[0]['BODY']['BLOCKS'])){
                $BOT->sendMessage('br.max',$this->CORE.' '.$delta.'JS::   '.json_encode($file_a_old));
                new myError('Почему-то не смог получить содержимое файла?',['core'=>$this->CORE,'delta'=>$delta,'file_a_old'=>$file_a_old,'file_a'=>$file_a]);
                $this->fclose($file_a);                     //закрыли соединение
                exit;
//                echo "[$this->CORE] ".date(DATE_FORMAT,time())." {$delta}_{$CORE_NUM} +\n";
            }else{
                $this->fwrite($file_a);             //записали изменения
            }
            $this->fclose($file_a);                     //закрыли соединение
        }
//        dd($this->CORE,1);
//        dd(1);
//
//        dd($file_a,1);
//
//
//        dd($file,1);

//        $this->DOM['core'][$this->CORE] = $file;    //+добавили монету в DOM[core]
    }
    /**
     * Обновить META ядра
     * @param $file
     */
    private function updGiving(&$file){
        if(!isset($file['BODY']['BLOCKS']) || empty($file['BODY']['BLOCKS'])){
            new myError('Надо проверить file',['file'=>$file]);
        }
        $file['BODY']['CPU']        = 0;        //сбросили CPU
        $file['BODY']['CURRENT']    = 0;        //сбросили CURRENT
        foreach ($file['BODY']['BLOCKS'] as $k=>$v){        //$k=B1, $v = array(BTC,BCTC...)
            $v1 = &$file['BODY']['NAME'];       //название ядра
            $count = count($v);                         //получили кол-во элементов в блоке
            if(!isset($this->DOM['custom'][$v1][$k][0])){
                new myError('ТУТ КОСЯК...',['name'=>$file['BODY']['NAME'],'file'=>$file]);
            }
            $time = $this->DOM['custom'][$v1][$k][0];   //получили частоту обновления блока
            $give = ceil($count/$time);           //получили GIVE для данного блока

            $file['BODY']['GIVING'][$k] = $give;        //сохранили GIVE для данного блока
            $file['BODY']['CPU'] += $give;              //изменили общ CPU ядра
            $file['BODY']['CURRENT'] += $count;         //изменили общ CURRENT ядра
        }
//        #ЗАПИСЫВАЕМ ВРЕМЯ ОТРАБОТКИ ЯДРА
//        $finish = microtime(true);       //время конца выполнения скрипта
//        $delta = $finish - START;                  //ИТОГО
//        $file['TIME'] = round($delta,2); //пересчитали КЕШ MD5
        #ОБНОВИЛИ MD5 КЕШ
        $file['MD5'] = md5(json_encode($file['BODY'])); //пересчитали КЕШ MD5
        return $file;
    }
    /**
     * Создаем в доме статистику по всем ядрам
     */
    private function run_stat(){
        $this->DOM['stat'] = [
            'CPU'   =>0,        //общее CPU
            'CURRENT' =>0         //общее кол-во монет
        ];    //создали/обновили ячейку для статистики
        foreach ($this->DOM['core'] as $block){    //$body=array(MD5,BODY)
            $this->DOM['stat']['CPU'] += $block['BODY']['CPU'];
            $this->DOM['stat']['CURRENT'] += $block['BODY']['CURRENT'];
        }
    }

    //====================FIND==========================
    /**
     * Поиск файла с минимальным CPU для опр монеты - чтобы не было качель!
     * @coin   BTC-1ST
     * @return v1
     */
    public function findCPU($coin){
        #ПРОВЕРКА МОНЕТЫ В СТЕКЕ - ЕСЛИ ЕСТЬ ТО УЧИТЫВАЕМ ТЕКУЩЕЕ ПОЛОЖЕНИЕ
        $d = &$this->DOM['has'][$coin];
        if($d){     //если монета уже присутствует - получаем ее адрес
            $v_name = explode('_',$d)[0];         //текущее ядро
            $v_cpu = &$this->DOM['core'][$v_name]['BODY']['CPU'];               //текущее CPU
        }
        #СЧИТАЕМ НОВОЕ МЕСТО
        $f_cpu  = false;
        $f_name = false;    //$f_name=v1
        foreach ($this->DOM['core'] as $k=>$v){     //$k=v1  $v=MD5+BODY
            $cpu = &$v['BODY']['CPU'];
            if($f_cpu===false || $cpu<$f_cpu){
                $f_cpu = $cpu;
                $f_name = $k;
            }    //поиск минимального CPU
        }
        #ДОП АНАЛИЗ НАЙДЕНОГО ЯДРА - АНТИ КАЧЕЛИ - если текущее-найденое>1 - меняем ядро
        if(isset($v_cpu)){              //если монета из 1 пункта была найнена - т.е её сравниваем с текущим положением
            if(($v_cpu-$f_cpu)>1){          //меняем ядро
                return $f_name;
            }else{                          //оставляем старое
                return $v_name;
            }
        }else{                      //если данной монеты нету в стеке
            return $f_name;
        }
    }
    /**
     * Поиск имени первого блока в данном ядре
     * @v1 - имя ядра где будем искать блок
     * @num - текущее значение монеты, по умолчанию 0 - чтобы переместить в B1
     * @return B1
     */
    private function findBlock($v1,$num=0){
        $custom = &$this->DOM['custom'][$v1];
        $bname = array_keys($custom)[0];
        foreach ($custom as $k=>$v){        //$k=B1  $v=array(0+num,1+array)
            if($num>=$v[1][0] && $num<=$v[1][1]){
                $bname = $k;
            }
        }
        return $bname;
    }

    //====================COIN==========================
    /**
     * Просто получить список монет, у которых подошла очередь на обработку
     * @param int $num  число получаемы элементов
     * @param string $core  название ядра из которого берем монеты v1..
     * @return array
     */
    public function getCoin($core,$num=''){
        if(!$num){  //если не было выбрано максимальное кол-во монет
            global $MAX_CPU;
            $num = $MAX_CPU;
        }
        $giving = &$this->DOM['core'][$core]['BODY']['GIVING'];
        if(!$giving){
            new myError('отсутствует ядро..'.$core);
            return false;
        }
        $list = [];
        foreach ($giving as $k=>$v){                        //$k=B1   $v=3
            if(count($list)>=$num) break;                   //не больше пределе CPU
            if(empty($v)) continue;                         //если гивинг=0 пропускаем
            #ЕСЛИ ЕСТЬ ЧТО БРАТЬ - ПЕРЕБИРАЕМ СООТВЕТСТВУЮЩИЙ БЛОК И БЕРЕМ СКОЛЬКО НАДО
            $i = 0;                                         //делаем внутрениий счетчик
            $block = &$this->DOM['core'][$core]['BODY']['BLOCKS'][$k];      //array(BTC,BTHT...)
            foreach ($block as $coin=>$x){                  //$coin=BTC, $x='пусто'
                if($i>=$v || count($list)>=$num) break;     //если больше внутренего передела или MAX_CPU
                $list[] = $coin;
                $i++;
            }
        }
        return $list;
    }
    /**
     * Добавили новую монету в ядра, если есть 2 параметр - то в указанное место v1_B2
     * @param $coin = BTC-THT
     */
    public function addCoin($coin,$mesto_0=false){
        if($this->hasCoin($coin)) return false;        //если есть уже такая монета - выходим
        #ЕСЛИ НЕТУ УКАЗАННОГО МЕСТА — ИЩИМ МЕСТО КУДА ЗАПИШЕМ НОВУЮ МОНЕТУ
        if(!$mesto_0){                      //находим название ядра - v1_B1 (если не был указан иной)
            $cpu_0 = $this->findCPU($coin);                     //находим название ЯДРА
            $mesto_0 = $cpu_0.'_'.$this->findBlock($cpu_0);     //находим место в ядре
        }
        if($mesto_0===false){
            new myError('Не определили новое место для монеты...',['coin'=>$coin,'mesto_0'=>$mesto_0]);
            return false;
        }
        $mesto = explode('_',$mesto_0);
        $CORE = &$mesto[0];      //v1 название ядра
        $BLOCK = &$mesto[1];     //B1 место блока
        #ПОЛУЧИЛИ ДАННЫЕ
        $file_a = $this->fopen($CORE);
        $file_a[0]['BODY']['BLOCKS'][$BLOCK][$coin] = '';        //добавили монету в ЯДРО
        $file = $this->fwrite($file_a);     //записали изменения
        $this->DOM['core'][$CORE] = $file;                //+добавили монету в DOM[core]
        $this->DOM['has'][$coin] = $mesto_0;              //+добавили монету в DOM[has]
        $this->fclose($file_a);     //закрыли соединение
        #ПОДСЧЕТ ОБЩЕЙ СТАТИСТИКИ
        $this->run_stat();
        return $mesto_0;
    }
    /**
     * Переместить монету в заданное место
     * @param $coin BTC-1ST название монеты
     * @param $suda v2_B2 куда переместить
     */
    public function moveCoin($coin,$suda){
        if(!$suda){
            new myError('Не указан путь куда переместить монету',['$coin'=>$coin,'$suda'=>$suda]);
            return false;
        }
        $ot = $this->hasCoin($coin);    //получаем текущее положение данной монеты  - v1_b1
        if(!$ot){
            //new myError('Не получилось переместить монету, т.к ее нету в ядрах',['coin'=>$coin,'suda'=>$suda]);
            return false;
        }
        $ot = explode('_',$ot);         //[v1, B1]
        $suda = explode('_',$suda);     //[v2, B2]
        #ПРОВЕРЯЕМ ОБЩЕЕ ЛИ У НИХ ЯДРО?
        if($ot[0]!=$suda[0]){   //ядра разные - значит блокируем новое ядро тоже
            #ПОЛУЧИЛИ ДАННЫЕ
            $file_a_ot = $this->fopen($ot[0]);
            $file_a_suda = $this->fopen($suda[0]);
            //OT
            if(isset($file_a_ot[0]['BODY']['BLOCKS'][$ot[1]][$coin]))
                unset($file_a_ot[0]['BODY']['BLOCKS'][$ot[1]][$coin]);   //удаляем в старом монету
            $file_ot = $this->fwrite($file_a_ot);
            $this->DOM['core'][$ot[0]] = $file_ot;
            //SUDA
            $file_a_suda[0]['BODY']['BLOCKS'][$suda[1]][$coin] = '';           //добавили в новое ядро
            $file_suda = $this->fwrite($file_a_suda);
            $this->DOM['core'][$suda[0]] = $file_suda;                //перезаписали в DOM[core]
            //HAS
            $this->DOM['has'][$coin] = $suda[0].'_'.$suda[1];       //заменили HAS
            $this->fclose($file_a_ot);     //закрыли соединение
            $this->fclose($file_a_suda);     //закрыли соединение
            #ПОДСЧЕТ ОБЩЕЙ СТАТИСТИКИ
            $this->run_stat();
            return true;
        }else{      //Ядра одинаковые
            #ПОЛУЧИЛИ ДАННЫЕ
            $file_a = $this->fopen($ot[0]);
            $file_ot = $file_a[0];
            if(isset($file_ot['BODY']['BLOCKS'][$ot[1]][$coin]))
                unset($file_ot['BODY']['BLOCKS'][$ot[1]][$coin]);   //удаляем в старом монету
            $file_ot['BODY']['BLOCKS'][$suda[1]][$coin] = '';           //добавили в новое ядро
            $file_a[0] = $file_ot;
            $file_ot = $this->fwrite($file_a);     //записали изменения
            $this->DOM['core'][$ot[0]] = $file_ot;                //перезаписали в DOM[core]
            $this->DOM['has'][$coin] = $suda[0].'_'.$suda[1];
            $this->fclose($file_a);     //закрыли соединение
            #ПОДСЧЕТ ОБЩЕЙ СТАТИСТИКИ
            $this->run_stat();
            return true;
        }
    }
    /**
     * Удалить монету
     * @param $coin = BTC-THT
     */
    public function removeCoin($coin){
        if(!$this->hasCoin($coin)) return false;        //если монеты такой нету - выходим
        //new myError('Удалили монету',['coin'=>$coin]);
        $mesto = explode('_',$this->DOM['has'][$coin]);
        $CORE = &$mesto[0];      //v1 название ядра
        $BLOCK = &$mesto[1];     //B1 место блока
        #ПОЛУЧИЛИ ДАННЫЕ
        $file_a = $this->fopen($CORE);
        if(isset($file_a[0]['BODY']['BLOCKS'][$BLOCK][$coin])) unset($file_a[0]['BODY']['BLOCKS'][$BLOCK][$coin]);      //удалили монету в DOM[core]
        if(isset($this->DOM['has'][$coin])) unset($this->DOM['has'][$coin]);                                    //удалили монету в DOM[has]
        $file = $this->fwrite($file_a);     //записали изменения
        $this->DOM['core'][$CORE] = $file;                //+добавили монету в DOM[core]
        $this->fclose($file_a);     //закрыли соединение
        #ПОДСЧЕТ ОБЩЕЙ СТАТИСТИКИ
        $this->run_stat();
        return 1;
    }
    /**
     * Обновить положение монеты в "пространстве"
     * @param $coin название поменты которую надо пересчитать + обновить
     * @param $num  текущее значение этой монеты
     * @return bool
     */
    public function updCoin($coin, $num){
        if($num===0){   //если данные были пустые.. значит просто переставляет монету в томже блоке - только в конец
            $move = $this->hasCoin($coin);
        }else{
            $v1 = $this->findCPU($coin);                    //находим ядро с минимальным CPU
            $isOpen = $this->MARKETS->isOpen($coin);        //проверка - состоит ли монета в ордере
            #ПОИСК БЛОКА КУДА ПЕРЕМЕСТИМ
            if($isOpen){    //если монета АКТИВНАЯ в ТОРГАХ - открыт ордер
                $block = $this->findBlock($v1);      //получаем минимальный блок
            }else{
                $block = $this->findBlock($v1,$num);      //получаем минимальный блок
            }
            #ПЕРЕМЕЩАЕМ В НОВОЕ МЕСТО
            $move = $v1.'_'.$block;
            if(empty($v1) || empty($block) || empty($move)){
                new myError('Что-то не определилось..',['v1'=>$v1,'block'=>$block,'isOpen'=>$isOpen]);
            }
        }
        $this->moveCoin($coin,$move);
        return $move;
    }
    /**
     * Проверяем есть ли данная монета среди всех ядер
     * @param $coin = BTC-THT
     * @return int
     */
    public function hasCoin($coin){
        if(!isset($this->DOM['has'][$coin])) return false;  //проверили монету в DOM
        $mesto = explode('_',$this->DOM['has'][$coin]);
        $file_a = $this->fopen($mesto[0]);
        $file = $file_a[0];
        if(isset($file['BODY']['BLOCKS'][$mesto[1]][$coin])) return $this->DOM['has'][$coin];       //проверили монету в ядре
        return false;
    }
    /**
     * СИНХРОНИЗИРОВАТЬ МОНЕТЫ ИЗ db_MARKETS С МОНЕТАМИ ИЗ core
     */
    public function syncCoin(){
        $markets = $this->MARKETS->get('key');        //из БД
        $core_list = $this->DOM['has'];                 //получили список монет из CORE
        #ДОБАВЛЯЕМ МОНЕТЫ
        foreach ($markets as $coin=>$arr){         //$coin=>coin  $arr=>array(...)
            if(!isset($core_list[$coin])) $this->addCoin($coin);        //добавляем если нету
            else unset($core_list[$coin]);
        }
        #УДАЛЯЕМ МОНЕТЫ
        if(!empty($core_list)){
            foreach ($core_list as $coin=>$arr){      //$coin=>coin  $arr=>array(...)
                $this->removeCoin($coin);
            }
        }
    }

}