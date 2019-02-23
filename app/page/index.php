<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="/css/index.css<?= VER ?>">
    <link rel="stylesheet" href="/css/font-awesome.min.css<?= VER ?>">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700&amp;subset=cyrillic-ext" rel="stylesheet">
<!--    <link rel="stylesheet" href="/css/helpers/owl.carousel.min.css">-->

    <!-- <link type="text/css" rel="stylesheet" href="/css/demo.css" />
     <link type="text/css" rel="stylesheet" href="/css/mmenu/jquery.mmenu.all.css" />-->

    <script type="text/javascript" src="/js/jquery.min.js"></script>
<!--    <script src="/js/owl.carousel.min.js"></script>-->
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
<body id="body">


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
    <div class="fx navigator__fx">
        <div class="navigator__black"></div>
        <div class="navigator__wrap">
            <div class="navigator__logo">
                <a href="/"><i class="svg-icon svg-icon_logo2 svg-icon_logo2-dims"></i></a>
            </div>
            <div class="navigator__search">ПОИСК!</div>
            <div class="navigator__cart">xxaaaaaaaaaaa21a</div>
            <div class="navigator__phone">
                <div class="-email">
                    <a href="mailto:support@email.com">support@email.com</a>
                </div>
                <div class="-phone">
                    <a href="tel:+74952232323">+7 (495) 223-23-23</a>
                </div>
            </div>
            <div class="navigator__menu">
                <ul>
                    <li><a href="/">Главная</a></li>
                    <li><a href="/catalog">Каталог</a></li>
                    <li><a href="/dostavka">Доставка и оплата</a></li>
                    <li><a href="/contacts">Контакты</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="fx">

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
</div>


<script>
    //свой небольшой модуль
    (function (ow){
        let win = [];
        function ZakModule() {
            this.body = $('#body');
            this.add = function(name){
                return win.push(name);
            };
            this.get = function(){
                return win;
            };
            this.open = function(name){
                this.add(name);                 //добавляем в стек
                $(name).addClass('-open');      //открываем окно
                this.body.addClass('ovh');
            };
            this.close = function(name){
                // this.remove(name);                 //добавляем в стек
                $(name).removeClass('-open');      //открываем окно
                this.body.removeClass('ovh');      //открываем окно
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
