<?php

namespace AwemaPL\Docs;

use Illuminate\Support\ServiceProvider;

class DocsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'docs');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/docs'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../config/docs.php' => config_path('docs.php'),
        ], 'config');

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/docs.php',
            'docs'
        );

        $this->app->bind('awema-pl_docs', Docs::class);
    }

}
