const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')

    // public path used by the web server to access the output path
    .setPublicPath('/build')

    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add entry files here
     */
    .addEntry('app', './assets/app.js')
    // .addStyleEntry('app', './assets/styles/global/app.css')
    .addStyleEntry('login', './assets/styles/global/login.css')
    .addStyleEntry('home', './assets/styles/User/home.css')
    .addStyleEntry('library', './assets/styles/User/library.css')
    .addStyleEntry('base', './assets/styles/global/base.css')
    .addStyleEntry('product_main', './assets/styles/User/product/product_main.css')
    .addStyleEntry('edit', './assets/styles/User/product/edit.css')
    .addStyleEntry('new', './assets/styles/User/product/new.css')
    .addStyleEntry('show', './assets/styles/User/product/show.css')
    .addStyleEntry('profile_index', './assets/styles/global/profile_index.css')
    .addStyleEntry('profile_edit', './assets/styles/global/profile_edit.css')
    .addStyleEntry('register', './assets/styles/global/register.css')

    // admin
    .addStyleEntry('index', './assets/styles/Admin/game-management/index.css')
    .addStyleEntry('show_gameManagement', './assets/styles/Admin/game-management/show_gameManagement.css')
    .addStyleEntry('game_edit', './assets/styles/Admin/game-management/game_edit.css')
    .addStyleEntry('game_new', './assets/styles/Admin/game-management/game_new.css')
    .addStyleEntry('stock_index', './assets/styles/Admin/stock/stock_index.css')
    .addStyleEntry('stock_show', './assets/styles/Admin/stock/stock_show.css')
    .addStyleEntry('stock_edit', './assets/styles/Admin/stock/stock_edit.css')
    .addStyleEntry('stock_new', './assets/styles/Admin/stock/stock_new.css')
    .addStyleEntry('key_index', './assets/styles/Admin/license-key/key_index.css')
    .addStyleEntry('key_edit', './assets/styles/Admin/license-key/key_edit.css')
    .addStyleEntry('key_show', './assets/styles/Admin/license-key/key_show.css')
    .addStyleEntry('key_new', './assets/styles/Admin/license-key/key_new.css')
    .addStyleEntry('analytics_page', './assets/styles/Admin/analytics_page.css')
    .addStyleEntry('admin_dashboard', './assets/styles/Admin/admin_dashboard.css')
    .addStyleEntry('order_index', './assets/styles/Admin/order_index.css')
    .addStyleEntry('user_index', './assets/styles/Admin/user/user_index.css')
    .addStyleEntry('user_edit', './assets/styles/Admin/user/user_edit.css')
    .addStyleEntry('user_new', './assets/styles/Admin/user/user_new.css')
    .addStyleEntry('user_show', './assets/styles/Admin/user/user_show.css')
    .addStyleEntry('activity_logs', './assets/styles/Admin/activity_logs.css')

    // .addEntry('login', './assets/styles/login.js')
    // .addEntry('home', './assets/styles/home.js')


    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    // .enableStimulusBridge('./assets/controllers.json')

    // enable PostCSS if you need it
    .enablePostCssLoader()

    // fixes your issue: don’t rewrite absolute /images/... URLs
    .configureCssLoader(options => {
        options.url = false;
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // enable hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .enableSingleRuntimeChunk()

;

module.exports = Encore.getWebpackConfig();
