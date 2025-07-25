import gulp from "gulp";
import browserSyncPkg from "browser-sync";
const browserSync = browserSyncPkg.create();

import gulpSass from "gulp-sass";
import sassCompiler from "sass";
const sass = gulpSass(sassCompiler);

import autoprefixer from "gulp-autoprefixer";
import babelify from "babelify";
import source from "vinyl-source-stream";
import browserify from "browserify";
import uglify from "gulp-uglify";
import rename from "gulp-rename";
import cssnano from "gulp-cssnano";
import plumber from "gulp-plumber";

function sassTask() {
    return gulp
        .src("./source/scss/app.scss")
        .pipe(sass().on("error", sass.logError))
        .pipe(
            autoprefixer({
                cascade: false,
            })
        )
        .pipe(gulp.dest("./assets/css"))
        .pipe(browserSync.stream());
}

function adminSassTask() {
    return gulp
        .src("./source/scss/admin/admin.scss")
        .pipe(sass().on("error", sass.logError))
        .pipe(
            autoprefixer({
                cascade: false,
            })
        )
        .pipe(gulp.dest("./assets/css"))
        .pipe(browserSync.stream());
}

function jsTask() {
    return browserify({
        entries: ["./source/js/app.js"],
        debug: true,
    })
        .transform(babelify)
        .bundle()
        .pipe(plumber())
        .pipe(source("app.js"))
        .pipe(gulp.dest("./assets/js"))
        .pipe(browserSync.stream());
}

function adminJsTask() {
    return browserify({
        entries: ["./source/js/admin/admin.js"],
        debug: true,
    })
        .transform(babelify)
        .bundle()
        .pipe(plumber())
        .pipe(source("admin.js"))
        .pipe(gulp.dest("./assets/js"));
}

function buildJS() {
    return gulp
        .src("./assets/js/app.js")
        .pipe(uglify())
        .pipe(rename("app.min.js"))
        .pipe(gulp.dest("./assets/js"));
}

function buildCSS() {
    return gulp
        .src("./assets/css/app.css")
        .pipe(cssnano())
        .pipe(rename("app.min.css"))
        .pipe(gulp.dest("./assets/css"));
}

function browserSyncTask(done) {
    // https://browsersync.io/docs/options
    browserSync.init({
        proxy: "local.roolith-framework.me",
        port: 3000,
        open: false,
    });

    done();
}

function browserSyncReload(done) {
    browserSync.reload();

    done();
}

function watchFiles() {
    gulp.watch("./source/scss/**/*.scss", sassTask);
    gulp.watch("./source/js/**/*.js", jsTask);
    gulp.watch(["./**/*.html", "./**/*.php"], browserSyncReload);
}

function watchAdmin() {
    gulp.watch("./source/scss/admin/**/*.scss", adminSassTask);
    gulp.watch("./source/js/admin/**/*.js", adminJsTask);
}

gulp.task("watch", gulp.parallel(watchFiles, browserSyncTask));
gulp.task("sass", sassTask);
gulp.task("js", jsTask);
gulp.task(
    "production",
    gulp.series("sass", "js", gulp.parallel(buildJS, buildCSS))
);
gulp.task("watch:admin", watchAdmin);
gulp.task("build:admin", gulp.parallel(adminSassTask, adminJsTask));
