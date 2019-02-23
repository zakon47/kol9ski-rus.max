'use strict';

//МОЯ БИБЛИОТЕКА ПОЛИФИЛОВ
var _zak = {};
_zak.o = Object.prototype;
_zak.a = Array.prototype;
_zak.s = String.prototype;
function dd(e){
    return console.log(e);
}
function ddd(e){
    return console.dir(e);
}

/**
 * Проверям соответствует данный элемент селектору
 * @type {*|matches}
 */
if(!Element.prototype.matches){
    Element.prototype.matches = Element.prototype.webkitMatchesSelector || Element.prototype.oMatchesSelector || Element.prototype.msMatchesSelector || Element.prototype.mozMatchesSelector || function matches(selector){
        var p = this.parentNode;
        if(p){
            if(p.querySelector(selector))
                return true;
        }
        return false;
    };}
/**
 * Удалить текущий узел
 * @type {Element.remove}
 */
Document.prototype.remove = Element.prototype.remove = function remove() {
    if (this.parentNode) {
        this.parentNode.removeChild(this);
    }
};

//проверка есть ли св-ва в этом элементе - НЕ РАБОТАЕТ!
function defineProperty(prop,elem){
    prop = prop.split('.');
    var str = '';
    for(var i=0;i<prop.length;i++){
        if(i!=0){
            str = prop[i];
        }
        console.log(elem);
        // if(!elem.hasOwnProperty(prop[i])){
        //     return false;
        // }
    }
    return true;
}

// ========================Ширина прокрутки=============================
// создадим элемент с прокруткой
var div = document.createElement('div');
div.style.overflowY = 'scroll';
div.style.width = '50px';
div.style.height = '50px';
div.style.visibility = 'hidden';
document.body.appendChild(div);
var scrollWidth = div.offsetWidth - div.clientWidth;
document.body.removeChild(div);
// ============================ВЫСОТА и ШИРИНА и ПОЗИЦИОНИРОВАНИЕ квадрата=========================
function getElementPosition(elem){
    return 'пока не робит';
}
function getElementSize(elem){
    return {"width":elem.offsetWidth,"height":elem.offsetHeight};
}
// =====================================================

_zak.scrollFN = function(opt){
    opt = {
        boxName: '.scroll',
        mode: 0,        //позиционирование баров
        barY: {up:'▲',center:'<div></div>',down:'▼'},
        barX: {left:'◀',center:'<div></div>',right:'▶'}
    };
    var elements = document.querySelectorAll(opt.boxName); //инициализация всех элементов
    var elements_len = elements.length;
    var self = this;
    var list = [];
    /**
     * Возвращает состояние overflow блока
     * @param item
     * @returns {{x: *, y: *}}
     */
    var getOverflow = function(item){
        return {x:getComputedStyle(item).overflowX,y:getComputedStyle(item).overflowY};
    };
    /**
     * Возвращает ширину скролл бара
     * @param item
     * @returns {{x: number, y: number}}
     */
    var getScroll = function(item){
        return {x:item.offsetHeight-item.clientHeight,y:item.offsetWidth-item.clientWidth};
    };
    /**
     * Создаем и Возвращаем размеры опорных КВАДРАТОВ
     * @param item
     * @returns {{height: number, width: number}}
     */
    var getBoxs = function(item){
        var box = document.createElement('div');
        box.classList.add('-box');
        var R = box.cloneNode();
        R.classList.add('-boxR');
        var Y = box.cloneNode();
        Y.classList.add('-boxY');
        var X = box.cloneNode();
        X.classList.add('-boxX');
        var fragment = document.createDocumentFragment();
        fragment.appendChild(R);
        fragment.appendChild(Y);
        fragment.appendChild(X);
        item.parentNode.appendChild(fragment);
        // return getElementSize(mix);//{height:mix.offsetHeight, width:mix.offsetWidth};
        return {R:getElementSize(R),Y:getElementSize(Y),X:getElementSize(X)};
    };

    /**
     * Добавляем наш СКРОЛЛ БАР
     * @param item
     */
    this.addBar = function(elem, type){
        var code = document.createElement('div');   //создаем бокс для бара
        code.classList.add('-bar');
        var up = document.createElement('div');     //кнопка наверх
        up.classList.add('-btn');
        var free = document.createElement('div');   //ползунок
        free.classList.add('-free');
        var center = document.createElement('div');
        center.classList.add('-center');
        var down = document.createElement('div');   //кнопка вниз
        down.classList.add('-btn');

        if(type=='Y'){
            code.style.bottom = elem._boxs['R'].height+'px';
            code.classList.add('-barY');
            up.classList.add('-up');
            up.innerHTML = opt.barY.up;
            center.innerHTML = opt.barY.center;
            down.classList.add('-down');
            down.innerHTML = opt.barY.down;
        }else{
            code.style.right = elem._boxs['R'].width+'px';
            code.classList.add('-barX');
            up.classList.add('-left');
            up.innerHTML = opt.barX.left;
            center.innerHTML = opt.barX.center;
            down.classList.add('-right');
            down.innerHTML = opt.barX.right;
        }
        free.appendChild(center);
        code.appendChild(up);
        code.appendChild(free);
        code.appendChild(down);

        elem.parentNode.appendChild(code);
    };
    var initScroll = function(elem){
        //инициализируем Y
        var parentH = elem.parentNode.clientHeight;
        var elemH = elem.scrollHeight;
        var h = (100*parentH)/elemH;
        var y = elem._barY.querySelector('.-center');
        y.style.height = h+'%';
        //инициализируем X
        var parentW = elem.parentNode.clientWidth;
        var elemW = elem.scrollWidth;
        var w = (100*parentW)/elemW;
        if(w===100) w = 0;
        var x = elem._barX.querySelector('.-center');
        x.style.width = w+'%';

        //выставляем новый оступ сверху
        var raznica = elemH-parentH;
        var top = elem.scrollTop;
        var left = elem.scrollLeft;
        var freeH = y.parentNode.clientHeight;
        var freeW = x.parentNode.clientWidth;
        if(raznica>0){
            var y_proc = (top*100)/elemH;
            var x_proc = (left*100)/elemH;
            y.style.marginTop = freeH*y_proc/100+'px';
            x.style.marginLeft = freeW*x_proc/100+'px';
        }
        // console.log(freeH*y_proc/100,freeW*x_proc/100);

        //подсвечиваем кнопки
        var up = elem._barY.querySelector('.-up');
        if(top>0){
            up.classList.add('-active');
        }else{
            up.classList.remove('-active');
        }
        var down = elem._barY.querySelector('.-down');
        if(top+parentH<=elemH){
            down.classList.add('-active');
        }else{
            down.classList.remove('-active');
        }

    };
    var findElementClass = function(elem,className){
        var children = elem.children;
        for(var i=0;i<children.length;i++){
            if(children[i].classList.contains(className)){
                return children[i];
            }
        }
    };
    this.update = function(elem){
        var x = elem._scroll.x;
        var y = elem._scroll.y;
        var barY = findElementClass(elem.parentNode,'-barY');
        var barX = findElementClass(elem.parentNode,'-barX');
        var boxR = findElementClass(elem.parentNode,'-boxR');
        elem._barX = barX;
        elem._barY = barY;
        initScroll(elem);

        elem.addEventListener('1scroll',function(e){
            initScroll(elem);
        });

        if(x && y){                   //2 бара + отрисовка
            boxR.classList.add('-active');
            barY.style.display = '';
            barX.style.display = '';
        }else if(y){                         //1 бар + отрисовка
            barY.style.display = '';
            barX.style.display = 'none';
            barY.style.bottom = '0';
            elem.style.paddingBottom = '0';
            elem.style.height = parseInt(getComputedStyle(elem.parentNode).height)+'px';
            boxR.classList.remove('-active');
        }else if(x){                         //1 бар + отрисовка
            barY.style.display = 'none';
            barX.style.display = '';
            barX.style.right = '0';
            // elem.style.marginRight = '0';
            boxR.classList.remove('-active');
        }else{
            barY.style.display = 'none';
            barX.style.display = 'none';
            // elem.style.marginRight = '0';
            elem.style.paddingBottom = '0';
            elem.style.height = parseInt(getComputedStyle(elem.parentNode).height)+'px';
            boxR.classList.remove('-active');
        }
    };
    var renderBar = function(elem){
        self.addBar(elem,'Y');
        self.addBar(elem,'X');
    };
    var onResize = function(){
        document.onresize = function(){
            console.log(1);
        }
    };
    /**
     * устанавливаем новую ширину Блока - и стераем его - чтобы убрать перескок размера
     * @param elem
     */
    var fixContent = function(elem,type){
        if(!type){
            elem.style.width = elem.parentNode.clientWidth+elem._scroll.y+'px';
        }else{
            elem.style.width = '';
            elem.style.marginRight = -scrollWidth+'px';
        }
    };
    var addEvents = function(elem){
        elem.addEventListener('click',function(e){
            var target = e.target;
            var px = 70;
            if(target.classList.contains('-btn')){
                // if(target.classList.contains('-up')){
                //     elem.scrollTop = elem.scrollTop - px;
                // }else if(target.classList.contains('-down')){
                //     elem.scrollTop = elem.scrollTop + px;
                // }
                // console.log(e.target,elem);
            }
            if(target.classList.contains('-center')){
                console.log(e.target);
            }
        });
    };
    //добавить элемент в обработчик - ПУСК КАЖДОГО ЕЛЕМЕНТА
    this.add = function(elem){
        elem.classList.remove('scrollnone');        //убрать мерцание старого бара
        elem._scroll = getScroll(elem);             //Получили ширину стандартного бара для Y и X [17,0]
        elem._boxs = getBoxs(elem);                 //Создаем опорные квадраты + Возвращаем размеры [Y,X,R]
        fixContent(elem);                           //убираем перескок контента внутри блока 1
        list.push(elem);                            //добавили в стек
        renderBar(elem);                            //отрисовали бар
        self.update(elem);          //обновляем позиции
        addEvents(elem);            //добавить события
        fixContent(elem,1);                         //убираем перескок контента внутри блока 2
    };
    //перебираем блоки с которыми работаем
    for(var i=0;i<elements_len;i++){
        this.add(elements[i]);
    }
    this.list = function(){
        return list;
    };
    window.addEventListener('resize',function(){
        for(var i=0;i<list.length;i++){
            var elem = list[i];
            self.update(elem);          //обновляем позиции
        }
    });
};
_zak.tooltipFN = function(opt){
    opt = {
        boxName: '.tooltip',
        flayBoxName: '.tooltip__flay'
    };
    /**
     * Возвращаем объект который всплывает - это DIV
     * @param elem
     * @returns {*}
     */
    var getBox = function(elem){
        //если бокс есть - возвращаем
        if('_ztooltip' in elem) return elem._ztooltip;
        //если его нету - создаем
        var text = elem.getAttribute('data-tooltip') || 'Пусто';
        var div = document.createElement('div');
        div.classList.add(opt.flayBoxName.substr(1));
        div.innerHTML = text;
        elem.appendChild(div);
        elem._ztooltip = div;
    };
    var initPosition = function(elem){
        var flay = elem._ztooltip;
        var coords = elem.getBoundingClientRect();
        var left = coords.left + (elem.offsetWidth-flay.offsetWidth)/2;
        if(left<0) left = 0;
        var top = coords.top - flay.offsetHeight - 5;
        if (top < 0) { // не вылезать за верхнюю границу окна
            top = coords.top + elem.offsetHeight + 5;
        }
        // console.log(left);
        flay.style.left = left+'px';
        flay.style.top = top+'px';
    };
    this.open = function(e){
        var t = e.target;
        if(!t.matches(opt.boxName)) return;
        var box = getBox(t);            //получаем контейнер
        initPosition(t);
        // box.hidden = !box.hidden;
    };
    this.close = function(e){
        var t = e.target;
        console.log(1,t._ztooltip);
        // e.target._ztooltip.hidden = !e.target._ztooltip.hidden;
    };
    var self = this;
    document.addEventListener('mouseover',function(){
        self.open.apply(this,arguments);
    });
    document.addEventListener('mouseout',function(){
        self.close.apply(this,arguments);
    });
};

_zak.scroll = new _zak.scrollFN();
_zak.tooltip = new _zak.tooltipFN();

var zzz = document.getElementById('zzz');


