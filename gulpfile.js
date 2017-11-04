var autoprefixer	= require('gulp-autoprefixer'),
	concat			= require('gulp-concat'),
	gulp			= require('gulp'),
	gulputil		= require('gulp-util'),
	rename			= require('gulp-rename'),
	replace			= require('gulp-replace'),
	sass			= require('gulp-sass'),
	sourcemaps		= require('gulp-sourcemaps'),
	uglify			= require('gulp-uglify');



function dest_path( dest, type ) {
	return './' + type +'/'+dest + '.' + type;
}
function source_path( src, type ) {
	if ( src.constructor == String  ) {
		return './src/' + type +'/'+src + '.' + type;
	} else if (src.constructor==Array) {
		current_type = type;
		return src.map(function(src){
			return source_path( src, type );
		})
	}
}

function do_scss( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( source_path(src,'scss') )
		.pipe( sourcemaps.init() )
		.pipe( sass( { outputStyle: 'nested' } ).on('error', sass.logError) )
		.pipe( autoprefixer({
			browsers:['last 2 versions']
		}) )
		.pipe( gulp.dest( './css/' + dir ) )
        .pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( rename( { suffix: '.min' } ) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest( './css/' + dir ) );

}

function do_js( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( source_path(src,'js') )
		.pipe( sourcemaps.init() )
		.pipe( gulp.dest( './js/' + dir ) )
		.pipe( uglify().on('error', gulputil.log ) )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' + dir ) );
}

function get_dest(dest,type) {
	var full = './' + type +'/'+dest + '.' + type,
		idx = full.lastIndexOf('/');
	return {
		path: full.substring( 0, idx ),
		file: full.substring( idx+1 ),
	}
}
function concat_js( src, dest ) {
	d = get_dest( dest, 'js' );
	return gulp.src( source_path(src,'js') )
		.pipe( sourcemaps.init() )
		.pipe( concat( d.file ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( d.path ) )
		.pipe( uglify().on('error', gulputil.log ) )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( d.path ) );

}

gulp.task('scss', function() {
	return [
		do_scss('admin/post'),
	];
});


gulp.task('js-admin', function() {
    return [
		do_js( 'admin/nav-menus' ),
    ];

});


gulp.task( 'js', function(){
	return concat_js( [
		'./src/js/frontend.js',
	], 'frontend.js');
} );


gulp.task('build', ['scss','js','js-admin' ] );


gulp.task('watch', function() {
	// place code for your default task here
	gulp.watch('./src/scss/**/*.scss',[ 'scss' ]);
	gulp.watch('./src/js/**/*.js',[ 'js', 'js-admin' ]);
});
gulp.task('default', ['build','watch']);
// fin!

//
// var gulp = require('gulp');
// var concat = require('gulp-concat');
// var uglify = require('gulp-uglify');
// var sass = require('gulp-sass');
// var sourcemaps = require('gulp-sourcemaps');
// var rename = require('gulp-rename');
//
// function logError(){
// 	console.log('error',arguments);
// }
// function jsProd( asset, path ) {
//     return gulp.src([
// 			'./js/'+( !!path ? path+'/' : '' ) + asset + '.js'
// 		])
// 		.pipe( uglify() )
// 		.on('error', logError)
// 		.pipe( rename( asset + '.min.js') )
//     	.pipe( gulp.dest( './js/' + path ) );
// }
//
// function scssDev( asset, path ) {
//     return gulp.src(
// 			'./scss/'+( !!path ? path+'/' : '' ) + asset + '.scss'
//     	)
// 		.pipe(sourcemaps.init())
//         .pipe( sass( {
//         	outputStyle: 'expanded'
//         } ).on('error', sass.logError) )
//         .pipe( sourcemaps.write() )
//         .pipe( gulp.dest( './css/'+path ) );
// }
// function scssProd( asset, path ) {
//     return gulp.src(
// 			'./scss/'+( !!path ? path+'/' : '' ) + asset + '.scss'
//     	)
// 		.pipe( sass( {
// 			outputStyle: 'compressed', omitSourceMapUrl: true
// 		} ).on('error', sass.logError) )
// 		.pipe( rename( asset + '.min.css') )
// 		.pipe( gulp.dest('./css/'+path));
// }
//
// gulp.task( 'js:admin:nav-menus',	function() { return jsProd('nav-menus','admin')	});
// //gulp.task( 'scss:nav-menus:dev', 		function() { return scssDev('nav-menus','admin'); });
// //gulp.task( 'scss:nav-menus:prod', 		function() { return scssProd('nav-menus','admin'); });
// gulp.task( 'scss:post:dev', 		function() { return scssDev('post','admin'); });
// gulp.task( 'scss:post:prod', 		function() { return scssProd('post','admin'); });
//
//
// gulp.task('default', function() {
// 	// place code for your default task here
// 	gulp.watch('scss/**/*.scss', [
// 		'scss:post:prod', 'scss:post:dev',
// //		'scss:nav-menus:prod', 'scss:nav-menus:dev',
// 	] );
// 	gulp.watch('js/**/*.js', [ 'js:admin:nav-menus' ] );
// });
