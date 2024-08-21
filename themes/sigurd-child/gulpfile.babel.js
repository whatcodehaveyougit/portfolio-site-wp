import { src, dest, watch } from 'gulp';
import yargs from 'yargs';
const sass = require('gulp-sass')(require('sass'));
import cleanCss from 'gulp-clean-css';
import gulpif from 'gulp-if';
const PRODUCTION = yargs.argv.prod;
const gulp = require('gulp');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');

export const scripts = () => {
  return src('assets/js/*.js') // Adjust the source directory as needed
    .pipe(concat('scripts.js'))   // Concatenate all JS files into all.js
    .pipe(uglify())           // Minify the concatenated file
    .pipe(gulp.dest('dist/js')); // Output the final file to the dist directory
};


export const styles = () => {
  return src('./assets/styles/scss/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulpif(PRODUCTION, cleanCss({compatibility:'ie8'})))
    .pipe(dest('dist/css'));
}


export const watchForChanges = () => {
  watch('assets/assets/styles/sass/*.scss', scripts);
  watch('assets/assets/styles/*.scss', scripts);
  watch('assets/scripts/js/*.js', styles);
}