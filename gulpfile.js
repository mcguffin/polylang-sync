var gulp = require('gulp');
var concat = require('gulp-concat');  
var uglify = require('gulp-uglify');  
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var rename = require('gulp-rename');


gulp.task('styles:dev', function() {
	// dev
});

gulp.task('styles:prod', function() {
	// dev
});


gulp.task('default', function() {
	// place code for your default task here
	gulp.watch('scss/**/*.scss',['styles:prod']);
	gulp.watch('scss/**/*.scss',['styles:dev']);
//	gulp.watch('js/src/*.js',['scripts']);
});