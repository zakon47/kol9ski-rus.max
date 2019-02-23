<html>
<head>
<!--    <link rel="stylesheet" href="/css/zlib/zgrid.css--><?//= VER ?><!--">-->
    <link rel="stylesheet" href="/css/index.css<?= VER ?>">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <style>
        .item {
            width: 200px;
        }
        .item img{
            max-width: 100%;
        }

        .item .itemtitle {
            font-weight: bold;
            font-size: 2em;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body id="body">

<div id="zdevice"></div>


<div id="listing">

    <!-- first few items are loaded normally -->
    <div class="item">
        <img
                src="/img/spiderman/1.png"
                alt="Spider Boy"
                width="300px"/>
        <span class="itemtitle">Spider Boy</span>
    </div>
    <div class="item">
        <img
                src="/img/spiderman/2.png"
                alt="Spider Boy"
                width="300px"/>
        <span class="itemtitle">Spider Boy</span>
    </div>
    <div class="lazy-wrap1  lazy-wrap">
        <div class="item">
            <img
                    src="/img/spiderman/load.gif"
                    class="lazy zlazy"
                    data-src="/img/spiderman/5.png"
                    alt="Dr. Strangefate"/>
            <span class="itemtitle">Dr. Strangefate</span>
        </div>
    </div>
    <div class="item">
        <img
                src="/img/spiderman/load.gif"
                class="lazy"
                data-src="/img/spiderman/6.png"
                alt="Dr. Strangefate"/>
        <span class="itemtitle">Dr. Strangefate</span>
    </div>

    <!-- everything after this is lazy -->
    <div id="viewMore">
        <a href="flatpage.html#more">View more</a>
    </div>
    <span id="nextPage" class="hidden lazy-wrap lazy-wrap2">
        <div class="item">
            <img
                    src="/img/spiderman/load.gif"
                    class="lazy zlazy"
                    data-src="/img/spiderman/3.png"
                    data-zdevice="sm"
                    alt="Dr. Strangefate"/>
            <span class="itemtitle">Dr. Strangefate</span>
        </div>
        <div class="item" id="myDiv">
            <img
                    src="/img/spiderman/load.gif"
                    class="lazy zlazy"
                    data-src="/img/spiderman/4.png"
                    data-zdevice="sm xs"
                    alt="Dr. Strangefate"/>
            <span class="itemtitle">Dr. Strangefate</span>
        </div>
    </span>
</div>

<script>
    var lazy = [];

    addEventListener('load', setLazy);      //отображаем gif - картинки не отобразятся НО ЗАГРУЗЯТСЯ
    // addEventListener('load', lazyLoad);     //заменяем ссылку data на src
    // addEventListener('scroll', lazyLoad);
    // addEventListener('resize', lazyLoad);

    function setLazy() {
        document.getElementById('listing').removeChild(document.getElementById('viewMore'));            //удалили viewMore
        document.getElementById('nextPage').removeAttribute('class');

        lazy = document.getElementsByClassName('lazy');         //выбрали все картинки
        // console.log('Found ' + lazy.length + ' lazy images');
    }   //нашли сколько-то картинок
    function lazyLoad() {
        for (var i = 0; i < lazy.length; i++) {
            if (isInViewport(lazy[i])) {
                if (lazy[i].getAttribute('data-src')) {
                    lazy[i].src = lazy[i].getAttribute('data-src');
                    lazy[i].removeAttribute('data-src');
                }
            }
        }

        // cleanLazy();         //удалили атрибут ленивой загрузки
    }
    function cleanLazy() {
        lazy = Array.prototype.filter.call(lazy, function (l) {
            return l.getAttribute('data-src');
        });
    }


    function isInViewport(el) {
        var rect = el.getBoundingClientRect();
        return (
            rect.bottom >= 0 &&
            rect.right >= 0 &&
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.left <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

</script>

<script>
    (function (ow){
        let win = [];

        function ZakLazyImg(){
            this.xaxa = function(){
                console.log(222);
            }
        }
        function ZakModule() {
            let param = {};
            let zDavice = ['lg','md','sm','xs','xss'];
            let lazyImg = [];
            let device = '';
            this.init = function(p){
                param = p;
                //ОПРЕДЕЛЯЕМ РАЗРЕЩЕНИЕ УСТРОЙСТВА
                Object.defineProperty(this, "device", {
                    get: function() {
                        if (!device) return;
                        return device;
                    }
                });
                function updateDevice(){
                    device = getComputedStyle(document.getElementById('zdevice')).content;
                    device = device.substring(1,device.length-1);
                }
                updateDevice();
                addEventListener('resize',updateDevice);

                //СЧИТЫВАЕМ ДОПОПЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ
                param = param || '';
                if(param['lazy']){
                    this.lazy = function(elems){
                        if(elems.length == undefined) elems = [elems];      //если выборка по #ID
                        var ID = lazyImg.length;
                        elems.forEach(function(elem){
                            let el = elem.querySelectorAll('.zlazy');
                            el.forEach(function(v){
                                //если нету такого элемента - то добавляем его
                                let elemID = v.lazyid;
                                if(!elemID){
                                    v.lazyid = ID;
                                    lazyImg[ID] = v;
                                    ID++;
                                }
                            });
                        });
                    };              //инициализиация элемента
                    this.lazy(param['lazy']);
                    this.lazy.list = function () {
                        return lazyImg;
                    };            //показать стек выбранных картинок

                    // this.loadImg();
                    addEventListener('load',this.loadImg);
                    addEventListener('scroll',this.loadImg);
                    addEventListener('resize',this.loadImg);
                }
            };
            this.loadImg = function(){
                lazyImg.forEach(function(v){
                    if (isInViewport(v)) {
                        let link = v.getAttribute('src');
                        if (v.getAttribute('data-src')) {
                            link = v.getAttribute('data-src');
                            // v.removeAttribute('data-src');
                            if (v.getAttribute('data-zdevice')) {
                                let dop = '';
                                let dev = v.getAttribute('data-zdevice').split(' ');
                                for(let j=0;j<zDavice.length;j++){
                                    if(device == zDavice[j]){
                                        break;
                                    }
                                    if(dev.indexOf(zDavice[j])!=-1){
                                        dop = zDavice[j];
                                    }
                                }
                                if(dop){
                                    let x = link.split('/');
                                    if(x.length > 1){
                                        x[x.length-1] = dop + '-' + x[x.length-1];
                                    }
                                    link = x.join('/');
                                }
                            }
                        }
                        v.src = link;
                    }
                });
            }
        }
        ZakModule.prototype.da = function(){
            console.log(1111);
        };
        window[ow] = new ZakModule();
    })('zak');
    zak.init({
        // lazy: document.getElementById('body')
        lazy: document.querySelectorAll('.lazy-wrap')
    });

    console.log(zak.device);
    addEventListener('resize', function(){
        console.log(zak.device);
    });
    // console.log(zak.lazy.list());
    // zak.lazy(document.querySelectorAll('.lazy-wrap2'));
    // console.log(zak.lazy.list());
</script>
</body>
</html>
