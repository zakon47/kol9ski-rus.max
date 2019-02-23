var domain = "kol9ski-rus.max",
    gulp = require('gulp'),
    browserSync = require('browser-sync'),			//Браузер который обновляет css файлы?
    sass = require('gulp-sass'),				//SASS компилятор
    autoprefixer = require('gulp-autoprefixer'),		//Установка префиксов для браузеров https://github.com/postcss/autoprefixer#options
    csscomb = require('gulp-csscomb'),       	//сортировка css файлов
    rename = require('gulp-rename'),			//переименовать файл или добавить  префикс
    cleanCSS = require('gulp-clean-css'),			//сжимаем css файл
    notify = require("gulp-notify"),
    imagemin = require('gulp-imagemin'),			//сжатие картинок

    sassImage = require('gulp-sass-image'),

    sourcemaps = require('gulp-sourcemaps'),
    sourcemapsDest = ".maps",
    rm            = require('gulp-rm'),					//удаление файлов
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
//
// pngquant       = require('imagemin-pngquant'),		//сжатие картинок 2
// cache          = require('gulp-cache'),				//кешируем чтобы быстрее собиралось
// autoprefixer   = require('gulp-autoprefixer'),		//Установка префиксов для браузеров https://github.com/postcss/autoprefixer#options
// bourbon        = require('node-bourbon'),
// ftp            = require('vinyl-ftp'),				//отправить файл на сервер
// csscomb 	   = require('gulp-csscomb'),       	//сортировка css файлов
// ts 				= require("gulp-typescript"),		//TypeScript module
// plumber 	   = require('gulp-plumber'),			//ловим ошибки


// ==========================
var path = {
    php: {
        'from': "./app/**/*.php"
    },
    html: {
        'from': "./app/**/*.php"
    },
    img: {
        'from': "./src/img/**/*.*",
        'public': "./public_html/img/",
    },
    svg: {
        'from': "./src/svg-sprite/**/*.svg",
        'public': "./src/scss/",
        'from2': "./src/svg/*.svg",
        'public2': "./public_html/svg/",
    },
    scss: {
        'from': "./src/scss/**/*.scss",
        'public': "./public_html/css/",
    },
    css: {
        'from': "./src/css/**/*.css",
        'public': "./public_html/css/",
    },
    fonts: {
        'from': "./src/fonts/**/*.*",
        'public': "./public_html/fonts/",
    },
    js: {
        'from': "./src/js/**/*.*",
        'public': "./public_html/js/",
    },
    rm: [
        './public_html/svg/**/*',
        './public_html/img/**/*',
        './public_html/css/.*/*',
        './public_html/css/**/*',
        './public_html/css/**/.*',
        './public_html/js/**/*',
        './public_html/fonts/**/*'
    ],
};
gulp.task('imgmin', function () {
    return gulp.src(path.img.from)
        .pipe(imagemin())
        .pipe(gulp.dest(path.img.public));
});                 //минифицировать картинки (сжать)
gulp.task('svg-sprite', function () {
    return gulp.src(path.svg.from)
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
        .pipe(svgSprite({
            shape: {
                spacing: {         // Add padding
                    padding: 2
                }
            },
            mode: {
                // sprite: path.svg.public+"sprite777.svg",  //sprite file name
                // render: {
                //     scss: true // Activate CSS output (with default options)
                // },
                css: { // Activate the «css» mode
                    bust: false,
                    sprite: "../../svg/sprite.svg",
                    render: {
                        scss: true // Activate CSS output (with default options)
                    },
                }
            }
        }))
        .pipe(rename({suffix: '', prefix: '_'}))
        .pipe(gulp.dest(path.svg.public));
});             //SVG sprite
gulp.task('scss', function () {
    return gulp.src(path.scss.from)
        .pipe(sourcemaps.init())
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
        .pipe(sourcemaps.write(sourcemapsDest))
        .pipe(gulp.dest(path.scss.public))					//выгружаем
        .pipe(rename({suffix: '.min', prefix: ''}))
        .pipe(cleanCSS())
        .pipe(gulp.dest(path.scss.public))					//выгружаем
        .pipe(browserSync.reload({stream: true}));	//обновили в браузере
});             //Обработка SCSS файлов
gulp.task('css', function () {
    return gulp.src(path.css.from)
        .pipe(gulp.dest(path.css.public));
});                     //переносим css
gulp.task('js', function () {
    return gulp.src(path.js.from)
        .pipe(gulp.dest(path.js.public));
});                     //переносим js
gulp.task('fonts', function () {
    return gulp.src(path.fonts.from)
        .pipe(gulp.dest(path.fonts.public));
});                     //переносим fonts
gulp.task('rm', function () {
    return gulp.src(path.rm, {read: false })
        .pipe(rm());
});                     //переносим fonts

var dirSep = '/';
gulp.task('svg', function () {
    return gulp.src(path.svg.from2)
        .pipe(rename(function(path) {
            // Переменная dirSep содержит разделитель директорий для
            // текущей ОС. Этот хак позволяет проводить сборку как в
            // Linux так и в Windows системах.
            var dirs = path.dirname.split(dirSep);

            dirs.splice(1, 1);
            path.dirname = dirs.join(dirSep);
        }))
        .pipe(rename({basename: 'sprite'}))
        .pipe(gulp.dest(path.svg.public2));
        // .pipe(gulp.dest('.'));
});                     //переносим fonts

// ==========================
gulp.task('browser-sync', function () {
    browserSync({
        // server: {baseDir: web_dir},				//корневая директория
        proxy: domain,
        notify: false,
        // tunnel: true,
        // tunnel: "projectmane", //Demonstration page: http://projectmane.localtunnel.me
    });
});             //Запуск обновляемый браузер
gulp.task('watch', function (cb) {
    gulp.parallel(
        'svg-sprite',
        'svg',
        'imgmin',
        'css',
        'scss',
        'fonts',
        'browser-sync'
    )(cb);
    gulp.watch(path.svg.from, gulp.series('svg-sprite'));
    gulp.watch(path.svg.from2, gulp.series('svg'));
    gulp.watch(path.img.from, gulp.series('imgmin'));
    gulp.watch(path.scss.from, gulp.series('scss'));
    gulp.watch(path.js.from, gulp.series('js'));
    gulp.watch(path.fonts.from, gulp.series('fonts'));
    gulp.watch('./public_html/').on('change', browserSync.reload);          //любой отражаемый файл в public обновляется в браузере
    gulp.watch(path.php.from).on('change', browserSync.reload);             //тоже самое для php
});
gulp.task('default', gulp.series('watch'));
// gulp.task('DIST', gulp.series('rm'));
