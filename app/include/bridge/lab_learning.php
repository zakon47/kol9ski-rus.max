<?php

use Brocker\Brocker;
$BR = new Brocker('bittrex','x0_16');
$BR->init();

#ПОЛУЧИЛИ СПИСОК СТРАТЕГИЙ по монете и записали всю инфу в буфер!
function CREATE_LIST_STRATEGY($coin,$date){
    #Стандартизировали формат даты
	if(!isset($date['x1'])){
		$old_date = $date;
		$date = [
			'x1' => $date[0][0],
			'x2' => $date[count($date)-1][1]
		];
    }
	global $BR;
	#Получили список стратегий
	$strategy = $BR->getStrategy($coin);                //текущие стратегии
	$DATA_list = $BR->generateStrategy($strategy);      //+ сгенерировали новые
//	dd($DATA_list);

	#Получили СТЕК
	$STACK_DB = $BR->getStackDB($coin,$date,$DATA_list[count($DATA_list)-1]);
	$ARR = [
		'opt' => [
			'coin' => $coin,
			'date' => $date
		],
		'temp' => $DATA_list,
		'stack' => $STACK_DB
	];
	if(isset($old_date)) $ARR['opt']['date_steep'] = $old_date;     //сохраняем запрашиваемые промежутки

	#ДОБАВИЛИ ВСЕ В БУФЕР
	$BUF = new Buffer(TEMP,'optimizator-cash/'.$coin);
	$BUF->add('body',$ARR);

	return count($DATA_list);       //вернули кол-во стратегий
};
#ПОСЧИТАТЬ ОДНУ ИЗ СТРАТЕГИЙ
function STRATEGY_FROM_BUF($coin,$id,$key=FALSE){
	global $BR;
	$BUF = new Buffer(TEMP,'optimizator-cash/'.$coin);
	$BODY = $BUF->get('body');              //получили список ПРОВЕРЯЕМЫХ стратегий
	$date = $BODY['opt']['date'];
//dd($date);

	$simulator = $BR->simulator($BODY['opt']['coin'],$date,$BODY['temp'][$id],$BODY['stack']);
	$BUF->add('res_'.$id,$simulator);
//	$BUF2 = new Buffer(TEMP,"TEMP");
//	$simulator = $BUF2->get('data');
//	dd($simulator);

	#ВЫПОЛНЯЕМ код для ключа
	if($key){
		$limit = [];
		$day = pTime('D1');
		$date['x1'] = round_time($date['x1'],$day)-pTime('H3');
		$date['x2'] = round_time($date['x2'],$day,1)-pTime('H3');
		for($i=$date['x1'];$i<$date['x2'];$i+=$day){
			$limit[] = [
				'ot' => $i,
				'do' => $i+$day,
			];
		}
		$count_sim = count($simulator['act']);
		if($count_sim){
			for($i=0,$c=count($limit);$i<$c;$i++){
				$e = &$limit[$i];
				for($j=0;$j<$count_sim;$j++){
					$b = &$simulator['act'][$j];
					if($b['x2']>=$e['ot'] && $b['x2']<$e['do']){
						$e['data'][] = [
							'status' => $b['status'],
							'y2' => $b['y2']
						];
					}
				}
			}
		}
		for($i=0,$c=count($limit);$i<$c;$i++){
			$e = &$limit[$i];
			if(!isset($e['data'])){
				$e = $BR::num_format(0);
				continue;
			}
			$buy = false;
			$itog = 0;
			for($j=0,$z=count($e['data']);$j<$z;$j++){
				$p = &$e['data'][$j];
				if($p['status']=='BUY'){
					$buy = $p['y2'];
				}else{
					if($buy){
						$itog += ($p['y2']-$buy);
					}
				}
			}
			$e = $BR::num_format($itog);
		}
		$simulator['limit'] = $limit;
	}

//	dd($BR->getStatistic());
	return $simulator;
}



//ЗАПУСК СТРАТЕГИИ - 1 этап             → получили список проверяемых стратегий
if(isset($_POST['action_go'])){
	$res = CREATE_LIST_STRATEGY($_POST['coin'],$_POST['date']);
	echo $res;
	die();
}
//ЗАПУСК СТРАТЕГИИ - 2 этап             → получили
if(isset($_POST['action_go_x2'])){
	$res = STRATEGY_FROM_BUF($_POST['coin'],$_POST['id']);
	echo $res;
	die();
}