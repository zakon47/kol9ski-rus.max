<?php
/**
 * Отправка cURL параметров
 * @param $url
 * @param array $fields Массив GET запросов ?name=key
 * @param string $method Метод отправки
 * @param array $config Массив опцый
 * @return mixed
 */
function sendRequest($url, $fields = [], $method = 'get', $config = []) {
    if(!empty($fields)){
        $fields = http_build_query($fields);
    } // http://php.net/manual/ru/function.curl-setopt.php
    $_config = [
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36',
        CURLOPT_COOKIEFILE => 'cookie.txt',
        CURLOPT_COOKIEJAR => 'cookie.txt',
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => '',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,

//        $_config[CURLOPT_PROXY] = "23.236.162.37:3128",
        $_config[CURLOPT_PROXY] = "enter.proxy.expert",
        $_config[CURLOPT_PROXYPORT] = "1888",
//        $_config[CURLOPT_PROXY] = "par1.proxy.veesecurity.com:443",
//        $_config[CURLOPT_PROXYUSERPWD] = "PROXY_5AD4C914592CE:842e44fb77052379",
        $_config[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME,
    ];
    if ($method == 'post') {
        $_config[CURLOPT_POSTFIELDS] = $fields;
        $_config[CURLOPT_POST] = true;
    }
//curl_setopt ($ch, CURLOPT_PROXY, "par1.proxy.veesecurity.com:443");
//curl_setopt ($ch, CURLOPT_PROXYUSERPWD, "PROXY_5AD4C914592CE:842e44fb77052379");
//curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
    foreach ($config as $key => $value) {
        $_config[$key] = $value;
    }
    $curl = curl_init();
    curl_setopt_array($curl, $_config);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
/**
 * Возвращает подстроку переданной строки
 * @param string $str - Строчка
 * @param number $start - Начальная точка
 * @param number $finish - Конечная точка
 * @return string
 */
function substring(&$str,$start,$finish='x'){
    $strlen = strlen($str);
    $finish = ($finish==='x')?$strlen:$finish;
    if($finish>=0){
        if($finish-$start>0){
            return substr($str,$start,$finish-$start);
        }
        return substr($str,$finish,$start-$finish);
    }
//    return substr($str,$start,($strlen+$finish)-$start);
//    dd(2);
    return substring($str,$start,$strlen+$finish);
}
/**
 * Возращает содержимое любой переменной и останавливает код
 * @param var $fn - любая переменная, которую надо отрисовать
 * @param string $key - ключ, если есть то код не остановится после этой функции
 */
function dd($fn,$key=false){
    echo '<pre>';
    if(isset($fn))
        print_r($fn);
    else
        print_r('Нет такой переменной');
    echo '</pre>';
    if(!$key) exit();
}
/**
 * Возвращает следующее положение ключа - С ПЕРЕЗАПИСЬЮ КЛЮЧА!
 * @param int $key текушее значение ключа, например 0
 * @param int $len кол-во переключений, например 2
 * @return int следующий ключ переключателя
 */
function toggleKey(&$key,$len){
    $key = ($key+1)%$len;
}
$md5 = 'e7b53ee3ac8e75d7c02314a2ef44a9e2';
/**
 * округлить дату или время - сам написал
 * @param $ts
 * @param int $step
 * @return float
 */
function round_time($ts, $step=60,$up=0) {
	$ts = getTime($ts);
    if($up) $up = $step;
    return $ts-($ts%$step)+$up;
}
function round_time2($ts, $step=60,$end=0) {
	$ts = getTime($ts);
    if(is_string($step)) $step = pTime($step);
    $n = $end-$ts;
    $n = floor($n/$step);
    if($end){
        return ($end-$n*$step)-$step;
    }else{
        return $end-$n*$step;
    }
}
/**
 * Преобразователь времени. Первый параметр передает сколько надо времени,
 * а второй параметр в каких измерениях
 * @param $time
 * @param string $mrk
 * @return float|int
 */
function pTime($time,$mrk='S'){
	if(is_numeric($time)) return $time;
    if(strlen($time)){
        $type = $time[0];
        if(is_numeric($type)) return 0;
        $time = substr($time,1);
        switch ($type){
            case 'Y': $time*=12;
            case 'm': $time*=30;
            case 'D': $time*=24;
            case 'H': $time*=60;
            case 'M': $time*=60;
            case 'S': {$time*=1; break;}
            case 'W': $time*=60*60*24*7;
        }
        $x = 1;
        if($mrk!='S'){
            switch ($mrk){
                case 'Y': $x*=12;
                case 'm': $x*=30;
                case 'D': $x*=24;
                case 'H': $x*=60;
                case 'M': $x*=60;
                case 'S': {$x*=1; break;}
                case 'W': $x*=60*60*24*7;
            }
        }
        return $time/$x;
    }
    return false;
}
/**
 * преобразовывает массив в ini файл
 * @param array $a
 * @param array $parent
 * @return string
 */
function arr2ini(array $a, array $parent = array()){
    $out = '';
    foreach ($a as $k => $v) {
        if (is_array($v)) {
            $sec = array_merge((array) $parent, (array) $k);
            $out .= '[' . join('.', $sec) . ']' . PHP_EOL;
            $out .= arr2ini($v, $sec);
        }
        else {
            $out .= "$k=$v" . PHP_EOL;
        }
    }
    return $out;
}
/**
 * Разбиваем элемент по определенным знакам - Глобальный Explode
 * @param $array
 * @param $znak
 * @return array
 */
function str_explode($array, $znak){
    $k = 0;
    if(empty($array)) return $array;
    while(isset($znak[$k]) && !empty($znak[$k])){
        if(is_array($array)){
            for($i=0;$i<count($array);$i++){
                $array[$i] = str_explode($array[$i], $znak[$k]);
            }
            $k++;
        }else{
            $array = explode($znak[$k],$array);
            $array = array_values($array);
            $k++;
        }
    }
    return $array;
}
/**
 * Получить текущую дату с вычетом даты
 * @param $pTime
 * @return int
 */
function span($startTime='now',$pTime){
    if(!is_numeric($startTime))
        $startTime = strtotime($startTime);
    if(is_string($pTime))
        $pTime = $startTime-pTime($pTime);
    return ['ot'=>$pTime,'do'=>$startTime];
}
//преобразовать дату и время в тайм-стемп
function getTime($time){
    if(is_numeric($time)) return $time;
    $p = pTime($time);
    if(!$p) return strtotime($time);
    return $p;
}
//получить интервал во времени
function interval($time,$date,$to4nost='M1'){
    $time = getTime($time);
    $date = getTime($date);
    return ['x1'=>round_time($date-$time,$to4nost),'x2'=>$date];
}
function arr2tsv($arr,$type='js'){
    if(empty($arr)) return false;
    $str = '';
    if($type=='js'){
        $end = '\n\\'.PHP_EOL;
        $razd = '|';
    }else{
        $end = PHP_EOL;
        $razd = ' ';
    }
    $lenght = 0;
    $i = 0;
    foreach($arr as $k=>$v){
        if(empty($str)){    //добавляем заголовки
            $lenght = count($v);
            foreach ($v as $name=>$value){
                if($i==$lenght-1){  //если последний
                    $str .= $name;
                    $i = 0;
                }else{
                    $str .= $name.$razd;
                    $i++;
                }
            }
            $str.=$end;
        }
        foreach ($v as $name=>$value){
            if($i==$lenght-1){  //если последний
                $str .= $value;
                $i = 0;
            }else{
                $str .= $value.$razd;
                $i++;
            }
        }
        $str  .= $end;
    }
    return $str;
}
/**
 * Посмотреть имя переменной
 * @param $var переменная
 * @return array [key=>val]
 */
function get_var_name($var){
    foreach($GLOBALS as $name => $value) {
        if($value === $var) {
            return $name;
        }
    }
}
class Buffer{   //для буфферизации данных
    private $file;
    function __construct($path,$namefile){
        $this->file = $path.$namefile;
    }
    public function add($name,$var){    //('key',array());
        $file_a = $this->fopen($this->file);       //открыли
        $file_a[0][$name] = $var;                  //изменили
        $res = $this->fwrite($file_a);      //записали
        $this->fclose($file_a);             //закрыли
        if($res===FALSE) return 0;
        return 1;                       //TRUE или FALSE
    }
    public function get($var=''){
        $file_a = $this->fopen($this->file);    //открыли
        $this->fclose($file_a);                 //закрыли
        if(!empty($var)){
            return (isset($file_a[0][$var]))?$file_a[0][$var]:'undefined';
        }else{
            return $file_a[0];
        }
    }
    public function clean(){
        $file_a = $this->fopen($this->file);       //открыли
        $file_a[0] = [];                  //изменили
        $res = $this->fwrite($file_a);      //записали
        $this->fclose($file_a);             //закрыли
        if($res===FALSE) return 0;
        return 1;                       //TRUE или FALSE
    }
    public function remove($var){
        $file_a = $this->fopen($this->file);       //открыли
        $res = 1;
        if(isset($file_a[0][$var])){
            unset($file_a[0][$var]);            //удаляем
            $res = $this->fwrite($file_a);      //записали
        }
        $this->fclose($file_a);             //закрыли
        if($res===FALSE) return 0;
        return 1;                       //TRUE или FALSE
    }
    public function has($var){
        $file_a = $this->fopen($this->file);    //открыли
        $this->fclose($file_a);                 //закрыли
        if(isset($file_a[0][$var])){
            return 1;
        }else{
            return 0;
        }
    }
    //=========================
    public function fopen($path,$debug=false){
        if (!file_exists($path)) touch($path);          //если нету файла - то создали его
        $file_o = fopen($path, "r+");            //создали и подготовились ЧИТАТЬ
        $key = false;                                   //ключ блокировки файла
        $file = [];
        while (!$key && flock($file_o, LOCK_EX)) {        //пока не заблокируется -> БЛОКИРУЕМ ФАЙЛ
            clearstatcache();                      // скинуликеш
            $filesize = filesize($path);
            if($filesize) $file = fread($file_o, $filesize);
            if(!empty($file)) $file = json_decode($file,1);
            $key = true;
        }
        return [$file, $file_o, $path];
    }           // открыли и декодировали
    public function fwrite($file_o){
        $file_o[0] = json_encode($file_o[0],JSON_UNESCAPED_UNICODE);     //преобразовали для записи
        if(is_array($file_o[0])){
            dd('Buffer::fwrite получил для записи не строку!!!');
            return false;
        }
        ftruncate($file_o[1], 0);           // очищаем файл
        rewind($file_o[1]);                      // сбросили указатель
        if(fwrite($file_o[1], $file_o[0]) === FALSE) return false;       // если не записалось - FALSE
        return true;
    }        //очистили и записали
    public function fclose($fp){
        if(!isset($fp[1])){
            new myError('Отсутствует закрываемый файл в буфере!',['file_a'=>$fp]);
            return false;
        }
        fflush($fp[1]);                        // очищаем вывод перед отменой блокировки
        flock($fp[1], LOCK_UN);      // снимаем БЛОКИРОВКУ
        fclose($fp[1]);                        // закрыли файл
    }            //закрыли файл
}
//$BUFFER = new Buffer(TEMP);
/**
 * Сохранить переменную в буфер
 * @param $var
 */
function saveVar(&$var){
    $ini = arr2ini($var);
    file_put_contents('saveVar.ini', $ini);
}
/**
 * Загрузить переменную из буфера
 * @return array|bool
 */
function loadVar(){
    $file = 'saveVar.ini';
    if (file_exists($file)) {
        return parse_ini_file($file,1);
    }
    return false;
}
function get_root_file($path){
    if(empty($path)) return false;
    return substr($path,strlen($_SERVER['DOCUMENT_ROOT']));
}
/**
 * Проверяем есть и данная страка в обычном массиве + возвращаем его положение в нем
 * В противном случае -1 (если не найдет)
 * @param $search
 * @param $arr
 * @return bool|int
 */
function in_array_num($search,$arr){
    if(empty($arr)) return -1;
    if(!is_string($search)) return -1;
    foreach ($arr as $k=>$v){
        if($search == $v) return $k;
    }
    return -1;
}
/**
 * Отсканировать папку на наличие файлов
 * @param $dir — путь до сканируемой папки
 * @return array|bool
 */
function scandir_zak($dir){
    if(!file_exists($dir)) return false;
    $filelist = array();
    if($f = opendir($dir)){
        while($name = readdir($f)){
            if($name!='.' && $name!='..') $filelist[] = $name;
        }
        closedir($f);
    }
    return $filelist;
}
//if(AUTH){
//    $auth = 0;
//    if(isset($_COOKIE['p'])){
//        $p = $_COOKIE['p'];
//        if($_COOKIE['p']!=$md5){
//            setcookie('p','', time() - 3600,'/');
//            header('Location: /login'); exit;
//        }
//        $auth = 1;
//    }else{
//        if($url[0]!='login'){
//            header('Location: /login'); exit;
//        }
//    }
//}else{
//    $auth = 1;
//}
////Выход из пользователя
//if(isset($_GET['exit']) && $_GET['exit'] == 1){
//    setcookie('p','', time() - 3600,'/');
//    header('Location: /login'); exit;
//}

/**
 * получить временую метку ЛОКАЛЬНУЮ
 * @param $str (2018-07-06T07:06:08.007)
 * @param int $sdvig - кол-во дней ... D11  или M30
 * @return false|float|int
 */
function getLocalTime($str,$sdvig=0){
    $str = strtotime($str);
    if($sdvig>=0){
        $str += getTime($sdvig);
    }else{
        $str -= getTime($sdvig);
    }
    return $str;
}

/**
 * Время отображение СКРИПТА
 */
function SHOW_TIME($time = START){
    echo round(microtime(true)-$time,2);
}
