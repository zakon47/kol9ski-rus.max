<?php defined('_3AKOH') or die(header('/'));

include API.'markets_class.php';
class Markets extends Markets_parent{
    /**
     * Синхронизировать СЕРВЕР с БД
     */
    public function syncDB(){
        $SERVER = $this->repeat_collect('getmarkets');
//        dd($SERVER);
        if(!empty($SERVER) && $SERVER['success']){
            $insert = '';
            $update = [];
            $insert2 = [];
            #ПОЛУЧИЛИ ТЕКУЩИЕ МОНЕТЫ
            $DB = $this->getDB('id,market,min,isA,notice,md5',false);         //показать все позиции из DB
            if(isset($DB['error'])){
                $this->createTable();
                $DB = [];
            }         //если таблица ещё не создана
            $DB_KEY = $this->addKeyName($DB,'market');

            #ПЕРЕБИРАЕМ СПИСОК ПОЛУЧЕННЫХ МОНЕТ
            for($i=0;$i<count($SERVER['result']);$i++){    //перебираем новые SERVER маркеры
                $elem = &$SERVER['result'][$i];        //элемент
                #ЕСЛИ ЭТА МОНЕТА ЗАПРЕТНАЯ - ТО НЕ СИНХРОНИЗИРУЕМ
                if(in_array($elem['MarketName'],$this->CONFIG['block_coins'])) continue;

                $isA = (empty($elem['IsActive']))?'0':$elem['IsActive'];            //если пустое значение
                $md5 = md5($isA.$elem['MinTradeSize'].$elem['Notice']);         //md5 from SERVER

                #ПРОВЕРИЛИ НА АКТИВНУЮ ТОРГУЕМУЮ ПАРУ BTC,USDT
                $M = $elem['BaseCurrency'];
                $M .= "'";      //если выдруг совпадет часть монеты BT..[C]
                if(strpos(LOAD_COINS,$M)===false){
                    continue;
                }       //если не надо монету загружать - пропускаем

                #ТЕПЕРЬ РАСПРЕДЕЛЯЕМ ЭТУ МОНЕТУ  —  на UPDATE or INSERT
                $key = 1;   //разрешение на вставку новой записи в таблицу
                if(isset($DB_KEY[$elem['MarketName']])){
                    if($md5!=$DB_KEY[$elem['MarketName']]['md5']){      //обновляем контент!
                        $x = [
                            'id'            => $DB_KEY[$elem['MarketName']]['id'],      //id в DB
                            'elem_id'       => $i,                                      //id в SERVER
                            'min'           => $elem['MinTradeSize'],
                            'isA'           => $isA,
                            'logo'          => $elem['LogoUrl'],
                            'notice'        => $elem['Notice'],                          //какие-то сообщения
                            'md5'           => $md5
                        ];
                        $update[] = $x;
                    }
                    unset($DB_KEY[$elem['MarketName']]);
                    $key = 0;
                }
                #ПОДГОТОВКА ДЛЯ — ВСТАВКА НОВОЙ ЗАПИСИ
                if($key){   //не был найден в локальной таблице - значит эту запись надо вставить - создаем цифры
                    $s = $this->WALLET->getStatusCoin($elem['MarketName'],$elem['MinTradeSize']);  //получаем статус монеты + её активность [BUY,1]
                    if(!$s){
                        new myError('Не смог синхронизовать маркеры из ДБ с Сервером',['getStatusCoin'=>$s]);
                        return false;
                    }
                    $status = $s[0];        //BUY
                    $isOpen = $s[1];        //1

                    $insert2[] = $elem['MarketName'];
                    $insert .= ",('"
                        .$elem['MarketCurrency']."','"
                        .$elem['BaseCurrency']."','"
                        .$elem['MarketCurrencyLong']."','"
                        .$elem['BaseCurrencyLong']."','"
                        .$elem['MinTradeSize']."','"
                        .$elem['MarketName']."','"
                        .$isA."','"

                        .$status."','"
                        .$isOpen."','"

                        .$elem['Notice']."','"

                        .$elem['Created']."','"
                        .$elem['LogoUrl']."','"
                        .$md5."')";
                }
            }

            #ОБРАБОТКА МОНЕТ КОТОРЫЕ УДАЛИЛИ НА СЕРВЕРЕ а в DB они остались
            if(!empty($DB_KEY)){
                #ВЫБИРАЕМ МОНЕТЫ КОТОРЫЕ ЕЩЁ НЕ ОТКЛЮЧИЛИ
                $list = [];
                foreach ($DB_KEY as $k=>$v){
                    if($v['isA']) $list[] = $v;
                }
                #ОТКЛЮЧАЕМ МОНЕТЫ КОТОРЫЕ ЕЩЁ НЕ ОТКЛЮЧИЛИ т.к ИХ НЕТУ НА СЕРВЕРЕ ВООБЩЕ
                if(!empty($list)){
                    for($i=0,$c=count($list);$i<$c;$i++){
                        $e = &$list[$i];    //1 элемент
                        $upd = '';      //проверям изменились ли значения - если изменили -> ОБНОВЛЯЕМ
                        $num = array(':id'=>$e['id']);

                        $upd .= "`isA`=:isA,";
                        $upd .= "`md5`=:md5,";
                        $num[':isA'] = '0';
                        $num[':md5'] = md5($e['isA'].$e['min'].$e['notice']);

                        $upd = substr($upd,0,strlen($upd)-1);
                        $sql = "UPDATE ".$this->TABLE_NAME." SET $upd WHERE `id`=:id";
                        $this->DB->update($sql,$num);
                    }
                }
            }
            if(!empty($insert)){        //если есть что вставлять -> ВСТАВЛЯЕМ
                $insert = substr($insert,1);
                $sql = "INSERT INTO `$this->TABLE_NAME` (`cur`,`base`,`curN`,`baseN`,`min`,`market`,`isA`,`status`,`isOpen`,`notice`,`open`,`logo`,`md5`) VALUES $insert"; $num = array();
                $x = $this->DB->insert($sql,$num);
                if(isset($x['error'])){
                    $this->createTable();
                    $x = $this->DB->insert($sql,$num);
                    if(isset($x['error'])) new myError('Не вставил новый элемент MARKETS - была ошибка::bittrex',['$insert'=>$insert,'$sql'=>$sql]);
                }
            }
            if(!empty($update)) {        //если есть что обновить -> ОБНОВЛЯЕМ
                for($k=0,$c=count($update);$k<$c;$k++){
                    $upd = '';      //проверям изменились ли значения - если изменили -> ОБНОВЛЯЕМ
                    $num = array(':id'=>$update[$k]['id']);

                    $upd .= "`min`=:min,";
                    $upd .= "`isA`=:isA,";
                    $upd .= "`logo`=:logo,";
                    $upd .= "`notice`=:notice,";
                    $upd .= "`md5`=:md5,";
                    $num[':min'] = $update[$k]['min'];
                    $num[':isA'] = $update[$k]['isA'];
                    $num[':logo'] = $update[$k]['logo'];
                    $num[':notice'] = $update[$k]['notice'];
                    $num[':md5'] = $update[$k]['md5'];

                    $upd = substr($upd,0,strlen($upd)-1);
                    $sql = "UPDATE ".$this->TABLE_NAME." SET $upd WHERE `id`=:id";
                    $this->DB->update($sql,$num);
                }
            }
            return true;
        }else{
            new myError('Ошибка при получение дадынных с сервера new Markets::syncDB()',['SERVER'=>$SERVER]);
            return false;
        }
    }
}
