<?php defined('_3AKOH') or die(header('/'));

#ОБЩИЕ НАСТРОЙКИ
include LIB.'api_header.php';          //подключили общие настройки

#ИНДИВИДУАЛЬНЫЕ НАСТРОЙКИ API СЕРВЕРА
define('OFFSET_TIME',   pTime('H'.$CONFIG['sreda'][SREDA]['timezone']));     //разница между сервером откуда берем данные и нашим

define('DELTA_BUY_SELL',     20);           //дельта неточности покупки в процентах, 0 - без коррекции


include API.'bot_class.php';       //подключаем бота для телеги
include API.'error_class.php';     //подключаем обработчик ошибок
include API.'ask_class.php';       //подключаем бота для телеги
include API.SREDA.'/collect.php';     //подключаем COLLECT
include API.SREDA.'/wallet.php';      //подключаем WALLET
include API.SREDA.'/market.php';      //подключаем MARKET
include API.SREDA.'/stack.php';       //подключаем STACK
include API.'core_class.php';         //подключаем обработчик ошибок
include API.'analitics_class.php';    //подключаем АНАЛИТИКУ
return true;
