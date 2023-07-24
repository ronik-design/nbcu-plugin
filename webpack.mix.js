// webpack.mix.js
// Documentaion https://laravel-mix.com/docs/6.0/installation
let mix = require('laravel-mix');

mix.webpackConfig({
    // stats: {
    //         children: true
    // }
});

mix
    .js('public/assets/src/app.js', 'public/assets/dist/') // creates 'dist/app.js'
    .sass('public/assets/src/sass/main.scss', 'public/assets/dist/') // creates 'dist/main.css'
    // Minfies all included files and append .min | Minification of files will only work when npm run production runs.
    .minify(['public/assets/dist/main.css', 'public/assets/dist/app.js']);

