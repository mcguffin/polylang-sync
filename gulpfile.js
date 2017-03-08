var gulp = require('gulp');
var concat = require('gulp-concat');  
var uglify = require('gulp-uglify');  
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var rename = require('gulp-rename');

function logError(){
	console.log('error',arguments);
}
function jsProd( asset, path ) {
    return gulp.src([
			'./js/'+( !!path ? path+'/' : '' ) + asset + '.js'
		])
		.pipe( uglify() )
		.on('error', logError)
		.pipe( rename( asset + '.min.js') )
    	.pipe( gulp.dest( './js/' + path ) );
}

function scssDev( asset, path ) {
    return gulp.src(
			'./scss/'+( !!path ? path+'/' : '' ) + asset + '.scss'
    	)
		.pipe(sourcemaps.init())
        .pipe( sass( { 
        	outputStyle: 'expanded' 
        } ).on('error', sass.logError) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest( './css/'+path ) );
}
function scssProd( asset, path ) {
    return gulp.src(
			'./scss/'+( !!path ? path+'/' : '' ) + asset + '.scss'
    	)
		.pipe( sass( { 
			outputStyle: 'compressed', omitSourceMapUrl: true 
		} ).on('error', sass.logError) )
		.pipe( rename( asset + '.min.css') )
		.pipe( gulp.dest('./css/'+path));
}

gulp.task( 'js:admin:nav-menus',	function() { return jsProd('nav-menus','admin')	});
//gulp.task( 'scss:nav-menus:dev', 		function() { return scssDev('nav-menus','admin'); });
//gulp.task( 'scss:nav-menus:prod', 		function() { return scssProd('nav-menus','admin'); });
gulp.task( 'scss:post:dev', 		function() { return scssDev('post','admin'); });
gulp.task( 'scss:post:prod', 		function() { return scssProd('post','admin'); });


gulp.task('default', function() {
	// place code for your default task here
	gulp.watch('scss/**/*.scss', [ 
		'scss:post:prod', 'scss:post:dev',
//		'scss:nav-menus:prod', 'scss:nav-menus:dev', 
	] );
	gulp.watch('js/**/*.js', [ 'js:admin:nav-menus' ] );
});