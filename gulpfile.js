'use strict';

var gulp = require('gulp');
var shell = require('gulp-shell');
var plumber = require('gulp-plumber');
var del = require('del');
var sound = require('mac-sounds');
var argv = require('yargs').argv;

var config = {
    svn: {
        url: 'https://plugins.svn.wordpress.org/mpress-custom-front-page/',
        src: [
            './**',
            '!**/svn',
            '!**/svn/**',
            '!**/readme.md',
            '!**/package.json',
            '!**/node_modules',
            '!**/node_modules/**',
            '!**/bower.json',
            '!**/bower_components',
            '!**/bower_components/**',
            '!**/gulpfile.js',
            '!**/gulp',
            '!**/gulp/**',
            '!**/composer.json',
            '!**/composer.lock'
        ],
        dest: './svn/trunk',
        clean: './svn/trunk/**/*'
    }
};

gulp.task('svn:checkout', shell.task('svn co ' + config.svn.url + ' svn'));

gulp.task('svn:clean', function () {
    return del(config.svn.clean);
});

gulp.task('svn:copy', ['svn:clean'], function () {
    return gulp.src(config.svn.src)
        .pipe(plumber({
            errorHandler: function (err) {
                sound('blow');
                console.log(err);
            }
        }))
        .pipe(gulp.dest(config.svn.dest));
});

gulp.task('svn:addremove', function () {
    return gulp.src('*.js', {read: false})
        .pipe(shell([
            "svn st | grep ^? | sed '\''s/?    //'\'' | xargs svn add",
            "svn st | grep ^! | sed '\''s/!    //'\'' | xargs svn rm"
        ], {
            cwd: './svn'
        }))
});

gulp.task('svn:tag', function () {
    return gulp.src('*.js', {read: false})
        .pipe(shell([
            'svn cp trunk tags/' + argv.v
        ], {
            cwd: './svn'
        }))
});

gulp.task('project:build', ['svn:copy']);