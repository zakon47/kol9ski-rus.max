<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="/css/index.css<?= VER ?>">
    <link rel="stylesheet" href="/css/sprite/css/sprite.min.css<?= VER ?>">
    <link rel="stylesheet" href="/css/font-awesome.min.css<?= VER ?>">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700&amp;subset=cyrillic-ext" rel="stylesheet">
    <link rel="stylesheet" href="/css/owl.carousel.min.css">

    <!-- <link type="text/css" rel="stylesheet" href="/css/demo.css" />
     <link type="text/css" rel="stylesheet" href="/css/mmenu/jquery.mmenu.all.css" />-->

    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <!-- <script type="text/javascript" src="/js/mmenu/jquery.mmenu.all.js"></script>
    <script type="text/javascript">
        $(function() {
            $("#menu").mmenu({
                "extensions": [
                    "fx-panels-zoom",
                    "pagedim-black"
                ],
                "navbars": [
                    {
                        "position": "top",
                        "content": [
                            "<div>zakon</div>"
                        ]
                    },
                    {
                        "position": "top"
                    },
                    {
                        "position": "bottom",
                        "content": [
                            "<a class='fa fa-envelope' href='#/'></a>",
                            "<a class='fa fa-twitter' href='#/'></a>",
                            "<a class='fa fa-facebook' href='#/'></a>"
                        ]
                    }
                ]
            });
        });
    </script>-->
</head>
<body>


<div id="device"></div>


<div class="topline">
    <div class="fx">
        <div class="topline__wrap">
            <div class="topline__bar">
                <i class="fa fa-bars" aria-hidden="true"></i>
            </div>
            <div class="topline__phone">
                тут типо телефон
            </div>
        </div>

    </div>
</div>
<div class="navigator -open1">
    <div class="navigator__black"></div>
    <div class="navigator__wrap">
        <div class="navigator__logo">loG</div>
        <div class="navigator__search"></div>
        <div class="navigator__cart">xxaaaaaaaaaaaa</div>
        <div class="navigator__phone"></div>
        <div class="navigator__menu">
            <ul>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
                <li>xaxa</li>
            </ul>
        </div>
    </div>
</div>


<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Lorem ipsum dolor sit amet, consectetur adipisicing
    elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>


<script>
    //свой небольшой модуль
    (function (ow){
        let win = [];
        function ZakModule() {
            this.add = function(name){
                return win.push(name);
            };
            this.get = function(){
                return win;
            };
            this.open = function(name){
                this.add(name);                 //добавляем в стек
                $(name).addClass('-open');      //открываем окно
            };
            this.close = function(name){
                // this.remove(name);                 //добавляем в стек
                $(name).removeClass('-open');      //открываем окно
            };
        }
        window[ow] = new ZakModule();
    })('zak');

    $('.topline').on('click','.topline__bar i',function(){
        zak.open('.navigator');
    });
    $('.navigator').on('click','.navigator__black',function(){
        zak.close('.navigator');
    });

</script>

<!--<script src="/js/menu.js"></script>-->

</body>
</html>
