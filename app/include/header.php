<?
$LOG = new Buffer(TEMP,'ERROR.log');
$err = $LOG->get();    //получили все ошибки
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?=$PAGE['title']?></title>
    <link href="/css/zlib/zgrid.min.css?<?=_3AKOH?>" rel="stylesheet"></link>
    <link href="/css/index.min.css?<?=_3AKOH?>" rel="stylesheet"></link>
    <link href="/css/font-awesome.min.css?<?=_3AKOH?>" rel="stylesheet"></link>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/d3.v4.min.js"></script>
</head>
<body>
<script>
    var timer = false;
    var timer_old = 0;
    var prop = {
        'path':<?=(isset($_COOKIE['path2']))?$_COOKIE['path2']:'""'?>,
        'analizator':<?=(isset($_COOKIE['analizator']))?$_COOKIE['analizator']:'""'?>,
    };
    var var_time_name = {
        M5   :'5 минут',
        M10  :'10 минут',
        M15  :'15 минут',
        M30  :'30 минут',
        H1  :'1 час',
        H2  :'2 часа',
        H6  :'6 часов',
        H12  :'12 часов',
        H24  :'24 часа',
        D1  :'1 день',
    };
    function time_name(name){
        if(var_time_name[name]!=undefined){
            return var_time_name[name];
        }else {
            return name;
        }
    }
    function sprintf( ) {	// Return a formatted string
        //
        // +   original by: Ash Searle (http://hexmen.com/blog/)
        // + namespaced by: Michael White (http://crestidg.com)

        var regex = /%%|%(\d+\$)?([-+#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuidfegEG])/g;
        var a = arguments, i = 0, format = a[i++];

        // pad()
        var pad = function(str, len, chr, leftJustify) {
            var padding = (str.length >= len) ? '' : Array(1 + len - str.length >>> 0).join(chr);
            return leftJustify ? str + padding : padding + str;
        };

        // justify()
        var justify = function(value, prefix, leftJustify, minWidth, zeroPad) {
            var diff = minWidth - value.length;
            if (diff > 0) {
                if (leftJustify || !zeroPad) {
                    value = pad(value, minWidth, ' ', leftJustify);
                } else {
                    value = value.slice(0, prefix.length) + pad('', diff, '0', true) + value.slice(prefix.length);
                }
            }
            return value;
        };

        // formatBaseX()
        var formatBaseX = function(value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
            // Note: casts negative numbers to positive ones
            var number = value >>> 0;
            prefix = prefix && number && {'2': '0b', '8': '0', '16': '0x'}[base] || '';
            value = prefix + pad(number.toString(base), precision || 0, '0', false);
            return justify(value, prefix, leftJustify, minWidth, zeroPad);
        };

        // formatString()
        var formatString = function(value, leftJustify, minWidth, precision, zeroPad) {
            if (precision != null) {
                value = value.slice(0, precision);
            }
            return justify(value, '', leftJustify, minWidth, zeroPad);
        };

        // finalFormat()
        var doFormat = function(substring, valueIndex, flags, minWidth, _, precision, type) {
            if (substring == '%%') return '%';

            // parse flags
            var leftJustify = false, positivePrefix = '', zeroPad = false, prefixBaseX = false;
            for (var j = 0; flags && j < flags.length; j++) switch (flags.charAt(j)) {
                case ' ': positivePrefix = ' '; break;
                case '+': positivePrefix = '+'; break;
                case '-': leftJustify = true; break;
                case '0': zeroPad = true; break;
                case '#': prefixBaseX = true; break;
            }

            // parameters may be null, undefined, empty-string or real valued
            // we want to ignore null, undefined and empty-string values
            if (!minWidth) {
                minWidth = 0;
            } else if (minWidth == '*') {
                minWidth = +a[i++];
            } else if (minWidth.charAt(0) == '*') {
                minWidth = +a[minWidth.slice(1, -1)];
            } else {
                minWidth = +minWidth;
            }

            // Note: undocumented perl feature:
            if (minWidth < 0) {
                minWidth = -minWidth;
                leftJustify = true;
            }

            if (!isFinite(minWidth)) {
                throw new Error('sprintf: (minimum-)width must be finite');
            }

            if (!precision) {
                precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type == 'd') ? 0 : void(0);
            } else if (precision == '*') {
                precision = +a[i++];
            } else if (precision.charAt(0) == '*') {
                precision = +a[precision.slice(1, -1)];
            } else {
                precision = +precision;
            }

            // grab value using valueIndex if required?
            var value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++];

            switch (type) {
                case 's': return formatString(String(value), leftJustify, minWidth, precision, zeroPad);
                case 'c': return formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad);
                case 'b': return formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
                case 'o': return formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
                case 'x': return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
                case 'X': return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad).toUpperCase();
                case 'u': return formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
                case 'i':
                case 'd': {
                    var number = parseInt(+value);
                    var prefix = number < 0 ? '-' : positivePrefix;
                    value = prefix + pad(String(Math.abs(number)), precision, '0', false);
                    return justify(value, prefix, leftJustify, minWidth, zeroPad);
                }
                case 'e':
                case 'E':
                case 'f':
                case 'F':
                case 'g':
                case 'G':
                {
                    var number = +value;
                    var prefix = number < 0 ? '-' : positivePrefix;
                    var method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())];
                    var textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2];
                    value = prefix + Math.abs(number)[method](precision);
                    return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]();
                }
                default: return substring;
            }
        };

        return format.replace(regex, doFormat);
    }

    //Отобразить время в формате часы:минуты:секунды
    function getTime(time){
        let sec = time%60;
        if(sec<10) sec = '0'+sec;
        let min = Math.floor(time/60);
        if(min<10) min = '0'+min;
        let hours = Math.floor(time/60/60);
        if(hours<10) hours = '0'+hours;
        return hours+':'+min+':'+sec;
    }
    function sleep(ms) {
        var date = new Date();
        var curDate = null;
        do { curDate = new Date(); }
        while(curDate-date < ms);
    }   //задержка
    function getTimestemp(date,change=''){
        change *= 1;
        date = date.replace(":",'-');
        date = date.split("-");     //разбиваем строку
        date = new Date(date[2],date[1]-1,date[0],date[3],date[4]);
        date.setMinutes(date.getMinutes()+change);
        return date;
    }
    function zeroise(num,count){
        return sprintf('%0'+count+'d', num);    // 0055
    }
    var day_name = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
    function $_GET(key) {
        var p = window.location.search;
        p = p.match(new RegExp(key + '=([^&=]+)'));
        return p ? p[1] : false;
    }
    function loader_parent(key){
        var loader_parent = $('#loader_parent img');
        var time = loader_parent.parent().children('span');
        var i = 0;
        if(key){
            loader_parent.css({'visibility':'visible'});
            time.text(i+'/'+timer_old);
            i++;
            time.css({'visibility':'visible'});
            timer = setInterval(function () {
                time.text(i+'/'+timer_old);
                i++;
            },1000);
        }else{
            loader_parent.css({'visibility':'hidden'});
            clearInterval(timer);
            timer_old = time.text().split('/')[0];
            time.css({'visibility':'hidden'});
        }
    }
</script>
<div class="line <?=(!$auth)?'-none':''?>">
    <div class="zt">
        <div class="zt-cell line__navig">
            <?
//            dd($mark);
            if($url[0]=='lab'):?>
                <form action="/grafic" method="post" id="coins">
                    <?
                    if(isset($_COOKIE['coin']) && $_COOKIE['coin']!='undefined-undefined'){
                        $ex = explode('-',$_COOKIE['coin']);
                        $n1 = $ex[0];
                        $n2 = $ex[1];
                    }
                    $n1 = (isset($n1))?$n1:'BTC';
                    $n2 = (isset($n2))?$n2:'1ST';
                    ?>
                    <?if(isset($mark) && !empty($mark)):?>
                        <label>
                            <select name="base">
                                <?foreach ($mark as $k=>$v):?>
                                    <?if($k==$n1):?>
                                        <option value="<?=$k?>" selected="selected"><?=$k?></option>
                                    <?else:?>
                                        <option value="<?=$k?>"><?=$k?></option>
                                    <?endif;?>
                                <?endforeach;?>
                            </select>
                        </label>
                        <label>
                            <select name="current">
                                <?for($i=0;$i<count($mark[$n1]);$i++):?>
                                    <?if($mark[$n1][$i]['cur']==$n2):?>
                                        <option value="<?=$mark[$n1][$i]['cur']?>" selected="selected"><?=$mark[$n1][$i]['cur']?></option>
                                    <?else:?>
                                        <option value="<?=$mark[$n1][$i]['cur']?>"><?=$mark[$n1][$i]['cur']?></option>
                                    <?endif;?>
                                <?endfor;?>
                            </select>
                        </label>
                    <?endif;?>
                    <button class="btn -grey">Показать</button>
                    <a href="#" class="get_strategy" id="get_strategy">Show STR</a>
                </form>
            <?elseif ($url[0]=='change'):?>
                <form action="/change" method="post" id="wallet">
                    <?
                    if(isset($_COOKIE['wallet'])){
                        $span = $_COOKIE['wallet'];
                    }
                    $span = (isset($span))?$span:'';
                    ?>
                    <span>Выберите аккаунт &nbsp;</span>
                    <label>
                        <select name="wallet">
                            <?if(isset($CONFIG['wallet']) && !empty($CONFIG['wallet'])):?>
                            <?foreach ($CONFIG['wallet'] as $k=>$v):?>
                                <?if($k==$span):?>
                                    <option value="<?=$k?>" selected="selected"><?=$k?></option>
                                <?else:?>
                                    <option value="<?=$k?>"><?=$k?></option>
                                <?endif;?>
                            <?endforeach;?>
                            <?else:?>
                                <option value="">Нет активных аккаунтов</option>
                            <?endif?>
                        </select>
                    </label>
                    <button type="button" class="-set-upd">Обновить</button>
                </form>
            <?elseif ($url[0]=='learning'):?>
                <form action="/grafic" method="post" id="coins">
                    <?
                    if(isset($_COOKIE['coin'])){
                        $ex = explode('-',$_COOKIE['coin']);
                        $n1 = $ex[0];
                        $n2 = $ex[1];
                    }
                    $n1 = (isset($n1))?$n1:'BTC';
                    $n2 = (isset($n2))?$n2:'1ST';
                    ?>
                    <label>
                        <select name="base">
                            <?if(isset($mark) && !empty($mark)):?>
                                <?foreach ($mark as $k=>$v):?>
                                    <?if($k==$n1):?>
                                        <option value="<?=$k?>" selected="selected"><?=$k?></option>
                                    <?else:?>
                                        <option value="<?=$k?>"><?=$k?></option>
                                    <?endif;?>
                                <?endforeach;?>
                            <?endif;?>
                        </select>
                    </label>
                    <button class="btn -grey">Показать</button>
                </form>
            <?endif?>
        </div>
        <div class="zt-cell line__exit">
            <a href="/error" class="<?=(!empty($err))?'-new':''?>">Ошибки</a>
            <a href="/">Главная</a>
            <select name="core_sreda">
                <option value="bittrex">bittrex</option>
            </select>
            <a href="?exit=1">Выйти из системы</a>
        </div>
    </div>
    <div class="line__logo" id="loader_parent"><span></span><img src="/img/loader2.gif" alt="" style="vertical-align: middle;"> Coin-Analizator <b style="color: #333;">v<?=_3AKOH?></b></div>
</div>