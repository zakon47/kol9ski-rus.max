<script>
    var data_server;

    function sendData(){
        var data = {};
        data['days'] = $('select[name=days]').val();
        data['last'] = $('input[name=last]').val();
        data['count'] = $('input[name=count]').val();
        data['sendData'] = 1;
        data['coins'] = [];
        if(data['count']==0){       //если выбрано 0 элементов
            var input = $('.table-lern tbody td.-name .-check input');
        }else{
            input = $('.table-lern tbody td.-name .-check input:checked');
        }
        input.each(function(i){
            data['coins'].push(input.eq(i).data('coin'));
        });
        var img = $('.head-lear .-action > img');
        $.ajax({
            type: "POST",
            url: '/learning',
            async: true,
            data: data,
            beforeSend: function (data) {
                img.show();
            },
            success: function (data) {
                console.log(data);
                return false;
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
                    // data_server = data;
                    // init_item();
                    // init_grafic();
                }
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
        return false;
    }

    //обработчик нажатия на пункты меню в списке монет
    $('.-action_go').on('click','> li > a',function(){
        let _this = $(this);
        var href = $(this).attr('href');
        var td = $(this).closest('td');
        switch (href){
            case 'win1':
                var tr = td.closest('tr');
                if(tr!=undefined){
                    Action_GO(tr);
                }
                break;
            case 'win2':
                //отобразили блок
                td.children('div').addClass('hide');
                td.find('.-win2').removeClass('hide');
                //добавили обрабатываемую монету
                var tr = _this.closest('.-tr');       //текущий TR блок
                var blockOpt = tr.closest('#table-lern2');
                blockOpt.data('tr',[tr]);
                Action_GO();
                break;
            case 'win3':
                console.log('win3');
                break;
            case 'win4':
                console.log('win4');
                break;
        }
        return false;
    });
    function Action_GO() {
        let blockOtp = $('#table-lern2').data('tr');
        let first = blockOtp.shift();
        if(first == undefined) return false;

        //собираем общие параметры
        var data = {};
        data['date'] = getDateLine(1);
        data['action_go'] = 1;
        data['coin'] = first.data('coin');
        var img = $('.head-lear .-action > img');
        //ПОЛУЧАЕМ КОЛ_ВО ВАРИАНТОВ
        $.ajax({
            type: "POST",
            url: '/bridge?lab_learning',
            async: 1,
            data: data,
            beforeSend: function (data) {
                img.show();
            },
            success: function (data) {
                if(isNaN(data) || data==false){
                    throw new Error('→ Данные с сервера пришли - но там не число!');
                    return false;
                }
                let startT = new Date();
                first.strategy = {startTime:startT,steep:data,data:[]};

                //ОТОБРАЖАЕМ ВРЕМЯ!
                let timer = $('.optimizator_block__ostatok .-etap span.-t1');
                let endT = Math.round((new Date()-startT)/1000);
                timer.text(getTime(endT));
                first.strategy.timer = setInterval(function () {
                    let timer = $('.optimizator_block__ostatok .-etap span.-t1');
                    let endT = Math.round((new Date()-startT)/1000);
                    timer.text(getTime(endT));
                },1000);
                init_optimizator(first);
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
    }
    //ЗАПУСК ОПТИМИЗАТОРА → шаг 2
    function init_optimizator(tr){
        show_new_data(tr);
        let coin = tr.data('coin');
        tr_str = tr.strategy;
        var i = tr_str.data.length;
        //ЕСЛИ ШАГИ ЗАКОНЧИЛИСЬ
        if(tr_str.steep == i){
            let endTime = new Date();//.getTime();
            tr_str.endTime = (endTime-tr_str.startTime)/1000;
            clearInterval(tr_str.timer);
            console.log('ВЫХОД',tr_str);
            var img = $('.head-lear .-action > img');
            img.hide();
            Action_GO();
            return false;
        }
        //Если делаем следующий шаг
        var data = {};
        data['action_go_x2'] = 1;
        data['coin'] = coin;
        data['id'] = i;
        var startTime = new Date();
        $.ajax({
            type: "POST",
            url: '/bridge?lab_learning',
            async: 1,
            data: data,
            success: function (data) {
                try {
                    data = JSON.parse();
                }catch (e) {
                    console.log(e);
                    return false;
                }
                console.log(data);
                let endTime = new Date();
                let diff = endTime-startTime;
                tr_str.data.push({time:diff/1000,data:data});
                init_optimizator(tr);
            },
            error: function (result) {
                alert('ошибка при загрузке страницы');
            }
        });
    }
    function show_new_data(tr) {
        tr_str = tr.strategy;
        //Вставляем шаги
        let opt_block = tr.find('.optimizator_block__ostatok');
        let current = opt_block.find('.-etapin');
        let primerno = opt_block.find('.-primerno');
        let len = tr_str.data.length;
        let progress = opt_block.find('.-progress');
        let steep = tr_str.steep;
        progress.css({width:(len/steep)*100+'%'});
        current.text(len+' / '+steep);
        let tbody = tr.find('.table-lern2__wrap > tbody');
        if(len){
            let itog = 0;
            let end = opt_block.find('.-t2');
            for(var i=0;i<len;i++){
                itog += tr_str.data[i].time;
            }
            itog = Math.round(itog/len);
            primerno.text('~'+itog);
            end.text(getTime(itog*steep));

            //Вставляем данные
            let last_data = tr_str.data[len-1];
            tbody.removeClass('hide');
            let tr_wrap = $('<tr>');
            let td_1 = $('<td class="-name -optoma">\n' +
                '                <div class="zt">\n' +
                '                    <div class="zt-cell -itog">'+last_data.data.kpd+'</div>\n' +
                '                    <div class="zt-cell -numbers"><span>'+len+'</span></div>\n' +
                '                </div>\n' +
                '            </td>');
            let td_2 = $('<td class="-days">\n' +
                '             <div class="zt">\n' +
                '                 <div class="zt-cell -day -res -good">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '                 <div class="zt-cell -day -res -good">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '                 <div class="zt-cell -day -res -bad">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '                 <div class="zt-cell -day -res -bad">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '                 <div class="zt-cell -day -res">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '                 <div class="zt-cell -day -res -good">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '                 <div class="zt-cell -day -res -good">\n' +
                '                     + 1565161\n' +
                '                 </div>\n' +
                '             </div>\n' +
                '         </td>');
            let td_3 = $('<td class="-act" data-id="'+(len-1)+'">\n' +
                '              <div class="-bts">\n' +
                '                  <div class="optimizator_block__item zt">\n' +
                '                      <div class="zt-cell -bbb">\n' +
                '                          <div class="-bbb-w">\n' +
                '                              <button class="-t_show"><i class="fa fa-television" aria-hidden="true"></i></button>\n' +
                '                              <button class="-t_link"><i class="fa fa-external-link" aria-hidden="true"></i></button>\n' +
                '                              <button class="-t_enter"><i class="fa fa-check" aria-hidden="true"></i></button>\n' +
                '                          </div>\n' +
                '                      </div>\n' +
                '                  </div>\n' +
                '              </div>\n' +
                '          </td>');
            tr_wrap.append(td_1);
            tr_wrap.append(td_2);
            tr_wrap.append(td_3);
            tbody.append(tr_wrap);
            console.log(tr_wrap);
        }else{
            tbody.empty();
            tr.find('.-win0').addClass('hide').parent().find('.-win2').removeClass('hide');
        }
    }
    $('#action').on('submit',function(){
        var type_action = $(this).find('[name=action]').val();
        if(type_action=='go'){                                                      //ЗАПУСК стратегий
            var input = $('.table-lern2 .-name .-check input:checked');
            if(input.length==undefined) input = $('.table-lern2 .-name .-check input');
            var blockOpt = $('#table-lern2');
            let new_tr_arr = [];
            input.each(function(i){
                let tr = input.eq(i).closest('.-tr');   //текущий TR блок
                new_tr_arr.push(tr);
            });
            blockOpt.data('tr',new_tr_arr);
            Action_GO();
        }
        return false;
    });


    
    function getDateLine(type){
        var days = $('.head-lear .-change');
        var day = days.find('[name=days]').val();               //кол-во дней
        var first_data = days.find('[name=first]');             //начальный input
        var last_data = days.find('[name=last]');               //конечный input
        last_data = getTimestemp(last_data.val()+'-23:59');     //timestemp last
        var first = new Date(last_data);
        first.setDate(first.getDate()-day);
        first_data.val(zeroise(first.getDate(),2)+'-'+zeroise(first.getMonth()+1,2)+'-'+first.getFullYear());       //вставляем полученное значение в начало времени
        var date_head = [];
        for(var i=0;i<day;i++){
            var d = new Date(first);
            d.setDate(d.getDate()+i);
            if(type){
                var f = new Date(d);
                f.setHours(0);
                f.setMinutes(0);
                date_head.push([f.getTime()/1000,d.getTime()/1000]);
            }else {
                date_head.push(d);
            }
        }
        return date_head;
    }   //формируем массив промежутков времени которое используем - если type=true то массив двух сторонний для отправки на сервер
    function updateDays(){
        //скрываем открытые окна
        var win_wrap = $('.table-lern2__wrap .-act');
        win_wrap.children().addClass('hide');
        win_wrap.children('.-win0').removeClass('hide');

        //обновляем данные
        var date_head = getDateLine();
        var day = date_head.length;               //кол-во дней
        //подписи дня недели
        var t = $('<div class="zt-cell -day -title">\n' +
            '          <span>—</span>\n' +
            '          <div>—</div>\n' +
            '      </div>');
        var thead = $('.table-lern2 .-thead .-th .-in-days');
        thead.empty();
        var width = 100/day;
        var new_str = '';
        for(i=0;i<date_head.length;i++){            //создали THEAD
            var new_t = t.clone();
            var e = date_head[i];
            new_t.css('width',width+'%');
            new_t.find('span').text(e.getDate()+'.'+e.getMonth()+'.'+e.getFullYear());
            new_t.find('div').text(day_name[e.getDay()]);
            thead.append(new_t);
        }

        //сам внутрений блок
        var item_tr = $('.table-lern2__wrap');
        let t_head = item_tr.children('thead');
        let t_body = item_tr.children('tbody');
        t_body.empty();

        var td = $('<div class="zt-cell -day">\n' +
            '           <div class="-wrap">\n' +
            '               <div class="-many">—</div>\n' +
            '           </div>\n' +
            '       </div>');
        item_tr.each(function(i){
            var days = t_head.eq(i).find('.-days>.zt');
            item_tr.eq(i).find('.-name').removeClass('-good').removeClass('-bad').find('.-many').text('—');
            days.empty();
            for(i=0;i<date_head.length;i++){            //создали THEAD
                var new_t = td.clone();
                var e = date_head[i];
                new_t.css('width',width+'%');
                days.append(new_t);
            }
        });
    }             //формируем визуальные блоки - при изменение даты или кол-во дней
    $('.head-lear .-change .-item.-proc button').on('click',function(){
        var last = $('.head-lear .-change .-item.-proc input[name=last]');
        var date = new Date();
        last.val(date.getDate()+'-'+zeroise(date.getMonth()+1,2)+'-'+date.getFullYear()).change();
        return false;
    });         //изменили дату на тукущую
    $('.head-lear .-change .-item select,.head-lear .-change .-item.-proc input').on('change',function(){
        updateDays();
    });         //изменили даты

    function updataAction(){
        var input = $('.table-lern2 .-name .-check input:checked');
        var count = input.length;
        var form = $('#action');
        form.find('[name=count]').val(count);
        if(count>0){
            form.find('button,select').removeAttr('disabled');
        }else{
            form.find('button,select').attr('disabled','disabled');
        }
    }           //обновить кол-вол выбранных элементов + активация формы
    $('.table-lern2 .-thead .-th.-name input').on('change',function(){
        var prop = ($(this).prop('checked'))? 1 : 0;
        var input = $('.table-lern2 .-name .-check input');
        if(prop){   //если выбрано -> удаляем
            input.each(function(i){
                input.eq(i).prop('checked','checked');
            });
        }else{
            input.each(function(i){
                input.eq(i).removeAttr('checked');
            });
        }
        updataAction();
    });         //"выбрать все"
    $('.table-lern2 .-name .-check input').on('change',function(){
        updataAction();
    });         //"выбрать одну из монет"

    $(document).ready(function() {
        updataAction();
        updateDays();
    });
</script>