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
            <div class="topline__logo">
                <a href="/"><i class="svg-icon svg-icon_logo svg-icon_logo-dims"></i></a>
                <span>Магазин удобных <br>детских колясок</span>
            </div>
            <div class="topline__phone">
                <div class="-email">
                    <a href="mailto:support@email.com">support@email.com</a>
                </div>
                <div class="-phone">
                    <a href="tel:+74952232323">+7 (495) 223-23-23</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="navigator -open1">
    <div class="fx navigator__fx">
        <div class="navigator__black"></div>
        <div class="navigator__wrap">
            <div class="navigator__logo">
                <a href="/"><i class="svg-icon svg-icon_logo svg-icon_logo-dims"></i></a>
                <span>Магазин удобных <br>детских колясок</span>
            </div>
            <div class="navigator__search">
                <form action="/" method="post">
                    <input type="search" placeholder="Поиск">
                    <button type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                </form>
            </div>
            <div class="navigator__cart">
                <div class="-wrap">
                    <a href="/cart">
                        <div class="svg-icon svg-icon_cart  svg-icon_cart-dims"><b>5</b></div>
                        <span>1 товар (7 490 р.)</span>
                    </a>
                    <button>
                        <i></i>
                    </button>
                </div>
            </div>
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
                    <li><a href="/" class="-active">Главная</a></li>
                    <li><a href="/catalog">Каталог</a></li>
                    <li><a href="/dostavka">Доставка и оплата</a></li>
                    <li><a href="/contacts">Контакты</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="slider ten">
    <div class="fx">
        <div class="slider__wrap">
            <div class="slider__info">
                <h1>Самая легкая коляска</h1>
                <div class="slider__img2">
                    <img src="/img/slider/kol9ska.png" class="img-responsive">
                </div>
                <ul class="-desc zli">
                    <li>Можно взять в ручную кладь</li>
                    <li>Коляска очень легкая</li>
                    <li>Не занимает много места</li>
                    <li>Помещается в любой багажник</li>
                </ul>
                <a href="/catalog" class="btn -big">Перейти в каталог</a>
            </div>
            <div class="slider__img">
                <img src="/img/slider/kol9ska.png" class="img-responsive">
            </div>
        </div>
    </div>
</div>
<div class="catalog">
    <div class="fx">
        <div class="catalog__wrap">
            <div class="catalog__title">
                <h2>Каталог детских колясок YOYA</h2>
                <div class="volna svg-icon svg-icon_volna svg-icon_volna-dims"></div>
            </div>
            <div class="catalog__list">
                <div>
                    <div class="catalog__item">
                        <div class="-podarok">
                            <i class="svg-icon svg-icon_podarok svg-icon_podarok-dims"></i>
                            <span>Подарок</span>
                        </div>
                        <div class="-img">
                            <a href="/">
<!--                                <img src="/img/catalog/1.jpg" class="img-responsive">-->
                                <img src="/img/catalog/1.jpg" class="img-responsive">
                            </a>
                        </div>
                        <div class="-wrap">
                            <div class="-name">Коляска YOYA 175</div>
                            <div class="-desc">
                                <p>Цвет: бежевый</p>
                            </div>
                            <div class="-price">
                                <button class="btn">В корщину</button>
                                <span>7 490 р.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="catalog__item">
                        <div class="-podarok">
                            <i class="svg-icon svg-icon_podarok svg-icon_podarok-dims"></i>
                            <span>Подарок</span>
                        </div>
                        <div class="-img">
                            <a href="/">
                                <!--                                <img src="/img/catalog/1.jpg" class="img-responsive">-->
                                <img src="/img/catalog/2.jpg" class="img-responsive">
                            </a>
                        </div>
                        <div class="-wrap">
                            <div class="-name">Коляска YOYA 175</div>
                            <div class="-desc">
                                <p>Цвет: бежевый</p>
                            </div>
                            <div class="-price">
                                <button class="btn">В корщину</button>
                                <span>7 490 р.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="catalog__item">
                        <div class="-podarok">
                            <i class="svg-icon svg-icon_podarok svg-icon_podarok-dims"></i>
                            <span>Подарок</span>
                        </div>
                        <div class="-img">
                            <a href="/">
                                <!--                                <img src="/img/catalog/1.jpg" class="img-responsive">-->
                                <img src="/img/catalog/3.jpg" class="img-responsive">
                            </a>
                        </div>
                        <div class="-wrap">
                            <div class="-name">Коляска YOYA 175</div>
                            <div class="-desc">
                                <p>Цвет: бежевый</p>
                            </div>
                            <div class="-price">
                                <button class="btn">В корщину</button>
                                <span>7 490 р.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="catalog__item">
                        <div class="-podarok">
                            <i class="svg-icon svg-icon_podarok svg-icon_podarok-dims"></i>
                            <span>Подарок</span>
                        </div>
                        <div class="-img">
                            <a href="/">
                                <!--                                <img src="/img/catalog/1.jpg" class="img-responsive">-->
                                <img src="/img/catalog/4.jpg" class="img-responsive">
                            </a>
                        </div>
                        <div class="-wrap">
                            <div class="-name">Коляска YOYA 175</div>
                            <div class="-desc">
                                <p>Цвет: бежевый</p>
                            </div>
                            <div class="-price">
                                <button class="btn">В корщину</button>
                                <span>7 490 р.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="catalog__item">
                        <div class="-podarok">
                            <i class="svg-icon svg-icon_podarok svg-icon_podarok-dims"></i>
                            <span>Подарок</span>
                        </div>
                        <div class="-img">
                            <a href="/">
                                <!--                                <img src="/img/catalog/1.jpg" class="img-responsive">-->
                                <img src="/img/catalog/5.jpg" class="img-responsive">
                            </a>
                        </div>
                        <div class="-wrap">
                            <div class="-name">Коляска YOYA 175</div>
                            <div class="-desc">
                                <p>Цвет: бежевый</p>
                            </div>
                            <div class="-price">
                                <button class="btn">В корщину</button>
                                <span>7 490 р.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="catalog__item">
                        <div class="-podarok">
                            <i class="svg-icon svg-icon_podarok svg-icon_podarok-dims"></i>
                            <span>Подарок</span>
                        </div>
                        <div class="-img">
                            <a href="/">
                                <!--                                <img src="/img/catalog/1.jpg" class="img-responsive">-->
                                <img src="/img/catalog/6.jpg" class="img-responsive">
                            </a>
                        </div>
                        <div class="-wrap">
                            <div class="-name">Коляска YOYA 175</div>
                            <div class="-desc">
                                <p>Цвет: бежевый</p>
                            </div>
                            <div class="-price">
                                <button class="btn">В корщину</button>
                                <span>7 490 р.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="catalog__showall">
                <a href="/" class="btn -big">Смотреть все модели</a>
            </div>
        </div>
    </div>
</div>
<div class="osobennosti">
    <div class="fx">
        <div class="osobennosti__wrap">
            <div class="osobennosti__title">
                <h2>Особенности колясок YOYA</h2>
                <div class="volna svg-icon svg-icon_volna svg-icon_volna-dims"></div>
            </div>
            <div class="osobennosti__body">
                <div class="osobennosti__img">
                    <img src="/img/colaska.png" class="img-responsive">
                </div>
                <div class="osobennosti__list">
                    <div class="osobennosti__item">
                        <span>Невероятно компактная</span>
                    </div>
                    <div class="osobennosti__item">
                        <span>Отстегивающийся бампер</span>
                    </div>
                    <div class="osobennosti__item">
                        <span>Окошко в капе</span>
                    </div>
                    <div class="osobennosti__item">
                        <span>Амортизация передних колес</span>
                    </div>
                    <div class="osobennosti__item">
                        <span>Карман на задней спинке</span>
                    </div>
                    <div class="osobennosti__item">
                        <span>Тормоз-трубка блокирует оба колеса</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="easy">
    <div class="fx">
        <div class="easy__wrap">
            <div class="easy__title">
                <h2>Простота и легкость <br>
                    в использовании коляски</h2>
                <div class="volna svg-icon svg-icon_volna svg-icon_volna-dims"></div>
            </div>
            <div class="easy__body">
                <div class="easy__img">
                    <i class="svg-icon svg-icon_romb svg-icon_romb-dims"></i>
                </div>
                <div class="easy__list">
                    <div>
                        <div class="easy__item">
                            <div class="-img"><img src="/img/easy/1.jpg" class="img-responsive"></div>
                            <div class="-title">Легко складывается</div>
                            <div class="-desc">Складывание за 10 секунд!</div>
                        </div>
                    </div>
                    <div>
                        <div class="easy__item">
                            <div class="-img"><img src="/img/easy/2.jpg" class="img-responsive"></div>
                            <div class="-title">Легко раскладывается</div>
                            <div class="-desc">Раскладывание всего за 2 секунды одной рукой</div>
                        </div>
                    </div>
                    <div>
                        <div class="easy__item">
                            <div class="-img"><img src="/img/easy/3.jpg" class="img-responsive"></div>
                            <div class="-title">Надёжная</div>
                            <div class="-desc">Независимая подвеска, адаптированная к любым поверхностям, с поворотом передних колес на 360°</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="komplect">
    <div class="fx">
        <div class="komplect__wrap">
            <div class="komplect__title">
                <h2>Что входит в комплект с коляской?</h2>
                <div class="volna svg-icon svg-icon_volna svg-icon_volna-dims"></div>
            </div>
            <div class="komplect__body">
                <div class="komplect__img">
                    <i class="svg-icon svg-icon_romb svg-icon_romb-dims"></i>
                </div>
                <div class="komplect__list">
                    <div class="komplect__item">
                        <img src="/img/komplect/1.png" class="img-responsive">
                        <span>Матрас из бамбука</span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/2.png" class="img-responsive">
                        <span>Бампер регулируемый </span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/3.png" class="img-responsive">
                        <span>Чехол для хранения</span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/4.png" class="img-responsive">
                        <span>Ремень на плечо</span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/5.png" class="img-responsive">
                        <span>Сетка москитная</span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/6.png" class="img-responsive">
                        <span>Ремешок на руку</span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/7.png" class="img-responsive">
                        <span>Дождевик</span>
                    </div>
                    <div class="komplect__item">
                        <img src="/img/komplect/8.png" class="img-responsive">
                        <span>Подстаканник</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="sertificat">
    <div class="fx">
        <div class="sertificat__wrap">
            <div class="sertificat__title">
                <h2>Имеем сертификат соответствия</h2>
                <div class="volna svg-icon svg-icon_volna svg-icon_volna-dims"></div>
            </div>
            <div class="sertificat__body">
                <div class="sertificat__img">
                    <i class="svg-icon svg-icon_romb svg-icon_romb-dims"></i>
                </div>
                <div class="sertificat__list">
                    <div class="-info">
                        <i></i><div>Каждая коляска фирмы YOYA сертифицирована</div>
                    </div>
                    <div class="-img">
                        <img src="/img/sertificat.jpg" class="img-responsive">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="garantia">
    <div class="fx">
        <div class="garantia__wrap">
            <div class="garantia__title">
                <h2>Официальная гарантия <br>
                    <span>365 дней</span> с момента покупки</h2>
            </div>
            <div class="garantia__body">
                <div class="garantia__img">
                    <i class="svg-icon svg-icon_romb svg-icon_romb-dims"></i>
                </div>
                <div class="garantia__list">
                    <div class="-info">
                        <div class="-txt">
                            <p>Если сломалась коляска, то обменяем её
                                на новую или вернем деньги</p>

                            <p>Заводская гарантия 1 год.</p>

                            <p>Бесплатное гарантийное обслуживание
                                6 месяцев: (бесплатная замена колес, подшипников в случае поломки, даже по вине перевозчика,
                                в течение 10 минут в нашем сервисном центре, без выяснения причин)</p>
                        </div>
                        <div class="-btn">
                            <a href="/catalog" class="btn -big">Выбрать коляску</a>
                        </div>
                    </div>
                    <div class="-video">
                        <div class="-www">
                            <strong>Посмотрите видео-обзор <br>на коляску YOYA</strong>
                            <div>
                                <i class="svg-icon svg-icon_play svg-icon_play-dims"></i>
                                <a href="/">
                                    <img src="/img/video.jpg" class="img-responsive">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<br><br><br><br><br><br><br><br><br>
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
                $(name).toggleClass('-open');      //открываем окно
                this.body.toggleClass('ovh');
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
