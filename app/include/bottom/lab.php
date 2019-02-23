<script>
    var data_server = {};
    var data_optimizator = [];

    function getCoin() {
        var coins = $('#coins');
        if($_GET('coin')){  //если есть GET Запрос монеты
            return $_GET('coin');
        }else if(coins.find('[name=base]').val()!=undefined){
            return coins.find('[name=base]').val()+'-'+coins.find('[name=current]').val();      //пара
        }else{
            return '';
        }
    }
    //Инициализируем страницу - собираем данные и отправляем их на сервер
    function initPage(){
        loader_parent(1);
        var data = {};
        var coins = $('#coins');
        var coins_info = $('.coin');
        var set_time = $('#set_time');
        var period = $('#period');
        var sim = $('.simulat__form');
        var timings = $('.simulat__first .-timings');
        var _ot = timings.find('.-ot');
        var _do = timings.find('.-do');
        var date = getTimestemp(set_time.find('[name=date]').val()+'-'+set_time.find('[name=time]').val());
        _ot = getTimestemp(_ot.find('[name=date]').val()+'-'+_ot.find('[name=time]').val());
        _do = getTimestemp(_do.find('[name=date]').val()+'-'+_do.find('[name=time]').val());

        data['initPage'] = 1;      //пара

        data['coin'] = getCoin();

        data['date'] = date.getTime()/1000; // переводим в секунды
        data['group'] = period.find('[name=group]').val();
        data['dlina'] = period.find('[name=dlina]').val();
        data['analizator_type'] = coins_info.find('[name=analizator_type]:checked').val();
        data['analiz_size'] = coins_info.find('[name=analiz_size]').val();
        data['analiz_count'] = coins_info.find('[name=analiz_count]').val();
        data['simulator'] = sim.find('[name=simulator]').prop('checked');
        data['_ot'] = _ot.getTime()/1000;
        data['_do'] = _do.getTime()/1000;
        data['optimzacia'] = sim.find('[name=optimzacia]').prop('checked');
        $.ajax({
            type: "POST",
            url: '/lab',
            async: true,
            data: data,
            success: function (data) {
                var error = 0;
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    error = 1;
                    console.error('Проблемы с JSON:');
                    console.error(data);
                    loader_parent(0);
                    return false;
                }
                if(!error){
                    console.log(data);
                    data_server = data;
                    init_grafic();          //отображаем график
                    init_stat();            //отображаем
                    if(data['analizator']!=undefined) init_analizator();
                    if(data['simulator']!=undefined) init_simulator();
                    loader_parent(0);
                }
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
        return false;
    }

    //Производим сортировку!
    $('.simulat__last .-vkl .-icons button').on('click',function(){
        var list = data_optimizator;
        list.sort(data_optimizator_sort);     //сортируем

        var len = list.length;
        if(len>0){
            var mesto = $('.simulat__last .-result');       //место куда вставлем
            mesto.empty();
            var temp = "<div class=\"-list zt\">\n" +
                "           <div class=\"zt-cell -num\"><div>1</div></div>\n" +
                "           <div class=\"zt-cell -bbb\"><button>Применить</button></div>\n" +
                "           <div class=\"zt-cell -info\">+ 0.05448985</div>\n" +
                "       </div>";
            var block_X = $(temp);

            //перебираем элементы
            for(var i=0;i<len;i++){
                var block = block_X.clone();

                var elem = list[i];
                block.find('.-num div').text(elem.time);
                var kpd = elem.data.kpd
                block.find('.-info').text(kpd);
                if(kpd>0){
                    block.addClass('-good');
                }else{
                    block.addClass('-red');
                }
                block.data('id',elem.id);

                mesto.append(block);
            }
        }
        return false;
    });
    function loading_optimizator(id) {
        //загрузка лоадера
        var loder_bar = $('.simulat__last .-load .-progress');
        var count_str = data_server.optimizator.count_strategy;
        var current_str = data_server.optimizator.res.length;

        //рисуем загрузчик!
        if(count_str == current_str){
            //убрать загрузчик
        }
        var proc = (current_str*100)/count_str;
        loder_bar.text(proc+'%');
        loder_bar.css({width:proc+'%'});

        //Вставка полученного блока
        if(count_str>0){
            var mesto = $('.simulat__last .-result');       //место куда вставлем
            // if(len==1) mesto.empty();
            mesto.show(200);
            var temp = "<div class=\"-list zt\">\n" +
                "           <div class=\"zt-cell -num\"><div>1</div></div>\n" +
                "           <div class=\"zt-cell -bbb\"><button>Применить</button></div>\n" +
                "           <div class=\"zt-cell -info\">+ 0.05448985</div>\n" +
                "       </div>";
            var block = $(temp);
            //берем последний эллемент
            var elem = data_server.optimizator.res[id];

            console.log(elem);
            return false;
            block.find('.-num div').text(id);
            var kpd = elem.data.kpd;
            block.find('.-info').text(kpd);
            if(kpd>0){
                block.addClass('-good');
            }else{
                block.addClass('-red');
            }
            block.data('id',elem.id);

            mesto.append(block);
            // console.log(elem);
        }
    }
    function set_block_optimizator(){
        var loader = $(this).next('.-loader');
        loader.hide();
        var len = data_optimizator.length;

    }

    $('.action__perexod .-set-time-now button').on('click',function(){
        //Получаем элементы текущего времени
        var t = new Date();
        var mes9c = (t.getMonth()+1);
        if(mes9c<10) mes9c = '0'+mes9c;
        var den = t.getDate();
        if(den<10) den = '0'+den;
        var chas = t.getHours();
        if(chas<10) chas = '0'+chas;
        var minuta = t.getMinutes();
        if(minuta<10) minuta = '0'+minuta;

        //Формируем строчки для вывода
        var date = den+'-'+mes9c+'-'+t.getFullYear();
        var time = chas+':'+minuta;

        //Устанавливаем новые значения
        $('.action__perexod .-data input').val(date);
        $('.action__perexod .-time input').val(time);
    });

    function data_optimizator_sort(a,b) {
        if (a.data.kpd > b.data.kpd)
            return -1;
        if (a.data.kpd < b.data.kpd)
            return 1;
        return 0;
    }
    //ЗАПУСК ОПТИМИЗАТОРА!
    function send_data_optimizator(i,len){
        if(data_server.optimizator[i]==undefined) return false;
        //ОТПРАВЛЯЕМ КАЖДУЮ СТРАТЕГИ И ЖДЕМ РЕЗУЛЬТАТ
        var data = {};
        var sim = $('.simulat__form');
        var timings = $('.simulat__first .-timings');
        var coins = $('#coins');
        var _ot = timings.find('.-ot');
        var _do = timings.find('.-do');
        _ot = getTimestemp(_ot.find('[name=date]').val()+'-'+_ot.find('[name=time]').val());
        _do = getTimestemp(_do.find('[name=date]').val()+'-'+_do.find('[name=time]').val());

        data['optimization'] = 1;
        data['coin'] = coins.find('[name=base]').val()+'-'+coins.find('[name=current]').val();      //пара
        data['simulator'] = sim.find('[name=simulator]').prop('checked');
        data['_ot'] = _ot.getTime()/1000;
        data['_do'] = _do.getTime()/1000;

        var time = performance.now();   //начало
        let e = data_server.optimizator[i];     //DATA
        data['DATA'] = e;
        $.ajax({
            type: "POST",
            url: '/lab',
            // async: 1,
            data: data,
            success: function (data) {
                if(i==len-1){
                    var loader = $('.simulat__last .-vkl .-button .-loader');
                    loader.hide();     //убираем лоадер на последнем элементе загрузки
                }
                time = performance.now() - time;    //конец
                var error = 0;
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    error = 1;
                    console.error('Проблемы с JSON:');
                    console.error(data);
                    return false;
                }
                if(!error){
                    var obj = {
                        data:data,
                        time:(time/1000).toFixed(1),
                        $DATA:e,
                        id:data_optimizator.length
                    };
                    data_optimizator.push(obj);        //вставили в общий стек
                    set_block_optimizator();        //вставить новый элемент
                    i++;
                    send_data_optimizator(i,len)
                    return false;
                }
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
    }

    //ВЫЗВАЛИ ОПТИМИЗАЦИЮ СТРАТЕГИИ
    $('#form_optimizator').on('click',function () {
        data_optimizator = [];
        //Показали загрузчик
        var loader = $(this).next('.-loader');
        loader.show();
        //получаем время
        var timings = $('.simulat__first .-timings');
        var _ot = timings.find('.-ot');
        var _do = timings.find('.-do');
        _ot = getTimestemp(_ot.find('[name=date]').val()+'-'+_ot.find('[name=time]').val());
        _do = getTimestemp(_do.find('[name=date]').val()+'-'+_do.find('[name=time]').val());
        _ot = _ot.getTime()/1000;
        _do = _do.getTime()/1000;
        if(_ot==_do){
            alert("Промежутки совпадают!");
            loader.hide();
            return false;
        }
        //Отправляем запрос на получение стратегий
        var coins = $('#coins');
        var data = {};
        data['get_strategy_all'] = 1;

        //получаем монету
        var coin = getCoin();
        data['coin'] = coin;      //пара
        data['_ot'] = _ot;      //пара
        data['_do'] = _do;      //пара
        if(data['coin']==''){
            alert('Отсутствует монета!');
            loader.hide();
            return false;
        }
        var count_strategy = 0;

        $.ajax({
            type: "POST",
            url: '/lab',
            async: 0,
            data: data,
            success: function (data_count) {
                data_count = data_count*1;
                count_strategy = data_count;
                data_server['optimizator'] = {'count_strategy':data_count,'res':[]};
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
        //ЕСЛИ ЕСТЬ ДАННЫЕ КОТОРЫЕ НАДО ПРОВРИТЬ - ЗАПУСКАЕМ ЦИКЛ = count($stratedy)
        if(count_strategy>0){           //=2 если всего 2 элемента
            //создаем сокет соединение

            var data = {};
            data['get_strategy_all_send'] = 1;
            data['coin'] = coin;
            data['_ot'] = _ot;
            data['_do'] = _do;
            var i = 0;
            while (i<count_strategy){
                data['id'] = i;
                // console.log(i,0);
                $.ajax({
                    type: "POST",
                    url: '/lab',
                    async: 1,
                    data: data,
                    success: function (data) {
                        try {
                            data = JSON.parse(data);
                        }catch (e) {
                            console.log(data);
                            alert("Критическая ошибка распознавания!");
                            return false;
                        }
                        data_server.optimizator.res.push(data.data);
                        // loading_optimizator(data.id);
                    },
                    error: function (result) {
                        alert('ошибка при загрузке страницы');
                    }
                });
                i++;
            }
            loader.show();
            return false;

            if(0){
                try{
                    var socket = new WebSocket("ws:<?=$CONFIG['socket']['web']?>");
                }catch (e) {
                    console.log(e);
                    return false;
                }
                var status = $('#socket_status');
                socket.onopen = function (event) {
                    console.log(status);
                    status.show();

                    //отправляем полученные данные
                    if(socket.readyState){
                        //перебираем все стратегии и запрашиваем для каждой ее результат!
                        var data = {};
                        data['get_strategy_all_send'] = 1;
                        data['coin'] = coin;
                        data['_ot'] = _ot;
                        data['_do'] = _do;
                        for (var i=1;i<=count_strategy;i++){
                            data['id'] = i-1;
                            $.ajax({
                                type: "POST",
                                url: '/lab',
                                async: 1,
                                data: data,
                                success: function (data) {
                                    console.log(data);
                                },
                                error: function (result) {
                                    alert('ошибка при загрузке страницы');
                                }
                            });
                        }
                        socket.close();
                    }
                };
                socket.onmessage = function (event) {       //обрабатываем значение от SOCKET сервера!
                    console.log(event.data);
                    alert(1);
                };
                socket.onerror = function (event) {
                    status.hide();
                };
                socket.onclose = function (event) {
                    status.hide();
                };

                return false;

                //ВРЕМЯ ВЫПОЛНЕНИЯ
                var alltime = performance.now();        //ВСЕ ВРЕМЯ

                send_data_optimizator(0,len);

                //И НАДО ВСТАВИТЬ ОСТАТКИ ВРЕМЕНИ
                alltime = performance.now() - alltime;    //конец
                alltime = (alltime/1000).toFixed(1);
            }

        }
        loader.hide();
        return false;
    });
    //ПОКАЗАТЬ СТРАТЕГИЮ
    $('body').on('click','.simulat__last .-result .-list .-info',function () {
        var id = $(this).closest('.-list').data('id');
        alert(JSON.stringify(data_optimizator[id]["$DATA"], null, 4));
    });

    //СИМУЛЯЦИИ - изменили диапазоны времени для СИМУЛЯЦИИ
    $('#form_simulator').on('click',function(){
        loader_parent(1);
        var data = {};
        var sim = $('.simulat__form');
        var timings = $('.simulat__first .-timings');
        var coins = $('#coins');
        var _ot = timings.find('.-ot');
        var _do = timings.find('.-do');
        _ot = getTimestemp(_ot.find('[name=date]').val()+'-'+_ot.find('[name=time]').val());
        _do = getTimestemp(_do.find('[name=date]').val()+'-'+_do.find('[name=time]').val());

        data['form_simulator'] = 1;
        data['coin'] = coins.find('[name=base]').val()+'-'+coins.find('[name=current]').val();      //пара
        data['simulator'] = sim.find('[name=simulator]').prop('checked');
        data['_ot'] = _ot.getTime()/1000;
        data['_do'] = _do.getTime()/1000;
        data['optimzacia'] = sim.find('[name=optimzacia]').prop('checked');
        $.ajax({
            type: "POST",
            url: '/lab',
            async: true,
            data: data,
            success: function (data) {
                var error = 0;
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    error = 1;
                    console.error('Проблемы с JSON:');
                    console.error(data);
                    return false;
                }
                if(!error){
                    data_server['simulator'] = data;
                    init_simulator();
                }
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
        return false;
    });



    //CHANGE - изменили монету или время отсчета для графика
    $('#set_time,#coins').on('submit',function(){
        initPage();
        return false;
    });

    //ПОКАЗАЛИ СТРАТЕГИЮ МОНЕТЫ
    $('#get_strategy').on('click',function(){
        var data = {};
        var coins = $('#coins');
        data['get_strategy'] = 1;      //пара
        data['coin'] = getCoin();      //пара
        if(data['coin']=='') alert('Не найдена запрашиваемая монета!');
        $.ajax({
            type: "POST",
            url: '/lab',
            async: true,
            data: data,
            success: function (data) {
                alert(data);
            },
            error: function (result) {
                alert('ошибка при получение данных');
            }
        });
        return false;
    });
    //ПРАВАЯ ПАНЕЛЬ - изменили любое значение из правой панели
    $('.coin__info .-item .-checked input,.coin__info .-item .-num input,.coin__info .-dop-var input[type=text],.simulat__form .-button input').on('change',function(){
        var name = $(this).attr('name');
        var data = {};
        var prop = ($(this).prop('checked'))? 1 : 0;
        if(name=='simulator'){
            data['simulator'] = prop;
            var btn = $(this).next('button');
            var simulator__SVG = $('.grafic__SVG .-simulator');
            var optimzacia = $(this).parents('form').eq(0).find('[name=optimzacia]');
            if(prop){
                btn.removeAttr('disabled');
                optimzacia.removeAttr('disabled');
                simulator__SVG.show();
                if(optimzacia.prop('checked')){
                    optimzacia.next('button').removeAttr('disabled');
                }else{
                    optimzacia.next('button').attr('disabled',1);
                }
            }else{
                simulator__SVG.hide();
                optimzacia.attr('disabled',1);
                optimzacia.next('button').attr('disabled',1);
                btn.attr('disabled',1);
            }
        }
        if(name=='optimzacia'){
            data['optimzacia'] = prop;
            var btn = $(this).next('button');
            var block = $(this).parent().parent();
            if(prop){
                btn.removeAttr('disabled');
            }else{
                btn.attr('disabled',1);
            }
        }
        if(name=='analiz_size' || 'analiz_count'){
            var parent = $(this).parents('.zt').eq(0);
            data['analiz_size'] = parent.find('[name=analiz_size]').val();
            data['analiz_count'] = parent.find('[name=analiz_count]').val();
        }
        if(name=='path2'){
            var grafic__SVG = $('.grafic__SVG .-path');
            if(prop){
                grafic__SVG.show();
            }else{
                grafic__SVG.hide();
            }
            data['path'] = prop;
        }
        if(name=='analizator_type'){
            data['analizator_type'] = $(this).val();
        }
        if(name=='analizator'){
            var grafic__SVG = $('.grafic__SVG .-analizator');
            if(prop){
                grafic__SVG.show();
            }else{
                grafic__SVG.hide();
            }
            data['analizator'] = prop;
        }
        data['property'] = 1;
        $.ajax({
            type: "POST",
            url: '/lab',
            async: true,
            data: data
        });
    });     //запись кук - изменили INPUTS

    $('#time_dlina,#time_size,.coin__info .-dop-var input[type=text]').on('change',function(){    //изменили АНАЛИЗАТР - inputs
        initPage();
        return false;
    });
    $('.coin__info .-dop-var .-off-line input[type=checkbox]').on('change',function(){
        var grafic = $('#grafic .-analizator .-dop');
        var prop = ($(this).prop('checked'))? 1 : 0;
        if(prop){
            grafic.show();
        }else{
            grafic.hide();
        }
        return false;
    });
    $('.coin__info .-item .-num b.-radio input').on('change',function(){
        init_analizator();
        return false;
    });
    $('#change_time').on('submit',function(){
        var val = $(this).find('[name=add]').val()*1;
        var set_time = $('#set_time');
        var date = set_time.find('[name=date]').val()+'-'+set_time.find('[name=time]').val();
        date = getTimestemp(date,val);
        set_time.find('[name=date]').val(date.getDate()+'-'+(parseInt(date.getMonth())+1)+'-'+date.getFullYear());
        set_time.find('[name=time]').val(date.getHours()+':'+date.getMinutes());
        initPage({date:date});
        return false;
    });

    var grafic = document.querySelector('#grafic');
    var svg_size = {
        width:parseInt(getComputedStyle(grafic).width),
        height:parseInt(getComputedStyle(grafic).height)
    };
    var psv,path_data,scaleX,scaleY,line,format,width,height;
    var margin = {top: 20, right: 80, bottom: 30, left: 0};
    var svg = d3.select("#grafic").attr("width",svg_size['width']).attr("height",svg_size['height']);     //тут создали холст
    var g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    //==================================================== INIT
    function init_simulator(){
        $('#grafic .-simulator').remove();
        var data = data_server['simulator'] || '';
        var simulator = g.append('g').attr('class','-simulator');

        console.log(data);
        //ОТОБРАЖАЕМ КОЛ_ВО ПОКУПОК
        if(data['count']!=undefined){
            var simulat__itog = $('.simulat__itog');
            var itog = simulat__itog.children('div');
            if(data['kpd']*1>0){
                itog.addClass('-good');
                itog.removeClass('-red');
            }else if(data['kpd']*1<0){
                itog.removeClass('-good');
                itog.addClass('-red');
            }else{
                itog.removeClass('-good');
                itog.removeClass('-red');
            }
            itog.text(data['kpd']);
            simulat__itog.children('span').html('BUY:<b>'+data['BUY']+'</b> SELL:<b>'+data['SELL']+'</b>');
        }
        //ОТОБРАЖАЕМ ТОЧКИ
        if(data['act']!=undefined){
            var block = simulator.append('g').attr('class','-point');
            //Рисуем точки
            var circle = block.selectAll('circle').data(data_server['simulator']['act']).enter().append('circle')
                .attr('cx',function(d){return scaleX(format(d['x2']))})
                .attr('cy',function(d){return scaleY(d['y2'])})
                .attr('r',3)
                .attr('fill',function(d){ if(d['status']=='BUY') return 'green'; else return 'red';});
            //Рисуем СТОП-ЛОССЫ - TOP
            var lossTop = block.selectAll('rect').data(data_server['simulator']['act']).enter().append('rect')
                .attr('x',function(d){return scaleX(format(d['x2']))})
                .attr('y',function(d){return scaleY(d['LOSS']['top'])})
                .attr('height',1)
                .attr('width',20)
                .attr('transform','translate(-10,0)')
                .attr('fill',function(d){ if(d['status']=='BUY') return 'green'; else return 'red';});
            //Рисуем СТОП-ЛОССЫ - BOTTOM
            var lossBot = block.selectAll('rect.x2').data(data_server['simulator']['act']).enter().append('rect')
                .attr('x',function(d){return scaleX(format(d['x2']))})
                .attr('y',function(d){return scaleY(d['LOSS']['bot'])})
                .attr('height',1)
                .attr('width',20)
                .attr('transform','translate(-10,0)')
                .attr('fill',function(d){ if(d['status']=='BUY') return 'green'; else return 'red';});
//        var circle = block.append('circle').attr('cx',0).attr('cy',0).attr('r',3)
//            .attr('fill','blue');
//        var text = block.append('text').text('0.54684168').attr('x',5).attr('y',-3).attr('font-size',14)
//            .attr('fill','blue');
        }
        loader_parent(0);
    }
    //отображаем НАЗВАНИЕ монеты и ее статус НАД ГРАФИКОМ
    function init_stat(){
        var data = data_server['stat']['segment'];
        var data_coin = data_server['coin'];
        var coin__stat = $('.coin__stat .-item span');
        if(data!=undefined){
            for(var i=0;i<coin__stat.length;i++){
                if(data[i]!=undefined){
                    if(data[i]['razX']>0){
                        coin__stat.eq(i).addClass('-up').removeClass('-down');
                    }else{
                        coin__stat.eq(i).removeClass('-up').addClass('-down');
                    }
                    coin__stat.eq(i).children('b').text(data[i]['razX']+'%');
                    coin__stat.eq(i).prev('small').text(time_name(data[i]['name']));
                }else{
                    coin__stat.eq(i).removeClass('-up').removeClass('-down');
                    coin__stat.eq(i).children('b').text('—');
                }
            }
        }
        var coin_name = $('.grafic__SVG .-coin');
        if(data_coin!=undefined){
            var orderType = data_coin['orderType'];
            coin_name.children('span').text(data_coin['coin']);
            coin_name.children('b').text(orderType);
            if(orderType=='BUY'){
                coin_name.addClass('-buy').removeClass('-sell');
            }else if(orderType=='SELL'){
                coin_name.removeClass('-buy').addClass('-sell');
            }
        }
    }
    //Отрисовать полоски БЛОЧНЫЙ и ЛИНЕЙНЫЙ анализатор
    function init_analizator(data){
        $('#grafic .-analizator').remove();
        data = data_server['analizator'];

        var coins_info = $('.coin');    //Правая панель
        analizator_type = coins_info.find('[name=analizator_type]:checked').val();
        anal_d = data[analizator_type]['segment'];
        if(anal_d){
            var analizator = g.append('g').attr('class','-analizator');
            var a_axis = analizator.append('g').attr('class','-axis');
            if(anal_d!=undefined && data_server['path']['error']==undefined){
                a_axis.selectAll("line")
                    .data(anal_d)
                    .enter()
                    .append("line").attr('stroke','orange').attr("stroke-width", 0.5).attr('transform','translate(0,'+height+')')
                    .classed("grid-line", true)
                    .attr("x1", function(d,i){return scaleX(format(d['x1']))})
                    .attr("y1", 0)
                    .attr("x2", function(d,i){return scaleX(format(d['x1']))})
                    .attr("y2", -(height));

                var lin = analizator.append('g').attr('class','-line')
                    .selectAll("g")
                    .data(anal_d)
                    .enter().append("g");
                lin.append("line").attr('stroke','blue').attr("stroke-width", 1.5)//.attr('transform','translate(0,'+height+')')
                    .classed("-raznica", true).classed("-dop", true)
                    .attr("x1", function(d,i){return scaleX(format(d['x1']))})
                    .attr("y1", function(d,i){return scaleY(d['y1'])})
                    .attr("x2", function(d,i){return scaleX(format(d['x2']))})
                    .attr("y2", function(d,i){return scaleY(d['y2'])});
                lin.append("line").attr('stroke','black').attr("stroke-width", 1)//.attr('transform','translate(0,'+height+')')
                    .classed("grid-line", true)
                    .attr("x1", function(d,i){return scaleX(format(d['x1']))})
                    .attr("y1", function(d,i){return scaleY(d['y0'])})
                    .attr("x2", function(d,i){return scaleX(format(d['x2']))})
                    .attr("y2", function(d,i){return scaleY(d['y0'])});
                lin.append('text').attr('class','-raznica').classed("-dop", true)
                    .text(function(d,i){return (d['raz']!=undefined)?d['raz']:''})
                    .attr('x',function(d,i){return scaleX(format(d['x1']+(d['x2']-d['x1'])/2))}).attr('y',function(d,i){return -7}).attr('font-size',9)
                    .attr('fill','blue');
                lin.append('text').attr('class','-raznicaX').classed("-dop", true)
                    .text(function(d,i){return (d['razX']!=undefined)?d['razX']:''})
                    .attr('x',function(d,i){return scaleX(format(d['x1']+(d['x2']-d['x1'])/2))}).attr('y',function(d,i){return 4}).attr('font-size',9)
                    .attr('fill','blue');
                lin.append('text').attr('class','-proc').classed("-dop", true)
                    .text(function(d,i){return (d['proc']!=undefined)?d['proc']:''})
                    .attr('x',function(d,i){return scaleX(format(d['x1']+(d['x2']-d['x1'])/2))}).attr('y',function(d,i){return (height-17)}).attr('font-size',9)
                    .attr('fill','blue');
                lin.append('text').attr('class','-procX').classed("-dop", true)
                    .text(function(d,i){return (d['procX']!=undefined)?d['procX']:''})
                    .attr('x',function(d,i){return scaleX(format(d['x1']+(d['x2']-d['x1'])/2))}).attr('y',function(d,i){return (height-5)}).attr('font-size',9)
                    .attr('fill','blue');
                //.attr('transform','rotate(-90)');
//            lin.append('text')
//                .attr('x',function(d,i){return scaleX(format(d['ot']))-35}).attr('y',0).attr('font-size',12)
//                .text(function(d){return (d['first-last']!=undefined)?d['first-last']+'%':''})
//                .attr('fill','blue');
                if(!prop.analizator){
                    $('.grafic__SVG .-analizator').hide();
                }

                // lin.append('g')
                //     .selectAll("circle")
                //     .data(anal_d)
                //     .enter().append("circle")
                //     .attr("cx", function(d) { return scaleX(format(d['time_id'])) })
                //     .attr("cy", function(d) { return scaleY(d['BUY_price_last']) })
                //     .attr("stroke-width", "none")
                //     .attr("fill", "#c30505" )
                //     //.attr("visibility", "hidden")
                //     .attr("r", 1.5)
                //     .attr('opacity',.2);
            }
        }
    }
    //Отрисовать график
    function init_grafic(){
        // console.log(data_server);
        // return false;
        d3.select("#grafic > g").selectAll("*").remove();       //очистили текущий график
        // psv = d3.dsvFormat("|");
        var error = $('.grafic__error > div');        //BOX error
        if(data_server['error']!=undefined){
            error.find('span').text(data_server['error']);
            error.show();
            loader_parent(0);
        }else{
            error.hide();
            var path_d = data_server['path'];
            if(path_d['error']){
                alert("Отсутствуют данные для графика");
                loader_parent(0);
                return false;
            }
            if(path_d){
                // path_data = psv.parse(path_d);
                path_data = path_d['segment'];
                $('.coin__info .-item.-path .-num b').text(path_data[0]['y2']);
                format = d3.timeParse('%s');
                width = svg.attr("width") - margin.left - margin.right;
                height = svg.attr("height") - margin.top - margin.bottom;
                scaleX = d3.scaleTime()
                    .domain(d3.extent(path_data,function(d){return format(d['x2'])})).range([0,width]);
                scaleY = d3.scaleLinear()
                    .domain(d3.extent(path_data,function(d){return d['y2']})).range([height,0]).nice();
                line = d3.line()
                    .x(function(d){return scaleX(format(d['x2']))}).y(function(d){return scaleY(d['y2'])});

                var da = 10;
                var axis = g.append('g').attr("class", "-axis");
                var axisX = axis.append('g')
                    .call(d3.axisBottom(scaleX))
                    .attr("class", "-axisX")
                    .attr('transform','translate(0,'+height+')');
                var axisY = axis.append('g')
                    .call(d3.axisRight(scaleY).ticks(da))
                    .attr("class", "-axisY")
                    .attr('transform','translate('+width+',0)');

                var path = g.append('g').attr('class','-path').append('path')   //путь
                    .attr('d',line(path_data))//.attr("transform", "translate(24,0)")
                    .attr('stroke','red').attr("stroke-width", 1).attr('fill','none').attr("stroke-linejoin", "round").attr("stroke-linecap", "round").attr('opacity',.5);
                if(!prop.path){
                    $('.grafic__SVG .-path').hide();
                }
                //создаем набор вертикальных линий для сетки
                axis.append('g').attr('class','-gridY')     //вертикальные и гор. линии
                    .selectAll('line')
                    .data(scaleX.ticks(da))
                    .enter()
                    .append("line").attr('stroke','#eee').attr("stroke-width", 1).attr("transform", 'translate(0,'+height+')')
                    .classed("grid-line", true)
                    .attr('opacity',.7)
                    .attr("x1", function(d,i){return scaleX(d)})
                    .attr("y1", 0)
                    .attr("x2", function(d,i){return scaleX(d)})
                    .attr("y2", - (height+margin['top']));
                axis.append('g').attr('class','-gridX')
                    .selectAll('line')
                    .data(scaleY.ticks(da))
                    .enter()
                    .append("line").attr('stroke','#eee').attr("stroke-width", 1).attr("transform", 'translate(0,'+height+')')
                    .classed("grid-line", true)
                    .attr('opacity',.7)
                    .attr("x1", 0)
                    .attr("y1", function(d,i){return -scaleY(d)})
                    .attr("x2", width)
                    .attr("y2", function(d,i){return -scaleY(d)});
//         draw the data points as circles
//            g.append('g')
//                .selectAll("circle")
//                .data(data)
//                .enter().append("circle")
//                .attr("cx", function(d) { return scaleX(format(d['time_id'])) })
//                .attr("cy", function(d) { return scaleY(d['BUY_price_last']) })
//                .attr("stroke-width", "none")
//                .attr("fill", "#c30505" )
//                //.attr("visibility", "hidden")
//                .attr("r", 1.5)
//                .attr('opacity',.2);

//            svg.selectAll(".bar")   //http://bl.ocks.org/Caged/6476579
//                .data(strategy)
//                .enter().append("rect")
//                .attr("class", "bar")
//                .attr("x", function(d) { return scaleX(format(d['x1'])); })
//                .attr("width", function(d) { return 15 })
//                .attr("y", function(d) { return scaleY(d['y1']); })
//                .attr("height", function(d) { return height - scaleY(d['y1']); });
//                .on('mouseover', tip.show)
//                .on('mouseout', tip.hide)
                //РИСУЕМ СТРАТЕГИИ - вертикальный линии
            }
        }
    }



    $(document).ready(function() {
        initPage();
    });
</script>