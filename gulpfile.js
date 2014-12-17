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
  }
};

var pkg = require('./package.json');

var banner = [
  '/*! <%= pkg.name %> v<%= pkg.version %>',
  'by <%= pkg.author %>',
  'Licensed under <%= pkg.license.type %>',
  '2014-12-17 */ \n'
].join(' | ');

// 合并压缩代码
gulp.task('minify:js', function() {
  return gulp.src(config.path.js + '/main.js')
    .pipe(gp.header(banner, {pkg: pkg}))
    .pipe(gp.rename('minty.js'))
    .pipe(gulp.dest(config.dist.js))
    .pipe(gp.uglify())
    .pipe(gp.header(banner, {pkg: pkg}))
    .pipe(gp.rename('minty.min.js'))
    .pipe(gulp.dest(config.dist.js));
});

gulp.task('minify:css', function() {
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
  gulp.watch(config.path.js + '/main.js', ['minify:js']);
  gulp.watch(config.path.css + '/*.css', ['minify:css']);
});

// 注册缺省任务
gulp.task('default', ['minify:js', 'minify:css', 'watch'])
