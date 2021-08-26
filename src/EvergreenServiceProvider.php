<?php

namespace Evergreen\Generic;

use Illuminate\Support\ServiceProvider;
use Evergreen\Generic\Console\MakeEgcCommand;
use Evergreen\Generic\Console\ModelEgcCommand;
use Evergreen\Generic\App\Exceptions\EGCExceptionHandler;
use Illuminate\Container\Container;

class EvergreenServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'egl');

        //publish our views so that they can be used by the application
        $publishes = [
            __DIR__.'/assets/js/app.js' => resource_path('assets/js/app.js'),
            __DIR__.'/assets/js/login.js' => resource_path('assets/js/login.js'),
            __DIR__.'/assets/sass/app.sass' => resource_path('assets/sass/app.sass'),
            __DIR__.'/assets/sass/_overrides.scss' => resource_path('assets/sass/_overrides.scss'),
            __DIR__.'/assets/sass/_variables.sass' => resource_path('assets/sass/_variables.sass'),
            __DIR__.'/package.json' => base_path('package.json'),
            __DIR__.'/webpack.mix.js' => base_path('webpack.mix.js'),
            __DIR__.'/public/logo.png' => public_path('logo.png'),
            __DIR__.'/public/eg-icons.svg' => public_path('eg-icons.svg'),
            __DIR__.'/public/eg_logo.svg' => public_path('eg_logo.svg'),
            __DIR__.'/database' => database_path(),
            __DIR__.'/.babelrc' => base_path(".babelrc"),
            __DIR__.'/yaml' => base_path("/yaml"),
        ];

        if (!isset($this->app->getLoadedProviders()["Evergreen\Egcms\EgcmsServiceProvider"])) {
            $publishes[__DIR__.'/config'] = config_path();
        }

        $this->publishes($publishes);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeEgcCommand::class,
                ModelEgcCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if (! $this->app->routesAreCached()) {
            $this->app->router->group(['namespace' => 'Evergreen\Generic\App\Http\Controllers'], function ($router) {
                require __DIR__.'/routes/auth.php';
            });

            $this->app->router->group(['namespace' => 'App\Http\Controllers'], function ($router) {
                require __DIR__.'/routes/web.php';
            });

            if (isset($this->app->getLoadedProviders()["Evergreen\Egcms\EgcmsServiceProvider"])) {
                $cmsFile = __DIR__.'/../../egcms/src/routes/cmsRoutes.php';
                if (file_exists($cmsFile)) {
                    $this->app->router->group(['namespace' => 'App\Http\Controllers'], function ($router) use ($cmsFile) {
                        require $cmsFile;
                    });
                }
            }
        }

        //load our Aliases
        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            //EGL
            $loader->alias('Controller', \Evergreen\Generic\App\Http\Controllers\Controller::class);
            $loader->alias('EGForm', \Evergreen\Generic\App\Helpers\EGForm::class);
            $loader->alias('EGUtil', \Evergreen\Generic\App\Helpers\EGUtil::class);
            $loader->alias('EGFiles', \Evergreen\Generic\App\EGFiles::class);

            //third party
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Bhash', \Illuminate\Hashing\BcryptHasher::class);
            $loader->alias('Carbon', \Carbon\Carbon::class);
            $loader->alias('Toast', \Evergreen\Generic\App\Helpers\Toast::class);
            $loader->alias('PDF', \Barryvdh\Snappy\Facades\SnappyPdf::class);
        }

        //Load our other Service Providers.
        $this->app->register('Unisharp\Ckeditor\ServiceProvider');
        $this->app->register('Evergreen\Generic\App\Providers\EGAuthServiceProvider');

        $this->app->extend(\App\Exceptions\Handler::class, function ($handler) {
            return new EGCExceptionHandler($this->app);
        });

        $this->app->singleton('laravel-toastr', function () {
            return $this->app->make('Evergreen\Generic\App\Toastr');
        });
    }
}
