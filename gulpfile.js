'use strict';

var gulp = require('gulp');
var gp = require('gulp-load-plugins')();

var config = {
  path: {
    js: './src/js',
    css: './src/css'
  },
  dist: {
    js: './dist/js',
    css: './dist/css'
  },
  temp: './.temp'
};

var pkg = require('./package.json');

var banner = [
  '/*! <%= pkg.name %> v<%= pkg.version %> (<%= pkg.homepage %>)',
  'by <%= pkg.author %> ' + gp.util.date(Date.now(), 'UTC:yyyy-mm-dd'),
  'Licensed under <%= pkg.license.type %> */ \n'
].join(' | ');

gulp.task('build:main', function() {
  return gulp.src(config.path.js + '/main.js')
    .pipe(gp.header(banner, {pkg: pkg}))
    .pipe(gp.rename('minty.js'))
    .pipe(gulp.dest(config.dist.js))
    .pipe(gp.uglify())
    .pipe(gp.header(banner, {pkg: pkg}))
    .pipe(gp.rename('minty.min.js'))
    .pipe(gulp.dest(config.temp))
    .pipe(gulp.dest(config.dist.js));
});

gulp.task('copy:sm', function() {
  return gulp.src(config.path.js + '/soundmanager2-nodebug-jsmin.js')
    .pipe(gp.rename('soundmanager2.min.js'))
    .pipe(gulp.dest(config.temp))
    .pipe(gulp.dest(config.dist.js));
});

// 合并压缩代码
gulp.task('build:concat', ['build:main', 'copy:sm'], function() {
  return gulp.src([config.temp + '/soundmanager2.min.js', config.temp + '/minty.min.js'])
    .pipe(gp.concat('minty.concat.min.js', {newLine: '\n\n'}))
    .pipe(gulp.dest(config.dist.js));
});

gulp.task('build:css', function() {
  return gulp.src(config.path.css + '/*.css')
    .pipe(gulp.dest(config.dist.css))
    .pipe(gp.minifyCss())
    .pipe(gp.rename({
      suffix: '.min',
      extname: '.css'
    }))
    .pipe(gulp.dest(config.dist.css));
});

// 监视文件的变化
gulp.task('watch', function() {
  gulp.watch(config.path.js + '/main.js', ['build:concat']);
  gulp.watch(config.path.css + '/*.css', ['build:css']);
});

// 注册缺省任务
gulp.task('default', ['build:main', 'copy:sm', 'build:concat', 'build:css', 'watch'])
