var gulp           = require('gulp'),
    browserSync    = require('browser-sync'),			//Браузер который обновляет css файлы?
    sass           = require('gulp-sass'),				//SASS компилятор
    autoprefixer   = require('gulp-autoprefixer'),		//Установка префиксов для браузеров https://github.com/postcss/autoprefixer#options
    csscomb 	   = require('gulp-csscomb'),       	//сортировка css файлов
    rename         = require('gulp-rename'),			//переименовать файл или добавить  префикс
    cleanCSS       = require('gulp-clean-css'),			//сжимаем css файл
    notify         = require("gulp-notify"),

    sassImage = require('gulp-sass-image'),

    svgSprite = require('gulp-svg-sprite'),			//генерируем спрайт
    svgmin = require('gulp-svgmin'),					//минифицируем спрайт (каждый)
    cheerio = require('gulp-cheerio'),				//чистим от лишних тегов
    replace = require('gulp-replace');				//дочищаем + убираем баг

    // gutil          = require('gulp-util' ),				// используется для вывода цветных сообщений на экран
    // sass           = require('gulp-sass'),				//SASS компилятор
    // browserSync    = require('browser-sync'),			//Браузер который обновляет css файлы?
    // concat         = require('gulp-concat'),			//собирает Js файлы в один
    // uglify         = require('gulp-uglify'),			//сжимает js файлы
    // cleanCSS       = require('gulp-clean-css'),			//сжимаем css файл
    // rename         = require('gulp-rename'),			//переименовать файл или добавить  префикс
    // del            = require('del'),					//удаление файлов
    // imagemin       = require('gulp-imagemin'),			//сжатие картинок
    // pngquant       = require('imagemin-pngquant'),		//сжатие картинок 2
    // cache          = require('gulp-cache'),				//кешируем чтобы быстрее собиралось
    // autoprefixer   = require('gulp-autoprefixer'),		//Установка префиксов для браузеров https://github.com/postcss/autoprefixer#options
    // bourbon        = require('node-bourbon'),
    // ftp            = require('vinyl-ftp'),				//отправить файл на сервер
    // csscomb 	   = require('gulp-csscomb'),       	//сортировка css файлов
    // ts 				= require("gulp-typescript"),		//TypeScript module
    // plumber 	   = require('gulp-plumber'),			//ловим ошибки


var dist_dir		= 'dist',						//боевой 	Проект
    dist_img_dir  	= dist_dir+'/img',				//JS 		Folder
    dist_css_dir  	= dist_dir+'/css',				//JS 		Folder
    dist_js_dir  	= dist_dir+'/js',				//JS 		Folder
    dist_fonts_dir  = dist_dir+'/fonts',			//Fonts 	Folder
    app_dir		= 'app',						//APP 		Проект
    web_dir		= 'web',						//DEV 		Проект
    img_dir  	= web_dir+'/img',				//JS 		Folder
    js_dir  	= web_dir+'/js',				//JS 		Folder
    ts_dir  	= web_dir+'/ts',				//TS 		Folder
    css_dir  	= web_dir+'/css',				//CSS 		Folder
    scss_dir  	= web_dir+'/scss',				//SCSS 		Folder
    svg_dir  	= web_dir+'/svg',				//SCSS 		Folder
    svg_file  	= svg_dir+'/**/*.svg',				//SCSS 		Folder
    svg_dir_dist = img_dir+'/svg',				//SCSS 		Folder
    html_file  	= web_dir+'/**/*.html',			//HTML 	File
    php_file  	= app_dir+'/**/*.php',			//PHP 	File
    img_file  	= img_dir+'/**/*',				//IMG 	File
    css_file  	= css_dir+'/**/*.css',			//CSS 	File
    js_file  	= js_dir+'/**/*.js',			//JS 	File
    scss_file  	= scss_dir+'/**/*.scss',		//SCSS 	File
    domain  	= "kol9ski-rus.max",			//domain
    ts_file  	= ts_dir+'/**/*.ts';			//TS 	File

//SVG sprite
gulp.task('svg_sprite', function () {
    return gulp.src(svg_file)
    // минифицируем svg
        .pipe(svgmin({
            js2svg: {
                pretty: true
            }
        }))
        // remove all fill, style and stroke declarations in out shapes
        .pipe(cheerio({
            run: function ($) {
                // $('[fill]').removeAttr('fill');
                $('[stroke]').removeAttr('stroke');
                $('[style]').removeAttr('style');
            },
            parserOptions: {xmlMode: true}
        }))
        // cheerio plugin create unnecessary string '&gt;', so replace it.
        .pipe(replace('&gt;', '>'))

        // .pipe(svgSprite({
        //         selector: "i-sp-%f",
        //         svg: {
        //             sprite: "svg.svg"
        //         },
        //         svgPath: "%f",
        //         cssFile: "svg_sprite.css",
        //         common: "ic"
        //     }
        // ))
        // .pipe(svgSprite({
        //         mode: {
        //             stack: {
        //                 sprite: "sprite.svg"  //sprite file name
        //             }
        //         },
        //     }
        // ))
        .pipe(svgSprite({
            shape: {
                spacing: {         // Add padding
                    padding: 2
                }
            },
            mode: {
                css: { // Activate the «css» mode
                    bust: false,
                    sprite: "/img/svg/sprite.svg",
                    render: {
                        scss: true // Activate CSS output (with default options)
                    }
                }
            }
            // mode: {
            //     symbol: {
            //         sprite: "sprite.svg",
            //         render: {
            //             scss: {
            //                 dest: '/'+scss_dir+'/sprite/_sprite.scss',
            //                 // dest: '_sprite.scss',
            //                 template: scss_dir+"/sprite/_sprite_template2.scss"
            //                 // templates: {
            //                 //     css: scss_dir + '/sprite/sprite-template2.scss'
            //                 // }
            //             }
            //         },
            //         // templates: {
            //         //     css: scss_dir + '/sprite/sprite-template.scss'
            //         // }
            //     }
            // }


            // cssFile: 'ZAK.css',
            // preview: false,
            // layout: 'diagonal',
            // padding: 5,
            // svg: {
            //     sprite: 'ZAK.svg'
            // },
            // templates: {
            //     css: scss_dir + '/sprite/sprite-template2.scss'
            // }
        }))
        .pipe(gulp.dest(scss_dir + '/sprite/'));
});
gulp.task('svg_sprite_optimaze', function () {
    return gulp.src(svg_dir_dist+'/**/*.svg')
        .pipe(svgmin({
            js2svg: {
                pretty: true
            }
        }))
        .pipe(cheerio({
            run: function ($) {
                // $('[fill]').removeAttr('fill');
                $('[stroke]').removeAttr('stroke');
                $('[style]').removeAttr('style');
            },
            parserOptions: {xmlMode: true}
        }))
        .pipe(replace('&gt;', '>'))
        .pipe(gulp.dest(svg_dir_dist));
});

// gulp.task('svg', function () {
//     return gulp.src(svg_dir+'/**/*.svg')
//         .pipe(svgSprite({
//             cssFile: 'sprite.css',
//             preview: false,
//             layout: 'diagonal',
//             padding: 5,
//             svg: {
//                 sprite: 'sprite.svg'
//             },
//             template: scss_dir+"/_sprite_template2.scss"
//         }))
//         .pipe(gulp.dest(svg_dir_dist));
// });

//Обработка SCSS файлов
gulp.task('scss',function(){
    return gulp.src(scss_file)
        .pipe(sass()).on("error", notify.onError())								//компилируем
        // .pipe(autoprefixer({
        //     //browsers: ['> 1% in RU','IE>9','ff > 3','Opera > 7','Chrome > 5'],						//https://github.com/ai/browserslist#queries
        //     browsers: ['> 0.1% in RU',																	// процент по россии браузеров
        //         'last 10 versions',
        //         'firefox >= 4',
        //         'safari >7',
        //         'IE >9'],
        //     cascade: true
        // }))
        // .pipe(csscomb())													//сортировка css
        .pipe(gulp.dest(css_dir))					//выгружаем
        .pipe(rename({suffix: '.min', prefix : ''}))
        .pipe(cleanCSS())
        .pipe(gulp.dest(css_dir))					//выгружаем
        .pipe(browserSync.reload({stream:true}));	//обновили в браузере
});

//Запуск обновляемый браузер
gulp.task('browser-sync', function() {
    browserSync({
        // server: {baseDir: web_dir},				//корневая директория
        proxy: domain,
        notify: false,
        // tunnel: true,
        // tunnel: "projectmane", //Demonstration page: http://projectmane.localtunnel.me
    });
});

// //ВОТЧЕР для - SCSS
// gulp.task('watch',['scss','browser-sync'],function(){
//     gulp.watch(scss_file,['scss']);
//     // gulp.watch(['libs/**/*.js', 'app/js/common.js'], ['scripts']);
//     gulp.watch(html_file, browserSync.reload);			//следим за изменением html
//     gulp.watch(php_file, browserSync.reload);			//следим за изменением php
//     gulp.watch(js_file, browserSync.reload);			//следим за изменением js
//     gulp.watch(ts_file, browserSync.reload);			//следим за изменением ts
// });
//
//
// gulp.task('default', ['watch']);

gulp.task('watch', function(cb) {
    gulp.parallel(
        'scss',
        'browser-sync'
    )(cb);
    // gulp.watch(svg_dir_dist+'/**/*.svg', gulp.series('svg_sprite_optimaze'));
    gulp.watch(scss_file, gulp.series('scss'));
    // gulp.watch(['app/libs/**/*.js', 'app/js/main.js'], gulp.series('js'));
    gulp.watch(php_file).on('change', browserSync.reload);
    gulp.watch(html_file).on('change', browserSync.reload);
});

gulp.task('svg', gulp.series('svg_sprite'));
gulp.task('default', gulp.series('watch'));
