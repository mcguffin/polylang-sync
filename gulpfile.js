var autoprefixer	= require('gulp-autoprefixer'),
	concat			= require('gulp-concat'),
	gulp			= require('gulp'),
	gulputil		= require('gulp-util'),
	rename			= require('gulp-rename'),
	sass			= require('gulp-sass'),
	sourcemaps		= require('gulp-sourcemaps'),
	uglify			= require('gulp-uglify');

function do_scss( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/scss/' + src + '.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( autoprefixer({
			browsers:['last 2 versions']
		}) )
		.pipe( gulp.dest( './css/' + dir ) )
        .pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( rename( { suffix: '.dev' } ) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest( './css/' + dir ) );

}

function do_js( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/js/' + src + '.js' )
		.pipe( sourcemaps.init() )
		.pipe( gulp.dest( './js/' + dir ) )
		.pipe( uglify() )
		.pipe( rename( { suffix: '.dev' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' + dir ) );
}

function concat_js( src, dest ) {
	return gulp.src( src )
		.pipe( sourcemaps.init() )
		.pipe( uglify() )
		.pipe( concat( dest ) )
		.pipe( gulp.dest( './js/' ) )
		.pipe( rename( { suffix: '.dev' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' ) );

}


// scss tasks

// scss admin tasks
gulp.task('scss:post', function(){
	return do_scss('admin/post')
});

// scss
gulp.task('scss', gulp.parallel( 'scss:post' ));

// admin js

gulp.task( 'js:admin', function(){
	return do_js( 'admin/admin' );
} );
gulp.task( 'js:nav-menus', function(){
	return do_js( 'admin/nav-menus' );
} );

gulp.task( 'js:frontend', function(){
	return concat_js( [
	], 'frontend.js');
} );

gulp.task('js', gulp.parallel( 'js:admin','js:nav-menus' ) );

gulp.task('build', gulp.parallel('js','scss') );

gulp.task('watch', function() {
	// place code for your default task here
	gulp.watch('./src/scss/**/*.scss',gulp.parallel( 'scss' ));
	gulp.watch('./src/js/**/*.js',gulp.parallel( 'js' ) );
});
gulp.task('default', gulp.parallel('build','watch'));
