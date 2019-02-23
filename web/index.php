<?php
use Lib\DB;

if(!isset($argv)){
    session_start();
}
define('_3AKOH', 4.47); include __DIR__.'/../config.php';
define('START', microtime(true));       //время НАЧАЛА выполнения скрипта)


#ОТОБРАЖЕНИЕ ОШИБОК
ini_set('display_errors', $CONFIG['custom']['show_error']);         //отображаем ошибки
error_reporting($CONFIG['custom']['report_error']);                         //какие ошибки отображать
date_default_timezone_set($CONFIG['server']['timezone']);                   //локальная сервер зона

#КОНСТАНТЫ + MVC
define('SERVER_URL',        $CONFIG['server']['url']);
define('SERVER_PROTOCOL',   $CONFIG['server']['protocol']);
define('ERROR_LOG',         $CONFIG['custom']['log']);           //логирование ошибок
define('ERROR_SEND',        $CONFIG['custom']['send']);          //отправка оповещений об ошибке
$url = getRequest(isset($argv)?$argv:'');               //получаем $url

define('VER','?X');                   //версия проекта
//define('PROTOCOL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://');        //протокол сайта
define('HOME',__DIR__.'/../');
define('FORMS',HOME.'forms/');
define('TEMP',HOME.'temp/');
define('EXE',HOME.'exe/');
define('APP',HOME.'app/');
define('INC',APP.'include/');
define('BOTTOM',INC.'bottom/');
define('PATH', __DIR__.'/');                 // корень для внешних файлов например: /assets/theme/ относительно web
define('LINK', $CONFIG['server']['protocol'].$CONFIG['server']['url']);                 // имя домена
define('LAYOUT',APP.'layout/');
define('LIB',APP.'lib/');
define('API',LIB.'API/');
define('PAGE',APP.'page/');
define('FORMA',HOME.'forms/');
define('PREFIX_DB ','xxx');
//require HOME.'vendor/autoload.php';
//require HOME.'app/lib/DB.php';



#ПОДКЛЮЧАЕМ БД
//$DB = new DB($CONFIG['localhost']);    //подключились к БД
#СЧИТЫВАЕМ СРЕДУ В КОТОРОЙ РАБОТАЕМ
require LIB."php_header.php";

//ОПРЕДЕЛЯЕМ СРЕДУ!
//if(isset($url[0]) && $url[0]=='cron' && isset($url[1]) && isset($CONFIG['sreda'][$url[1]])){
//    define('SREDA', $url[1]);
//}else{
//    if(isset($_COOKIE['sreda']) && isset($CONFIG['sreda'][$_COOKIE['sreda']])){
//        define('SREDA', $_COOKIE['sreda']);
//    }else{
//        define('SREDA', key($CONFIG['sreda']));       //получаем первый ключ
//        setcookie('sreda',SREDA, time() + 3600*24,'/');        //сохраняем новый ключ
//    }
//}


//dd(1);


if($url[0]=='list') {
    include APP . $url[0] . '.php';
}else{
    if(empty($url[0])) $url[0] = 'index';
    include PAGE.$url[0].'.php';
}


?>
