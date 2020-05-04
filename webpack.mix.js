const path = require('path')
const fs = require('fs-extra')
const mix = require('laravel-mix')
require('laravel-mix-versionhash')
const publicDir = path.resolve(__dirname, './public')
//const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer')

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
  /* CSS */
  .sass('resources/sass/main.scss', 'public/dist/css/dashmix.css')
  .sass('resources/sass/dashmix/themes/xeco.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xinspire.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xmodern.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xsmooth.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xwork.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xdream.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xpro.scss', 'public/dist/css/themes/')
  .sass('resources/sass/dashmix/themes/xplay.scss', 'public/dist/css/themes/')
  //.sass('resources/sass/app.scss', 'public/dist/css')

  /* JS */
  .js('resources/js/dashmix/app.js', 'public/dist/js/dashmix.app.js')
  .js('resources/js/app.js', 'public/dist/js')


  /* Tools */
  .disableNotifications()


if (mix.inProduction()) {
  mix
    // .extract() // Disabled until resolved: https://github.com/JeffreyWay/laravel-mix/issues/1889
    // .version() // Use `laravel-mix-versionhash` for the generating correct Laravel Mix manifest file.
    .versionHash()
} else {
  mix.sourceMaps()
  mix.copyDirectory(path.join(publicDir, 'build', 'dist'), path.join(publicDir, 'dist'))
}


mix.webpackConfig({
  plugins: [
    // new BundleAnalyzerPlugin()
  ],
  resolve: {
    extensions: ['.js', '.json', '.vue'],
    alias: {
      '~': path.join(__dirname, './resources/js')
    }
  },
  output: {
    chunkFilename: 'dist/js/[chunkhash].js',
    path: mix.config.hmr ? '/' : path.resolve(__dirname, './public/build')
  },
  optimization: {
    splitChunks: {
      cacheGroups: {
        default: false,
        vendors: {
          chunks: 'initial',
          name: 'vendor',
          filename: 'dist/js/[name].js'
        }
      }
    },
  }
})

mix.then(() => {
  if (!mix.config.hmr) {
    process.nextTick(() => publishAseets())
  }
})

function publishAseets() {
  //const publicDir = path.resolve(__dirname, './public')

  if (mix.inProduction()) {
    fs.removeSync(path.join(publicDir, 'dist'))
  }

  fs.copySync(path.join(publicDir, 'build', 'dist'), path.join(publicDir, 'dist'))
  fs.removeSync(path.join(publicDir, 'build'))
}
