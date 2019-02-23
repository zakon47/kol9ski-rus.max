$(document).ready(function(){
    var var_zak_dd = 0;

    //Работа с SELECT
    $("body").on('mouseup',function(e) {
        var i_zak_dl = $('.i-zak-dl');
        var i_zak_dd = $('.i-zak-dd');
        //console.log(i_zak_dl.is(e.target));
        //console.log(i_zak_dl.has(e.target).length);
        if(i_zak_dl.has(e.target).length || i_zak_dl.is(e.target)){         //Нажали на селект
            var status_dd = $(e.target).parents('.i-zak').find('.i-zak-dd');
            if(status_dd.hasClass('none')){
                i_zak_dd.addClass('none');
                status_dd.removeClass('none');
            }else{
                status_dd.addClass('none');
            }
        }else if(i_zak_dd.has(e.target).length || i_zak_dd.is(e.target)) {         //выбрали пункт из селект
            if($(e.target).get(0).tagName=='OPTION' && !$(e.target).attr('disabled') && !$(e.target).hasClass('optgroup')){
                var v = $(e.target).val();
                $(e.target).parents('.i-zak-dd').addClass('none');
                $(e.target).parents('.i-zak').prev().val(v).change();
            }
        }else if(i_zak_dd.has(e.target).length === 0){                      //нажали мимо открытого селекта
            i_zak_dd.addClass('none');
        }
    });
    $(document).on('click','.i-zak',function () {
        var s = $(this);
        if(s.parent().get(0).tagName != 'LABEL' && s.parent().parent().get(0).tagName != 'LABEL' && s.parent().parent().parent().get(0).tagName != 'LABEL'){
            //console.log(s.prev('input').val(''));
            s.prev('input').trigger('click');
        }
    });

    //Обработка SELECT в I-ZAK
    function convertSelect(i,val){
        var s = i.next();                   //i-zak элемент сл за ним
        if(s.hasClass('i-zak')) {           //Если следующий элемент содержит класс i-zak
            var head_t = '';
            var select = i.find(':checked').attr('selected');
            if(select!=undefined){
                var val = i.find(':checked').val();
            }
            if(!val){                       //инициализация при старте

                    $.each(i[0],function(x){
                        if(!$(this).attr('disabled')){
                            head_t = i[0][x].text;
                            i[0][x].className = 'checked';
                            return false;
                        }
                    });
            }else{                          //Пришло значение оптион
                $.each(i[0],function(){
                    $(this).removeClass('checked');
                });
                head_t = i[0][i.get(0).selectedIndex].text;
                i[0][i.get(0).selectedIndex].className = 'checked';
            }
            if(i[0][0].text == ''){
                i.get(0).removeChild(i.get(0)[0]);
                i.val('');
            }
            if(head_t=='')head_t = '-- Выберите --';

            var h = i.html();
            s.find('.i-zak-dt').text(head_t);
            s.find('ul').html(h);
        }
    }

    //Инициизация СЕЛЕКТ
    $('select').each(function(){
        convertSelect($(this),0);
    });

    //Костыль для оперы 11
    $('input').change(function(){
    });
    //Изменили INPUT или SELECT
    $(document).on('change','input,select',function(){
        var i = $(this);
        var s = i.next('.i-zak');
        switch (i.get(0).tagName){
            case 'INPUT':
                if(i.attr('type')== 'checkbox'){
                    if(i.is(':checked')){
                        s.addClass('checked');
                    }else{
                        s.removeClass('checked');
                    }
                }else if(i.attr('type')== 'radio'){
                    i.parent().parent().find('input[type=radio]').each(function(){      //1 этаж в верх
                        $(this).next().removeClass('checked');
                    });
                    s.addClass('checked');
                }else if(i.attr('type')== 'file'){
                    var file_api = ( window.File && window.FileReader && window.FileList && window.Blob ) ? true : false;
                    var file_name;
                    if( file_api && i[ 0 ].files[ 0 ] )
                        file_name = i[ 0 ].files[ 0 ].name;
                    else
                        file_name = i.val().replace( "C:\\fakepath\\", '' );
                    if(!file_name.length) return;

                    var s = s.find('.i-zak-title').text(file_name);

                    console.log(file_name);
                    console.log(s);
                }
                break;
            case 'SELECT':
                convertSelect(i,i.val());
                break;
        }
        //console.log(i.get(0).tagName +' => '+ s.hasClass('i-zak'));
    });


    function showValues() {
        var str = decodeURIComponent($("form").serialize());
        $("#results").text( str );
    }
    $("input[type='checkbox'], input[type='radio']").on( "click", showValues );
    $("select").on( "change", showValues );
    showValues();

});
