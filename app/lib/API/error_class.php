<?php

/**
 * Class myError
 * ЛИЧНЫЙ ОБРАБОТЧИК ОШИБОК - [DOM-ДЕРЕВО + ЛОГИРОВАНИ + ОПОВЕЩЕНИЕ]
 * $msg = сообщение для оповещения ошибки
 * $data = массив данных которые задо тоже записать
 * $error_code - код ошибки - если автоматически он был не назначен  0...1024
 */
class myError{
    public function __construct($msg,$data=[],$error_code=0){
        $time = time();                     //время возникновения ошибки
        #ПОУЛЧАЕМ ТИП ОШИБКИ
        $num_err = [
            '0'  => 'E_ZAK',
            '1'  => 'E_ERROR',
            '2'  => 'E_WARNING',
            '4'  => 'E_PARSE',
            '8'  => 'E_NOTICE',
            '16'  => 'E_CORE_ERROR',
            '32'  => 'E_CORE_WARNING',
            '64'  => 'E_COMPILE_ERROR',
            '128'  => 'E_COMPILE_WARNING',
            '256'  => 'E_USER_ERROR',
            '512'  => 'E_USER_WARNING',
            '1024'  => 'E_USER_NOTICE',
        ];
        $type = $num_err[$error_code];      //Тип ошибки   —> E_WARNING
        #ПОЛУЧАЕМ STEEP ПРОЙДЕННЫЕ ДО ОШИБКИ
        $callers=debug_backtrace();     //получили стек вызовов
//        dd($callers);
        $steep = [];                    //иерархия возникновения ошибки - стек
        //dd($callers);
        $file = '';
        $line = '';
        for($i=0,$c=count($callers);$i<$c;$i++){
            $e = &$callers[$i];     //элемент ошибки
            #ЕСЛИ ЭТО ИСКУСТВЕННАЯ ОШИБКА — E_ZAK
            if($error_code==0 && $i==0){        //учитываем первый элемент
                $file = ' in <b>'.get_root_file($e['file']).'</b>';
                $line = ' on line <b>'.$e['line'].'</b>';
                continue;
            }
            #ЕСЛИ ЭТО СТАНДАРТНАЯ ОШИБКА
            if($error_code!=0){
                if($i==0) continue;     //первый элемент не учитываем
                if($i==1){
//                    if(!isset($e['file'])) dd($e);
                    #НЕ БЫЛО ОШИБОК В АРГУМЕНТАХ
                    if(isset($e['file'])){
                        $file = ' in <b>'.get_root_file($e['file']).'</b>';
                        $line = ' on line <b>'.$e['line'].'</b>';
                    }else{  //был передан плохой аргемент
                        $file = ' in <b>'.get_root_file($e['args'][2]).'</b>';
                        $line = ' on line <b>'.$e['args'][3].'</b>';
                    }
                    continue;
                }
            }
            if(isset($e['file'])) $from = get_root_file($e['file']).' ['.$e['line'].']';    //если есть путь ошибки

            $str = '<b>';
            if(isset($e['class'])) $str.=$e['class'].'::';
            $str.=$e['function'].'</b>';
//            dd($e);
            #ПОЛУЧАЕМ АРГУМЕНТЫ
            if(isset($e['args'])){
                if(empty($e['args'])){
                    $str.='()';
                } else{
                    $str.='(';
                    foreach ($e['args'] as $val){
                        if(is_array($val)){
                            $str.= 'Array, ';
                        }else{
                            if($val[1]==':') $val = get_root_file($val);
                            $str.= $val.', ';
                        }
                    }
                    if(strlen($str)>2){
                        $str = substr($str,0,-2);
                    }
                    $str.=')';
                }
            }
            $steep[] = ['from'=>$from,'run'=>$str];
        }
        #ФОРМИРУЕМ ОБЪЕКТ ОШИБКИ
        $error = [
            'msg'   =>'<b>'.$type.'</b>: '.$msg.$file.$line,
            'type'  => $type,
            'steep' =>$steep,
        ];
        if(!empty($data)) $error['data'] = $data;
        #ЕСЛИ ВКЛЮЧЕННО ЛОГИРОВАНИЕ
        if(ERROR_LOG){
            $ERROR = new Buffer(TEMP,'ERROR.log');
            $ERROR->add($time,$error);    //записали тело
        }
        #ЕСЛИ ВКЛЮЧЕННО ОТПРАВКА ОПОВЕЩЕНИЯ
        if(ERROR_SEND){
            $BOT = new Bot();
            $msg = hex2bin('F09F85B0')." <b>".$msg."</b>";        //делаем жирность
            #СОЗДАЛИ ССЫЛКУ
            $msg.="\n".LINK.'/error/'.$time;
            $BOT->sendMessage('br.max',$msg);
        }
        if($type==2 || $type==8){
            dd($error,1);
        }else{
            dd($error);
        }
    }
}
/**
 * ПЕРЕХВАТЫВАЕМ ВСЕ ОШИБКИ И ОБРАБАТЫВАЕМ ИХ
 * @param $error_code - код ошибки
 * @param $msg - сообщение
 */
function errHandler($error_code, $msg){
    if ($error_code) {
        new myError($msg,[],$error_code);
    }
}

#устанавливает пользовательский обработчик ошибок.
if(isset($CONFIG['custom']['myerror']) && $CONFIG['custom']['myerror']) set_error_handler('errHandler', error_reporting());