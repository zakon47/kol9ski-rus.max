

function imenu_init(){
    var lastNumber = 0;
    let imenu = $('#imenu');
    let imenu__item = imenu.find('.imenu__item');           //список возможных меню (глобал)
    imenu__item.each(function(i, elem){                     //перебираем каждое меню отдельно и формируем его
        let tmenu = imenu__item.eq(i).data('type-menu');
        let flay = imenu__item.eq(i).find('.-over');
        switch (tmenu) {
            case 'products':
                //если есть внутрений FLAT элемент - формируем ПОДМЕНЮ!
                if (flay.length){
                    let LI = flay.find('.imenu__s-nav .imenu__submenu > li');
                    let BODY = flay.find('.imenu__s-body');
                    BODY.empty();   ///очистка блока
                    //перебираем менюшки и делаем ДОП ОКНА!
                    LI.each(function(item){
                        let child = LI.eq(item).children('.-newsub');      //берем потомка
                        //если есть потомок - то создаем окно
                        if (child.length){
                            LI.eq(item).addClass('-win').data('win',item);
                            let div = $('<div></div>');
                            div.addClass('-win').addClass('-win'+item);
                            div.append(child).children().data('win','-win'+item);
                            // let cp = child.clone();
                            // console.log(777,cp);
                            BODY.append(div);
                            lastNumber = item;
                            if (item==0){
                                LI.eq(0).children('a').trigger('mouseover');
                                // .addClass('-hover');
                                // div.children('.-newsub').show();
                            }
                        }
                        // console.log(1,child);
                    });
                    //активация 1 пункта

                    // console.log(LI.eq(0));
                }
                break;
            case 'arenda':
                if (flay.length){
                    let win = flay.find('.imenu__s-nav .-win');
                    lastNumber++;
                    imenu__item.eq(i).data('win',lastNumber);
                    win.addClass('-win'+lastNumber).children('.-newsub').data('win','-win'+lastNumber);
                    win.find('a.-tmp').eq(0).trigger('mouseenter');
                }
                break;
            case 'useful':
                if (flay.length){
                    let win = flay.find('.imenu__s-nav .-win');
                    lastNumber++;
                    imenu__item.eq(i).data('win',lastNumber);
                    win.addClass('-win'+lastNumber).children('.-newsub').data('win','-win'+lastNumber);
                }
                break;
            case 'razsale':
                if (flay.length){
                    let win = flay.find('.imenu__s-nav .-win');
                    lastNumber++;
                    imenu__item.eq(i).data('win',lastNumber);
                    win.addClass('-win'+lastNumber).children('.-newsub').data('win','-win'+lastNumber);
                }
                break;
        }
    });

    //инициализация мобильного меню
    imenu_init_mob();
}
function getDevice(){
    let dev = $('#device');
    if(dev.is(":hidden")) device = true; else device=false;
    // console.log(device);
}
//изменяем всплывающее меню при скролинге
function optimizator_imenu(){
    let doc_height = $('body').height();
    //утсанавливаем новый размер всплывающего окна
    let imenu = $('#imenu');
    let pos = imenu.get(0).getBoundingClientRect();
    let c = imenu.find('.-main');
    let o = imenu.find('.-flay .-over');
    o.width(c.width()+16);

    let height = $('.iheader').height() + $('.top-line').height();
    if (pos.top <= 0){
        imenu.addClass('-fixed');
    }else{
        imenu.removeClass('-fixed');
    }
}
//Инициализация меню - МОБ
function imenu_init_mob () {
    let imenu = $('#imenu');
    let imenu_type = imenu.data('type');                // -> mob version
    let imenu__content = imenu.find('.imenu__content');
    let imenu__main__wrap = imenu.find('.imenu__main__wrap');
    let win = imenu.find('.imenu__item .-win .-newsub');        //окна для отображения
    let top_line = $('.top-line__wrap');
    let mob_top_line = $('.imenu__itemX .-dops-nav');


    //мобильная версия
    if (device && imenu_type!='mob'){
        //переносим "контакты"
        mob_top_line.empty().append(top_line.children());
        //переносим "соцсетей"
        $('.imenu__itemX .-socsety').empty().append($('.top-line__soc .-wrap').children('.-www'));

        imenu.data('type','mob');
        win.each(function (i) {
            let num_win = win.eq(i).data('win');        //номер онка -> -win1
            // console.log(win.eq(i),num_win);
            let new_win = $('<div></div>');
            new_win.addClass('-win').addClass(num_win);
            new_win.addClass('imenu__slider');        //добавили стили
            let back = '<div data-win="X" class="-back"><small class="-link"><b><i class="fa fa-angle-left" aria-hidden="true"></i></b><span>Back</span></small></div>';       //кнопка возврата
            new_win.append(back);
            new_win.append(win.eq(i));
            imenu__content.append(new_win);
            // console.log(new_win.get(0));
        });
        imenu_reset();
    }else if(!device && imenu_type=='mob'){
        //переносим "контакты"
        if (!top_line.children().length){
            top_line.prepend(mob_top_line.children());
        }
        //переносим "соцсетей"
        if(!$('.top-line__soc .-wrap').children('.-www').length){
            $('.top-line__soc .-wrap').prepend($('.imenu__itemX .-socsety').children('.-www'));
        }


        //десктопная версия
        let body = $('body');
        if (body.hasClass('imenu__ovh')){
            body.removeClass('imenu__ovh');
        }
        imenu.data('type','');
        let wins = imenu__content.children('.-win');
        wins.each(function(i){
            let newsub = wins.eq(i).children('.-newsub');
            let name_win = newsub.data('win');          //имя окна
            imenu__main__wrap.find('.'+name_win).empty().append(newsub);
            wins.eq(i).remove();
        });
    }
}
function imenu_reset(){
    let imenu = $('#imenu');
    let el = imenu.find('.imenu__slider').eq(0);
    el.animate({scrollTop: 0},0);
    el.addClass('-open').siblings().removeClass('-open');
}
function is_touch_device() {
    return !!('ontouchstart' in window);
}       //имеет ли дисплей тач??
var device = false;

$(document).ready(function(){
//мобильное устройство ли это?

//Инициализация меню
    $('.imenu__black, .imenu__bar-open.-close').on('click',function(){
        //если бала открыта форма поиска
        let search = $('#imenu__search').closest('.-wrap');
        let pp = $(this).hasClass('-big');
        if (search.hasClass('-active') && !pp){
            return true;
        }
        //далее закрываем
        let scroll = $('body').data('scroll');
        $('body').removeClass('imenu__ovh');
        $('html').animate({scrollTop: scroll},0);
        $(this).closest('#imenu').removeClass('-open');
        return false;
    });   //для моб - закрыть меню
    $('.imenu__bar-open').on('click',function(){
        if($(this).hasClass('-close')) return true;
        let imenu = $(this).closest('#imenu');
        let top = $(window).scrollTop();        //высота скролла
        $('body').data('scroll',top).css({top:-top+"px"}).addClass('imenu__ovh');
        imenu_reset();
        imenu.addClass('-open');
    });                         //для моб - открыть меню

    $('.imenu__a').on('click',function(){
        //закрываем топ меню
        $('.top-line__link').parent().removeClass('-opens');
        //отработка
        let parent = $(this).parent();
        if(parent.hasClass('-power')){
            parent.removeClass('-power');
        }else{
            parent.addClass('-power').siblings('.imenu__item').removeClass('-power');
        }
    });         //открыли всплываюку для десктопа
    $('.imenu__main__wrap > .imenu__black, .imenu__item .-flay').on('click',function(){
        if ($( event.target ).hasClass('-flay') || $( event.target ).hasClass('-black')){
            $(this).closest('.imenu__main__wrap').children('.imenu__item').removeClass('-power');
        }
    });   //закрыли всплываюку для десктопа


    $('#imenu,.top-line').on('click','.top-line__link',function(){
        $(this).addClass('---x');
        $(this).closest('.-nav').eq(0).parent().find('.top-line__link').not('.---x').parent().removeClass('-opens');
        $(this).removeClass('---x').parent().toggleClass('-opens');
        return false;
    });
    $('.imenu__mob .-mob-phone-dt').on('click',function(){
        $('body').addClass('imenu__ovh');
        $(this).addClass('-open');
        console.log($(this));
    });
    $('.imenu__mob .-mob-phone-dd .-black').on('click',function(){
        $('body').removeClass('imenu__ovh');
        $(this).parent().parent().children('.-mob-phone-dt').removeClass('-open');
    });
    $('#imenu__search').on('click',function(){
        //закрываем топ меню
        $('.top-line__link').parent().removeClass('-opens');
        //отработка
        $(this).closest('.-wrap').addClass('-active');
        $('.imenu__header').hide();
    });
    $('.imenu__search .imenu__black').on('click',function(){
        $(this).parent().removeClass('-active');
        $('.imenu__header').show();
        return false;
    });

    $('#imenu').on('click','.-link',function(event){
        // return false;
        if(is_touch_device()){
            event.preventDefault();
        }
        if (!device) return; //если это НЕ мобильная версия - выходим

        let imenu__content = $(this).closest('.imenu__content');
        let win = $(this).parent().data('win');
        if (win || win==0){
            let open = imenu__content.children('.-win'+win);
            //если это не главное меню -> вставляем ссылку назад
            if (win!='X'){
                open.find('.-back span').text($(this).text());
            }
            let active_open = open.parent().children('.-open');     //текущее открытое окно
            // console.log(win,active_open);
            // return false;
            active_open.animate({
                zIndex: 40,
                transition: ".0s"
            }, 0, function(){
                open.addClass('-open').siblings().removeClass('-close').removeClass('-open');
                active_open.removeAttr('style');
            });
            // });
            //     active_open.addClass('-close');
            // open.siblings().addClass('-close').removeClass('-open');
            // open.addClass('-open').siblings().removeClass('-close').removeClass('-open');
            // active_open.removeClass('-close');
            event.preventDefault();
        }else{
            if($(this).closest('.-newsub') && $(this).attr('href')){
                location.href = $(this).attr('href');
            }
        }
    });                      //для моб - переключение окон
    $('.imenu__products .-link').on('mouseover',function(event){
        if (device){
            return false; //если это мобильная версия - выходим
            //добавили ховер
        }
        if(is_touch_device()){
            event.preventDefault();
        }
        console.log(333);
        $(this).parent().siblings().children().removeClass('-hover');
        $(this).addClass('-hover');

        let li = $(this).parent();
        let BODY = $(this).closest('.-flay').find('.imenu__s-body');
        // console.log(BODY);
        //если есть дополнительный блок
        if (li.hasClass('-win')){
            let winName = li.data('win');
            let winBlock = BODY.find('.-win'+winName);
            if (!winBlock.length){
                alert("Не смогли найти открывающее меню для пункта меню - меню не сформировано в imenu_init()");
                return
            }
            winBlock.siblings().children().hide();
            winBlock.children().fadeIn(0);
        }else{
            let winBlock = BODY.find('.-win');
            winBlock.children().fadeOut(0);
        }
    });          //для десктопа - переключение меню
    $('#imenu').on('mouseover','.imenu__products a.-load, .imenu__arenda a.-load',function(){
        let imgSrc = $(this).data('img');
        if (!imgSrc) return;

        var load = true;
        if (!window['load_key']){
            window['load_key'] = [];
            window['load_key'].push(imgSrc);
        }else{
            for(let x=0;x<window['load_key'].length;x++){
                if (window['load_key'][x] == imgSrc){
                    load = false;
                    break;
                }
            }
        }
        let over = $(this).closest('.-over');
        let imgBlock = over.find('.-big-img');
        let loader = imgBlock.find('.-loader');
        let img = imgBlock.find('.-img');
        //загружать ли картинку?
        if(load){
            window['load_key'].push(imgSrc);
            loader.addClass('-show');
            img.addClass('-hide');
            img.attr('src',imgSrc).load(function(){
                img.removeClass('-hide');
                loader.removeClass('-show')
            });
        }else{
            img.attr('src',imgSrc);
            img.removeClass('-hide');
            loader.removeClass('-show')
        }
        //отображаем текст для описание раздела
        let DESC = over.find('.imenu__s-dop .-desc');
        if (DESC.length){
            let desc = $(this).data('desc');
            if(desc){
                DESC.text(desc).fadeIn(0);
            }else{
                DESC.text('').fadeOut(0);
            }
        }
    });                 //загрузка картинок при наведение


    $(".owl_main").owlCarousel({
        nav: true,
        loop:false,
        margin:0,
        navText: ["<div class='-prev'><i class=\"fa fa-angle-left\" aria-hidden=\"true\"></i></div>", "<div class='-next'><i class=\"fa fa-angle-right\" aria-hidden=\"true\"></i></div>"],
        autoplay:false,
        // autoplayTimeout:3000,
        // autoplayHoverPause:true,
        responsive:{
            0:{
                items:2,
                margin:10,
            },
            550:{
                items:3,
                margin:16,
            },
            1040:{
                items:4
            }
        }
    });
    getDevice();
    imenu_init();
    optimizator_imenu();
});
$(document).on('scroll',function(){
    optimizator_imenu();
});
$(window).resize(function(){
    getDevice();
    imenu_init_mob();
    optimizator_imenu();
});
