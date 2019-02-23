<script>
    var data_server;    //записываваем сюда ответ от initPage()

    //Инициализируем страницу - собираем данные и отправляем их на сервер
    function initPage(){
        //СОБИРАЕМ ДАННЫЕ
        var data = {};
        data['span'] = $('.head-change .-change .-item.-radio select').val();           //рост за последние Х минут
        data['raznica'] = $('.head-change .-change .-item.-proc input').val();          //на %
        data['dlina_change'] = $('.head-change .-change .-item.-dlina select').val();   //на промежутке
        var set_time = $('.head-change .-change .-item.-set_time');
        var set_check = set_time.find('[name=set_check]').prop('checked');
        data['set_time'] = set_time.find('[name=set_time]').val();

        if(set_check){      //если выбрали дату свою
            data['set_check'] = 1;          //отправляем положение ключа - какое время (текущее или свое)
        }else{
            data['set_check'] = 0;          //отправляем положение ключа - какое время (текущее или свое)
        }
        data['initPage'] = 1;
        if(!data['raznica']) return false;
        var img = $('.head-change .-empty img');        //картинка-анимация LOADER
        $.ajax({
            type: "POST",
            url: '/change',
            async: true,
            data: data,
            beforeSend: function (data) {
                img.show();
            },
            success: function (data) {
                img.hide();
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
                    data_server = data;
                    console.log(data);
                    init_item();
                }
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
        return false;
    }
    //Формируем и выводим список блоков из переменной DATA_SERVER
    function init_item(){
        var tbody = $('.table-change tbody');
        tbody.empty();
        var tr = '\n' +
            '                            <tr>\n' +
            '                                <td class="-num">\n' +
            '                                    —\n' +
            '                                </td>\n' +
            '                                <td class="-name">\n' +
            '                                    <b>BTC-1ST</b>\n' +
            '                                    <div><a href="//" target="_blank">Ссылка</a></div>\n' +
            '                                </td>\n' +
            '                                <td class="-update"><div class="-border">~ 2145</div>\n' +
            '                                </td>\n' +
            '                                <td class="-map">\n' +
            '                                    <div class="zt">\n' +
            '                                        <div class="zt-cell -upd">\n' +
            '                                            <div class="coin__stat"></div>\n' +
            '                                        </div>\n' +
            '                                        <div class="zt-cell -svg">\n' +
            '                                            <svg height="100"></svg>\n' +
            '                                        </div>\n' +
            '                                    </div>\n' +
            '                                </td>\n' +
            '                                <td class="-act">\n' +
            '                                    <div class="go_act">\n' +
            '                                        <div class="-msg">Проверка</div>\n' +
            '                                        <form method="post" class="-buy">\n' +
            '                                            <input type="text" name="add" maxlength="5" value="0.1">\n' +
            '                                            <button type="submit" class="btn -blue">Купить</button>\n' +
            '                                        </form>\n' +
            '                                        <div class="-refresh">\n' +
            '                                            <a href="?d">Обновить</a>\n' +
            '                                        </div>\n' +
            '                                    </div>\n' +
            '                                </td>\n' +
            '                            </tr>';
        var tx = $(tr);
        var data = data_server;
        if(data.length){
            for(var i=0;i<data.length;i++){
                var new_tr = tx.clone();
                new_tr.addClass('-x'+i).attr('data-id',i);
                //вставляем номер
                new_tr.find('.-num').text(i+1);
                //вставляем название
                new_tr.find('.-name b').text(data[i]['name']);
                new_tr.find('.-name div a').attr('href','https://bittrex.com/Market/Index?MarketName='+data[i]['name']);
                //вставляем график
                tbody.append(new_tr);


                if(data[i]['stat']!=undefined){
                    var div_t = $('<div class="row xxs-g0"></div>');
                    var b_t = $('<div class="col xs-8">\n' +
                        '           <div class="-item"><small>—</small>\n' +
                        '               <span>\n' +
                        '               <b>-3.2%</b>\n' +
                        '               <i class="fa fa-arrow-circle-up" aria-hidden="true"></i>\n' +
                        '           </span>\n' +
                        '           </div>\n' +
                        '      </div>');
                    var stat = data[i]['stat'];
                    var str = [];
                    for(k=0;k<stat.length;k++){
                        if(k>8) break;
                        if(k%3==0){
                            var div = div_t.clone();
                        }
                        var b = b_t.clone();
                        var span = b.find('span');
                        var item = b.find('.-item');
                        var small = b.find('small');
                        small.text(time_name(stat[k]['name']));
                        if(stat[k]['raz']>0){
                            span.addClass('-up');
                        }else{
                            span.addClass('-down');
                            item.addClass('-red');
                        }
                        span.children('b').text(stat[k]['raz']+'%');
                        div.append(b);
                        if(k%3==1){
                            $('.table-change tbody tr.-x'+i+' .coin__stat').append(div);
                        }
                    }
                }

                init_grafic(i);
            }
        }
    }
    function init_grafic(i){
        format = d3.timeParse('%s');
        var data = data_server[i]['grafic'];
        var svg = d3.select('.-x'+i+' .-svg');
        var svg_size = svg.node().getBoundingClientRect();
        svg = svg.select('svg').attr("width",svg_size['width']).attr("height",svg_size['height']);
        var margin = {top: 10, right: 0, bottom: 10, left: 0};
        var g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");
        width = svg.attr("width") - margin.left - margin.right;
        height = svg.attr("height") - margin.top - margin.bottom;

        scaleX = d3.scaleTime()
            .domain(d3.extent(data,function(d){return format(d['x2'])})).range([0,width]);
        scaleY = d3.scaleLinear()
            .domain(d3.extent(data,function(d){return d['y2']})).range([height,0]).nice();
        line = d3.line()
            .x(function(d){return scaleX(format(d['x2']))}).y(function(d){return scaleY(d['y2'])});
        var path = g.append('g').attr('class','-path').append('path')   //путь
            .attr('d',line(data))//.attr("transform", "translate(24,0)")
            .attr('stroke','red').attr("stroke-width", 1).attr('fill','none').attr("stroke-linejoin", "round").attr("stroke-linecap", "round").attr('opacity',.5);

    }       //рисуем графики
    //покупаем монету

    //Нажали кнопку - ОБНОВИТЬ
    $('.line__navig button.-set-upd').on('click',function(){
        initPage();
    });
    //Нажали кнопку - КУПИТЬ
    $('.table-change').on('submit','tbody td.-act .-buy',function(){        //нажали кнопку - купить монету/докупить
        var data = {};
        data['buy_coin'] = 1;
        data['price'] = $(this).find('[name=add]').val()*1;                 //сколько заплатить
        data['coin'] = $(this).closest('tr').find('.-name > b').text();     //название монеты
        var balance = $('.head-change .-empty b').text()*1;                 //текущий баланс - для сравнения
        var msg = $(this).closest('.go_act').find('.-msg');                 //место дя сообщений
        //если баланс позволяет
        if(balance>data['price']){
            msg.css('visibility','hidden');
            var img = $('.head-change .-empty img');
            $.ajax({
                type: "POST",
                url: '/change',
                async: true,
                data: data,
                beforeSend: function (data) {
                    img.show();
                },
                success: function (data) {
                    img.hide();
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
                        console.log(data);
                    }
                },
                error: function (result) {
                    alert('ошибка при загрузке страницы');
                }
            });
        }else{
            msg.text('Недостаточно средств').css('visibility','visible');
        }
        return false;
    });
    //Выбрали другой ACCOUNT
    $('#wallet select[name=wallet]').on('change',function(){
        var data = {};
        data['upd_wallet'] = 1;
        data['wallet'] = $(this).val();
        var img = $('.head-change .-empty img');
        $.ajax({
            type: "POST",
            url: '/change',
            async: false,
            data: data,
            beforeSend: function (data) {
                img.show();
            },
            success: function (data) {
                img.hide();
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
                    $('.head-change .-empty b').text(data['dostupno']);
                }
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
        return false;
    });
    //Изменили диапазоны данных
    $('.head-change .-change .-item.-radio select,.head-change .-change .-item.-dlina select,.head-change .-change .-item.-proc input,.head-change .-change .-item.-set_time input').on('change',function(){
        if($(this).attr('name')=='set_check'){
            var input = $(this).closest('.-item').children('input');
            if($(this).prop('checked')){        //включили галку
                input.removeAttr('disabled');
            }else{                              //отключили галку
                input.attr('disabled','disabled');
            }
        }
        initPage();
    });


    $(document).ready(function() {
        initPage();
    });
</script>