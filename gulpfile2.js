var gulp           = require('gulp'),
	gutil          = require('gulp-util' ),				// используется для вывода цветных сообщений на экран
	sass           = require('gulp-sass'),				//SASS компилятор
	browserSync    = require('browser-sync'),			//Браузер который обновляет css файлы?
	concat         = require('gulp-concat'),			//собирает Js файлы в один
	uglify         = require('gulp-uglify'),			//сжимает js файлы
	cleanCSS       = require('gulp-clean-css'),			//сжимаем css файл
	rename         = require('gulp-rename'),			//переименовать файл или добавить  префикс
	del            = require('del'),					//удаление файлов
	imagemin       = require('gulp-imagemin'),			//сжатие картинок
	pngquant       = require('imagemin-pngquant'),		//сжатие картинок 2
	cache          = require('gulp-cache'),				//кешируем чтобы быстрее собиралось
	autoprefixer   = require('gulp-autoprefixer'),		//Установка префиксов для браузеров https://github.com/postcss/autoprefixer#options
	bourbon        = require('node-bourbon'),
	ftp            = require('vinyl-ftp'),				//отправить файл на сервер
	csscomb 	   = require('gulp-csscomb'),       	//сортировка css файлов
	ts 				= require("gulp-typescript"),		//TypeScript module
	plumber 	   = require('gulp-plumber'),			//ловим ошибки
	notify         = require("gulp-notify");

	svgSprite = require('gulp-svg-sprite'),			//генерируем спрайт
	svgmin = require('gulp-svgmin'),					//минифицируем спрайт (каждый)
	cheerio = require('gulp-cheerio'),				//чистим от лишних тегов
	replace = require('gulp-replace');				//дочищаем + убираем баг
//update @2.0 for SVG sprite
	// gulp-css-group-media         = require("livereload????");

var dist_dir		= 'dist',						//боевой 	Проект
    dist_img_dir  	= dist_dir+'/img',				//JS 		Folder
    dist_css_dir  	= dist_dir+'/css',				//JS 		Folder
    dist_js_dir  	= dist_dir+'/js',				//JS 		Folder
    dist_fonts_dir  = dist_dir+'/fonts',			//Fonts 	Folder
	app_dir		= 'app',						//APP 		Проект
	web_dir		= 'web/',						//DEV 		Проект
	img_dir  	= web_dir+'/img',				//JS 		Folder
	js_dir  	= web_dir+'/js',				//JS 		Folder
	ts_dir  	= web_dir+'/ts',				//TS 		Folder
	css_dir  	= web_dir+'/css',				//CSS 		Folder
	scss_dir  	= web_dir+'/scss',				//SCSS 		Folder
	svg_dir  	= web_dir+'/svg',				//SCSS 		Folder
	svg_dir_dist = img_dir+'/svg',				//SCSS 		Folder
	svg_file  	= web_dir+'/**/*.svg',			//HTML 	File
	html_file  	= web_dir+'/**/*.html',			//HTML 	File
	php_file  	= app_dir+'/**/*.php',			//PHP 	File
	img_file  	= img_dir+'/**/*',				//IMG 	File
	css_file  	= css_dir+'/**/*.css',			//CSS 	File
	js_file  	= js_dir+'/**/*.js',			//JS 	File
	scss_file  	= scss_dir+'/**/*.scss',		//SCSS 	File
	domain  	= "br2.max",			//domain
	ts_file  	= ts_dir+'/**/*.ts';			//TS 	File


gulp.task('svg', function () {
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
				$('[fill]').removeAttr('fill');
				$('[stroke]').removeAttr('stroke');
				$('[style]').removeAttr('style');
			},
			parserOptions: {xmlMode: true}
		}))
		// cheerio plugin create unnecessary string '&gt;', so replace it.
		.pipe(replace('&gt;', '>'))
		// build svg sprite
		.pipe(svgSprite({
			mode: {
				symbol: {
					sprite: svg_dir_dist+"/sprite.svg",
					render: {
						scss: {
							dest: svg_dir_dist+'/_sprite.scss',
							template: svg_dir_dist + "/_sprite_template.scss"
							// dest:'../../../sass/_sprite.scss',
							// template: assetsDir + "sass/templates/_sprite_template.scss"
						}
					}
				}
			}
		}))
		.pipe(gulp.dest(svg_dir_dist + '/sprite/'));
});

//Обработка SCSS файлов
gulp.task('scss',function(){
    return gulp.src(scss_file)
        .pipe(sass()).on("error", notify.onError())								//компилируем
        .pipe(autoprefixer({
            //browsers: ['> 1% in RU','IE>9','ff > 3','Opera > 7','Chrome > 5'],						//https://github.com/ai/browserslist#queries
            browsers: ['> 0.1% in RU',																	// процент по россии браузеров
            'last 10 versions',
            'firefox >= 4',
            'safari >7',
            'IE >9'],
            cascade: true
        }))
        .pipe(csscomb())													//сортировка css
        .pipe(gulp.dest(css_dir))					//выгружаем
        .pipe(rename({suffix: '.min', prefix : ''}))
        .pipe(cleanCSS())
        .pipe(gulp.dest(css_dir))					//выгружаем
        .pipe(browserSync.reload({stream:true}));	//обновили в браузере
});

//Скрипты JS проекта
gulp.task('scripts', function() {
	return gulp.src([									//выбираем файлы
		'app/libs/jquery/dist/jquery.min.js',
		'app/js/common.js', 							// Всегда в конце
		])
	.pipe(concat('scripts.min.js'))						//название нового файла (куда все слили)
	.pipe(uglify())										//сжимаем файл
	.pipe(gulp.dest('app/js'))							//выгружаем
	.pipe(browserSync.reload({stream: true}));			//обновляем браузер
});

//Скрипты TS проекта
gulp.task('typescript', function () {
    return gulp.src(ts_file)
		.pipe(plumber())
        .pipe(ts({
            target: 'ES5'
        }))
        .pipe(gulp.dest(js_dir+'/full'))
        // .pipe(rename({suffix: '.min', prefix : ''}))
        .pipe(uglify({
            sourceMap: {
                filename: "out.js",
                url: "out.js.map"
            }
		}))										//сжимаем файл
        .pipe(gulp.dest(js_dir+'/min'));
});

//минифицируем картинки
gulp.task('imagemin', function() {
    return gulp.src(img_file)
        .pipe(cache(imagemin({
            interlaced: true,
            progressive: true,
            svgoPlugins: [{removeViewBox: false}],
            une: [pngquant()]
        })))
        .pipe(gulp.dest(dist_img_dir));
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

//ВОТЧЕР для - SCSS
gulp.task('watch',['scss','typescript','browser-sync'],function(){
    gulp.watch(scss_file,['scss']);
    gulp.watch(ts_file,['typescript']);
    // gulp.watch(['libs/**/*.js', 'app/js/common.js'], ['scripts']);
    gulp.watch(html_file, browserSync.reload);			//следим за изменением html
    gulp.watch(php_file, browserSync.reload);			//следим за изменением php
    gulp.watch(js_file, browserSync.reload);			//следим за изменением js
    gulp.watch(ts_file, browserSync.reload);			//следим за изменением ts
});

//Собираем боевую сборку
gulp.task('build', ['deldist', 'imagemin', 'scss', 'scripts'], function() {
	//переносим Html и PHP файлы
	// var buildFiles = gulp.src([html_file, php_file , web_dir+'/.htaccess'])
     //    .pipe(sass()).on("error", notify.onError())								//компилируем
	// 	.pipe(gulp.dest(dist_dir));
	//Переносим CSS
	var buildCss = gulp.src(['app/css/main.min.css'])
        .pipe(sass()).on("error", notify.onError())								//компилируем
		.pipe(gulp.dest(dist_css_dir));
    //Переносим JS
	var buildJs = gulp.src(['app/js/scripts.min.js'])
        .pipe(sass()).on("error", notify.onError())								//компилируем
		.pipe(gulp.dest(dist_js_dir));
    //Переносим Шрифты
	var buildFonts = gulp.src(['app/fonts/**/*'])
        .pipe(sass()).on("error", notify.onError())								//компилируем
		.pipe(gulp.dest(dist_fonts_dir));
});

//отправить файлы на сервер
gulp.task('deploy', function() {
	var conn = ftp.create({
		host:      'hostname.com',
		user:      'username',
		password:  'userpassword',
		parallel:  10,
		log: gutil.log
	});
	var globs = [
	'dist/**',
	'dist/.htaccess'
	];
	return gulp.src(globs, {buffer: false})
	.pipe(conn.dest('/path/to/folder/on/server'));
});

gulp.task('deldist', function() { return del.sync('dist'); });			//удалить папку Dist
gulp.task('clearcache', function () { return cache.clearAll(); });		//сбросим кеш картинок
gulp.task('default', ['watch']);


//Обработчик ошибок
function wrapPipe(taskFn) {
    return function(done) {
        var onSuccess = function() {
            done();
        };
        var onError = function(err) {
            done(err);
        }
        var outStream = taskFn(onSuccess, onError);
        if(outStream && typeof outStream.on === 'function') {
            outStream.on('end', onSuccess);
        }
    }
}