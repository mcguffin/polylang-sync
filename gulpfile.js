var gulp = require('gulp');
var concat = require('gulp-concat');  
var uglify = require('gulp-uglify');  
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var rename = require('gulp-rename');


gulp.task('scripts', function() {
	// dev
});

gulp.task('styles:post:dev', function() {
	// dev
    return gulp.src('scss/admin/post.scss')
		.pipe(sourcemaps.init())
        .pipe( sass( { 
        	outputStyle: 'expanded' 
        } ).on('error', sass.logError) )
        .pipe(sourcemaps.write())
        .pipe( gulp.dest('./css/admin/'));
});

gulp.task('styles:post:prod', function() {
	// prod
	return gulp.src('scss/admin/post.scss')
		.pipe( sass( { 
			outputStyle: 'compressed', omitSourceMapUrl: true 
		} ).on('error', sass.logError) )
		.pipe(rename('post.min.css'))
		.pipe( gulp.dest('./css/admin/'));
});


gulp.task('default', function() {
	// place code for your default task here
	gulp.watch('scss/**/*.scss',['styles:post:prod']);
	gulp.watch('scss/**/*.scss',['styles:post:dev']);
	gulp.watch('js/src/**/*.js',['scripts']);
});