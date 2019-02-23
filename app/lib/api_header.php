<?php defined('_3AKOH') or die(header('/'));

#ОБЩИЕ НАСТРОЙКИ ДЛЯ ВСЕХ API СИСТЕМ
define('DATE_FORMAT','d/m/y → H:i:s');       //кол-во секунд в 1 дне
define('DATE_FORMAT_DB','Y-m-d H:i:s');       //кол-во секунд в 1 дне
define('DATE_PARSE','%d/%m/%y—[%H:%M:%S]');       //кол-во секунд в 1 дне
define('ANALYSIS_TIME_MIN','2017-01-22 00:00:00');  //минимальное время для теста
define('ANALYSIS_TIME_MAX','2020-01-22 00:00:00');  //максимальное время для теста
define('TO4NOST'            ,'16,8');       //точность данных для записи в БД
define('TIME_VYBORKI_HISTORY'            ,getTime('D7'));       //точность данных полученых от ЗАКРЫТЫХ ОРДЕРОВ

#РАБОТА С БД
define('TABLE_NAME_WALLET',PREFIX_DB.'WALLET');       //название таблицы
define('TABLE_NAME_MARKETS',PREFIX_DB.'MARKETS');       //название таблицы
define('TABLE_NAME_HISTORY',PREFIX_DB.'HISTORY');       //название таблицы
define('GET_DATA_STACK','dates as d,high as max,low as min,buy,sell');                               //обычная выборка
define('GET_DATA_WALLET','cur,balance,dostupno,address');                               //обычная выборка
define('GET_DATA_WALLET_UPD','id,cur,balance,dostupno,isA,md5');         //при синхронизации
define('GET_DATA_MARKETS','id,coin,cur,base,min,isA,status,isOpen,opt,strategy');     //обычная выборка
define('GET_DATA_MARKETS_UPD','id,coin,min,isA,notice,md5');                          //при синхронизации

#АНАЛИЗАТОР
define('DEFAULT_LOSS_BOTTOM',5);    //значение для шаблона - на сколько % установить lost_bottom ниже
define('TO4NOST_LAST_PRICE',10);    //кол-во выбраных элементов из БД, опр.точность  last_price из БД
define('GO_STACK_DAY',getTime('H1'));       //кол-во дней, по которым есть информация из БД для анализа

//переключаем аккаунт
if(isset($_POST['upd_wallet']) && isset($_COOKIE['wallet'])){
    if($_COOKIE['wallet']!=$_POST['wallet']){
        $_COOKIE['wallet'] = $_POST['wallet'];
        setcookie('wallet',$_POST['wallet'], time() + 3600*24,'/');
    }
}
