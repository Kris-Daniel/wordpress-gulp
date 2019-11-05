const
    gulp         = require('gulp'),
    watch        = require('gulp-watch'),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCSS     = require('gulp-clean-css'),
    preproc      = require('gulp-less'),
    rigger       = require('gulp-rigger'),
    babel        = require('gulp-babel'),
    uglify       = require('gulp-uglify'),
    plumber      = require('gulp-plumber'),
    combineMq    = require('gulp-combine-mq');
    jade         = require('gulp-jade-php');

const theme = '../dascodingWP/';

const path = {
    build: {
        html:     theme,
        php:      theme,
        js:       theme + 'assets/js/',
        css:      theme,
        img:      theme + 'assets/img',
        fonts:    theme + 'assets/fonts/',
        cssAdmin: theme + 'assets/css/',
        jsAdmin:  theme + 'assets/js/'
    },
    src: {
        html:     'src/**/*.jade',
        php:      'src/**/*.php',
        js:       'src/assets/js/script.js',
        jsAll:    'src/assets/js/**/*.js',
        css:      'src/assets/css/style.less',
        cssAll:   'src/assets/css/**/*.less',
        img:      'src/assets/img/**/*.*',
        fonts:    'src/assets/fonts/**/*.*',
        cssAdmin: 'src/assets/css/**/*.css',
        jsAdmin:  'src/assets/js/admin.js',
    },
    clean: '/build'
};

gulp.task('html', function() {
    gulp.src(path.src.html)
        .pipe(plumber())
        .pipe(jade())
        .pipe(gulp.dest(path.build.html));
});
gulp.task('php', function () {
    gulp.src(path.src.php)
        .pipe(plumber())
        .pipe(gulp.dest(path.build.php))
});
gulp.task('js', function () {
    gulp.src(path.src.js)
        .pipe(plumber())
        .pipe(rigger())
        /*.pipe(babel({
                presets: ["env"],
            }))*/
        .pipe(uglify())
        .pipe(gulp.dest(path.build.js))
});

gulp.task('style', function () {
    gulp.src(path.src.css)
        .pipe(plumber())
        .pipe(preproc())
        //.pipe(cleanCSS({level: 2}))
        .pipe(combineMq())
        .pipe(autoprefixer({
            browsers: ['> 0.1%'],
            cascade: false
        }))
        .pipe(gulp.dest(path.build.css))
});

gulp.task('cssAdmin', function () {
    gulp.src(path.src.cssAdmin)
        .pipe(plumber())
        .pipe(gulp.dest(path.build.cssAdmin))
});

gulp.task('jsAdmin', function () {
    gulp.src(path.src.jsAdmin)
        .pipe(plumber())
        .pipe(gulp.dest(path.build.jsAdmin))
});

gulp.task('image', function () {
    gulp.src(path.src.img)
        .pipe(plumber())
        .pipe(gulp.dest(path.build.img))
});

gulp.task('fonts', function() {
    gulp.src(path.src.fonts)
        .pipe(plumber())
        .pipe(gulp.dest(path.build.fonts))
});

gulp.task('build', ['html', 'php', 'js', 'style', 'fonts', 'image', 'cssAdmin', 'jsAdmin']);

gulp.task('watch',['build'], function(){
    gulp.watch(path.src.html,     ['html']);
    gulp.watch(path.src.php,      ['php']);
    gulp.watch(path.src.jsAll,    ['js']);
    gulp.watch(path.src.cssAll,   ['style']);
    gulp.watch(path.src.fonts,    ['fonts']);
    gulp.watch(path.src.img,      ['image']);
    gulp.watch(path.src.cssAdmin, ['cssAdmin']);
    gulp.watch(path.src.jsAdmin,  ['jsAdmin']);
});

gulp.task('default', function() {

});